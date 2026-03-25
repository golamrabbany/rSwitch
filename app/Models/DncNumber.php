<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DncNumber extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'phone_number', 'reason', 'added_by',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public function addedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'added_by');
    }

    /**
     * Check if a phone number is on the DNC list.
     */
    public static function isBlocked(string $phoneNumber): bool
    {
        return static::where('phone_number', $phoneNumber)->exists();
    }

    /**
     * Filter out DNC numbers from a list. Returns clean numbers.
     */
    public static function filterNumbers(array $phoneNumbers): array
    {
        $blocked = static::whereIn('phone_number', $phoneNumbers)->pluck('phone_number')->toArray();
        return array_values(array_diff($phoneNumbers, $blocked));
    }
}
