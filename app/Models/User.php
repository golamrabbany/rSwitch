<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, HasRoles, Notifiable;

    protected $fillable = [
        'name', 'email', 'password', 'role', 'parent_id', 'status',
        'kyc_status', 'kyc_verified_at', 'kyc_rejected_reason',
        'billing_type', 'credit_limit', 'balance', 'currency', 'rate_group_id',
        'min_balance_for_calls', 'low_balance_threshold',
        'max_channels', 'daily_spend_limit', 'daily_call_limit',
        'destination_whitelist_enabled',
        'two_fa_enabled', 'two_fa_secret', 'two_fa_recovery_codes', 'two_fa_confirmed_at',
        'otp_code', 'otp_expires_at',
    ];

    protected $hidden = [
        'password', 'remember_token', 'two_fa_secret', 'two_fa_recovery_codes', 'otp_code',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'kyc_verified_at' => 'datetime',
            'two_fa_confirmed_at' => 'datetime',
            'two_fa_recovery_codes' => 'array',
            'balance' => 'decimal:4',
            'credit_limit' => 'decimal:4',
            'destination_whitelist_enabled' => 'boolean',
            'two_fa_enabled' => 'boolean',
            'otp_expires_at' => 'datetime',
        ];
    }

    // --- Relationships ---

    public function parent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(User::class, 'parent_id');
    }

    public function rateGroup(): BelongsTo
    {
        return $this->belongsTo(RateGroup::class);
    }

    public function kycProfile(): HasOne
    {
        return $this->hasOne(KycProfile::class);
    }

    public function sipAccounts(): HasMany
    {
        return $this->hasMany(SipAccount::class);
    }

    public function dids(): HasMany
    {
        return $this->hasMany(Did::class, 'assigned_to_user_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function callRecords(): HasMany
    {
        return $this->hasMany(CallRecord::class);
    }

    /**
     * For admins: get assigned resellers (many-to-many).
     */
    public function assignedResellers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'admin_resellers', 'admin_id', 'reseller_id')
                    ->withTimestamps();
    }

    /**
     * For resellers: get assigned admins (many-to-many).
     */
    public function assignedAdmins(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'admin_resellers', 'reseller_id', 'admin_id')
                    ->withTimestamps();
    }

    // --- Scopes ---

    public function scopeResellers($query)
    {
        return $query->where('role', 'reseller');
    }

    public function scopeClients($query)
    {
        return $query->where('role', 'client');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope query to only users visible to the given user.
     * Super Admin sees all, Admin/Recharge Admin sees assigned resellers + their clients,
     * Reseller sees own clients, Client sees only self.
     */
    public function scopeVisibleTo($query, User $user)
    {
        if ($user->isSuperAdmin()) {
            return $query;
        }

        if ($user->isRegularAdmin() || $user->isRechargeAdmin()) {
            // Admin/Recharge Admin sees only assigned resellers and their clients
            $assignedResellerIds = $user->assignedResellers()->pluck('users.id')->toArray();

            if (empty($assignedResellerIds)) {
                // No assigned resellers - sees nothing except themselves
                return $query->where('id', $user->id);
            }

            $clientIds = User::whereIn('parent_id', $assignedResellerIds)->pluck('id')->toArray();
            $visibleIds = array_merge($assignedResellerIds, $clientIds);

            return $query->whereIn('id', $visibleIds);
        }

        if ($user->isReseller()) {
            return $query->where(function ($q) use ($user) {
                $q->where('id', $user->id)
                  ->orWhere('parent_id', $user->id);
            });
        }

        return $query->where('id', $user->id);
    }

    // --- Helpers ---

    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin';
    }

    /**
     * Check if user has admin-level access (super_admin or admin).
     */
    public function isAdmin(): bool
    {
        return in_array($this->role, ['super_admin', 'admin']);
    }

    /**
     * Check if user is a regular admin (not super admin).
     */
    public function isRegularAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isReseller(): bool
    {
        return $this->role === 'reseller';
    }

    public function isClient(): bool
    {
        return $this->role === 'client';
    }

    public function isRechargeAdmin(): bool
    {
        return $this->role === 'recharge_admin';
    }

    public function isPrepaid(): bool
    {
        return $this->billing_type === 'prepaid';
    }

    /**
     * Get all user IDs in this user's subtree.
     * Super Admin: all users
     * Admin/Recharge Admin: assigned resellers + their clients
     * Reseller: self + clients
     * Client: self only
     */
    public function descendantIds(): array
    {
        if ($this->isSuperAdmin()) {
            return User::pluck('id')->all();
        }

        if ($this->isRegularAdmin() || $this->isRechargeAdmin()) {
            $resellerIds = $this->assignedResellers()->pluck('users.id')->toArray();

            if (empty($resellerIds)) {
                return [$this->id];
            }

            $clientIds = User::whereIn('parent_id', $resellerIds)->pluck('id')->all();
            return array_merge([$this->id], $resellerIds, $clientIds);
        }

        $ids = [$this->id];

        if ($this->isReseller()) {
            $clientIds = User::where('parent_id', $this->id)->pluck('id')->all();
            $ids = array_merge($ids, $clientIds);
        }

        return $ids;
    }

    /**
     * Get only client IDs (excludes self).
     * Super Admin: all clients
     * Admin/Recharge Admin: clients under assigned resellers
     * Reseller: own clients
     */
    public function clientIds(): array
    {
        if ($this->isSuperAdmin()) {
            return User::where('role', 'client')->pluck('id')->all();
        }

        if ($this->isRegularAdmin() || $this->isRechargeAdmin()) {
            $resellerIds = $this->assignedResellers()->pluck('users.id')->toArray();

            if (empty($resellerIds)) {
                return [];
            }

            return User::whereIn('parent_id', $resellerIds)->where('role', 'client')->pluck('id')->all();
        }

        if ($this->isReseller()) {
            return User::where('parent_id', $this->id)->where('role', 'client')->pluck('id')->all();
        }

        return [];
    }

    /**
     * Check if this user can manage the given user.
     * Super Admin: can manage anyone
     * Admin: can manage assigned resellers and their clients
     * Reseller: can manage own clients
     * Client: can only manage self
     */
    public function canManage(User $target): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        if ($this->isRegularAdmin()) {
            // Cannot manage super admins or other admins
            if ($target->isSuperAdmin() || $target->isRegularAdmin()) {
                return false;
            }

            $assignedResellerIds = $this->assignedResellers()->pluck('users.id')->toArray();

            // Can manage assigned resellers
            if ($target->isReseller()) {
                return in_array($target->id, $assignedResellerIds);
            }

            // Can manage clients under assigned resellers
            if ($target->isClient()) {
                return in_array($target->parent_id, $assignedResellerIds);
            }

            return false;
        }

        if ($this->isReseller() && $target->parent_id === $this->id) {
            return true;
        }

        return $this->id === $target->id;
    }

    /**
     * Check if this user can perform balance recharge/adjustment on the target user.
     * Super Admin / Admin: same as canManage()
     * Recharge Admin: can only recharge assigned resellers and their clients
     */
    public function canRechargeBalance(User $target): bool
    {
        if ($this->isSuperAdmin() || $this->isRegularAdmin()) {
            return $this->canManage($target);
        }

        if ($this->isRechargeAdmin()) {
            $assignedResellerIds = $this->assignedResellers()->pluck('users.id')->toArray();

            if ($target->isReseller()) {
                return in_array($target->id, $assignedResellerIds);
            }

            if ($target->isClient()) {
                return in_array($target->parent_id, $assignedResellerIds);
            }
        }

        return false;
    }

    /**
     * Get reseller IDs that this user can manage (for admin scoping).
     */
    public function managedResellerIds(): array
    {
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
    }
}
