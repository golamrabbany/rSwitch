<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Broadcast;
use App\Models\SipAccount;
use App\Models\SurveyTemplate;
use App\Models\VoiceFile;
use App\Services\BroadcastService;
use Illuminate\Http\Request;

class BroadcastController extends Controller
{
    public function index(Request $request)
    {
        $query = Broadcast::where('user_id', auth()->id())
            ->with('voiceFile:id,name');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $broadcasts = $query->orderByDesc('created_at')->paginate(20);

        $baseQuery = Broadcast::where('user_id', auth()->id());
        $stats = [
            'total' => (clone $baseQuery)->count(),
            'draft' => (clone $baseQuery)->where('status', 'draft')->count(),
            'running' => (clone $baseQuery)->where('status', 'running')->count(),
            'paused' => (clone $baseQuery)->where('status', 'paused')->count(),
            'completed' => (clone $baseQuery)->where('status', 'completed')->count(),
            'cancelled' => (clone $baseQuery)->where('status', 'cancelled')->count(),
        ];

        return view('client.broadcasts.index', compact('broadcasts', 'stats'));
    }

    public function create()
    {
        $sipAccounts = SipAccount::where('user_id', auth()->id())->where('status', 'active')->get();
        $voiceFiles = VoiceFile::where('user_id', auth()->id())->approved()->get();
        $surveyTemplates = SurveyTemplate::where('client_id', auth()->id())
            ->where('status', 'approved')
            ->get(['id', 'name', 'config']);

        return view('client.broadcasts.create', compact('sipAccounts', 'voiceFiles', 'surveyTemplates'));
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

        // Verify ownership
        abort_unless(SipAccount::where('id', $validated['sip_account_id'])->where('user_id', auth()->id())->exists(), 403);

        if ($validated['type'] === 'simple') {
            abort_unless(VoiceFile::where('id', $validated['voice_file_id'])->where('user_id', auth()->id())->approved()->exists(), 403);
        }

        $data = array_merge($validated, [
            'user_id' => auth()->id(),
            'caller_id_name' => auth()->user()->name,
            'caller_id_number' => SipAccount::find($validated['sip_account_id'])->username,
            'csv_file' => $request->file('csv_file'),
        ]);

        // If survey, use template config
        if ($validated['type'] === 'survey' && !empty($validated['survey_template_id'])) {
            $surveyTemplate = SurveyTemplate::findOrFail($validated['survey_template_id']);
            abort_unless($surveyTemplate->client_id === auth()->id(), 403);
            abort_unless($surveyTemplate->isApproved(), 422, 'Template must be approved.');
            $data['survey_config'] = $surveyTemplate->config;
            $data['survey_template_id'] = $surveyTemplate->id;
            $firstVfId = collect($surveyTemplate->config['questions'] ?? [])->pluck('voice_file_id')->filter()->first();
            if ($firstVfId) {
                $data['voice_file_id'] = $firstVfId;
            }
        }

        // Handle scheduling
        if ($request->input('schedule_type') === 'scheduled' && $request->filled('scheduled_date') && $request->filled('scheduled_time')) {
            $scheduledAt = \Carbon\Carbon::parse($request->scheduled_date . ' ' . $request->scheduled_time);
            abort_if($scheduledAt->isPast(), 422, 'Scheduled time must be in the future.');
            $data['scheduled_at'] = $scheduledAt;
            $data['status'] = 'scheduled';
        }

        $broadcast = $service->create($data, auth()->user());

        $msg = "Broadcast '{$broadcast->name}' created with {$broadcast->total_numbers} numbers.";
        if ($broadcast->status === 'scheduled') {
            $msg .= ' Scheduled for ' . $broadcast->scheduled_at->format('M d, Y g:i A') . '.';
        }

        return redirect()->route('client.broadcasts.show', $broadcast)->with('success', $msg);
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

    public function edit(Broadcast $broadcast)
    {
        abort_unless($broadcast->user_id === auth()->id(), 403);
        abort_unless(in_array($broadcast->status, ['draft', 'scheduled', 'paused']), 403, 'Pause the broadcast first to edit.');

        $broadcast->load('voiceFile', 'sipAccount');

        $sipAccounts = SipAccount::where('user_id', auth()->id())
            ->where('status', 'active')
            ->get(['id', 'username', 'max_channels']);

        // draft/scheduled can change more fields than paused
        $canEditFull = in_array($broadcast->status, ['draft', 'scheduled']);

        return view('client.broadcasts.edit', compact('broadcast', 'sipAccounts', 'canEditFull'));
    }

    public function update(Request $request, Broadcast $broadcast)
    {
        abort_unless($broadcast->user_id === auth()->id(), 403);
        abort_unless(in_array($broadcast->status, ['draft', 'scheduled', 'paused']), 403, 'Pause the broadcast first to edit.');

        $canEditFull = in_array($broadcast->status, ['draft', 'scheduled']);

        $rules = [
            'sip_account_id' => ['nullable', 'exists:sip_accounts,id'],
        ];

        if ($canEditFull) {
            $rules['name'] = ['required', 'string', 'max:150'];
            $rules['phone_numbers'] = ['nullable', 'string'];
            $rules['csv_file'] = ['nullable', 'file', 'mimes:csv,txt', 'max:5120'];
        }

        $request->validate($rules);

        $data = [];

        // SIP account change — auto-set max_concurrent from SIP max_channels
        if ($request->filled('sip_account_id')) {
            $sip = SipAccount::where('id', $request->sip_account_id)->where('user_id', auth()->id())->first();
            abort_unless($sip, 403);
            $data['sip_account_id'] = $sip->id;
            $data['caller_id_number'] = $sip->username;
            $data['max_concurrent'] = $sip->max_channels ?? $broadcast->max_concurrent;
        }

        // Draft/Scheduled: can also update name, schedule, add numbers
        if ($canEditFull) {
            $data['name'] = $request->name;

            // Schedule change
            if ($request->input('schedule_type') === 'scheduled' && $request->filled('scheduled_date') && $request->filled('scheduled_time')) {
                $scheduledAt = \Carbon\Carbon::parse($request->scheduled_date . ' ' . $request->scheduled_time);
                abort_if($scheduledAt->isPast(), 422, 'Scheduled time must be in the future.');
                $data['scheduled_at'] = $scheduledAt;
                $data['status'] = 'scheduled';
            } elseif ($request->input('schedule_type') === 'now' && $broadcast->status === 'scheduled') {
                $data['scheduled_at'] = null;
                $data['status'] = 'draft';
            }

            // Add more numbers (append, don't replace)
            $newNumbers = [];
            if ($request->filled('phone_numbers')) {
                $raw = preg_split('/[\r\n,;]+/', $request->phone_numbers);
                $newNumbers = collect($raw)
                    ->map(fn ($n) => preg_replace('/[^0-9+]/', '', trim($n)))
                    ->filter(fn ($n) => strlen($n) >= 7)
                    ->unique()
                    ->values()
                    ->toArray();
            } elseif ($request->hasFile('csv_file')) {
                $lines = file($request->file('csv_file')->getRealPath(), FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                $newNumbers = collect($lines)
                    ->map(fn ($n) => preg_replace('/[^0-9+]/', '', trim(str_getcsv($n)[0] ?? '')))
                    ->filter(fn ($n) => strlen($n) >= 7)
                    ->unique()
                    ->values()
                    ->toArray();
            }

            if (!empty($newNumbers)) {
                // Remove DNC + existing numbers
                $cleanNumbers = \App\Models\DncNumber::filterNumbers($newNumbers);
                $existing = $broadcast->numbers()->pluck('phone_number')->toArray();
                $cleanNumbers = array_values(array_diff($cleanNumbers, $existing));

                if (!empty($cleanNumbers)) {
                    $now = now();
                    $inserts = [];
                    foreach ($cleanNumbers as $number) {
                        $inserts[] = [
                            'broadcast_id' => $broadcast->id,
                            'phone_number' => $number,
                            'status' => 'pending',
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    }
                    \App\Models\BroadcastNumber::insert($inserts);
                    $data['total_numbers'] = $broadcast->total_numbers + count($cleanNumbers);
                }
            }
        }

        $broadcast->update($data);

        // Save & Start
        if ($request->input('edit_action') === 'start' && $broadcast->total_numbers > 0) {
            app(BroadcastService::class)->start($broadcast);
            return redirect()->route('client.broadcasts.show', $broadcast)->with('success', 'Broadcast updated and started.');
        }

        return redirect()->route('client.broadcasts.show', $broadcast)->with('success', 'Broadcast updated.');
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
}
