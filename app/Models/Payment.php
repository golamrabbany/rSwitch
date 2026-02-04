<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $fillable = [
        'user_id', 'amount', 'currency', 'payment_method',
        'gateway_transaction_id', 'gateway_response',
        'recharged_by', 'notes', 'status', 'completed_at',
        'transaction_id', 'invoice_id',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:4',
            'gateway_response' => 'array',
            'completed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function rechargedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recharged_by');
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }
}
