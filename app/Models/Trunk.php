<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Trunk extends Model
{
    protected $fillable = [
        'name', 'provider', 'direction', 'host', 'port', 'username', 'password',
        'register', 'register_string', 'transport', 'codec_allow', 'max_channels',
        'outgoing_priority',
        'dial_pattern_match', 'dial_pattern_replace', 'dial_prefix', 'dial_strip_digits', 'tech_prefix',
        'cli_mode', 'cli_override_number', 'cli_prefix_strip', 'cli_prefix_add',
        'incoming_context', 'incoming_auth_type', 'incoming_ip_acl',
        'health_check', 'health_check_interval', 'health_status',
        'health_last_checked_at', 'health_last_up_at', 'health_fail_count',
        'health_auto_disable_threshold', 'health_asr_threshold',
        'status', 'notes',
    ];

    protected $hidden = ['password'];

    protected function casts(): array
    {
        return [
            'register' => 'boolean',
            'health_check' => 'boolean',
            'health_last_checked_at' => 'datetime',
            'health_last_up_at' => 'datetime',
        ];
    }

    public function routes(): HasMany
    {
        return $this->hasMany(TrunkRoute::class);
    }

    public function dids(): HasMany
    {
        return $this->hasMany(Did::class);
    }

    public function scopeOutgoing($query)
    {
        return $query->whereIn('direction', ['outgoing', 'both']);
    }

    public function scopeIncoming($query)
    {
        return $query->whereIn('direction', ['incoming', 'both']);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeHealthy($query)
    {
        return $query->where('health_status', '!=', 'down')
            ->where('status', '!=', 'auto_disabled');
    }
}
