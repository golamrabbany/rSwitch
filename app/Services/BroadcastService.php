<?php

namespace App\Services;

use App\Models\Broadcast;
use App\Models\BroadcastNumber;
use App\Models\DncNumber;
use App\Models\User;
use App\Models\VoiceFile;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Redis;

class BroadcastService
{
    /**
     * Create a broadcast with phone numbers.
     */
    public function create(array $data, User $creator): Broadcast
    {
        $voiceFile = VoiceFile::findOrFail($data['voice_file_id']);
        abort_unless($voiceFile->isApproved(), 422, 'Voice file must be approved before broadcasting.');

        // Parse phone numbers
        $numbers = [];
        if (($data['phone_list_type'] ?? 'manual') === 'csv' && isset($data['csv_file'])) {
            $numbers = $this->parseCsvNumbers($data['csv_file']);
        } elseif (!empty($data['phone_numbers'])) {
            $numbers = $this->parseManualNumbers($data['phone_numbers']);
        }

        abort_if(empty($numbers), 422, 'No valid phone numbers provided.');

        // Deduplicate
        $numbers = array_values(array_unique($numbers));

        // Remove DNC numbers
        $cleanNumbers = DncNumber::filterNumbers($numbers);
        $dncCount = count($numbers) - count($cleanNumbers);

        // Resolve voice file paths for survey v2 questions
        if (!empty($data['survey_config']) && ($data['survey_config']['version'] ?? 1) >= 2) {
            $questions = $data['survey_config']['questions'] ?? [];
            foreach ($questions as &$q) {
                if (!empty($q['voice_file_id'])) {
                    $vf = \App\Models\VoiceFile::find($q['voice_file_id']);
                    if ($vf && $vf->status === 'approved') {
                        $q['voice_file_path'] = $vf->file_path_asterisk;
                    }
                }
            }
            unset($q);
            $data['survey_config']['questions'] = $questions;
        }

        $broadcast = Broadcast::create([
            'user_id' => $data['user_id'],
            'sip_account_id' => $data['sip_account_id'],
            'voice_file_id' => $data['voice_file_id'],
            'type' => $data['type'] ?? 'simple',
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'status' => 'draft',
            'caller_id_name' => $data['caller_id_name'] ?? null,
            'caller_id_number' => $data['caller_id_number'] ?? null,
            'max_concurrent' => $data['max_concurrent'] ?? 5,
            'retry_attempts' => $data['retry_attempts'] ?? 1,
            'retry_delay' => $data['retry_delay'] ?? 300,
            'ring_timeout' => $data['ring_timeout'] ?? 30,
            'survey_config' => $data['survey_config'] ?? null,
            'phone_list_type' => $data['phone_list_type'] ?? 'manual',
            'total_numbers' => count($cleanNumbers),
            'created_by' => $creator->id,
        ]);

        // Bulk insert phone numbers
        $inserts = [];
        $now = now();
        foreach (array_chunk($cleanNumbers, 1000) as $chunk) {
            foreach ($chunk as $number) {
                $inserts[] = [
                    'broadcast_id' => $broadcast->id,
                    'phone_number' => $number,
                    'status' => 'pending',
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
            BroadcastNumber::insert($inserts);
            $inserts = [];
        }

        AuditService::logCreated($broadcast, 'broadcast.created');

        return $broadcast;
    }

    /**
     * Parse phone numbers from textarea (one per line).
     */
    public function parseManualNumbers(string $text): array
    {
        $lines = preg_split('/[\r\n]+/', trim($text));
        $numbers = [];
        foreach ($lines as $line) {
            $number = preg_replace('/[^0-9+]/', '', trim($line));
            if (strlen($number) >= 6 && strlen($number) <= 20) {
                $numbers[] = $number;
            }
        }
        return $numbers;
    }

    /**
     * Parse phone numbers from CSV file.
     */
    public function parseCsvNumbers(UploadedFile $file): array
    {
        $numbers = [];
        $handle = fopen($file->getRealPath(), 'r');
        if (!$handle) return $numbers;

        // Skip header row
        fgetcsv($handle);

        while (($row = fgetcsv($handle)) !== false) {
            $number = preg_replace('/[^0-9+]/', '', trim($row[0] ?? ''));
            if (strlen($number) >= 6 && strlen($number) <= 20) {
                $numbers[] = $number;
            }
        }
        fclose($handle);

        return $numbers;
    }

    /**
     * Start a broadcast — publish to Redis for Python Celery.
     */
    public function start(Broadcast $broadcast): void
    {
        abort_unless($broadcast->can_start, 422, 'Broadcast cannot be started in current state.');

        $broadcast->update(['status' => 'queued']);

        Redis::publish('rswitch:broadcast.start', json_encode([
            'broadcast_id' => $broadcast->id,
        ]));

        AuditService::logAction('broadcast.started', $broadcast);
    }

    /**
     * Pause a running broadcast.
     */
    public function pause(Broadcast $broadcast): void
    {
        abort_unless($broadcast->can_pause, 422, 'Broadcast cannot be paused in current state.');

        Redis::set("rswitch:broadcast:{$broadcast->id}:control", 'pause');
        $broadcast->update(['status' => 'paused']);

        AuditService::logAction('broadcast.paused', $broadcast);
    }

    /**
     * Resume a paused broadcast.
     */
    public function resume(Broadcast $broadcast): void
    {
        abort_unless($broadcast->can_resume, 422, 'Broadcast cannot be resumed in current state.');

        Redis::del("rswitch:broadcast:{$broadcast->id}:control");
        $broadcast->update(['status' => 'queued']);

        Redis::publish('rswitch:broadcast.start', json_encode([
            'broadcast_id' => $broadcast->id,
        ]));

        AuditService::logAction('broadcast.resumed', $broadcast);
    }

    /**
     * Cancel a broadcast.
     */
    public function cancel(Broadcast $broadcast): void
    {
        abort_unless($broadcast->can_cancel, 422, 'Broadcast cannot be cancelled in current state.');

        Redis::set("rswitch:broadcast:{$broadcast->id}:control", 'cancel');

        // Mark remaining pending numbers as cancelled (using failed status)
        BroadcastNumber::where('broadcast_id', $broadcast->id)
            ->where('status', 'pending')
            ->update(['status' => 'failed', 'error_reason' => 'Broadcast cancelled']);

        $broadcast->update([
            'status' => 'cancelled',
            'completed_at' => now(),
            'failed_count' => BroadcastNumber::where('broadcast_id', $broadcast->id)
                ->where('status', 'failed')->count(),
        ]);

        AuditService::logAction('broadcast.cancelled', $broadcast);
    }
}
