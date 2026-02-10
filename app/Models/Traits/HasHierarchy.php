<?php

namespace App\Models\Traits;

use App\Models\User;
use Illuminate\Support\Facades\Cache;

/**
 * Trait for user hierarchy and scoping methods.
 * Uses materialized path pattern for efficient descendant queries.
 */
trait HasHierarchy
{
    /**
     * Boot the trait - auto-generate hierarchy path on create/update.
     */
    public static function bootHasHierarchy(): void
    {
        // Use 'created' event so ID is available
        static::created(function ($model) {
            $model->generateHierarchyPath();
            $model->saveQuietly(); // Save without triggering events
        });

        static::updating(function ($model) {
            if ($model->isDirty('parent_id')) {
                $model->generateHierarchyPath();
                // Clear cache when hierarchy changes
                Cache::forget("user_descendants_{$model->id}");
            }
        });
    }

    /**
     * Generate the hierarchy path based on parent.
     * Format: /1/5/12/ where numbers are user IDs from root to self.
     */
    public function generateHierarchyPath(): void
    {
        if ($this->parent_id && $this->parent) {
            $this->hierarchy_path = $this->parent->hierarchy_path . $this->id . '/';
        } else {
            $this->hierarchy_path = '/' . $this->id . '/';
        }
    }

    /**
     * Get all user IDs in this user's subtree using materialized path.
     * Uses caching for performance.
     */
    public function descendantIds(): array
    {
        $cacheKey = "user_descendants_{$this->id}";
        $cacheTtl = 300; // 5 minutes

        return Cache::remember($cacheKey, $cacheTtl, function () {
            return $this->computeDescendantIds();
        });
    }

    /**
     * Compute descendant IDs without cache.
     */
    protected function computeDescendantIds(): array
    {
        if ($this->isSuperAdmin()) {
            return User::pluck('id')->all();
        }

        if ($this->isRegularAdmin() || $this->isRechargeAdmin()) {
            return $this->getAdminScopedIds();
        }

        // Use hierarchy_path for efficient lookup if available
        if ($this->hierarchy_path) {
            $ids = User::where('hierarchy_path', 'LIKE', $this->hierarchy_path . '%')
                ->pluck('id')
                ->all();

            return array_unique(array_merge([$this->id], $ids));
        }

        // Fallback to recursive method
        return $this->getDescendantIdsRecursive();
    }

    /**
     * Get IDs for admin (scoped to assigned resellers).
     */
    protected function getAdminScopedIds(): array
    {
        $resellerIds = $this->getCachedAssignedResellerIds();

        if (empty($resellerIds)) {
            return [$this->id];
        }

        $clientIds = User::whereIn('parent_id', $resellerIds)->pluck('id')->all();

        return array_unique(array_merge([$this->id], $resellerIds, $clientIds));
    }

    /**
     * Recursive fallback for descendant IDs.
     */
    protected function getDescendantIdsRecursive(): array
    {
        $ids = [$this->id];

        if ($this->isReseller()) {
            $clientIds = User::where('parent_id', $this->id)->pluck('id')->all();
            $ids = array_merge($ids, $clientIds);
        }

        return $ids;
    }

    /**
     * Get only client IDs (excludes self).
     */
    public function clientIds(): array
    {
        $cacheKey = "user_clients_{$this->id}";
        $cacheTtl = 300;

        return Cache::remember($cacheKey, $cacheTtl, function () {
            if ($this->isSuperAdmin()) {
                return User::where('role', 'client')->pluck('id')->all();
            }

            if ($this->isRegularAdmin() || $this->isRechargeAdmin()) {
                $resellerIds = $this->getCachedAssignedResellerIds();

                if (empty($resellerIds)) {
                    return [];
                }

                return User::whereIn('parent_id', $resellerIds)
                    ->where('role', 'client')
                    ->pluck('id')
                    ->all();
            }

            if ($this->isReseller()) {
                return User::where('parent_id', $this->id)
                    ->where('role', 'client')
                    ->pluck('id')
                    ->all();
            }

            return [];
        });
    }

    /**
     * Get cached assigned reseller IDs for admin users.
     */
    public function getCachedAssignedResellerIds(): array
    {
        if (!$this->isRegularAdmin() && !$this->isRechargeAdmin()) {
            return [];
        }

        $cacheKey = "admin_resellers_{$this->id}";
        $cacheTtl = 300;

        return Cache::remember($cacheKey, $cacheTtl, function () {
            return $this->assignedResellers()->pluck('users.id')->toArray();
        });
    }

    /**
     * Get reseller IDs that this user can manage.
     */
    public function managedResellerIds(): array
    {
        $cacheKey = "managed_resellers_{$this->id}";
        $cacheTtl = 300;

        return Cache::remember($cacheKey, $cacheTtl, function () {
            if ($this->isSuperAdmin()) {
                return User::where('role', 'reseller')->pluck('id')->all();
            }

            if ($this->isRegularAdmin() || $this->isRechargeAdmin()) {
                return $this->assignedResellers()->pluck('users.id')->toArray();
            }

            if ($this->isReseller()) {
                return [$this->id];
            }

            return [];
        });
    }

    /**
     * Clear all hierarchy-related caches for this user.
     */
    public function clearHierarchyCache(): void
    {
        Cache::forget("user_descendants_{$this->id}");
        Cache::forget("user_clients_{$this->id}");
        Cache::forget("admin_resellers_{$this->id}");
        Cache::forget("managed_resellers_{$this->id}");
    }

    /**
     * Rebuild hierarchy paths for all users.
     * Run this after importing users or fixing data.
     */
    public static function rebuildAllHierarchyPaths(): void
    {
        // First, update users without parents (root level)
        User::whereNull('parent_id')->each(function ($user) {
            $user->hierarchy_path = '/' . $user->id . '/';
            $user->saveQuietly();
        });

        // Then, update all other users in order
        $maxDepth = 10; // Prevent infinite loops
        for ($depth = 0; $depth < $maxDepth; $depth++) {
            $updated = 0;

            User::whereNotNull('parent_id')
                ->whereNull('hierarchy_path')
                ->orWhere('hierarchy_path', '')
                ->each(function ($user) use (&$updated) {
                    if ($user->parent && $user->parent->hierarchy_path) {
                        $user->hierarchy_path = $user->parent->hierarchy_path . $user->id . '/';
                        $user->saveQuietly();
                        $updated++;
                    }
                });

            if ($updated === 0) {
                break;
            }
        }

        // Clear all caches
        Cache::flush();
    }
}
