<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RateGroup extends Model
{
    protected $fillable = [
        'name', 'description', 'type', 'parent_rate_group_id', 'created_by',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function parentRateGroup(): BelongsTo
    {
        return $this->belongsTo(RateGroup::class, 'parent_rate_group_id');
    }

    public function childRateGroups(): HasMany
    {
        return $this->hasMany(RateGroup::class, 'parent_rate_group_id');
    }

    public function rates(): HasMany
    {
        return $this->hasMany(Rate::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function rateImports(): HasMany
    {
        return $this->hasMany(RateImport::class);
    }
}
