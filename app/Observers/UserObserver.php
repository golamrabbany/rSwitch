<?php

namespace App\Observers;

use App\Models\User;
use App\Services\SipProvisioningService;
use Illuminate\Support\Facades\Log;

class UserObserver
{
    public function __construct(private SipProvisioningService $sip) {}

    /**
     * After a user record is updated: if their status flipped between
     * 'active' and a non-active state, sync that down to PJSIP for every
     * SIP they own. This guarantees a disabled/suspended user can no
     * longer REGISTER (and therefore can't receive calls), not just that
     * the AGI rejects them in-flight.
     *
     * - active → suspended/disabled : deprovision every SIP
     * - suspended/disabled → active : re-provision every active SIP
     * - status unchanged           : no-op
     */
    public function updated(User $user): void
    {
        if (!$user->wasChanged('status')) {
            return;
        }

        $newlyDisabled = $user->status !== 'active';
        $sips = $user->sipAccounts()->get();

        if ($sips->isEmpty()) {
            return;
        }

        foreach ($sips as $sip) {
            try {
                if ($newlyDisabled) {
                    $this->sip->deprovision($sip, skipReload: true);
                } else {
                    // Only re-provision SIPs that are themselves active.
                    if ($sip->status === 'active') {
                        $this->sip->provision($sip, skipReload: true);
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('UserObserver: SIP sync failed', [
                    'user_id' => $user->id,
                    'sip_id' => $sip->id,
                    'new_status' => $user->status,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Single AMI reload after the batch.
        try {
            $this->sip->reloadPjsip();
        } catch (\Throwable $e) {
            Log::warning('UserObserver: PJSIP reload failed after status change', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
