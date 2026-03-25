<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceItem extends Model
{
    protected $fillable = [
        'invoice_id', 'description', 'type', 'destination',
        'client_name', 'quantity', 'minutes', 'rate_per_minute', 'amount',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:4',
            'minutes' => 'decimal:2',
            'rate_per_minute' => 'decimal:6',
        ];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }
}
