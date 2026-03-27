<?php

namespace App\Http\Controllers\Reseller;

use App\Http\Controllers\Controller;
use App\Models\Broadcast;
use App\Models\SipAccount;
use App\Models\SurveyTemplate;
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
        $authUser = auth()->user();
        $descendantIds = $authUser->descendantIds();

        // Load approved voice templates belonging to reseller's clients
        $voiceTemplates = VoiceFile::with('user:id,name,email')
            ->whereIn('user_id', $descendantIds)
            ->approved()
            ->orderBy('name')
            ->get(['id', 'name', 'user_id', 'format', 'duration']);

        // Load approved survey templates for reseller's clients
        $surveyTemplates = SurveyTemplate::with('client:id,name,email')
            ->visibleTo($authUser)
            ->where('status', 'approved')
            ->orderBy('name')
            ->get(['id', 'name', 'client_id', 'config']);

        return view('reseller.broadcasts.create', compact('voiceTemplates', 'surveyTemplates'));
    }

    public function store(Request $request, BroadcastService $service)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'type' => ['required', 'in:simple,survey'],
            'voice_file_id' => ['required_if:type,simple', 'nullable', 'exists:voice_files,id'],
            'survey_template_id' => ['required_if:type,survey', 'nullable', 'exists:survey_templates,id'],
            'sip_account_id' => ['required', 'exists:sip_accounts,id'],
            'phone_list_type' => ['required', 'in:manual,csv'],
            'phone_numbers' => ['required_if:phone_list_type,manual', 'nullable', 'string'],
            'csv_file' => ['required_if:phone_list_type,csv', 'nullable', 'file', 'mimes:csv,txt', 'max:5120'],
            'max_concurrent' => ['nullable', 'integer', 'min:1', 'max:50'],
            'ring_timeout' => ['nullable', 'integer', 'min:10', 'max:60'],
        ]);

        $descendantIds = auth()->user()->descendantIds();

        // Derive client from selected template
        if ($validated['type'] === 'simple') {
            $voiceFile = VoiceFile::findOrFail($validated['voice_file_id']);
            $client = User::findOrFail($voiceFile->user_id);
        } else {
            $surveyTemplate = SurveyTemplate::findOrFail($validated['survey_template_id']);
            abort_unless($surveyTemplate->isApproved(), 422, 'Template must be approved.');
            $client = User::findOrFail($surveyTemplate->client_id);
        }

        abort_unless(in_array((int) $client->id, $descendantIds), 403);

        $data = array_merge($validated, [
            'user_id' => $client->id,
            'caller_id_name' => $client->name,
            'caller_id_number' => SipAccount::find($validated['sip_account_id'])->username ?? '',
            'csv_file' => $request->file('csv_file'),
        ]);

        // If survey, use template config
        if ($validated['type'] === 'survey' && isset($surveyTemplate)) {
            $data['survey_config'] = $surveyTemplate->config;
            $data['survey_template_id'] = $surveyTemplate->id;
            $firstVfId = collect($surveyTemplate->config['questions'] ?? [])->pluck('voice_file_id')->filter()->first();
            if ($firstVfId) {
                $data['voice_file_id'] = $firstVfId;
            }
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
            $config = $broadcast->survey_config;

            if ($broadcast->isMultiQuestion()) {
                // Multi-question v2
                $allResponses = $broadcast->numbers()
                    ->whereNotNull('survey_response')
                    ->pluck('survey_response');

                foreach ($broadcast->getSurveyQuestions() as $q) {
                    $key = $q['key'];
                    $options = $q['options'] ?? [];
                    $counts = [];
                    $total = 0;

                    foreach ($allResponses as $respJson) {
                        $resp = is_string($respJson) ? json_decode($respJson, true) : $respJson;
                        if (is_array($resp) && !empty($resp[$key])) {
                            $digit = $resp[$key];
                            $counts[$digit] = ($counts[$digit] ?? 0) + 1;
                            $total++;
                        }
                    }

                    $breakdown = [];
                    foreach ($counts as $digit => $count) {
                        $breakdown[] = [
                            'digit' => $digit,
                            'label' => $options[$digit] ?? "Option {$digit}",
                            'count' => $count,
                            'percentage' => $total > 0 ? round(($count / $total) * 100, 1) : 0,
                        ];
                    }

                    $surveyBreakdown[] = [
                        'key' => $key,
                        'label' => $q['label'] ?? $key,
                        'total_responses' => $total,
                        'breakdown' => $breakdown,
                    ];
                }
            } else {
                // Legacy single-question
                $surveyResponses = $broadcast->numbers()
                    ->whereNotNull('survey_response')
                    ->get()
                    ->map(function ($n) {
                        $resp = is_string($n->survey_response) ? json_decode($n->survey_response, true) : $n->survey_response;
                        return is_array($resp) ? ($resp['q1'] ?? null) : $n->getRawOriginal('survey_response');
                    })
                    ->filter()
                    ->countBy()
                    ->sortKeys();

                $totalResponses = $surveyResponses->sum();
                $config = is_array($config) ? $config : json_decode($config ?? '[]', true);
                $labels = [];
                if (!empty($config['options'])) {
                    $labels = $config['options'];
                } elseif (is_array($config)) {
                    $labels = collect($config)->pluck('label', 'digit')->toArray();
                }

                foreach ($surveyResponses as $digit => $count) {
                    $surveyBreakdown[] = [
                        'digit' => $digit,
                        'label' => $labels[$digit] ?? "Option {$digit}",
                        'count' => $count,
                        'percentage' => $totalResponses > 0 ? round(($count / $totalResponses) * 100, 1) : 0,
                    ];
                }
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

            $config = $broadcast->survey_config;
            $isMultiQ = $broadcast->isMultiQuestion();
            $questions = $isMultiQ ? $broadcast->getSurveyQuestions() : collect();

            $cols = ['Phone Number', 'Status', 'Attempts', 'Duration (s)', 'Cost'];
            if ($broadcast->isSurvey()) {
                if ($isMultiQ) {
                    foreach ($questions as $q) {
                        $cols[] = $q['label'] ?? $q['key'];
                    }
                } else {
                    $cols[] = 'Survey Response';
                }
            }
            fputcsv($out, $cols);

            foreach ($numbers as $n) {
                $row = [$n->phone_number, $n->status, $n->attempts, $n->duration ?? 0, $n->cost ?? 0];
                if ($broadcast->isSurvey()) {
                    $resp = is_string($n->survey_response) ? json_decode($n->survey_response, true) : $n->survey_response;
                    if ($isMultiQ) {
                        foreach ($questions as $q) {
                            $row[] = is_array($resp) ? ($resp[$q['key']] ?? '') : '';
                        }
                    } else {
                        $row[] = is_array($resp) ? ($resp['q1'] ?? '') : ($n->getRawOriginal('survey_response') ?? '');
                    }
                }
                fputcsv($out, $row);
            }
            fclose($out);
        }, 200, ['Content-Type' => 'text/csv', 'Content-Disposition' => "attachment; filename=\"{$filename}\""]);
    }

    /**
     * AJAX: Get client info + SIP accounts for a template.
     */
    public function templateData(Request $request)
    {
        $descendantIds = auth()->user()->descendantIds();
        $type = $request->input('type'); // 'voice' or 'survey'
        $templateId = $request->input('template_id');

        if ($type === 'voice') {
            $template = VoiceFile::with('user:id,name,email')->findOrFail($templateId);
            $clientId = $template->user_id;
            $client = $template->user;
        } else {
            $template = SurveyTemplate::with('client:id,name,email')->findOrFail($templateId);
            $clientId = $template->client_id;
            $client = $template->client;
        }

        abort_unless(in_array((int) $clientId, $descendantIds), 403);

        $sipAccounts = SipAccount::where('user_id', $clientId)->where('status', 'active')->get(['id', 'username']);

        return response()->json([
            'client' => ['id' => $client->id, 'name' => $client->name, 'email' => $client->email],
            'sip_accounts' => $sipAccounts,
        ]);
    }

    public function edit(Broadcast $broadcast)
    {
        $this->authorize($broadcast);
        abort_unless(in_array($broadcast->status, ['draft', 'scheduled']), 403, 'Only draft or scheduled broadcasts can be edited.');

        return view('reseller.broadcasts.edit', compact('broadcast'));
    }

    public function update(Request $request, Broadcast $broadcast)
    {
        $this->authorize($broadcast);
        abort_unless(in_array($broadcast->status, ['draft', 'scheduled']), 403, 'Only draft or scheduled broadcasts can be edited.');

        $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'max_concurrent' => ['nullable', 'integer', 'min:1', 'max:50'],
            'ring_timeout' => ['nullable', 'integer', 'min:10', 'max:120'],
        ]);

        $broadcast->update($request->only('name', 'max_concurrent', 'ring_timeout'));

        return redirect()->route('reseller.broadcasts.show', $broadcast)->with('success', 'Broadcast updated.');
    }

    private function authorize(Broadcast $broadcast): void
    {
        $descendantIds = auth()->user()->descendantIds();
        abort_unless(in_array($broadcast->user_id, $descendantIds), 403);
    }
}
