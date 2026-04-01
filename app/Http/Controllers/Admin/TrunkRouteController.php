<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Trunk;
use App\Models\TrunkRoute;
use App\Services\AuditService;
use App\Services\RouteSelectionService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class TrunkRouteController extends Controller
{
    public function __construct(
        private RouteSelectionService $routeSelection,
    ) {}

    public function index(Request $request)
    {
        $query = TrunkRoute::with('trunk');

        if ($request->filled('prefix')) {
            $query->where('prefix', 'like', $request->prefix . '%');
        }

        if ($request->filled('trunk_id')) {
            $query->where('trunk_id', $request->trunk_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $routes = $query
            ->orderBy('prefix')
            ->orderBy('priority')
            ->orderByDesc('weight')
            ->paginate(50);

        $trunks = Trunk::outgoing()->orderBy('name')->get();

        return view('admin.trunk-routes.index', compact('routes', 'trunks'));
    }

    public function create(Request $request)
    {
        $trunks = Trunk::outgoing()->active()->orderBy('name')->get();
        $selectedTrunkId = $request->query('trunk_id');

        return view('admin.trunk-routes.create', compact('trunks', 'selectedTrunkId'));
    }

    public function store(Request $request)
    {
        $validated = $this->validateRoute($request);
        $this->normalizeDaysAndTime($request, $validated);

        $overlap = $this->checkTimeOverlaps($validated);

        $route = TrunkRoute::create($validated);

        AuditService::logCreated($route, 'trunk_route.created');

        $redirect = redirect()->route('admin.trunk-routes.index', ['prefix' => $route->prefix])
            ->with('success', "Routing rule for prefix {$route->prefix} created.");

        if ($overlap) {
            $redirect = $redirect->with('warning', $overlap);
        }

        return $redirect;
    }

    public function edit(TrunkRoute $trunkRoute)
    {
        $trunks = Trunk::outgoing()->orderBy('name')->get();

        return view('admin.trunk-routes.edit', compact('trunkRoute', 'trunks'));
    }

    public function update(Request $request, TrunkRoute $trunkRoute)
    {
        $validated = $this->validateRoute($request, $trunkRoute);
        $this->normalizeDaysAndTime($request, $validated);

        $overlap = $this->checkTimeOverlaps($validated, $trunkRoute->id);

        $original = $trunkRoute->getAttributes();
        $trunkRoute->update($validated);

        AuditService::logUpdated($trunkRoute, $original, 'trunk_route.updated');

        $redirect = redirect()->route('admin.trunk-routes.index', ['prefix' => $trunkRoute->prefix])
            ->with('success', "Routing rule for prefix {$trunkRoute->prefix} updated.");

        if ($overlap) {
            $redirect = $redirect->with('warning', $overlap);
        }

        return $redirect;
    }

    public function destroy(TrunkRoute $trunkRoute)
    {
        $prefix = $trunkRoute->prefix;

        AuditService::logAction('trunk_route.deleted', $trunkRoute, [
            'prefix' => $prefix,
            'trunk_id' => $trunkRoute->trunk_id,
        ]);

        $trunkRoute->delete();

        return redirect()->route('admin.trunk-routes.index')
            ->with('success', "Routing rule for prefix {$prefix} deleted.");
    }

    /**
     * AJAX route test tool — returns JSON with matched routes.
     */
    public function testRoute(Request $request)
    {
        $validated = $request->validate([
            'destination'    => ['required', 'string', 'regex:/^\d+$/'],
            'test_time'      => ['nullable', 'date_format:Y-m-d\TH:i'],
            'test_timezone'  => ['nullable', 'string', 'max:50'],
        ]);

        $time = isset($validated['test_time'])
            ? Carbon::parse($validated['test_time'], $validated['test_timezone'] ?? 'UTC')
            : now();

        $result = $this->routeSelection->selectTrunks($validated['destination'], $time);

        $formatRoute = fn (?TrunkRoute $r) => $r ? [
            'id'          => $r->id,
            'prefix'      => $r->prefix,
            'trunk_name'  => $r->trunk->name,
            'trunk_id'    => $r->trunk_id,
            'provider'    => $r->trunk->provider,
            'priority'    => $r->priority,
            'weight'      => $r->weight,
            'time_window' => $r->time_start
                ? substr($r->time_start, 0, 5) . ' - ' . substr($r->time_end, 0, 5)
                : 'Always',
            'days'        => $r->days_of_week ?? 'All days',
        ] : null;

        return response()->json([
            'primary'        => $formatRoute($result['primary']),
            'failover'       => $formatRoute($result['failover']),
            'all_matches'    => $result['all']->map($formatRoute)->values(),
            'matched_prefix' => $result['all']->first()?->prefix,
            'evaluation_time' => $time->toIso8601String(),
        ]);
    }

    protected function validateRoute(Request $request, ?TrunkRoute $route = null): array
    {
        $validated = $request->validate([
            'trunk_id'            => ['required', 'exists:trunks,id'],
            'prefix'              => ['required', 'string', 'max:20', 'regex:/^\d+$/'],
            'time_start'          => ['nullable', 'date_format:H:i'],
            'time_end'            => ['nullable', 'date_format:H:i', 'required_with:time_start'],
            'timezone'            => ['required', 'string', 'max:50'],
            'priority'            => ['required', 'integer', 'min:1', 'max:100'],
            'weight'              => ['required', 'integer', 'min:1', 'max:1000'],
            'remove_prefix'       => ['nullable', 'string', 'max:20', 'regex:/^\d*$/'],
            'add_prefix'          => ['nullable', 'string', 'max:20', 'regex:/^\d*$/'],
            'mnp_enabled'         => ['nullable', 'boolean'],
            'status'              => ['required', Rule::in(['active', 'disabled'])],
        ]);

        // Validate trunk is outgoing/both
        $trunk = Trunk::findOrFail($validated['trunk_id']);
        if (!in_array($trunk->direction, ['outgoing', 'both'])) {
            throw ValidationException::withMessages([
                'trunk_id' => 'The selected trunk must have outgoing or both direction.',
            ]);
        }

        // Handle MNP checkbox (unchecked = not sent)
        $validated['mnp_enabled'] = $request->boolean('mnp_enabled');

        return $validated;
    }

    /**
     * Normalize days[] array and time format before saving.
     */
    protected function normalizeDaysAndTime(Request $request, array &$validated): void
    {
        // Handle days[] checkbox array → comma-separated string
        if ($request->filled('days')) {
            $validDays = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'];
            $days = array_intersect($request->input('days'), $validDays);
            $validated['days_of_week'] = !empty($days) ? implode(',', $days) : null;
        } else {
            $validated['days_of_week'] = null;
        }

        // Normalize time: HH:MM → HH:MM:SS
        if (!empty($validated['time_start']) && strlen($validated['time_start']) === 5) {
            $validated['time_start'] .= ':00';
        }
        if (!empty($validated['time_end']) && strlen($validated['time_end']) === 5) {
            $validated['time_end'] .= ':00';
        }

        // Convert empty strings to null
        $validated['time_start'] = $validated['time_start'] ?: null;
        $validated['time_end'] = $validated['time_end'] ?: null;
    }

    /**
     * Check for overlapping time windows (soft warning, not blocking).
     */
    protected function checkTimeOverlaps(array $data, ?int $excludeId = null): ?string
    {
        if (empty($data['time_start'])) {
            return null;
        }

        $query = TrunkRoute::where('prefix', $data['prefix'])
            ->where('priority', $data['priority'])
            ->whereNotNull('time_start');

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        $overlapping = $query->get()->filter(function (TrunkRoute $existing) use ($data) {
            return $this->timeWindowsOverlap(
                $data['time_start'], $data['time_end'],
                $existing->time_start, $existing->time_end
            );
        });

        if ($overlapping->isEmpty()) {
            return null;
        }

        $ids = $overlapping->pluck('id')->implode(', ');
        return "This route's time window overlaps with existing route(s) (ID: {$ids}) for the same prefix and priority.";
    }

    protected function timeWindowsOverlap(string $s1, string $e1, string $s2, string $e2): bool
    {
        // Normal windows (start < end): overlap if one starts before the other ends
        $normal1 = $s1 <= $e1;
        $normal2 = $s2 <= $e2;

        if ($normal1 && $normal2) {
            return $s1 < $e2 && $s2 < $e1;
        }

        // If either wraps midnight, convert to a simpler check
        // Overnight window covers [start, 24:00) + [00:00, end)
        // Two overnight windows always overlap (both cover midnight)
        if (!$normal1 && !$normal2) {
            return true;
        }

        // One is overnight, one is normal
        [$normalStart, $normalEnd] = $normal1 ? [$s1, $e1] : [$s2, $e2];
        [$overnightStart, $overnightEnd] = $normal1 ? [$s2, $e2] : [$s1, $e1];

        // Overnight [overnightStart, 24:00) ∪ [00:00, overnightEnd)
        // Overlaps with normal [normalStart, normalEnd) if:
        //   normalStart < overnightEnd (falls in morning part) OR
        //   normalEnd > overnightStart (falls in evening part)
        return $normalStart < $overnightEnd || $normalEnd > $overnightStart;
    }
}
