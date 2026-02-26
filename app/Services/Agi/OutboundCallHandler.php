<?php

namespace App\Services\Agi;

use App\Models\CallRecord;
use App\Models\DestinationBlacklist;
use App\Models\DestinationWhitelist;
use App\Models\RingGroup;
use App\Models\SipAccount;
use App\Models\Trunk;
use App\Models\User;
use App\Services\BalanceService;
use App\Services\RatingService;
use App\Services\RouteSelectionService;
use Illuminate\Support\Facades\DB;
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

        // 5a. Daily spend limit
        if ($user->daily_spend_limit > 0) {
            $todaySpend = $this->getTodaySpend($user->id);
            if (bccomp($todaySpend, (string) $user->daily_spend_limit, 4) >= 0) {
                $agi->verbose("rSwitch: Daily spend limit reached for user {$user->id} ({$todaySpend})", 1);
                $this->reject($agi, 'daily_spend_limit');
                return;
            }
        }

        // 5b. Daily call limit
        if ($user->daily_call_limit > 0) {
            $todayCalls = $this->getTodayCallCount($user->id);
            if ($todayCalls >= $user->daily_call_limit) {
                $agi->verbose("rSwitch: Daily call limit reached for user {$user->id} ({$todayCalls})", 1);
                $this->reject($agi, 'daily_call_limit');
                return;
            }
        }

        // 5c. Max concurrent channels
        if ($user->max_channels > 0) {
            $activeCalls = $this->getActiveChannelCount($user->id);
            if ($activeCalls >= $user->max_channels) {
                $agi->verbose("rSwitch: Max channels reached for user {$user->id} ({$activeCalls}/{$user->max_channels})", 1);
                $this->reject($agi, 'max_channels');
                return;
            }
        }

        // 6. Check for internal SIP-to-SIP or Ring Group call
        $internalResult = $this->handleInternalCall($agi, $extension, $sipAccount, $user, $channel, $callerId, $callerName);
        if ($internalResult !== false) {
            return; // Internal call handled
        }

        // 7. Trunk selection via RouteSelectionService (external calls)
        $routing = $this->routeService->selectTrunks($extension);
        $primaryRoute = $routing['primary'];

        if (!$primaryRoute) {
            $agi->verbose("rSwitch: No route found for {$extension}", 1);
            $this->reject($agi, 'no_route');
            return;
        }

        $trunk = $primaryRoute->trunk;
        $failoverRoute = $routing['failover'];

        // 8. Apply route-level dial prefix manipulation (remove/add prefix)
        $routeNumber = $primaryRoute->applyDialPrefixManipulation($extension);
        if ($routeNumber !== $extension) {
            $agi->verbose("rSwitch: Route dial manipulation {$extension} -> {$routeNumber}", 2);
        }

        // 9. Apply MNP transformation if enabled on route
        $mnpNumber = $primaryRoute->applyMnpTransformation($routeNumber);
        if ($mnpNumber !== $routeNumber) {
            $agi->verbose("rSwitch: MNP transformation {$routeNumber} -> {$mnpNumber}", 2);
        }

        // 10. Apply trunk dial manipulation
        $dialNumber = $this->applyDialManipulation($mnpNumber, $trunk);
        $dialString = $this->buildDialString($dialNumber, $trunk);

        // 11. Build failover dial string
        $failoverDialString = '';

        if ($failoverRoute) {
            $failoverTrunk = $failoverRoute->trunk;
            $failoverRouteNum = $failoverRoute->applyDialPrefixManipulation($extension);
            $failoverMnp = $failoverRoute->applyMnpTransformation($failoverRouteNum);
            $failoverNumber = $this->applyDialManipulation($failoverMnp, $failoverTrunk);
            $failoverDialString = $this->buildDialString($failoverNumber, $failoverTrunk);
        }

        // 12. Apply CLI manipulation
        [$cliName, $cliNum] = $this->applyCliManipulation($callerName, $callerId, $sipAccount, $trunk);

        // 13. Create CDR entry
        $uuid = Str::uuid()->toString();

        CallRecord::create([
            'uuid' => $uuid,
            'sip_account_id' => $sipAccount->id,
            'user_id' => $user->id,
            'reseller_id' => $user->parent_id,
            'call_flow' => 'sip_to_trunk',
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

        // 13. Set channel variables for the dialplan
        $agi->setVariable('ROUTE_ACTION', 'DIAL');
        $agi->setVariable('ROUTE_DIAL_STRING', $dialString);
        $agi->setVariable('ROUTE_FAILOVER', $failoverDialString);
        $agi->setVariable('ROUTE_DIAL_TIMEOUT', '60');
        $agi->setVariable('ROUTE_CLI_NAME', $cliName);
        $agi->setVariable('ROUTE_CLI_NUM', $cliNum);
        $agi->setVariable('CDR_UUID', $uuid);
        $agi->setVariable('RECORD_CALL', $sipAccount->allow_recording ? '1' : '0');

        $agi->verbose("rSwitch: Route {$extension} via {$trunk->name} -> {$dialString}", 2);

        Log::info('AGI outbound routed', [
            'uuid' => $uuid,
            'user' => $user->id,
            'callee' => $extension,
            'trunk' => $trunk->name,
            'dial' => $dialString,
        ]);
    }

    /**
     * Handle internal SIP-to-SIP or Ring Group calls.
     * Returns true if handled, false if should continue to trunk routing.
     */
    private function handleInternalCall(
        AgiConnection $agi,
        string $extension,
        SipAccount $callerSip,
        User $callerUser,
        string $channel,
        string $callerId,
        string $callerName,
    ): bool {
        // Check if destination is a SIP account username
        $destinationSip = SipAccount::where('username', $extension)
            ->where('status', 'active')
            ->first();

        if ($destinationSip) {
            // Check if caller has P2P calls enabled
            if (!$callerSip->allow_p2p) {
                $agi->verbose("rSwitch: P2P calls disabled for SIP {$callerSip->username}", 1);
                $this->reject($agi, 'p2p_disabled');
                return true;
            }

            return $this->routeToSipAccount($agi, $destinationSip, $callerSip, $callerUser, $channel, $extension, $callerId, $callerName);
        }

        // Check if destination is a Ring Group (by name or ID pattern like RG100)
        $ringGroup = $this->findRingGroup($extension, $callerUser);

        if ($ringGroup) {
            return $this->routeToRingGroup($agi, $ringGroup, $callerSip, $callerUser, $channel, $extension, $callerId, $callerName);
        }

        // Not an internal call
        return false;
    }

    /**
     * Route call to an internal SIP account.
     */
    private function routeToSipAccount(
        AgiConnection $agi,
        SipAccount $destinationSip,
        SipAccount $callerSip,
        User $callerUser,
        string $channel,
        string $extension,
        string $callerId,
        string $callerName,
    ): bool {
        $destinationUser = $destinationSip->user;

        // Check if destination user is active
        if (!$destinationUser || $destinationUser->status !== 'active') {
            $agi->verbose("rSwitch: Destination user for SIP {$extension} is inactive", 1);
            $this->reject($agi, 'destination_inactive');
            return true;
        }

        // Check if caller can call this destination (same owner or admin/reseller access)
        if (!$this->canCallInternal($callerUser, $destinationUser)) {
            $agi->verbose("rSwitch: User {$callerUser->id} cannot call internal {$destinationUser->id}", 1);
            $this->reject($agi, 'internal_not_permitted');
            return true;
        }

        // Build dial string for internal SIP account
        $dialString = "PJSIP/{$destinationSip->username}";

        // Create CDR entry for internal call
        $uuid = Str::uuid()->toString();

        CallRecord::create([
            'uuid' => $uuid,
            'sip_account_id' => $callerSip->id,
            'user_id' => $callerUser->id,
            'reseller_id' => $callerUser->parent_id,
            'call_flow' => 'sip_to_sip',
            'caller' => $callerId,
            'callee' => $extension,
            'caller_id' => $callerSip->caller_id_number ?: $callerId,
            'destination' => $extension,
            'destination_sip_account_id' => $destinationSip->id,
            'call_start' => now(),
            'disposition' => 'NO ANSWER',
            'status' => 'in_progress',
            'ast_channel' => $channel,
            'ast_context' => $agi->getContext(),
            // Internal calls are typically free (no rates applied)
            'rate_per_minute' => '0.0000',
            'total_cost' => '0.0000',
        ]);

        // Set channel variables
        $agi->setVariable('ROUTE_ACTION', 'DIAL_INTERNAL');
        $agi->setVariable('ROUTE_DIAL_STRING', $dialString);
        $agi->setVariable('ROUTE_DIAL_TIMEOUT', '30');
        $agi->setVariable('ROUTE_CLI_NAME', $callerSip->caller_id_name ?: $callerName);
        $agi->setVariable('ROUTE_CLI_NUM', $callerSip->caller_id_number ?: $callerId);
        $agi->setVariable('CDR_UUID', $uuid);
        $agi->setVariable('RECORD_CALL', $callerSip->allow_recording ? '1' : '0');

        $agi->verbose("rSwitch: Internal call to SIP/{$extension} -> {$dialString}", 2);

        Log::info('AGI internal SIP-to-SIP routed', [
            'uuid' => $uuid,
            'caller_user' => $callerUser->id,
            'destination_user' => $destinationUser->id,
            'callee' => $extension,
            'dial' => $dialString,
        ]);

        return true;
    }

    /**
     * Route call to a Ring Group.
     */
    private function routeToRingGroup(
        AgiConnection $agi,
        RingGroup $ringGroup,
        SipAccount $callerSip,
        User $callerUser,
        string $channel,
        string $extension,
        string $callerId,
        string $callerName,
    ): bool {
        // Check if ring group has active members
        $dialString = $ringGroup->buildDialString();

        if (empty($dialString)) {
            $agi->verbose("rSwitch: Ring group {$ringGroup->name} has no active members", 1);
            $this->reject($agi, 'ring_group_empty');
            return true;
        }

        // Create CDR entry for ring group call
        $uuid = Str::uuid()->toString();

        CallRecord::create([
            'uuid' => $uuid,
            'sip_account_id' => $callerSip->id,
            'user_id' => $callerUser->id,
            'reseller_id' => $callerUser->parent_id,
            'call_flow' => 'sip_to_sip',
            'caller' => $callerId,
            'callee' => $extension,
            'caller_id' => $callerSip->caller_id_number ?: $callerId,
            'destination' => "RG:{$ringGroup->name}",
            'call_start' => now(),
            'disposition' => 'NO ANSWER',
            'status' => 'in_progress',
            'ast_channel' => $channel,
            'ast_context' => $agi->getContext(),
            'rate_per_minute' => '0.0000',
            'total_cost' => '0.0000',
        ]);

        // Determine dial timeout based on strategy
        $dialTimeout = $ringGroup->ring_timeout ?? 30;

        // Set channel variables
        $agi->setVariable('ROUTE_ACTION', 'DIAL_INTERNAL');
        $agi->setVariable('ROUTE_DIAL_STRING', $dialString);
        $agi->setVariable('ROUTE_DIAL_TIMEOUT', (string) $dialTimeout);
        $agi->setVariable('ROUTE_CLI_NAME', $callerSip->caller_id_name ?: $callerName);
        $agi->setVariable('ROUTE_CLI_NUM', $callerSip->caller_id_number ?: $callerId);
        $agi->setVariable('CDR_UUID', $uuid);
        $agi->setVariable('RING_GROUP_STRATEGY', $ringGroup->strategy);

        $agi->verbose("rSwitch: Ring group {$ringGroup->name} ({$ringGroup->strategy}) -> {$dialString}", 2);

        Log::info('AGI ring group routed', [
            'uuid' => $uuid,
            'caller_user' => $callerUser->id,
            'ring_group' => $ringGroup->name,
            'strategy' => $ringGroup->strategy,
            'dial' => $dialString,
        ]);

        return true;
    }

    /**
     * Find a Ring Group by extension number.
     * Supports formats: "RG1", "RG100", "600" (if configured as extension)
     */
    private function findRingGroup(string $extension, User $user): ?RingGroup
    {
        // Match RG prefix pattern (e.g., RG1, RG100)
        if (preg_match('/^RG(\d+)$/i', $extension, $matches)) {
            $ringGroupId = (int) $matches[1];

            return RingGroup::where('id', $ringGroupId)
                ->where('status', 'active')
                ->where(function ($q) use ($user) {
                    // Ring groups owned by the user, their parent, or admin (global)
                    $q->where('user_id', $user->id)
                      ->orWhere('user_id', $user->parent_id)
                      ->orWhereNull('user_id');
                })
                ->first();
        }

        // Check if there's a ring group with this exact name
        return RingGroup::where('name', $extension)
            ->where('status', 'active')
            ->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                  ->orWhere('user_id', $user->parent_id)
                  ->orWhereNull('user_id');
            })
            ->first();
    }

    /**
     * Check if caller user can make internal calls to destination user.
     * Rules:
     * - Same user (own accounts) - allowed
     * - Same parent (reseller's clients can call each other) - allowed
     * - Caller is parent of destination - allowed
     * - Destination is parent of caller - allowed
     */
    private function canCallInternal(User $caller, User $destination): bool
    {
        // Same user
        if ($caller->id === $destination->id) {
            return true;
        }

        // Same parent (siblings under same reseller)
        if ($caller->parent_id && $caller->parent_id === $destination->parent_id) {
            return true;
        }

        // Caller is parent of destination
        if ($destination->parent_id === $caller->id) {
            return true;
        }

        // Destination is parent of caller
        if ($caller->parent_id === $destination->id) {
            return true;
        }

        // Admin/Super Admin can call anyone
        if (in_array($caller->role, ['super_admin', 'admin', 'recharge_admin'])) {
            return true;
        }

        // Resellers can call other resellers and their clients
        if ($caller->role === 'reseller' && in_array($destination->role, ['reseller', 'client'])) {
            return true;
        }

        return false;
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

    /**
     * Get today's total spend for a user from call_records.
     */
    private function getTodaySpend(int $userId): string
    {
        $today = now()->toDateString();

        $result = DB::selectOne("
            SELECT COALESCE(SUM(total_cost), 0) AS spend
            FROM call_records
            WHERE user_id = ?
              AND call_start >= ?
              AND status != 'unbillable'
        ", [$userId, $today . ' 00:00:00']);

        return (string) ($result->spend ?? '0');
    }

    /**
     * Get today's total call count for a user.
     */
    private function getTodayCallCount(int $userId): int
    {
        $today = now()->toDateString();

        $result = DB::selectOne("
            SELECT COUNT(*) AS cnt
            FROM call_records
            WHERE user_id = ?
              AND call_start >= ?
        ", [$userId, $today . ' 00:00:00']);

        return (int) ($result->cnt ?? 0);
    }

    /**
     * Get count of currently active (in_progress) calls for a user.
     */
    private function getActiveChannelCount(int $userId): int
    {
        return CallRecord::where('user_id', $userId)
            ->where('status', 'in_progress')
            ->count();
    }
}
