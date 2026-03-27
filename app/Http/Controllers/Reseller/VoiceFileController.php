<?php

namespace App\Http\Controllers\Reseller;

use App\Http\Controllers\Controller;
use App\Models\User;
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
        $clients = User::whereIn('id', auth()->user()->descendantIds())
            ->where('role', 'client')
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        return view('reseller.voice-files.create', compact('clients'));
    }

    public function store(Request $request, VoiceFileService $service)
    {
        $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'name' => ['required', 'string', 'max:100'],
            'voice_file' => ['required', 'file', 'mimes:wav,mp3', 'max:10240'],
        ]);

        $authUser = auth()->user();
        $client = User::findOrFail($request->user_id);
        abort_unless(in_array((int) $client->id, $authUser->descendantIds()), 403);

        $voiceFile = $service->upload(
            $request->file('voice_file'),
            $client,
            $request->name
        );

        // Reseller uploads are always pending approval
        $voiceFile->update(['status' => 'pending']);

        return redirect()->route('reseller.voice-files.index')
            ->with('success', 'Voice template uploaded for ' . $client->name . '. Pending admin approval.');
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
