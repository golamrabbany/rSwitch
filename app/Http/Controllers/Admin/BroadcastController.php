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

        $baseQuery = Broadcast::ownedBy(auth()->user());
        $stats = [
            'total' => (clone $baseQuery)->count(),
            'draft' => (clone $baseQuery)->where('status', 'draft')->count(),
            'scheduled' => (clone $baseQuery)->where('status', 'scheduled')->count(),
            'running' => (clone $baseQuery)->where('status', 'running')->count(),
            'paused' => (clone $baseQuery)->where('status', 'paused')->count(),
            'completed' => (clone $baseQuery)->where('status', 'completed')->count(),
            'cancelled' => (clone $baseQuery)->where('status', 'cancelled')->count(),
        ];

        return view('admin.broadcasts.index', compact('broadcasts', 'stats'));
    }

    public function create()
    {
        $authUser = auth()->user();

        // Load all approved voice templates with client info
        $voiceTemplates = VoiceFile::with('user:id,name,email')
            ->ownedBy($authUser)
            ->approved()
            ->orderBy('name')
            ->get(['id', 'name', 'user_id', 'format', 'duration']);

        // Load all approved survey templates with client info
        $surveyTemplates = SurveyTemplate::with('client:id,name,email');
        if (!$authUser->isSuperAdmin()) {
            $surveyTemplates->visibleTo($authUser);
        }
        $surveyTemplates = $surveyTemplates->where('status', 'approved')
            ->orderBy('name')
            ->get(['id', 'name', 'client_id', 'config']);

        $voiceTemplatesJson = $voiceTemplates->map(function ($vt) {
            return ['id' => $vt->id, 'name' => $vt->name, 'client' => $vt->user->name ?? 'Unknown', 'format' => strtoupper($vt->format), 'duration' => $vt->duration];
        })->values();

        $surveyTemplatesJson = $surveyTemplates->map(function ($st) {
            return ['id' => $st->id, 'name' => $st->name, 'client' => $st->client->name ?? 'Unknown', 'questions' => $st->getQuestionCount()];
        })->values();

        return view('admin.broadcasts.create', compact('voiceTemplates', 'surveyTemplates', 'voiceTemplatesJson', 'surveyTemplatesJson'));
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
            'retry_attempts' => ['nullable', 'integer', 'min:0', 'max:5'],
        ]);

        // Derive client from selected template
        if ($validated['type'] === 'simple') {
            $voiceFile = VoiceFile::findOrFail($validated['voice_file_id']);
            $client = User::findOrFail($voiceFile->user_id);
        } else {
            $surveyTemplate = SurveyTemplate::findOrFail($validated['survey_template_id']);
            abort_unless($surveyTemplate->isApproved(), 422, 'Template must be approved.');
            $client = User::findOrFail($surveyTemplate->client_id);
        }

        abort_unless(auth()->user()->canManage($client), 403);

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

        return redirect()->route('admin.broadcasts.show', $broadcast)->with('success', $msg);
    }

    public function show(Broadcast $broadcast)
    {
        $broadcast->load('user', 'voiceFile', 'sipAccount', 'creator');

        $numberStats = $broadcast->numbers()
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        $callStats = $broadcast->numbers()
            ->selectRaw('COALESCE(SUM(cost), 0) as total_cost, AVG(CASE WHEN duration > 0 THEN duration END) as avg_duration, SUM(duration) as total_duration')
            ->first();

        return view('admin.broadcasts.show', compact('broadcast', 'numberStats', 'callStats'));
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

    public function edit(Broadcast $broadcast)
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);
        abort_unless(in_array($broadcast->status, ['draft', 'scheduled', 'paused']), 403, 'Pause the broadcast first to edit.');

        $broadcast->load('user', 'voiceFile', 'sipAccount');

        $sipAccounts = SipAccount::where('user_id', $broadcast->user_id)
            ->where('status', 'active')
            ->get(['id', 'username', 'max_channels']);

        $voiceFiles = VoiceFile::where('user_id', $broadcast->user_id)
            ->approved()
            ->orderBy('name')
            ->get(['id', 'name', 'format', 'duration']);

        return view('admin.broadcasts.edit', compact('broadcast', 'sipAccounts', 'voiceFiles'));
    }

    public function update(Request $request, Broadcast $broadcast)
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);
        abort_unless(in_array($broadcast->status, ['draft', 'scheduled', 'paused']), 403, 'Pause the broadcast first to edit.');

        $request->validate([
            'sip_account_id' => ['nullable', 'exists:sip_accounts,id'],
            'max_concurrent' => ['nullable', 'integer', 'min:1', 'max:50'],
            'ring_timeout' => ['nullable', 'integer', 'min:10', 'max:120'],
        ]);

        $data = $request->only('max_concurrent', 'ring_timeout');

        if ($request->filled('sip_account_id')) {
            $data['sip_account_id'] = $request->sip_account_id;
            $data['caller_id_number'] = SipAccount::find($request->sip_account_id)->username ?? $broadcast->caller_id_number;
        }

        $broadcast->update($data);

        return redirect()->route('admin.broadcasts.show', $broadcast)->with('success', 'Broadcast updated.');
    }

    public function suspend(Broadcast $broadcast)
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);

        $broadcast->update(['status' => 'paused']);

        return back()->with('success', 'Broadcast suspended.');
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
        $broadcast->load('user', 'voiceFile', 'sipAccount');
        $numbers = $broadcast->numbers()->orderBy('phone_number')->get();

        $isMultiQ = $broadcast->isMultiQuestion();
        $questions = $isMultiQ ? $broadcast->getSurveyQuestions() : collect();

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();

        // --- Sheet 1: Summary ---
        $summary = $spreadsheet->getActiveSheet();
        $summary->setTitle('Summary');

        // Title
        $summary->setCellValue('A1', 'Broadcast Report');
        $summary->getStyle('A1')->getFont()->setBold(true)->setSize(16);
        $summary->mergeCells('A1:D1');

        // Broadcast info
        $info = [
            ['Broadcast Name', $broadcast->name],
            ['Type', ucfirst($broadcast->type)],
            ['Status', ucfirst($broadcast->status)],
            ['Client', $broadcast->user?->name ?? '—'],
            ['Voice Template', $broadcast->voiceFile?->name ?? '—'],
            ['SIP Account', $broadcast->sipAccount?->username ?? '—'],
            ['Max Concurrent', $broadcast->max_concurrent],
            ['Ring Timeout', $broadcast->ring_timeout . 's'],
            ['Created', $broadcast->created_at->format('d M Y, g:i A')],
            [''],
            ['Total Numbers', $broadcast->total_numbers],
            ['Answered', $broadcast->answered_count],
            ['Failed', $broadcast->failed_count],
            ['Answer Rate', $broadcast->total_numbers > 0 ? round(($broadcast->answered_count / $broadcast->total_numbers) * 100, 1) . '%' : '0%'],
            ['Total Cost', format_currency($broadcast->total_cost ?? 0)],
        ];

        $row = 3;
        foreach ($info as $line) {
            if (count($line) === 2) {
                $summary->setCellValue("A{$row}", $line[0]);
                $summary->setCellValue("B{$row}", $line[1]);
                $summary->getStyle("A{$row}")->getFont()->setBold(true);
                $summary->getStyle("A{$row}")->getFont()->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('666666'));
            }
            $row++;
        }

        $summary->getColumnDimension('A')->setWidth(20);
        $summary->getColumnDimension('B')->setWidth(30);

        // --- Sheet 2: Call Data ---
        $data = $spreadsheet->createSheet();
        $data->setTitle('Call Data');

        // Headers
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

        $colIdx = 'A';
        foreach ($cols as $col) {
            $data->setCellValue("{$colIdx}1", $col);
            $colIdx++;
        }

        // Header style
        $lastCol = chr(ord('A') + count($cols) - 1);
        $headerStyle = $data->getStyle("A1:{$lastCol}1");
        $headerStyle->getFont()->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFFFFF'));
        $headerStyle->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('4F46E5');
        $headerStyle->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        // Data rows
        $row = 2;
        foreach ($numbers as $n) {
            $data->setCellValue("A{$row}", $n->phone_number);
            $data->setCellValue("B{$row}", ucfirst($n->status));
            $data->setCellValue("C{$row}", $n->attempts);
            $data->setCellValue("D{$row}", $n->duration ?? 0);
            $data->setCellValue("E{$row}", $n->cost ?? 0);

            if ($broadcast->isSurvey()) {
                $resp = is_string($n->survey_response) ? json_decode($n->survey_response, true) : $n->survey_response;
                $ci = 5; // column F onwards
                if ($isMultiQ) {
                    foreach ($questions as $q) {
                        $data->setCellValueByColumnAndRow($ci + 1, $row, is_array($resp) ? ($resp[$q['key']] ?? '') : '');
                        $ci++;
                    }
                } else {
                    $data->setCellValue("F{$row}", is_array($resp) ? ($resp['q1'] ?? '') : ($n->getRawOriginal('survey_response') ?? ''));
                }
            }

            // Status color
            $statusColors = ['answered' => 'DCFCE7', 'failed' => 'FEE2E2', 'no_answer' => 'FEF3C7', 'busy' => 'FFEDD5', 'pending' => 'F3F4F6'];
            if (isset($statusColors[$n->status])) {
                $data->getStyle("B{$row}")->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB($statusColors[$n->status]);
            }

            // Alternate row shading
            if ($row % 2 === 0) {
                $data->getStyle("A{$row}:{$lastCol}{$row}")->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('F9FAFB');
            }

            $row++;
        }

        // Auto-size columns
        foreach (range('A', $lastCol) as $c) {
            $data->getColumnDimension($c)->setAutoSize(true);
        }

        // Borders
        $data->getStyle("A1:{$lastCol}" . ($row - 1))->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('E5E7EB'));

        // Freeze header row
        $data->freezePane('A2');
        $data->setAutoFilter("A1:{$lastCol}1");

        // Set Call Data as active sheet
        $spreadsheet->setActiveSheetIndex(1);

        // Generate file
        $filename = 'broadcast-' . $broadcast->id . '-' . \Illuminate\Support\Str::slug($broadcast->name) . '.xlsx';
        $temp = tempnam(sys_get_temp_dir(), 'broadcast');
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save($temp);

        return response()->download($temp, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    public function destroy(Broadcast $broadcast)
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);
        abort_unless(in_array($broadcast->status, ['draft', 'cancelled']), 403, 'Only draft or cancelled broadcasts can be deleted.');
        abort_if($broadcast->answered_count > 0, 403, 'Cannot delete — broadcast has answered calls.');

        $broadcast->numbers()->delete();
        $broadcast->delete();

        return redirect()->route('admin.broadcasts.index')->with('success', "Broadcast '{$broadcast->name}' deleted.");
    }

    /**
     * AJAX: Get client info + SIP accounts for a template.
     */
    public function templateData(Request $request)
    {
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

        abort_unless(auth()->user()->canManage($client), 403);

        $sipAccounts = SipAccount::where('user_id', $clientId)->where('status', 'active')->get(['id', 'username', 'max_channels']);

        return response()->json([
            'client' => ['id' => $client->id, 'name' => $client->name, 'email' => $client->email, 'balance' => (float) $client->balance],
            'sip_accounts' => $sipAccounts,
        ]);
    }
}
