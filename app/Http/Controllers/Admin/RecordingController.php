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

        // Authorization: admins (super_admin, admin, recharge_admin) can access all
        // Clients and resellers can only access their own records
        $authUser = auth()->user();
        if ($authUser->isClient() || $authUser->isReseller()) {
            abort_unless($record->user_id === $authUser->id, 403);
        }

        // Check recording file exists
        $filePath = config('filesystems.disks.recordings.root') . '/' . $uuid . '.wav';
        abort_unless(file_exists($filePath), 404, 'Recording file not found.');

        return response()->file($filePath, [
            'Content-Type' => 'audio/wav',
        ]);
    }
}
