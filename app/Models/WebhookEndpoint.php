<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WebhookEndpoint extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'url', 'secret', 'events', 'active', 'description',
        'failure_count', 'last_triggered_at', 'last_failed_at',
    ];

    protected function casts(): array
    {
        return [
            'events' => 'array',
            'active' => 'boolean',
            'last_triggered_at' => 'datetime',
            'last_failed_at' => 'datetime',
        ];
    }

    public const AVAILABLE_EVENTS = [
        'call.completed' => 'Call Completed',
        'call.failed' => 'Call Failed',
        'payment.received' => 'Payment Received',
        'balance.low' => 'Low Balance Alert',
        'sip_account.created' => 'SIP Account Created',
        'sip_account.updated' => 'SIP Account Updated',
        'did.assigned' => 'DID Assigned',
        'invoice.issued' => 'Invoice Issued',
        'kyc.approved' => 'KYC Approved',
        'kyc.rejected' => 'KYC Rejected',
    ];

    // --- Relationships ---

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(WebhookLog::class);
    }

    // --- Scopes ---

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    public function scopeForEvent($query, string $event)
    {
        return $query->whereJsonContains('events', $event);
    }
}
