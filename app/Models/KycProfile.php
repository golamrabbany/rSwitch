<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KycProfile extends Model
{
    protected $fillable = [
        'user_id', 'account_type', 'full_name', 'contact_person',
        'phone', 'alt_phone', 'address_line1', 'address_line2',
        'city', 'state', 'postal_code', 'country',
        'id_type', 'id_number', 'id_expiry_date',
        'submitted_at', 'reviewed_at', 'reviewed_by',
    ];

    protected function casts(): array
    {
        return [
            'id_expiry_date' => 'date',
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(KycDocument::class);
    }
}
