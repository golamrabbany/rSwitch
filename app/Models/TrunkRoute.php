<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrunkRoute extends Model
{
    use HasFactory;

    protected $fillable = [
        'trunk_id', 'prefix', 'time_start', 'time_end',
        'days_of_week', 'timezone', 'priority', 'weight', 'status',
    ];

    public function trunk(): BelongsTo
    {
        return $this->belongsTo(Trunk::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
