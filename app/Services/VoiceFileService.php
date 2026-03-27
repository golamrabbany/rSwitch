<?php

namespace App\Services;

use App\Models\VoiceFile;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class VoiceFileService
{
    /**
     * Upload and process a voice file.
     */
    public function upload(UploadedFile $file, User $user, string $name): VoiceFile
    {
        $format = strtolower($file->getClientOriginalExtension());
        $originalFilename = $file->getClientOriginalName();
        $uuid = Str::uuid();

        // Store original in private storage
        $storagePath = "voice-files/{$user->id}/{$uuid}.{$format}";
        $file->storeAs("voice-files/{$user->id}", "{$uuid}.{$format}", 'private');

        // Extract duration via ffprobe
        $duration = $this->extractDuration($file->getRealPath());

        // Convert to Asterisk-compatible format (8kHz mono 16-bit PCM WAV)
        $asteriskDir = $this->getAsteriskDir();
        $asteriskFilename = "vf_{$uuid}";
        $asteriskPath = "{$asteriskDir}/{$asteriskFilename}.wav";

        $this->convertForAsterisk($file->getRealPath(), $asteriskPath);

        return VoiceFile::create([
            'user_id' => $user->id,
            'name' => $name,
            'original_filename' => $originalFilename,
            'file_path' => $storagePath,
            'file_path_asterisk' => $asteriskFilename, // without extension — Asterisk adds it
            'duration' => $duration,
            'format' => $format,
            'status' => 'pending',
        ]);
    }

    /**
     * Approve a voice file (Super Admin only).
     */
    public function approve(VoiceFile $voiceFile, User $admin): void
    {
        $voiceFile->update([
            'status' => 'approved',
            'approved_by' => $admin->id,
            'approved_at' => now(),
            'rejection_reason' => null,
        ]);

        AuditService::logAction('voice_file.approved', $voiceFile, [
            'approved_by' => $admin->name,
        ]);
    }

    /**
     * Reject a voice file with reason.
     */
    public function reject(VoiceFile $voiceFile, User $admin, string $reason): void
    {
        $voiceFile->update([
            'status' => 'rejected',
            'approved_by' => $admin->id,
            'approved_at' => now(),
            'rejection_reason' => $reason,
        ]);

        AuditService::logAction('voice_file.rejected', $voiceFile, [
            'rejected_by' => $admin->name,
            'reason' => $reason,
        ]);
    }

    /**
     * Replace the audio file on an existing VoiceFile record.
     */
    public function replace(VoiceFile $voiceFile, UploadedFile $file): void
    {
        $format = strtolower($file->getClientOriginalExtension());
        $originalFilename = $file->getClientOriginalName();

        // Delete old files
        Storage::disk('private')->delete($voiceFile->file_path);
        $oldAsteriskPath = $this->getAsteriskDir() . '/' . $voiceFile->file_path_asterisk . '.wav';
        if (file_exists($oldAsteriskPath)) {
            @unlink($oldAsteriskPath);
        }

        // Store new original
        $uuid = Str::uuid();
        $storagePath = "voice-files/{$voiceFile->user_id}/{$uuid}.{$format}";
        $file->storeAs("voice-files/{$voiceFile->user_id}", "{$uuid}.{$format}", 'private');

        // Extract duration
        $duration = $this->extractDuration($file->getRealPath());

        // Convert for Asterisk
        $asteriskFilename = "vf_{$uuid}";
        $asteriskPath = $this->getAsteriskDir() . "/{$asteriskFilename}.wav";
        $this->convertForAsterisk($file->getRealPath(), $asteriskPath);

        $voiceFile->update([
            'original_filename' => $originalFilename,
            'file_path' => $storagePath,
            'file_path_asterisk' => $asteriskFilename,
            'duration' => $duration,
            'format' => $format,
        ]);
    }

    /**
     * Delete a voice file and its converted copy.
     */
    public function delete(VoiceFile $voiceFile): void
    {
        // Delete original from storage
        Storage::disk('private')->delete($voiceFile->file_path);

        // Delete Asterisk WAV
        $asteriskPath = $this->getAsteriskDir() . '/' . $voiceFile->file_path_asterisk . '.wav';
        if (file_exists($asteriskPath)) {
            @unlink($asteriskPath);
        }

        $voiceFile->delete();
    }

    /**
     * Get the playback URL for browser audio player.
     */
    public function getPlaybackPath(VoiceFile $voiceFile): string
    {
        return Storage::disk('private')->path($voiceFile->file_path);
    }

    /**
     * Extract audio duration in seconds using ffprobe.
     */
    private function extractDuration(string $filePath): ?int
    {
        $cmd = sprintf(
            'ffprobe -v quiet -show_entries format=duration -of csv=p=0 %s 2>/dev/null',
            escapeshellarg($filePath)
        );

        $output = trim(shell_exec($cmd) ?? '');

        if ($output && is_numeric($output)) {
            return (int) round((float) $output);
        }

        return null;
    }

    /**
     * Convert audio to Asterisk-compatible format: 8kHz, mono, 16-bit PCM WAV.
     */
    private function convertForAsterisk(string $inputPath, string $outputPath): bool
    {
        $dir = dirname($outputPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $cmd = sprintf(
            'ffmpeg -y -i %s -ar 8000 -ac 1 -acodec pcm_s16le -f wav %s 2>/dev/null',
            escapeshellarg($inputPath),
            escapeshellarg($outputPath)
        );

        exec($cmd, $output, $returnCode);

        if ($returnCode === 0 && file_exists($outputPath)) {
            // Set permissions for Asterisk
            @chmod($outputPath, 0644);
            @chown($outputPath, 'asterisk');
            return true;
        }

        return false;
    }

    /**
     * Get the Asterisk voicebroadcast directory.
     */
    private function getAsteriskDir(): string
    {
        return env('BROADCAST_VOICE_PATH', '/var/spool/asterisk/voicebroadcast');
    }
}
