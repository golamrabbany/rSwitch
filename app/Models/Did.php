<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\SipAccount;

class Did extends Model
{
    protected $fillable = [
        'number', 'provider', 'trunk_id', 'assigned_to_user_id',
        'destination_type', 'destination_id', 'destination_number',
        'monthly_cost', 'monthly_price', 'status',
    ];

    protected function casts(): array
    {
        return [
            'monthly_cost' => 'decimal:4',
            'monthly_price' => 'decimal:4',
        ];
    }

    public function trunk(): BelongsTo
    {
        return $this->belongsTo(Trunk::class);
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
    }

    public function destinationSipAccount(): BelongsTo
    {
        return $this->belongsTo(SipAccount::class, 'destination_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
