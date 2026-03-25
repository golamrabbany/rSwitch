<?php

namespace App\Http\Controllers\Reseller;

use App\Http\Controllers\Controller;
use App\Models\Broadcast;
use App\Models\SipAccount;
use App\Models\User;
use App\Models\VoiceFile;
use App\Services\BroadcastService;
use Illuminate\Http\Request;

class BroadcastController extends Controller
{
    public function index(Request $request)
    {
        $descendantIds = auth()->user()->descendantIds();

        $query = Broadcast::with(['user:id,name', 'voiceFile:id,name'])
            ->whereIn('user_id', $descendantIds);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $broadcasts = $query->orderByDesc('created_at')->paginate(20);

        return view('reseller.broadcasts.index', compact('broadcasts'));
    }

    public function create()
    {
        $clients = User::whereIn('id', auth()->user()->descendantIds())
            ->where('role', 'client')
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        $clientsJson = $clients->map(function ($c) {
            return ['id' => $c->id, 'name' => $c->name, 'email' => $c->email];
        })->values()->toArray();

        return view('reseller.broadcasts.create', compact('clients', 'clientsJson'));
    }

    public function store(Request $request, BroadcastService $service)
    {
        $validated = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'name' => ['required', 'string', 'max:150'],
            'sip_account_id' => ['required', 'exists:sip_accounts,id'],
            'voice_file_id' => ['required', 'exists:voice_files,id'],
            'type' => ['required', 'in:simple,survey'],
            'phone_list_type' => ['required', 'in:manual,csv'],
            'phone_numbers' => ['required_if:phone_list_type,manual', 'nullable', 'string'],
            'csv_file' => ['required_if:phone_list_type,csv', 'nullable', 'file', 'mimes:csv,txt', 'max:5120'],
            'max_concurrent' => ['nullable', 'integer', 'min:1', 'max:50'],
            'ring_timeout' => ['nullable', 'integer', 'min:10', 'max:60'],
            'survey_config' => ['nullable', 'json'],
        ]);

        $descendantIds = auth()->user()->descendantIds();
        abort_unless(in_array((int) $validated['user_id'], $descendantIds), 403);

        $client = User::findOrFail($validated['user_id']);

        $data = array_merge($validated, [
            'caller_id_name' => $client->name,
            'caller_id_number' => SipAccount::find($validated['sip_account_id'])->username ?? '',
            'csv_file' => $request->file('csv_file'),
        ]);

        if ($validated['type'] === 'survey' && !empty($validated['survey_config'])) {
            $data['survey_config'] = json_decode($validated['survey_config'], true);
        }

        $broadcast = $service->create($data, auth()->user());

        return redirect()->route('reseller.broadcasts.show', $broadcast)
            ->with('success', "Broadcast '{$broadcast->name}' created with {$broadcast->total_numbers} numbers.");
    }

    public function show(Broadcast $broadcast)
    {
        $descendantIds = auth()->user()->descendantIds();
        abort_unless(in_array($broadcast->user_id, $descendantIds), 403);

        $broadcast->load('user', 'voiceFile', 'sipAccount');

        $numberStats = $broadcast->numbers()
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        return view('reseller.broadcasts.show', compact('broadcast', 'numberStats'));
    }

    public function start(Broadcast $broadcast, BroadcastService $service)
    {
        $this->authorize($broadcast);
        $service->start($broadcast);
        return back()->with('success', 'Broadcast started.');
    }

    public function pause(Broadcast $broadcast, BroadcastService $service)
    {
        $this->authorize($broadcast);
        $service->pause($broadcast);
        return back()->with('success', 'Broadcast paused.');
    }

    public function resume(Broadcast $broadcast, BroadcastService $service)
    {
        $this->authorize($broadcast);
        $service->resume($broadcast);
        return back()->with('success', 'Broadcast resumed.');
    }

    public function cancel(Broadcast $broadcast, BroadcastService $service)
    {
        $this->authorize($broadcast);
        $service->cancel($broadcast);
        return back()->with('success', 'Broadcast cancelled.');
    }

    public function results(Broadcast $broadcast)
    {
        $this->authorize($broadcast);

        $results = $broadcast->numbers()->orderByDesc('updated_at')->paginate(50);

        $statsRow = $broadcast->numbers()
            ->selectRaw('COUNT(*) as total, SUM(status = "answered") as answered, SUM(status IN ("failed","no_answer","busy")) as failed, COALESCE(SUM(cost), 0) as cost, AVG(CASE WHEN duration > 0 THEN duration END) as avg_duration')
            ->first();

        $stats = [
            'total' => $statsRow->total ?? $broadcast->total_numbers,
            'answered' => $statsRow->answered ?? 0,
            'failed' => $statsRow->failed ?? 0,
            'cost' => $statsRow->cost ?? 0,
            'avg_duration' => $statsRow->avg_duration ? round($statsRow->avg_duration) . 's' : '0s',
        ];

        $surveyBreakdown = [];
        if ($broadcast->isSurvey()) {
            $surveyResponses = $broadcast->numbers()
                ->whereNotNull('survey_response')
                ->selectRaw('survey_response, COUNT(*) as count')
                ->groupBy('survey_response')
                ->orderBy('survey_response')
                ->get();

            $totalResponses = $surveyResponses->sum('count');
            $config = is_array($broadcast->survey_config) ? $broadcast->survey_config : json_decode($broadcast->survey_config ?? '[]', true);
            $labels = collect($config)->pluck('label', 'digit')->toArray();

            foreach ($surveyResponses as $resp) {
                $surveyBreakdown[] = [
                    'digit' => $resp->survey_response,
                    'label' => $labels[$resp->survey_response] ?? "Option {$resp->survey_response}",
                    'count' => $resp->count,
                    'percentage' => $totalResponses > 0 ? round(($resp->count / $totalResponses) * 100, 1) : 0,
                ];
            }
        }

        return view('reseller.broadcasts.results', compact('broadcast', 'results', 'stats', 'surveyBreakdown'));
    }

    public function stats(Broadcast $broadcast)
    {
        $this->authorize($broadcast);

        $statsRow = $broadcast->numbers()
            ->selectRaw('COUNT(*) as total, SUM(status = "answered") as answered, SUM(status IN ("failed","no_answer","busy")) as failed, SUM(status = "pending") as pending, SUM(status = "dialing") as dialing')
            ->first();

        return response()->json([
            'status' => $broadcast->status,
            'total' => (int) ($statsRow->total ?? $broadcast->total_numbers),
            'answered' => (int) ($statsRow->answered ?? 0),
            'failed' => (int) ($statsRow->failed ?? 0),
            'pending' => (int) ($statsRow->pending ?? 0),
            'dialing' => (int) ($statsRow->dialing ?? 0),
            'progress' => $broadcast->total_numbers > 0
                ? round((($statsRow->answered + $statsRow->failed) / $broadcast->total_numbers) * 100, 1)
                : 0,
        ]);
    }

    public function exportResults(Broadcast $broadcast)
    {
        $this->authorize($broadcast);

        $numbers = $broadcast->numbers()->orderBy('phone_number')->get();
        $filename = 'broadcast-' . $broadcast->id . '-results.csv';

        return response()->stream(function () use ($numbers, $broadcast) {
            $out = fopen('php://output', 'w');
            $cols = ['Phone Number', 'Status', 'Attempts', 'Duration (s)', 'Cost'];
            if ($broadcast->isSurvey()) $cols[] = 'Survey Response';
            fputcsv($out, $cols);
            foreach ($numbers as $n) {
                $row = [$n->phone_number, $n->status, $n->attempts, $n->duration ?? 0, $n->cost ?? 0];
                if ($broadcast->isSurvey()) $row[] = $n->survey_response ?? '';
                fputcsv($out, $row);
            }
            fclose($out);
        }, 200, ['Content-Type' => 'text/csv', 'Content-Disposition' => "attachment; filename=\"{$filename}\""]);
    }

    /**
     * AJAX: Get SIP accounts + voice files for a client.
     */
    public function clientData(Request $request)
    {
        $descendantIds = auth()->user()->descendantIds();
        $clientId = $request->input('client_id');
        abort_unless(in_array((int) $clientId, $descendantIds), 403);

        $sipAccounts = SipAccount::where('user_id', $clientId)->where('status', 'active')->get(['id', 'username']);
        $voiceFiles = VoiceFile::where('user_id', $clientId)->approved()->get(['id', 'name', 'duration']);

        return response()->json(['sip_accounts' => $sipAccounts, 'voice_files' => $voiceFiles]);
    }

    private function authorize(Broadcast $broadcast): void
    {
        $descendantIds = auth()->user()->descendantIds();
        abort_unless(in_array($broadcast->user_id, $descendantIds), 403);
    }
}
