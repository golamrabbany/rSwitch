<?php

namespace App\Services\Agi;

use App\Models\CallRecord;
use App\Models\Did;
use App\Models\RingGroup;
use App\Models\Trunk;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class InboundCallHandler
{
    public function handle(AgiConnection $agi): void
    {
        $channel = $agi->getChannel();
        $extension = $agi->getExtension();
        $callerId = $agi->getCallerId();
        $callerName = $agi->getCallerIdName();

        $agi->verbose("rSwitch Inbound: {$callerId} -> DID {$extension}", 2);

        // 1. Identify incoming trunk from PJSIP endpoint channel variable
        $trunkEndpoint = $agi->getVariable('TRUNK_ENDPOINT');
        $trunk = null;

        if ($trunkEndpoint) {
            // Endpoint name format: trunk-{direction}-{id}
            if (preg_match('/^trunk-(?:incoming|both|outgoing)-(\d+)$/', $trunkEndpoint, $m)) {
                $trunk = Trunk::find((int) $m[1]);
            }
        }

        // 2. Look up DID by called number
        $did = $this->findDid($extension);

        if (!$did) {
            $agi->verbose("rSwitch: No active DID for {$extension}", 1);
            $agi->setVariable('ROUTE_ACTION', 'REJECT');
            $agi->setVariable('ROUTE_REJECT_REASON', 'did_not_found');
            return;
        }

        // 3. Route to destination
        $dialString = '';
        $destinationDesc = '';
        $ringTimeout = null;

        switch ($did->destination_type) {
            case 'sip_account':
                $sipAccount = $did->destinationSipAccount;

                if (!$sipAccount || $sipAccount->status !== 'active') {
                    $agi->verbose("rSwitch: DID {$extension} destination SIP inactive", 1);
                    $agi->setVariable('ROUTE_ACTION', 'REJECT');
                    $agi->setVariable('ROUTE_REJECT_REASON', 'destination_unavailable');
                    return;
                }

                $dialString = "PJSIP/{$sipAccount->username}";
                $destinationDesc = "SIP:{$sipAccount->username}";
                break;

            case 'ring_group':
                $ringGroup = RingGroup::with('activeMembers')->find($did->destination_id);

                if (!$ringGroup || $ringGroup->status !== 'active') {
                    $agi->verbose("rSwitch: DID {$extension} ring group inactive or missing", 1);
                    $agi->setVariable('ROUTE_ACTION', 'REJECT');
                    $agi->setVariable('ROUTE_REJECT_REASON', 'destination_unavailable');
                    return;
                }

                $dialString = $ringGroup->buildDialString();

                if (empty($dialString)) {
                    $agi->verbose("rSwitch: Ring group {$ringGroup->name} has no active members", 1);
                    $agi->setVariable('ROUTE_ACTION', 'REJECT');
                    $agi->setVariable('ROUTE_REJECT_REASON', 'no_ring_group_members');
                    return;
                }

                $destinationDesc = "RG:{$ringGroup->name}";
                $ringTimeout = $ringGroup->ring_timeout;
                break;

            case 'external':
                if (!$did->destination_number) {
                    $agi->verbose("rSwitch: DID {$extension} has no external destination", 1);
                    $agi->setVariable('ROUTE_ACTION', 'REJECT');
                    $agi->setVariable('ROUTE_REJECT_REASON', 'no_destination');
                    return;
                }

                // Route external destination via the highest-priority outgoing trunk
                $outTrunk = Trunk::outgoing()->active()->healthy()
                    ->orderBy('outgoing_priority')
                    ->first();

                if (!$outTrunk) {
                    $agi->setVariable('ROUTE_ACTION', 'REJECT');
                    $agi->setVariable('ROUTE_REJECT_REASON', 'no_outgoing_trunk');
                    return;
                }

                $endpoint = "trunk-{$outTrunk->direction}-{$outTrunk->id}";
                $dialString = "PJSIP/{$did->destination_number}@{$endpoint}";
                $destinationDesc = "EXT:{$did->destination_number}";
                break;

            default:
                $agi->verbose("rSwitch: Unknown destination type {$did->destination_type}", 1);
                $agi->setVariable('ROUTE_ACTION', 'REJECT');
                $agi->setVariable('ROUTE_REJECT_REASON', 'invalid_destination_type');
                return;
        }

        // 4. Create CDR entry
        $uuid = Str::uuid()->toString();

        CallRecord::create([
            'uuid' => $uuid,
            'user_id' => $did->assigned_to_user_id,
            'reseller_id' => $did->assignedUser?->parent_id,
            'call_flow' => 'inbound',
            'caller' => $callerId,
            'callee' => $extension,
            'caller_id' => $callerName ?: $callerId,
            'incoming_trunk_id' => $trunk?->id,
            'did_id' => $did->id,
            'destination' => $destinationDesc,
            'call_start' => now(),
            'disposition' => 'NO ANSWER',
            'status' => 'in_progress',
            'ast_channel' => $channel,
            'ast_context' => $agi->getContext(),
        ]);

        // 5. Set channel variables
        $agi->setVariable('ROUTE_ACTION', 'DIAL');
        $agi->setVariable('ROUTE_DIAL_STRING', $dialString);
        $agi->setVariable('ROUTE_FAILOVER', '');
        $agi->setVariable('ROUTE_DIAL_TIMEOUT', (string) ($ringTimeout ?? 60));
        $agi->setVariable('CDR_UUID', $uuid);

        $agi->verbose("rSwitch: DID {$extension} -> {$dialString}", 2);

        Log::info('AGI inbound routed', [
            'uuid' => $uuid,
            'did' => $extension,
            'did_id' => $did->id,
            'destination' => $dialString,
            'trunk' => $trunk?->name,
        ]);
    }

    /**
     * Find a DID by number, trying multiple formats.
     */
    private function findDid(string $number): ?Did
    {
        $did = Did::where('number', $number)->where('status', 'active')->first();

        if ($did) {
            return $did;
        }

        // Try without non-digit characters
        $clean = preg_replace('/\D/', '', $number);

        return Did::where('status', 'active')
            ->where(function ($q) use ($number, $clean) {
                $q->where('number', $clean)
                  ->orWhere('number', '+' . $clean);
            })
            ->first();
    }
}
