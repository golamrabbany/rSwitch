<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Broadcast;
use App\Models\SipAccount;
use App\Models\VoiceFile;
use App\Services\BroadcastService;
use Illuminate\Http\Request;

class BroadcastController extends Controller
{
    public function index()
    {
        $broadcasts = Broadcast::where('user_id', auth()->id())
            ->with('voiceFile:id,name')
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('client.broadcasts.index', compact('broadcasts'));
    }

    public function create()
    {
        $sipAccounts = SipAccount::where('user_id', auth()->id())->where('status', 'active')->get();
        $voiceFiles = VoiceFile::where('user_id', auth()->id())->approved()->get();

        return view('client.broadcasts.create', compact('sipAccounts', 'voiceFiles'));
    }

    public function store(Request $request, BroadcastService $service)
    {
        $validated = $request->validate([
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

        // Verify ownership
        abort_unless(SipAccount::where('id', $validated['sip_account_id'])->where('user_id', auth()->id())->exists(), 403);
        abort_unless(VoiceFile::where('id', $validated['voice_file_id'])->where('user_id', auth()->id())->approved()->exists(), 403);

        $data = array_merge($validated, [
            'user_id' => auth()->id(),
            'caller_id_name' => auth()->user()->name,
            'caller_id_number' => SipAccount::find($validated['sip_account_id'])->username,
            'csv_file' => $request->file('csv_file'),
        ]);

        if ($validated['type'] === 'survey' && !empty($validated['survey_config'])) {
            $data['survey_config'] = json_decode($validated['survey_config'], true);
        }

        $broadcast = $service->create($data, auth()->user());

        return redirect()->route('client.broadcasts.show', $broadcast)
            ->with('success', "Broadcast '{$broadcast->name}' created with {$broadcast->total_numbers} numbers.");
    }

    public function show(Broadcast $broadcast)
    {
        abort_unless($broadcast->user_id === auth()->id(), 403);
        $broadcast->load('voiceFile', 'sipAccount');

        $numberStats = $broadcast->numbers()
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        return view('client.broadcasts.show', compact('broadcast', 'numberStats'));
    }

    public function start(Broadcast $broadcast, BroadcastService $service)
    {
        abort_unless($broadcast->user_id === auth()->id(), 403);
        $service->start($broadcast);
        return back()->with('success', 'Broadcast started.');
    }

    public function pause(Broadcast $broadcast, BroadcastService $service)
    {
        abort_unless($broadcast->user_id === auth()->id(), 403);
        $service->pause($broadcast);
        return back()->with('success', 'Broadcast paused.');
    }

    public function resume(Broadcast $broadcast, BroadcastService $service)
    {
        abort_unless($broadcast->user_id === auth()->id(), 403);
        $service->resume($broadcast);
        return back()->with('success', 'Broadcast resumed.');
    }

    public function cancel(Broadcast $broadcast, BroadcastService $service)
    {
        abort_unless($broadcast->user_id === auth()->id(), 403);
        $service->cancel($broadcast);
        return back()->with('success', 'Broadcast cancelled.');
    }

    public function results(Broadcast $broadcast)
    {
        abort_unless($broadcast->user_id === auth()->id(), 403);

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

        return view('client.broadcasts.results', compact('broadcast', 'results', 'stats', 'surveyBreakdown'));
    }

    public function stats(Broadcast $broadcast)
    {
        abort_unless($broadcast->user_id === auth()->id(), 403);

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
        abort_unless($broadcast->user_id === auth()->id(), 403);

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
}
