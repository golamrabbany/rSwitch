<?php

namespace App\Models;

use App\Models\Traits\HasAuthorization;
use App\Models\Traits\HasHierarchy;
use App\Models\Traits\HasRoleHelpers;
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
    use HasRoleHelpers, HasHierarchy, HasAuthorization;

    protected $fillable = [
        'name', 'email', 'password', 'role', 'parent_id', 'hierarchy_path', 'status',
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
            // Use cached assigned reseller IDs
            $assignedResellerIds = $user->getCachedAssignedResellerIds();

            if (empty($assignedResellerIds)) {
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
}
