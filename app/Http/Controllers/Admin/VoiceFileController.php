<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\VoiceFile;
use App\Services\VoiceFileService;
use Illuminate\Http\Request;

class VoiceFileController extends Controller
{
    public function index(Request $request)
    {
        $query = VoiceFile::with('user:id,name')->ownedBy(auth()->user());

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhereHas('user', fn ($q) => $q->where('name', 'like', "%{$search}%"));
            });
        }

        $voiceFiles = $query->orderByDesc('created_at')->paginate(20);

        $stats = [
            'pending' => VoiceFile::ownedBy(auth()->user())->pending()->count(),
            'approved' => VoiceFile::ownedBy(auth()->user())->approved()->count(),
            'total' => VoiceFile::ownedBy(auth()->user())->count(),
        ];

        return view('admin.voice-files.index', compact('voiceFiles', 'stats'));
    }

    public function create()
    {
        $clients = User::where('role', 'client')->orderBy('name')->get(['id', 'name', 'email']);

        return view('admin.voice-files.create', compact('clients'));
    }

    public function store(Request $request, VoiceFileService $service)
    {
        $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'name' => ['required', 'string', 'max:100'],
            'voice_file' => ['required', 'file', 'mimes:wav,mp3', 'max:10240'],
        ]);

        $client = User::findOrFail($request->user_id);
        abort_unless(auth()->user()->canManage($client), 403);

        $voiceFile = $service->upload(
            $request->file('voice_file'),
            $client,
            $request->name
        );

        // Super Admin upload = auto-approved
        if (auth()->user()->isSuperAdmin()) {
            $service->approve($voiceFile, auth()->user());
        }

        return redirect()->route('admin.voice-files.show', $voiceFile)
            ->with('success', 'Voice file uploaded' . (auth()->user()->isSuperAdmin() ? ' and auto-approved.' : '. Pending approval.'));
    }

    public function show(VoiceFile $voiceFile)
    {
        $voiceFile->load('user', 'approver');

        return view('admin.voice-files.show', compact('voiceFile'));
    }

    public function approve(VoiceFile $voiceFile, VoiceFileService $service)
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403, 'Only Super Admin can approve voice files.');

        if (!$voiceFile->isPending()) {
            return back()->with('warning', 'Only pending voice files can be approved.');
        }

        $service->approve($voiceFile, auth()->user());

        return back()->with('success', "Voice file '{$voiceFile->name}' approved.");
    }

    public function reject(Request $request, VoiceFile $voiceFile, VoiceFileService $service)
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403, 'Only Super Admin can reject voice files.');

        $request->validate([
            'rejection_reason' => ['required', 'string', 'max:500'],
        ]);

        $service->reject($voiceFile, auth()->user(), $request->rejection_reason);

        return back()->with('success', "Voice file '{$voiceFile->name}' rejected.");
    }

    public function download(VoiceFile $voiceFile, VoiceFileService $service)
    {
        $path = $service->getPlaybackPath($voiceFile);
        abort_unless(file_exists($path), 404);

        return response()->download($path, $voiceFile->original_filename);
    }

    /**
     * Stream voice file for audio player.
     */
    public function play(VoiceFile $voiceFile, VoiceFileService $service)
    {
        $path = $service->getPlaybackPath($voiceFile);
        abort_unless(file_exists($path), 404);

        return response()->file($path, [
            'Content-Type' => $voiceFile->format === 'mp3' ? 'audio/mpeg' : 'audio/wav',
        ]);
    }
}
