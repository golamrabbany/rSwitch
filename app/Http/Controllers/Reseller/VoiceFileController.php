<?php

namespace App\Http\Controllers\Reseller;

use App\Http\Controllers\Controller;
use App\Models\VoiceFile;
use App\Services\VoiceFileService;
use Illuminate\Http\Request;

class VoiceFileController extends Controller
{
    public function index(Request $request)
    {
        $descendantIds = auth()->user()->descendantIds();

        $query = VoiceFile::with('user:id,name')
            ->whereIn('user_id', $descendantIds);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $voiceFiles = $query->orderByDesc('created_at')->paginate(20);

        return view('reseller.voice-files.index', compact('voiceFiles'));
    }

    public function show(VoiceFile $voiceFile)
    {
        $descendantIds = auth()->user()->descendantIds();
        abort_unless(in_array($voiceFile->user_id, $descendantIds), 403);

        $voiceFile->load('user', 'approver');

        return view('reseller.voice-files.show', compact('voiceFile'));
    }

    /**
     * Stream voice file for audio player.
     */
    public function play(VoiceFile $voiceFile, VoiceFileService $service)
    {
        $descendantIds = auth()->user()->descendantIds();
        abort_unless(in_array($voiceFile->user_id, $descendantIds), 403);

        $path = $service->getPlaybackPath($voiceFile);
        abort_unless(file_exists($path), 404);

        return response()->file($path, [
            'Content-Type' => $voiceFile->format === 'mp3' ? 'audio/mpeg' : 'audio/wav',
        ]);
    }
}
