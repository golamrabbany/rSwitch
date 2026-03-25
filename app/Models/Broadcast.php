<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Broadcast extends Model
{
    protected $fillable = [
        'user_id', 'sip_account_id', 'voice_file_id', 'type', 'name', 'description',
        'status', 'caller_id_name', 'caller_id_number', 'max_concurrent',
        'retry_attempts', 'retry_delay', 'ring_timeout', 'survey_config',
        'phone_list_type', 'scheduled_at', 'started_at', 'completed_at',
        'total_numbers', 'dialed_count', 'answered_count', 'failed_count',
        'total_cost', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'survey_config' => 'array',
            'scheduled_at' => 'datetime',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'total_cost' => 'decimal:4',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sipAccount(): BelongsTo
    {
        return $this->belongsTo(SipAccount::class);
    }

    public function voiceFile(): BelongsTo
    {
        return $this->belongsTo(VoiceFile::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function numbers(): HasMany
    {
        return $this->hasMany(BroadcastNumber::class);
    }

    public function getProgressPercentageAttribute(): float
    {
        if ($this->total_numbers === 0) return 0;
        return round(($this->dialed_count / $this->total_numbers) * 100, 1);
    }

    public function getIsRunningAttribute(): bool
    {
        return $this->status === 'running';
    }

    public function getCanStartAttribute(): bool
    {
        return in_array($this->status, ['draft', 'scheduled']);
    }

    public function getCanPauseAttribute(): bool
    {
        return $this->status === 'running';
    }

    public function getCanResumeAttribute(): bool
    {
        return $this->status === 'paused';
    }

    public function getCanCancelAttribute(): bool
    {
        return in_array($this->status, ['draft', 'scheduled', 'queued', 'running', 'paused']);
    }

    public function isSurvey(): bool
    {
        return $this->type === 'survey';
    }

    public function scopeOwnedBy($query, User $user)
    {
        if ($user->isSuperAdmin()) {
            return $query;
        }
        return $query->whereIn('user_id', $user->descendantIds());
    }
}
