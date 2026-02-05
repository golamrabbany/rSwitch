<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class RingGroup extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'description', 'strategy', 'ring_timeout',
        'user_id', 'status',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(SipAccount::class, 'ring_group_members')
            ->withPivot('priority', 'delay')
            ->withTimestamps()
            ->orderByPivot('priority');
    }

    public function activeMembers(): BelongsToMany
    {
        return $this->members()->where('sip_accounts.status', 'active');
    }

    public function dids()
    {
        return Did::where('destination_type', 'ring_group')
            ->where('destination_id', $this->id)
            ->get();
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Build the PJSIP dial string based on ring strategy.
     */
    public function buildDialString(): string
    {
        $members = $this->activeMembers;

        if ($members->isEmpty()) {
            return '';
        }

        return match ($this->strategy) {
            'simultaneous' => 'PJSIP/' . $members->pluck('username')->implode('&PJSIP/'),
            'sequential' => $members->pluck('username')->map(fn ($u) => "PJSIP/{$u}")->implode(','),
            'random' => 'PJSIP/' . $members->shuffle()->pluck('username')->implode('&PJSIP/'),
            default => 'PJSIP/' . $members->pluck('username')->implode('&PJSIP/'),
        };
    }
}
