<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name', 'email', 'password', 'role', 'parent_id', 'status',
        'kyc_status', 'kyc_verified_at', 'kyc_rejected_reason',
        'billing_type', 'credit_limit', 'balance', 'currency', 'rate_group_id',
        'min_balance_for_calls', 'low_balance_threshold',
        'max_channels', 'daily_spend_limit', 'daily_call_limit',
        'destination_whitelist_enabled',
        'two_fa_enabled', 'two_fa_secret', 'two_fa_recovery_codes', 'two_fa_confirmed_at',
    ];

    protected $hidden = [
        'password', 'remember_token', 'two_fa_secret', 'two_fa_recovery_codes',
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

    // --- Helpers ---

    public function isAdmin(): bool
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

    public function isPrepaid(): bool
    {
        return $this->billing_type === 'prepaid';
    }
}
