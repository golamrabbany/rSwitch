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
        'days_of_week', 'timezone', 'priority', 'weight',
        'remove_prefix', 'add_prefix',
        'mnp_enabled', 'mnp_prefix', 'mnp_insert_position', 'status',
    ];

    protected function casts(): array
    {
        return [
            'mnp_enabled' => 'boolean',
            'mnp_insert_position' => 'integer',
        ];
    }

    public function trunk(): BelongsTo
    {
        return $this->belongsTo(Trunk::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Apply route-level dial prefix manipulation.
     *
     * Order: remove_prefix first, then add_prefix.
     * Example: 880171xxx → remove "880" → 171xxx → add "0" → 0171xxx
     */
    public function applyDialPrefixManipulation(string $number): string
    {
        $result = $number;

        // 1. Remove matching prefix
        if ($this->remove_prefix && str_starts_with($result, $this->remove_prefix)) {
            $result = substr($result, strlen($this->remove_prefix));
            if ($result === '' || $result === false) {
                $result = $number; // Don't strip to empty
            }
        }

        // 2. Add prefix
        if ($this->add_prefix) {
            $result = $this->add_prefix . $result;
        }

        return $result;
    }

    /**
     * Apply MNP transformation to a number if enabled.
     *
     * Example: 88017XXXXXXXXX -> 880[71]17XXXXXXXXX
     * - mnp_prefix: "71"
     * - mnp_insert_position: 3 (insert after 3rd digit, i.e., after "880")
     *
     * @param string $number The original number
     * @return string The transformed number (or original if MNP not enabled)
     */
    public function applyMnpTransformation(string $number): string
    {
        if (!$this->mnp_enabled || empty($this->mnp_prefix)) {
            return $number;
        }

        $position = $this->mnp_insert_position ?? 3;

        // Ensure position is valid
        if ($position < 0 || $position > strlen($number)) {
            return $number;
        }

        // Insert MNP prefix at the specified position
        return substr($number, 0, $position) . $this->mnp_prefix . substr($number, $position);
    }
}
