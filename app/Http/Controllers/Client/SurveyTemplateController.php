<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\SurveyTemplate;
use App\Models\VoiceFile;
use App\Services\VoiceFileService;
use Illuminate\Http\Request;

class SurveyTemplateController extends Controller
{
    public function index(Request $request)
    {
        $query = SurveyTemplate::where('client_id', auth()->id());

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $templates = $query->orderByDesc('created_at')->paginate(20);

        $baseQuery = SurveyTemplate::where('client_id', auth()->id());
        $stats = [
            'total' => (clone $baseQuery)->count(),
            'pending' => (clone $baseQuery)->where('status', 'pending')->count(),
            'approved' => (clone $baseQuery)->where('status', 'approved')->count(),
            'rejected' => (clone $baseQuery)->where('status', 'rejected')->count(),
        ];

        return view('client.survey-templates.index', compact('templates', 'stats'));
    }

    public function create()
    {
        return view('client.survey-templates.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:150',
            'description' => 'nullable|string|max:500',
        ]);

        $authUser = auth()->user();

        $questions = [];
        $qNum = 0;
        foreach ($request->input('survey_questions', []) as $sq) {
            $type = $sq['type'] ?? 'question';
            $key = $type === 'intro' ? 'intro' : 'q' . (++$qNum);

            $q = ['key' => $key, 'type' => $type, 'label' => $sq['label'] ?? ''];

            if (!empty($sq['voice_file_id'])) {
                $vf = VoiceFile::find($sq['voice_file_id']);
                if ($vf && $vf->user_id === $authUser->id) {
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
            'user_id' => $authUser->id,
            'client_id' => $authUser->id,
            'name' => $request->name,
            'description' => $request->description,
            'status' => $request->input('action') === 'draft' ? 'draft' : 'pending',
            'config' => !empty($questions) ? ['version' => 2, 'questions' => $questions] : null,
            'created_by' => $authUser->id,
        ]);

        $msg = $template->status === 'draft'
            ? "Survey template saved as draft."
            : "Survey template submitted for approval.";

        return redirect()->route('client.survey-templates.index')->with('success', $msg);
    }

    public function show(SurveyTemplate $surveyTemplate)
    {
        abort_unless($surveyTemplate->client_id === auth()->id(), 403);
        $surveyTemplate->load(['user', 'approvedBy']);

        return view('client.survey-templates.show', compact('surveyTemplate'));
    }

    public function edit(SurveyTemplate $surveyTemplate)
    {
        abort_unless($surveyTemplate->client_id === auth()->id(), 403);
        abort_unless(in_array($surveyTemplate->status, ['draft', 'pending']), 403, 'Only draft or pending templates can be edited.');

        return view('client.survey-templates.edit', compact('surveyTemplate'));
    }

    public function update(Request $request, SurveyTemplate $surveyTemplate)
    {
        abort_unless($surveyTemplate->client_id === auth()->id(), 403);
        abort_unless(in_array($surveyTemplate->status, ['draft', 'pending']), 403);

        $request->validate([
            'name' => 'required|string|max:150',
            'description' => 'nullable|string|max:500',
        ]);

        $questions = [];
        $qNum = 0;
        foreach ($request->input('survey_questions', []) as $sq) {
            $type = $sq['type'] ?? 'question';
            $key = $type === 'intro' ? 'intro' : 'q' . (++$qNum);

            $q = ['key' => $key, 'type' => $type, 'label' => $sq['label'] ?? ''];

            if (!empty($sq['voice_file_id'])) {
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

        $surveyTemplate->update([
            'name' => $request->name,
            'description' => $request->description,
            'config' => !empty($questions) ? ['version' => 2, 'questions' => $questions] : $surveyTemplate->config,
            'status' => $request->input('action') === 'draft' ? 'draft' : $surveyTemplate->status,
        ]);

        return redirect()->route('client.survey-templates.show', $surveyTemplate)->with('success', 'Survey template updated.');
    }

    public function uploadVoiceFile(Request $request)
    {
        $request->validate([
            'voice_file' => 'required|file|mimes:wav,mp3|max:10240',
            'label' => 'nullable|string|max:200',
        ]);

        $vf = app(VoiceFileService::class)->upload($request->file('voice_file'), auth()->user(), $request->input('label', 'Survey Audio'));

        return response()->json([
            'id' => $vf->id,
            'name' => $vf->name,
            'duration' => $vf->duration,
            'format' => $vf->format,
            'file_path_asterisk' => $vf->file_path_asterisk,
        ]);
    }
}
