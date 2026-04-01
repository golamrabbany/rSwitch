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
        'remove_prefix', 'add_prefix', 'mnp_enabled', 'status',
    ];

    protected function casts(): array
    {
        return [
            'mnp_enabled' => 'boolean',
        ];
    }

    /**
     * BD operator prefix → MNP route number mapping.
     */
    public const BD_MNP_MAP = [
        '13' => '71',  // Grameenphone
        '14' => '91',  // Banglalink
        '15' => '51',  // Teletalk
        '16' => '81',  // Airtel
        '17' => '71',  // Grameenphone
        '18' => '81',  // Robi
        '19' => '91',  // Banglalink
    ];

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
     */
    public function applyDialPrefixManipulation(string $number): string
    {
        $result = $number;

        if ($this->remove_prefix && str_starts_with($result, $this->remove_prefix)) {
            $result = substr($result, strlen($this->remove_prefix));
            if ($result === '' || $result === false) {
                $result = $number;
            }
        }

        if ($this->add_prefix) {
            $result = $this->add_prefix . $result;
        }

        return $result;
    }

    /**
     * Apply BD MNP transformation.
     *
     * Auto-normalizes any input format to national number,
     * then builds MNP format: 880 + operator_route + national_number
     *
     * @param string $number The number after remove/add prefix manipulation
     * @return string MNP formatted number, or original if not BD mobile
     */
    public function applyMnpTransformation(string $number): string
    {
        if (!$this->mnp_enabled) {
            return $number;
        }

        // Normalize to national number (strip country code / leading zero)
        $national = self::normalizeToBdNational($number);

        if ($national === null) {
            return $number; // Not a BD number, passthrough
        }

        // Get operator from first 2 digits
        $operatorPrefix = substr($national, 0, 2);
        $mnpCode = self::BD_MNP_MAP[$operatorPrefix] ?? null;

        if ($mnpCode === null) {
            return $number; // Unknown operator, passthrough
        }

        return '880' . $mnpCode . $national;
    }

    /**
     * Normalize any BD number format to national number (without 0 or country code).
     *
     * +8801714101351  → 1714101351
     * 008801714101351 → 1714101351
     * 8801714101351   → 1714101351
     * 01714101351     → 1714101351
     * 1714101351      → 1714101351
     *
     * Returns null if not a recognizable BD mobile number.
     */
    public static function normalizeToBdNational(string $number): ?string
    {
        // Strip + prefix
        $number = ltrim($number, '+');

        // Strip 00880 / 880 / 0
        if (str_starts_with($number, '00880')) {
            $national = substr($number, 5);
        } elseif (str_starts_with($number, '880')) {
            $national = substr($number, 3);
        } elseif (str_starts_with($number, '0')) {
            $national = substr($number, 1);
        } else {
            $national = $number;
        }

        // Validate: must start with 13-19 and be 10 digits
        if (strlen($national) !== 10) {
            return null;
        }

        $op = substr($national, 0, 2);
        if (!isset(self::BD_MNP_MAP[$op])) {
            return null;
        }

        return $national;
    }
}
