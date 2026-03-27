<?php

namespace App\Http\Controllers\Reseller;

use App\Http\Controllers\Controller;
use App\Models\SurveyTemplate;
use Illuminate\Http\Request;

class SurveyTemplateController extends Controller
{
    public function index(Request $request)
    {
        $authUser = auth()->user();

        $query = SurveyTemplate::with(['user', 'client'])
            ->visibleTo($authUser);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $templates = $query->orderByDesc('created_at')->paginate(20);

        $baseQuery = SurveyTemplate::visibleTo($authUser);
        $stats = [
            'total' => (clone $baseQuery)->count(),
            'pending' => (clone $baseQuery)->where('status', 'pending')->count(),
            'approved' => (clone $baseQuery)->where('status', 'approved')->count(),
            'rejected' => (clone $baseQuery)->where('status', 'rejected')->count(),
        ];

        return view('reseller.survey-templates.index', compact('templates', 'stats'));
    }

    public function create()
    {
        return view('reseller.survey-templates.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:150',
            'description' => 'nullable|string|max:500',
        ]);

        $authUser = auth()->user();

        $template = SurveyTemplate::create([
            'user_id' => $authUser->id,
            'client_id' => $authUser->id,
            'name' => $request->name,
            'description' => $request->description,
            'status' => 'pending',
            'survey_config' => $request->survey_config ? json_decode($request->survey_config, true) : null,
            'created_by' => $authUser->id,
        ]);

        return redirect()->route('reseller.survey-templates.index')
            ->with('success', "Survey template \"{$template->name}\" created. Pending admin approval.");
    }

    public function show(SurveyTemplate $surveyTemplate)
    {
        $authUser = auth()->user();
        abort_unless($surveyTemplate->user_id === $authUser->id || in_array($surveyTemplate->client_id, $authUser->descendantIds()), 403);

        $surveyTemplate->load(['user', 'client', 'approvedBy']);

        return view('reseller.survey-templates.show', compact('surveyTemplate'));
    }
}
