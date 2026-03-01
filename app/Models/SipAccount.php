<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SipAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'username', 'password', 'auth_type', 'allowed_ips',
        'caller_id_name', 'caller_id_number', 'random_caller_id', 'max_channels',
        'codec_allow', 'allow_p2p', 'allow_recording',
        'status', 'last_registered_at', 'last_registered_ip',
    ];

    protected $hidden = ['password'];

    protected function casts(): array
    {
        return [
            'last_registered_at' => 'datetime',
            'random_caller_id' => 'boolean',
            'allow_p2p' => 'boolean',
            'allow_recording' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function getRandomCallerIdFromPool(): ?string
    {
        $user = $this->user;
        $reseller = $user->isReseller() ? $user : $user->parent;

        if (!$reseller) {
            return null;
        }

        return static::where('id', '!=', $this->id)
            ->where('status', 'active')
            ->whereIn('user_id', $reseller->descendantIds())
            ->whereNotNull('caller_id_number')
            ->where('caller_id_number', '!=', '')
            ->inRandomOrder()
            ->value('caller_id_number');
    }
}
