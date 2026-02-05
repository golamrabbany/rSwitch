<?php

namespace App\Services\Agi;

use App\Models\CallRecord;
use Illuminate\Support\Facades\Log;

class CallEndHandler
{
    public function handle(AgiConnection $agi): void
    {
        $uuid = $agi->getVariable('CDR_UUID');

        if (!$uuid) {
            $agi->verbose("rSwitch CallEnd: No CDR_UUID", 1);
            return;
        }

        $cdr = CallRecord::where('uuid', $uuid)->first();

        if (!$cdr) {
            $agi->verbose("rSwitch CallEnd: CDR not found for {$uuid}", 1);
            Log::warning('AGI CallEnd: CDR not found', ['uuid' => $uuid]);
            return;
        }

        // Read call stats from Asterisk channel variables
        $dialStatus = $agi->getVariable('DIALSTATUS');
        $duration = (int) ($agi->getVariable('CALL_DURATION') ?: 0);
        $billsec = (int) ($agi->getVariable('CALL_BILLSEC') ?: 0);
        $hangupCause = $agi->getVariable('HANGUPCAUSE');
        $dstChannel = $agi->getVariable('DIALEDPEERNAME');

        // Calculate duration from call_start if Asterisk CDR not available
        if ($duration === 0 && $cdr->call_start) {
            $duration = (int) now()->diffInSeconds($cdr->call_start);
        }

        // Map DIALSTATUS to CDR disposition
        $disposition = match ($dialStatus) {
            'ANSWER' => 'ANSWERED',
            'BUSY' => 'BUSY',
            'NOANSWER', 'CANCEL' => 'NO ANSWER',
            'CONGESTION', 'CHANUNAVAIL' => 'FAILED',
            default => $dialStatus ?: 'FAILED',
        };

        $updateData = [
            'call_end' => now(),
            'duration' => $duration,
            'billsec' => $billsec,
            'disposition' => $disposition,
            'hangup_cause' => $hangupCause,
        ];

        if ($dstChannel) {
            $updateData['ast_dstchannel'] = $dstChannel;
        }

        // Unanswered or zero-billsec calls are unbillable
        // Answered calls stay 'in_progress' for billing:rate-calls to process
        if ($disposition !== 'ANSWERED' || $billsec <= 0) {
            $updateData['status'] = 'unbillable';
        }

        $cdr->update($updateData);

        $agi->verbose("rSwitch CallEnd: {$uuid} {$disposition} {$billsec}s", 2);

        Log::info('AGI call ended', [
            'uuid' => $uuid,
            'disposition' => $disposition,
            'duration' => $duration,
            'billsec' => $billsec,
        ]);
    }
}
