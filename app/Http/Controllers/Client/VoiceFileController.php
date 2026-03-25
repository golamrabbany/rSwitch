<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\VoiceFile;
use App\Services\VoiceFileService;
use Illuminate\Http\Request;

class VoiceFileController extends Controller
{
    public function index()
    {
        $voiceFiles = VoiceFile::where('user_id', auth()->id())
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('client.voice-files.index', compact('voiceFiles'));
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

        return redirect()->route('client.voice-files.show', $voiceFile)
            ->with('success', 'Voice file uploaded. Pending admin approval.');
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
