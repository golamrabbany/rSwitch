<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SurveyTemplate extends Model
{
    protected $fillable = [
        'user_id', 'client_id', 'name', 'description', 'status',
        'config', 'approved_by', 'approved_at', 'rejection_reason',
    ];

    protected function casts(): array
    {
        return [
            'config' => 'array',
            'approved_at' => 'datetime',
        ];
    }

    // Relationships
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function client(): BelongsTo { return $this->belongsTo(User::class, 'client_id'); }
    public function approvedBy(): BelongsTo { return $this->belongsTo(User::class, 'approved_by'); }
    public function broadcasts(): HasMany { return $this->hasMany(Broadcast::class); }

    // Scopes
    public function scopeApproved($query) { return $query->where('status', 'approved'); }
    public function scopePending($query) { return $query->where('status', 'pending'); }

    public function scopeVisibleTo($query, User $user)
    {
        if ($user->isSuperAdmin()) return $query;
        if ($user->isRegularAdmin() || $user->isRechargeAdmin()) {
            return $query->whereIn('client_id', $user->descendantIds());
        }
        if ($user->isReseller()) {
            $childIds = $user->descendantIds();
            return $query->where(function ($q) use ($user, $childIds) {
                $q->where('user_id', $user->id)->orWhereIn('client_id', $childIds);
            });
        }
        // Client
        return $query->where(function ($q) use ($user) {
            $q->where('user_id', $user->id)->orWhere('client_id', $user->id);
        });
    }

    // Helpers
    public function isApproved(): bool { return $this->status === 'approved'; }
    public function isPending(): bool { return $this->status === 'pending'; }

    public function getQuestions(): \Illuminate\Support\Collection
    {
        $config = $this->config;
        if (!is_array($config) || empty($config['questions'])) return collect();
        return collect($config['questions'])->where('type', 'question')->values();
    }

    public function getIntro(): ?array
    {
        $config = $this->config;
        if (!is_array($config) || empty($config['questions'])) return null;
        return collect($config['questions'])->firstWhere('type', 'intro');
    }

    public function getQuestionCount(): int
    {
        return $this->getQuestions()->count();
    }
}
