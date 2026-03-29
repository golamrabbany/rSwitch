<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\VoiceFile;
use App\Services\VoiceFileService;
use Illuminate\Http\Request;

class VoiceFileController extends Controller
{
    public function index(Request $request)
    {
        $query = VoiceFile::where('user_id', auth()->id());

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $voiceFiles = $query->orderByDesc('created_at')->paginate(20);

        $baseQuery = VoiceFile::where('user_id', auth()->id());
        $stats = [
            'total' => (clone $baseQuery)->count(),
            'draft' => (clone $baseQuery)->where('status', 'draft')->count(),
            'pending' => (clone $baseQuery)->where('status', 'pending')->count(),
            'approved' => (clone $baseQuery)->where('status', 'approved')->count(),
            'rejected' => (clone $baseQuery)->where('status', 'rejected')->count(),
        ];

        return view('client.voice-files.index', compact('voiceFiles', 'stats'));
    }

    public function create()
    {
        return view('client.voice-files.create');
    }

    public function store(Request $request, VoiceFileService $service)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'voice_file' => ['required', 'file', 'mimes:wav,mp3', 'max:10240'],
        ]);

        $voiceFile = $service->upload(
            $request->file('voice_file'),
            auth()->user(),
            $request->name
        );

        $status = $request->input('action') === 'draft' ? 'draft' : 'pending';
        $voiceFile->update(['status' => $status]);

        $msg = $status === 'draft'
            ? 'Voice template saved as draft.'
            : 'Voice template uploaded. Pending admin approval.';

        return redirect()->route('client.voice-files.index')->with('success', $msg);
    }

    public function show(VoiceFile $voiceFile)
    {
        abort_unless($voiceFile->user_id === auth()->id(), 403);

        return view('client.voice-files.show', compact('voiceFile'));
    }

    public function destroy(VoiceFile $voiceFile, VoiceFileService $service)
    {
        abort_unless($voiceFile->user_id === auth()->id(), 403);

        if ($voiceFile->broadcasts()->exists()) {
            return back()->with('warning', 'Cannot delete: this voice file is used in broadcasts.');
        }

        $service->delete($voiceFile);

        return redirect()->route('client.voice-files.index')
            ->with('success', 'Voice file deleted.');
    }

    /**
     * Stream voice file for audio player.
     */
    public function play(VoiceFile $voiceFile, VoiceFileService $service)
    {
        abort_unless($voiceFile->user_id === auth()->id(), 403);

        $path = $service->getPlaybackPath($voiceFile);
        abort_unless(file_exists($path), 404);

        return response()->file($path, [
            'Content-Type' => $voiceFile->format === 'mp3' ? 'audio/mpeg' : 'audio/wav',
        ]);
    }
}
