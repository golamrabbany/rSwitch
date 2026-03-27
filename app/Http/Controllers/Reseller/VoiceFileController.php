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
        $authUser = auth()->user();
        $descendantIds = $authUser->descendantIds();

        $query = VoiceFile::with('user:id,name')
            ->whereIn('user_id', $descendantIds);

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

        $baseQuery = VoiceFile::whereIn('user_id', $descendantIds);
        $stats = [
            'total' => (clone $baseQuery)->count(),
            'pending' => (clone $baseQuery)->where('status', 'pending')->count(),
            'approved' => (clone $baseQuery)->where('status', 'approved')->count(),
            'rejected' => (clone $baseQuery)->where('status', 'rejected')->count(),
        ];

        return view('reseller.voice-files.index', compact('voiceFiles', 'stats'));
    }

    public function create()
    {
        return view('reseller.voice-files.create');
    }

    public function store(Request $request, VoiceFileService $service)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'voice_file' => ['required', 'file', 'mimes:wav,mp3', 'max:10240'],
        ]);

        $authUser = auth()->user();

        $voiceFile = $service->upload(
            $request->file('voice_file'),
            $authUser,
            $request->name
        );

        // Reseller uploads are always pending approval
        $voiceFile->update(['status' => 'pending']);

        return redirect()->route('reseller.voice-files.index')
            ->with('success', 'Voice template uploaded. Pending admin approval.');
    }

    public function show(VoiceFile $voiceFile)
    {
        $descendantIds = auth()->user()->descendantIds();
        abort_unless(in_array($voiceFile->user_id, $descendantIds), 403);

        $voiceFile->load('user', 'approver');

        return view('reseller.voice-files.show', compact('voiceFile'));
    }

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
