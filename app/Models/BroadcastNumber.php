<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BroadcastNumber extends Model
{
    protected $fillable = [
        'broadcast_id', 'phone_number', 'status', 'attempt_count',
        'last_attempt_at', 'answered_at', 'duration', 'cost',
        'survey_response', 'call_record_id', 'error_reason',
    ];

    protected function casts(): array
    {
        return [
            'last_attempt_at' => 'datetime',
            'answered_at' => 'datetime',
            'cost' => 'decimal:4',
            'survey_response' => 'array',
        ];
    }

    public function broadcast(): BelongsTo
    {
        return $this->belongsTo(Broadcast::class);
    }

    public function callRecord(): BelongsTo
    {
        return $this->belongsTo(CallRecord::class, 'call_record_id');
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isFailed(): bool
    {
        return in_array($this->status, ['failed', 'no_answer', 'busy']);
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function getSurveyAnswer(string $key): ?string
    {
        $response = $this->survey_response;
        return is_array($response) ? ($response[$key] ?? null) : null;
    }
}
