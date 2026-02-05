<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransferLog extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'transfer_type', 'transferred_item_id', 'transferred_item_type',
        'from_parent_id', 'to_parent_id', 'performed_by',
        'reason', 'metadata',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function performedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    public function fromParent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'from_parent_id');
    }

    public function toParent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'to_parent_id');
    }
}
