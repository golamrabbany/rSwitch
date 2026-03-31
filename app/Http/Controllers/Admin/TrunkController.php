<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RateGroup;
use App\Models\Trunk;
use App\Services\AuditService;
use App\Services\TrunkProvisioningService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TrunkController extends Controller
{
    public function __construct(
        private TrunkProvisioningService $provisioning,
    ) {}

    public function index(Request $request)
    {
        $query = Trunk::query();

        if ($request->filled('direction')) {
            $query->where('direction', $request->direction);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('provider', 'like', "%{$search}%")
                  ->orWhere('host', 'like', "%{$search}%");
            });
        }

        $trunks = $query->with('rateGroup:id,name')->orderByDesc('created_at')->paginate(20);

        return view('admin.trunks.index', compact('trunks'));
    }

    public function create()
    {
        $rateGroups = RateGroup::where('type', 'admin')->orderBy('name')->get();

        return view('admin.trunks.create', compact('rateGroups'));
    }

    public function store(Request $request)
    {
        $validated = $this->validateTrunk($request);
        $validated['register'] = $request->boolean('register');
        $validated['health_check'] = $request->boolean('health_check');
        $validated['cli_prefix_strip'] = $validated['cli_prefix_strip'] ?? 0;

        $trunk = Trunk::create($validated);

        $this->provisioning->provisionAll();

        AuditService::logCreated($trunk, 'trunk.created');

        return redirect()->route('admin.trunks.show', $trunk)
            ->with('success', "Trunk {$trunk->name} created and provisioned.");
    }

    public function show(Trunk $trunk)
    {
        $trunk->load('routes');

        // Check if trunk is provisioned in PJSIP realtime DB
        $trunkId = "trunk-{$trunk->direction}-{$trunk->id}";
        $provisioned = \DB::table('ps_endpoints')->where('id', $trunkId)->exists();

        return view('admin.trunks.show', compact('trunk', 'provisioned'));
    }

    public function edit(Trunk $trunk)
    {
        $rateGroups = RateGroup::where('type', 'admin')->orderBy('name')->get();

        return view('admin.trunks.edit', compact('trunk', 'rateGroups'));
    }

    public function update(Request $request, Trunk $trunk)
    {
        $validated = $this->validateTrunk($request, $trunk);
        $validated['register'] = $request->boolean('register');
        $validated['health_check'] = $request->boolean('health_check');
        $validated['status'] = $request->validate([
            'status' => ['required', Rule::in(['active', 'disabled'])],
        ])['status'];

        $original = $trunk->getAttributes();

        if (empty($validated['password'])) {
            unset($validated['password']);
        }

        $trunk->update($validated);

        $this->provisioning->provisionAll();

        AuditService::logUpdated($trunk, $original, 'trunk.updated');

        return redirect()->route('admin.trunks.show', $trunk)
            ->with('success', "Trunk {$trunk->name} updated.");
    }

    public function destroy(Trunk $trunk)
    {
        $name = $trunk->name;

        AuditService::logAction('trunk.deleted', $trunk, ['name' => $name]);

        $trunk->delete();

        $this->provisioning->provisionAll();

        return redirect()->route('admin.trunks.index')
            ->with('success', "Trunk {$name} deleted and deprovisioned.");
    }

    public function reprovision(Trunk $trunk)
    {
        $this->provisioning->provisionAll();

        AuditService::logAction('trunk.reprovisioned', $trunk);

        return back()->with('success', 'All trunks re-provisioned and PJSIP reloaded.');
    }

    protected function validateTrunk(Request $request, ?Trunk $trunk = null): array
    {
        return $request->validate([
            // Basic
            'name'              => ['required', 'string', 'max:100'],
            'provider'          => ['required', 'string', 'max:100'],
            'direction'         => ['required', Rule::in(['incoming', 'outgoing', 'both'])],
            'host'              => ['required', 'string', 'max:255'],
            'port'              => ['required', 'integer', 'min:1', 'max:65535'],
            'transport'         => ['required', Rule::in(['udp', 'tcp', 'tls'])],
            'codec_allow'       => ['required', 'string', 'max:100'],
            'max_channels'      => ['required', 'integer', 'min:1', 'max:9999'],
            'rate_group_id'     => ['nullable', 'exists:rate_groups,id'],

            // Authentication
            'username'          => ['nullable', 'string', 'max:100'],
            'password'          => [$trunk ? 'nullable' : 'nullable', 'string', 'max:100'],
            'register_string'   => ['nullable', 'string', 'max:255'],

            // Outgoing
            'outgoing_priority' => ['required', 'integer', 'min:1', 'max:100'],

            // Dial manipulation
            'dial_pattern_match'   => ['nullable', 'string', 'max:50'],
            'dial_pattern_replace' => ['nullable', 'string', 'max:50'],
            'dial_prefix'          => ['nullable', 'string', 'max:20'],
            'dial_strip_digits'    => ['required', 'integer', 'min:0', 'max:20'],
            'tech_prefix'          => ['nullable', 'string', 'max:20'],

            // CLI manipulation
            'cli_mode'             => ['required', Rule::in(['passthrough', 'override', 'prefix_strip', 'translate', 'hide'])],
            'cli_override_number'  => ['nullable', 'string', 'max:40'],
            'cli_prefix_strip'     => ['nullable', 'integer', 'min:0', 'max:20'],
            'cli_prefix_add'       => ['nullable', 'string', 'max:20'],

            // Incoming
            'incoming_context'     => ['required', 'string', 'max:80'],
            'incoming_auth_type'   => ['required', Rule::in(['ip', 'registration', 'both'])],
            'incoming_ip_acl'      => ['nullable', 'string', 'max:500'],

            // Health
            'health_check_interval'         => ['required', 'integer', 'min:10', 'max:3600'],
            'health_auto_disable_threshold' => ['required', 'integer', 'min:1', 'max:100'],
            'health_asr_threshold'          => ['nullable', 'numeric', 'min:0', 'max:100'],

            // Notes
            'notes'             => ['nullable', 'string', 'max:5000'],
        ]);
    }
}
