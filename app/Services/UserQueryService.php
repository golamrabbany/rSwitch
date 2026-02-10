<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

/**
 * Service class for user-related queries.
 * Centralizes scoping logic used across admin, reseller, and recharge-admin controllers.
 */
class UserQueryService
{
    /**
     * Get a base query scoped to the user's visibility.
     */
    public function scopedQuery(User $user): Builder
    {
        return User::visibleTo($user);
    }

    /**
     * Get resellers visible to the user.
     */
    public function getResellers(User $user): Builder
    {
        return $this->scopedQuery($user)->resellers();
    }

    /**
     * Get clients visible to the user.
     */
    public function getClients(User $user): Builder
    {
        return $this->scopedQuery($user)->clients();
    }

    /**
     * Get users available for balance operations (excludes admins).
     */
    public function getUsersForBalanceOps(User $user): Builder
    {
        return $this->scopedQuery($user)
            ->whereIn('role', ['reseller', 'client'])
            ->where('status', 'active');
    }

    /**
     * Get user counts for dashboard stats.
     */
    public function getCounts(User $user): array
    {
        $cacheKey = "user_counts_{$user->id}";
        $cacheTtl = 300; // 5 minutes

        return Cache::remember($cacheKey, $cacheTtl, function () use ($user) {
            $query = $this->scopedQuery($user);

            return [
                'total' => (clone $query)->count(),
                'resellers' => (clone $query)->resellers()->count(),
                'clients' => (clone $query)->clients()->count(),
                'active' => (clone $query)->active()->count(),
            ];
        });
    }

    /**
     * Find a user within scope or fail.
     */
    public function findOrFail(User $actor, int $userId): User
    {
        return $this->scopedQuery($actor)->findOrFail($userId);
    }

    /**
     * Check if a user can be viewed by the actor.
     */
    public function canView(User $actor, User $target): bool
    {
        return $actor->canView($target);
    }

    /**
     * Check if a user can be managed by the actor.
     */
    public function canManage(User $actor, User $target): bool
    {
        return $actor->canManage($target);
    }

    /**
     * Get all user IDs in the actor's scope (for filtering related models).
     */
    public function getScopedUserIds(User $user): array
    {
        return $user->descendantIds();
    }

    /**
     * Clear user-related caches for a specific user.
     */
    public function clearCache(User $user): void
    {
        Cache::forget("user_counts_{$user->id}");
        $user->clearHierarchyCache();
    }
}
