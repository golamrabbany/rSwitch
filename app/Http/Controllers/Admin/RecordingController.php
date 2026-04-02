<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CallRecord;
use Carbon\Carbon;

class RecordingController extends Controller
{
    public function play(string $uuid)
    {
        // Look up the call record — try current month first, then widen search
        $record = CallRecord::where('uuid', $uuid)
            ->whereBetween('call_start', [
                Carbon::now()->startOfMonth(),
                Carbon::now()->endOfMonth(),
            ])
            ->first();

        if (!$record) {
            // Fallback: search previous month
            $record = CallRecord::where('uuid', $uuid)
                ->whereBetween('call_start', [
                    Carbon::now()->subMonth()->startOfMonth(),
                    Carbon::now()->subMonth()->endOfMonth(),
                ])
                ->first();
        }

        abort_unless($record, 404, 'Call record not found.');

        // Admin only — resellers and clients cannot access recordings
        abort_unless(auth()->user()->isAnyAdmin(), 403);

        // Check recording file exists
        $filePath = config('filesystems.disks.recordings.root') . '/' . $uuid . '.wav';
        abort_unless(file_exists($filePath), 404, 'Recording file not found.');

        return response()->file($filePath, [
            'Content-Type' => 'audio/wav',
        ]);
    }
}
