<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KycDocument extends Model
{
    protected $fillable = [
        'kyc_profile_id', 'document_type', 'file_path', 'original_name',
        'mime_type', 'file_size', 'status', 'rejection_reason',
    ];

    public function kycProfile(): BelongsTo
    {
        return $this->belongsTo(KycProfile::class);
    }
}
