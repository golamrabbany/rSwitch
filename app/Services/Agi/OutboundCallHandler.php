<?php

namespace App\Services\Agi;

use App\Models\CallRecord;
use App\Models\DestinationBlacklist;
use App\Models\DestinationWhitelist;
use App\Models\SipAccount;
use App\Models\Trunk;
use App\Services\BalanceService;
use App\Services\RatingService;
use App\Services\RouteSelectionService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class OutboundCallHandler
{
    public function __construct(
        private RouteSelectionService $routeService,
        private RatingService $ratingService,
        private BalanceService $balanceService,
    ) {}

    public function handle(AgiConnection $agi): void
    {
        $channel = $agi->getChannel();
        $extension = $agi->getExtension();
        $callerId = $agi->getCallerId();
        $callerName = $agi->getCallerIdName();

        $agi->verbose("rSwitch Outbound: {$callerId} -> {$extension}", 2);

        // 1. Identify SIP account from channel name (PJSIP/username-uniqueid)
        $sipUsername = $this->extractSipUsername($channel);

        if (!$sipUsername) {
            $agi->verbose("rSwitch: Cannot identify SIP account from {$channel}", 1);
            $this->reject($agi, 'unknown_account');
            return;
        }

        $sipAccount = SipAccount::where('username', $sipUsername)->first();

        if (!$sipAccount || $sipAccount->status !== 'active') {
            $agi->verbose("rSwitch: SIP {$sipUsername} not found or inactive", 1);
            $this->reject($agi, 'account_inactive');
            return;
        }

        $user = $sipAccount->user;

        if (!$user || $user->status !== 'active') {
            $agi->verbose("rSwitch: User for SIP {$sipUsername} inactive", 1);
            $this->reject($agi, 'user_inactive');
            return;
        }

        // 2. Blacklist check
        if ($this->isBlacklisted($extension, $user->id)) {
            $agi->verbose("rSwitch: {$extension} blacklisted for user {$user->id}", 1);
            $this->reject($agi, 'blacklisted');
            return;
        }

        // 3. Whitelist check (if enabled)
        if ($user->destination_whitelist_enabled && !$this->isWhitelisted($extension, $user->id)) {
            $agi->verbose("rSwitch: {$extension} not whitelisted for user {$user->id}", 1);
            $this->reject($agi, 'not_whitelisted');
            return;
        }

        // 4. Rate lookup (estimate cost for 3 minutes to check balance)
        $rate = null;
        $estimatedCost = '0.0000';

        if ($user->rate_group_id) {
            $rate = $this->ratingService->findRate($extension, $user->rate_group_id);

            if ($rate) {
                $estimated = $this->ratingService->calculateCost(180, $rate);
                $estimatedCost = $estimated['total_cost'];
            }
        }

        // 5. Balance check
        if (!$this->balanceService->canAffordCall($user, $estimatedCost)) {
            $agi->verbose("rSwitch: Insufficient balance for user {$user->id}", 1);
            $this->reject($agi, 'no_balance');
            return;
        }

        // 6. Trunk selection via RouteSelectionService
        $routing = $this->routeService->selectTrunks($extension);
        $primaryRoute = $routing['primary'];

        if (!$primaryRoute) {
            $agi->verbose("rSwitch: No route found for {$extension}", 1);
            $this->reject($agi, 'no_route');
            return;
        }

        $trunk = $primaryRoute->trunk;
        $failoverRoute = $routing['failover'];

        // 7. Apply dial manipulation
        $dialNumber = $this->applyDialManipulation($extension, $trunk);
        $dialString = $this->buildDialString($dialNumber, $trunk);

        // 8. Build failover dial string
        $failoverDialString = '';

        if ($failoverRoute) {
            $failoverTrunk = $failoverRoute->trunk;
            $failoverNumber = $this->applyDialManipulation($extension, $failoverTrunk);
            $failoverDialString = $this->buildDialString($failoverNumber, $failoverTrunk);
        }

        // 9. Apply CLI manipulation
        [$cliName, $cliNum] = $this->applyCliManipulation($callerName, $callerId, $sipAccount, $trunk);

        // 10. Create CDR entry
        $uuid = Str::uuid()->toString();

        CallRecord::create([
            'uuid' => $uuid,
            'sip_account_id' => $sipAccount->id,
            'user_id' => $user->id,
            'reseller_id' => $user->parent_id,
            'call_flow' => 'outbound',
            'caller' => $callerId,
            'callee' => $extension,
            'caller_id' => $cliNum,
            'destination' => $dialNumber,
            'outgoing_trunk_id' => $trunk->id,
            'call_start' => now(),
            'disposition' => 'NO ANSWER',
            'status' => 'in_progress',
            'ast_channel' => $channel,
            'ast_context' => $agi->getContext(),
        ]);

        // 11. Set channel variables for the dialplan
        $agi->setVariable('ROUTE_ACTION', 'DIAL');
        $agi->setVariable('ROUTE_DIAL_STRING', $dialString);
        $agi->setVariable('ROUTE_FAILOVER', $failoverDialString);
        $agi->setVariable('ROUTE_DIAL_TIMEOUT', '60');
        $agi->setVariable('ROUTE_CLI_NAME', $cliName);
        $agi->setVariable('ROUTE_CLI_NUM', $cliNum);
        $agi->setVariable('CDR_UUID', $uuid);

        $agi->verbose("rSwitch: Route {$extension} via {$trunk->name} -> {$dialString}", 2);

        Log::info('AGI outbound routed', [
            'uuid' => $uuid,
            'user' => $user->id,
            'callee' => $extension,
            'trunk' => $trunk->name,
            'dial' => $dialString,
        ]);
    }

    private function reject(AgiConnection $agi, string $reason): void
    {
        $agi->setVariable('ROUTE_ACTION', 'REJECT');
        $agi->setVariable('ROUTE_REJECT_REASON', $reason);
    }

    /**
     * Extract SIP username from PJSIP channel name.
     * "PJSIP/1001-00000001" -> "1001"
     */
    private function extractSipUsername(string $channel): ?string
    {
        if (preg_match('/^PJSIP\/([^-]+)/', $channel, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Check if destination matches any blacklist prefix for this user.
     */
    private function isBlacklisted(string $destination, int $userId): bool
    {
        $prefixes = $this->generatePrefixes($destination);

        if (empty($prefixes)) {
            return false;
        }

        return DestinationBlacklist::whereIn('prefix', $prefixes)
            ->where(function ($q) use ($userId) {
                $q->where('applies_to', 'all')
                  ->orWhere(function ($q2) use ($userId) {
                      $q2->where('applies_to', 'specific_users')
                         ->where('user_id', $userId);
                  });
            })
            ->exists();
    }

    /**
     * Check if destination matches any whitelist prefix for this user.
     */
    private function isWhitelisted(string $destination, int $userId): bool
    {
        $prefixes = $this->generatePrefixes($destination);

        if (empty($prefixes)) {
            return false;
        }

        return DestinationWhitelist::where('user_id', $userId)
            ->whereIn('prefix', $prefixes)
            ->exists();
    }

    /**
     * Generate all possible prefixes from a number (longest to shortest).
     */
    private function generatePrefixes(string $number): array
    {
        $clean = preg_replace('/\D/', '', $number);

        if (empty($clean)) {
            return [];
        }

        $prefixes = [];
        $len = min(strlen($clean), 20);

        for ($i = $len; $i >= 1; $i--) {
            $prefixes[] = substr($clean, 0, $i);
        }

        return $prefixes;
    }

    /**
     * Apply trunk dial manipulation rules to the destination number.
     */
    private function applyDialManipulation(string $number, Trunk $trunk): string
    {
        $result = $number;

        // Pattern-based replacement
        if ($trunk->dial_pattern_match && $trunk->dial_pattern_replace !== null) {
            $replaced = @preg_replace(
                '/' . $trunk->dial_pattern_match . '/',
                $trunk->dial_pattern_replace,
                $result
            );

            if ($replaced !== null) {
                $result = $replaced;
            }
        }

        // Strip leading digits
        if ($trunk->dial_strip_digits > 0) {
            $stripped = substr($result, (int) $trunk->dial_strip_digits);
            $result = $stripped ?: $result;
        }

        // Add prefix
        if ($trunk->dial_prefix) {
            $result = $trunk->dial_prefix . $result;
        }

        // Tech prefix
        if ($trunk->tech_prefix) {
            $result = $trunk->tech_prefix . $result;
        }

        return $result;
    }

    /**
     * Build PJSIP dial string for a trunk.
     * Endpoint name matches TrunkProvisioningService: trunk-{direction}-{id}
     */
    private function buildDialString(string $number, Trunk $trunk): string
    {
        $endpoint = "trunk-{$trunk->direction}-{$trunk->id}";

        return "PJSIP/{$number}@{$endpoint}";
    }

    /**
     * Apply caller ID manipulation based on trunk CLI settings.
     */
    private function applyCliManipulation(
        string $callerName,
        string $callerNum,
        SipAccount $sipAccount,
        Trunk $trunk,
    ): array {
        // Start with SIP account's configured caller ID
        $cliName = $sipAccount->caller_id_name ?: $callerName;
        $cliNum = $sipAccount->caller_id_number ?: $callerNum;

        switch ($trunk->cli_mode) {
            case 'passthrough':
                break;

            case 'override':
                $cliNum = $trunk->cli_override_number ?: $cliNum;
                break;

            case 'prefix_manipulation':
                if ($trunk->cli_prefix_strip && str_starts_with($cliNum, $trunk->cli_prefix_strip)) {
                    $cliNum = substr($cliNum, strlen($trunk->cli_prefix_strip));
                }

                if ($trunk->cli_prefix_add) {
                    $cliNum = $trunk->cli_prefix_add . $cliNum;
                }
                break;
        }

        return [$cliName, $cliNum];
    }
}
