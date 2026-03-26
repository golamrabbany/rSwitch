<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Broadcast;
use App\Models\SipAccount;
use App\Models\User;
use App\Models\SurveyTemplate;
use App\Models\VoiceFile;
use App\Services\BroadcastService;
use Illuminate\Http\Request;

class BroadcastController extends Controller
{
    public function index(Request $request)
    {
        $query = Broadcast::with(['user:id,name', 'voiceFile:id,name'])
            ->ownedBy(auth()->user());

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('search')) {
            $query->where('name', 'like', "%{$request->search}%");
        }

        $broadcasts = $query->orderByDesc('created_at')->paginate(20);

        return view('admin.broadcasts.index', compact('broadcasts'));
    }

    public function create()
    {
        $resellers = User::where('role', 'reseller')->orderBy('name')->get(['id', 'name', 'email']);
        $clients = User::where('role', 'client')->orderBy('name')->get(['id', 'name', 'email']);

        return view('admin.broadcasts.create', compact('resellers', 'clients'));
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
            'retry_attempts' => ['nullable', 'integer', 'min:0', 'max:5'],
            'survey_config' => ['nullable', 'json'],
        ]);

        $client = User::findOrFail($validated['user_id']);
        abort_unless(auth()->user()->canManage($client), 403);

        $data = array_merge($validated, [
            'caller_id_name' => $client->name,
            'caller_id_number' => SipAccount::find($validated['sip_account_id'])->username ?? '',
            'csv_file' => $request->file('csv_file'),
        ]);

        // If a survey template is selected, use its config
        if ($request->filled('survey_template_id')) {
            $template = SurveyTemplate::findOrFail($request->survey_template_id);
            abort_unless($template->isApproved(), 422, 'Template must be approved.');
            $data['survey_config'] = $template->config;
            $data['survey_template_id'] = $template->id;
            // Set voice_file_id from first question
            $firstVfId = collect($template->config['questions'] ?? [])->pluck('voice_file_id')->filter()->first();
            if ($firstVfId) {
                $data['voice_file_id'] = $firstVfId;
            }
        }

        if ($validated['type'] === 'survey' && !empty($validated['survey_config'])) {
            $data['survey_config'] = json_decode($validated['survey_config'], true);
        }

        // Build survey_config v2 from form questions
        if ($request->type === 'survey' && !$request->filled('survey_template_id') && $request->has('survey_questions')) {
            $questions = [];
            $qNum = 0;
            foreach ($request->input('survey_questions', []) as $sq) {
                $type = $sq['type'] ?? 'question';
                $key = $type === 'intro' ? 'intro' : 'q' . (++$qNum);
                $q = [
                    'key' => $key,
                    'type' => $type,
                    'voice_file_id' => (int) ($sq['voice_file_id'] ?? 0),
                    'label' => $sq['label'] ?? '',
                ];
                if ($type === 'question') {
                    $q['max_digits'] = (int) ($sq['max_digits'] ?? 1);
                    $q['timeout'] = (int) ($sq['timeout'] ?? 10);
                    $q['max_retries'] = (int) ($sq['max_retries'] ?? 2);
                    $options = [];
                    foreach ($sq['options'] ?? [] as $opt) {
                        if (!empty($opt['digit']) && !empty($opt['label'])) {
                            $options[$opt['digit']] = $opt['label'];
                        }
                    }
                    $q['options'] = $options;
                }
                $questions[] = $q;
            }
            $data['survey_config'] = ['version' => 2, 'questions' => $questions];

            // Set voice_file_id to first voice file in questions
            $firstVfId = collect($questions)->pluck('voice_file_id')->filter()->first();
            if ($firstVfId) {
                $data['voice_file_id'] = $firstVfId;
            }
        }

        $broadcast = $service->create($data, auth()->user());

        return redirect()->route('admin.broadcasts.show', $broadcast)
            ->with('success', "Broadcast '{$broadcast->name}' created with {$broadcast->total_numbers} numbers.");
    }

    public function show(Broadcast $broadcast)
    {
        $broadcast->load('user', 'voiceFile', 'sipAccount', 'creator');

        $numberStats = $broadcast->numbers()
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        return view('admin.broadcasts.show', compact('broadcast', 'numberStats'));
    }

    public function start(Broadcast $broadcast, BroadcastService $service)
    {
        $service->start($broadcast);
        return back()->with('success', 'Broadcast started.');
    }

    public function pause(Broadcast $broadcast, BroadcastService $service)
    {
        $service->pause($broadcast);
        return back()->with('success', 'Broadcast paused.');
    }

    public function resume(Broadcast $broadcast, BroadcastService $service)
    {
        $service->resume($broadcast);
        return back()->with('success', 'Broadcast resumed.');
    }

    public function cancel(Broadcast $broadcast, BroadcastService $service)
    {
        $service->cancel($broadcast);
        return back()->with('success', 'Broadcast cancelled.');
    }

    public function results(Broadcast $broadcast)
    {
        $results = $broadcast->numbers()->orderByDesc('updated_at')->paginate(50);

        // Stats
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

        // Survey breakdown
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

        return view('admin.broadcasts.results', compact('broadcast', 'results', 'stats', 'surveyBreakdown'));
    }

    public function stats(Broadcast $broadcast)
    {
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
        $numbers = $broadcast->numbers()->orderBy('phone_number')->get();

        $filename = 'broadcast-' . $broadcast->id . '-results.csv';
        $headers = ['Content-Type' => 'text/csv', 'Content-Disposition' => "attachment; filename=\"{$filename}\""];

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
        }, 200, $headers);
    }

    /**
     * AJAX: Get SIP accounts + voice files for a client.
     */
    public function clientData(Request $request)
    {
        $clientId = $request->input('client_id');
        $sipAccounts = SipAccount::where('user_id', $clientId)->where('status', 'active')->get(['id', 'username']);
        $voiceFiles = VoiceFile::where('user_id', $clientId)->approved()->get(['id', 'name', 'duration']);

        $templates = SurveyTemplate::where('client_id', $clientId)
            ->where('status', 'approved')
            ->get(['id', 'name', 'config'])
            ->map(function ($t) {
                return [
                    'id' => $t->id,
                    'name' => $t->name,
                    'question_count' => $t->getQuestionCount(),
                ];
            });

        return response()->json([
            'sip_accounts' => $sipAccounts,
            'voice_files' => $voiceFiles,
            'survey_templates' => $templates,
        ]);
    }
}
