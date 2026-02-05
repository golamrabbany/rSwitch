<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RateImport extends Model
{
    use HasFactory;

    protected $fillable = [
        'rate_group_id', 'uploaded_by', 'file_name', 'file_path',
        'total_rows', 'imported_rows', 'skipped_rows', 'error_rows',
        'error_log', 'effective_date', 'status', 'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'error_log' => 'array',
            'effective_date' => 'date',
            'completed_at' => 'datetime',
        ];
    }

    public function rateGroup(): BelongsTo
    {
        return $this->belongsTo(RateGroup::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
