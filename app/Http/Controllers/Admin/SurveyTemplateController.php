<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SurveyTemplate;
use App\Models\VoiceFile;
use App\Services\VoiceFileService;
use Illuminate\Http\Request;

class SurveyTemplateController extends Controller
{
    public function index(Request $request)
    {
        $query = SurveyTemplate::with(['user', 'client', 'approvedBy']);

        if (!auth()->user()->isSuperAdmin()) {
            $query->visibleTo(auth()->user());
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $templates = $query->orderByDesc('created_at')->paginate(20);

        return view('admin.survey-templates.index', compact('templates'));
    }

    public function create()
    {
        return view('admin.survey-templates.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:150',
            'description' => 'nullable|string|max:500',
            'client_id' => 'required|exists:users,id',
        ]);

        // Build config from survey_questions
        $questions = [];
        $qNum = 0;
        $voiceFileService = app(VoiceFileService::class);

        foreach ($request->input('survey_questions', []) as $idx => $sq) {
            $type = $sq['type'] ?? 'question';
            $key = $type === 'intro' ? 'intro' : 'q' . (++$qNum);

            $q = [
                'key' => $key,
                'type' => $type,
                'label' => $sq['label'] ?? '',
            ];

            // Handle voice file upload for this question
            $fileKey = "survey_questions.{$idx}.voice_file";
            if ($request->hasFile($fileKey)) {
                $file = $request->file($fileKey);
                $vf = $voiceFileService->upload($file, auth()->user(), $sq['label'] ?? "Survey Q{$qNum}");
                // Auto-approve if super admin
                if (auth()->user()->isSuperAdmin()) {
                    $voiceFileService->approve($vf, auth()->user());
                }
                $q['voice_file_id'] = $vf->id;
                $q['voice_file_path'] = $vf->file_path_asterisk;
            } elseif (!empty($sq['voice_file_id'])) {
                // Use existing voice file
                $vf = VoiceFile::find($sq['voice_file_id']);
                if ($vf) {
                    $q['voice_file_id'] = $vf->id;
                    $q['voice_file_path'] = $vf->file_path_asterisk;
                }
            }

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

        $template = SurveyTemplate::create([
            'user_id' => auth()->id(),
            'client_id' => $request->client_id,
            'name' => $request->name,
            'description' => $request->description,
            'status' => auth()->user()->isSuperAdmin() ? 'approved' : 'pending',
            'config' => ['version' => 2, 'questions' => $questions],
            'approved_by' => auth()->user()->isSuperAdmin() ? auth()->id() : null,
            'approved_at' => auth()->user()->isSuperAdmin() ? now() : null,
        ]);

        return redirect()->route('admin.survey-templates.index')
            ->with('success', 'Survey template created successfully.');
    }

    public function show(SurveyTemplate $surveyTemplate)
    {
        $surveyTemplate->load(['user', 'client', 'approvedBy']);
        return view('admin.survey-templates.show', ['template' => $surveyTemplate]);
    }

    public function approve(SurveyTemplate $surveyTemplate)
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);

        // Also approve all voice files in the template
        $config = $surveyTemplate->config;
        if (!empty($config['questions'])) {
            foreach ($config['questions'] as $q) {
                if (!empty($q['voice_file_id'])) {
                    $vf = VoiceFile::find($q['voice_file_id']);
                    if ($vf && $vf->status !== 'approved') {
                        $vf->update([
                            'status' => 'approved',
                            'approved_by' => auth()->id(),
                            'approved_at' => now(),
                        ]);
                    }
                }
            }
        }

        $surveyTemplate->update([
            'status' => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
            'rejection_reason' => null,
        ]);

        return back()->with('success', 'Survey template approved.');
    }

    public function reject(Request $request, SurveyTemplate $surveyTemplate)
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);

        $request->validate(['rejection_reason' => 'required|string|max:500']);

        $surveyTemplate->update([
            'status' => 'rejected',
            'rejection_reason' => $request->rejection_reason,
        ]);

        return back()->with('success', 'Survey template rejected.');
    }

    public function destroy(SurveyTemplate $surveyTemplate)
    {
        abort_unless(
            auth()->user()->isSuperAdmin() || $surveyTemplate->user_id === auth()->id(),
            403
        );
        abort_if($surveyTemplate->broadcasts()->exists(), 422, 'Cannot delete template with existing broadcasts.');

        $surveyTemplate->delete();
        return redirect()->route('admin.survey-templates.index')
            ->with('success', 'Survey template deleted.');
    }
}
