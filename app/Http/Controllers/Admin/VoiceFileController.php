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
        $query = VoiceFile::with('user:id,name')->ownedBy(auth()->user())->where('status', '!=', 'draft');

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
            'total' => VoiceFile::ownedBy(auth()->user())->where('status', '!=', 'draft')->count(),
            'pending' => VoiceFile::ownedBy(auth()->user())->pending()->count(),
            'approved' => VoiceFile::ownedBy(auth()->user())->approved()->count(),
            'rejected' => VoiceFile::ownedBy(auth()->user())->where('status', 'rejected')->count(),
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
            'voice_file' => ['nullable', 'file', 'mimes:wav,mp3', 'max:10240'],
            'voice_file_id' => ['nullable', 'exists:voice_files,id'],
        ]);

        $client = User::findOrFail($request->user_id);
        abort_unless(auth()->user()->canManage($client), 403);

        if ($request->filled('voice_file_id')) {
            // Pre-uploaded via AJAX — just update ownership and name
            $voiceFile = VoiceFile::findOrFail($request->voice_file_id);
            $voiceFile->update([
                'user_id' => $client->id,
                'name' => $request->name,
            ]);
        } elseif ($request->hasFile('voice_file')) {
            // Traditional file upload
            $voiceFile = $service->upload(
                $request->file('voice_file'),
                $client,
                $request->name
            );
            if (auth()->user()->isSuperAdmin()) {
                $service->approve($voiceFile, auth()->user());
            }
        } else {
            return back()->withErrors(['voice_file' => 'Please upload an audio file.'])->withInput();
        }

        return redirect()->route('admin.voice-files.show', $voiceFile)
            ->with('success', 'Voice template uploaded' . ($voiceFile->status === 'approved' ? ' and approved.' : '. Pending approval.'));
    }

    public function show(VoiceFile $voiceFile)
    {
        $voiceFile->load('user', 'approver');

        return view('admin.voice-files.show', compact('voiceFile'));
    }

    public function edit(VoiceFile $voiceFile)
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);
        $voiceFile->load('user');
        return view('admin.voice-files.edit', compact('voiceFile'));
    }

    public function update(Request $request, VoiceFile $voiceFile, VoiceFileService $service)
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);

        $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'new_voice_file_id' => ['nullable', 'exists:voice_files,id'],
        ]);

        $voiceFile->update(['name' => $request->name]);

        // Replace with AJAX-uploaded file
        if ($request->filled('new_voice_file_id')) {
            $newVf = VoiceFile::find($request->new_voice_file_id);
            if ($newVf && $newVf->id !== $voiceFile->id) {
                // Delete old files
                \Illuminate\Support\Facades\Storage::disk('private')->delete($voiceFile->file_path);
                $oldAsteriskPath = config('services.asterisk.voice_path', '/var/spool/asterisk/voicebroadcast') . '/' . $voiceFile->file_path_asterisk . '.wav';
                if (file_exists($oldAsteriskPath)) {
                    @unlink($oldAsteriskPath);
                }

                // Transfer new file's paths to existing record
                $voiceFile->update([
                    'original_filename' => $newVf->original_filename,
                    'file_path' => $newVf->file_path,
                    'file_path_asterisk' => $newVf->file_path_asterisk,
                    'duration' => $newVf->duration,
                    'format' => $newVf->format,
                ]);

                // Delete the temp record (not the files — they're now owned by $voiceFile)
                $newVf->delete();
            }
        }

        return redirect()->route('admin.voice-files.show', $voiceFile)
            ->with('success', 'Voice template updated.');
    }

    public function approve(VoiceFile $voiceFile, VoiceFileService $service)
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);

        $service->approve($voiceFile, auth()->user());

        return back()->with('success', "Voice template '{$voiceFile->name}' approved.");
    }

    public function reject(Request $request, VoiceFile $voiceFile, VoiceFileService $service)
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);

        $request->validate([
            'rejection_reason' => ['required', 'string', 'max:500'],
        ]);

        $service->reject($voiceFile, auth()->user(), $request->rejection_reason);

        return back()->with('success', "Voice template '{$voiceFile->name}' rejected.");
    }

    public function suspend(VoiceFile $voiceFile)
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);

        $voiceFile->update(['status' => 'suspended']);

        return back()->with('success', "Voice template '{$voiceFile->name}' suspended.");
    }

    public function setPending(VoiceFile $voiceFile)
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);

        $voiceFile->update([
            'status' => 'pending',
            'approved_by' => null,
            'approved_at' => null,
            'rejection_reason' => null,
        ]);

        return back()->with('success', "Voice template '{$voiceFile->name}' set to pending.");
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

    public function destroy(VoiceFile $voiceFile, VoiceFileService $service)
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);
        abort_if($voiceFile->broadcasts()->exists(), 422, 'Cannot delete — this template is used in broadcasts.');

        $service->delete($voiceFile);

        return redirect()->route('admin.voice-files.index')->with('success', "Voice template '{$voiceFile->name}' deleted.");
    }
}
