<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Rate extends Model
{
    protected $fillable = [
        'rate_group_id', 'prefix', 'destination', 'rate_per_minute',
        'connection_fee', 'min_duration', 'billing_increment',
        'effective_date', 'end_date', 'status',
    ];

    protected function casts(): array
    {
        return [
            'rate_per_minute' => 'decimal:6',
            'connection_fee' => 'decimal:6',
            'effective_date' => 'date',
            'end_date' => 'date',
        ];
    }

    public function rateGroup(): BelongsTo
    {
        return $this->belongsTo(RateGroup::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active')
            ->where('effective_date', '<=', now()->toDateString())
            ->where(function ($q) {
                $q->whereNull('end_date')
                    ->orWhere('end_date', '>', now()->toDateString());
            });
    }
}
