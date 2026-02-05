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
        'caller_id_name', 'caller_id_number', 'max_channels',
        'codec_allow', 'status', 'last_registered_at', 'last_registered_ip',
    ];

    protected $hidden = ['password'];

    protected function casts(): array
    {
        return [
            'last_registered_at' => 'datetime',
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
}
