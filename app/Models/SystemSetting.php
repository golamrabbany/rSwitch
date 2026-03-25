<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SystemSetting extends Model
{
    protected $primaryKey = 'key';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['key', 'value', 'type', 'group', 'sort_order', 'label', 'description'];

    /**
     * Get a setting value with optional default.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $setting = Cache::remember("setting:{$key}", 300, function () use ($key) {
            return static::find($key);
        });

        if (!$setting) {
            return $default;
        }

        return match ($setting->type) {
            'integer' => (int) $setting->value,
            'float'   => (float) $setting->value,
            'boolean' => filter_var($setting->value, FILTER_VALIDATE_BOOLEAN),
            'json'    => json_decode($setting->value, true),
            default   => $setting->value,
        };
    }

    /**
     * Set a setting value.
     */
    public static function set(string $key, mixed $value): void
    {
        $setting = static::find($key);

        if ($setting) {
            $storeValue = is_array($value) ? json_encode($value) : (string) $value;
            $setting->update(['value' => $storeValue]);
        } else {
            static::create([
                'key'   => $key,
                'value' => is_array($value) ? json_encode($value) : (string) $value,
                'type'  => is_array($value) ? 'json' : (is_bool($value) ? 'boolean' : 'string'),
            ]);
        }

        Cache::forget("setting:{$key}");
    }

    /**
     * Get all settings grouped.
     */
    public static function allGrouped(): array
    {
        return static::orderBy('group')->orderBy('key')
            ->get()
            ->groupBy('group')
            ->toArray();
    }

    /**
     * Seed default settings.
     */
    public static function seedDefaults(): void
    {
        $defaults = [
            ['key' => 'company_name', 'value' => 'rSwitch', 'type' => 'string', 'group' => 'general', 'sort_order' => 1, 'label' => 'Company Name', 'description' => 'Company name shown in invoices and emails.'],
            ['key' => 'company_address', 'value' => '', 'type' => 'string', 'group' => 'general', 'sort_order' => 2, 'label' => 'Company Address', 'description' => 'Full company address for invoices.'],
            ['key' => 'company_email', 'value' => '', 'type' => 'string', 'group' => 'general', 'sort_order' => 3, 'label' => 'Support Email', 'description' => 'Email displayed in notifications.'],
            ['key' => 'default_currency', 'value' => 'USD', 'type' => 'string', 'group' => 'general', 'sort_order' => 4, 'label' => 'Default Currency', 'description' => 'Currency code for new users.'],

            ['key' => 'default_billing_type', 'value' => 'prepaid', 'type' => 'string', 'group' => 'billing', 'sort_order' => 1, 'label' => 'Default Billing Type', 'description' => 'Default billing type for new users (prepaid or postpaid).'],
            ['key' => 'default_credit_limit', 'value' => '0', 'type' => 'float', 'group' => 'billing', 'sort_order' => 2, 'label' => 'Default Credit Limit', 'description' => 'Default credit limit for postpaid users.'],
            ['key' => 'low_balance_threshold', 'value' => '5.00', 'type' => 'float', 'group' => 'billing', 'sort_order' => 3, 'label' => 'Low Balance Threshold', 'description' => 'Send low-balance alerts when balance drops below this.'],
            ['key' => 'invoice_prefix', 'value' => 'INV', 'type' => 'string', 'group' => 'billing', 'sort_order' => 4, 'label' => 'Invoice Number Prefix', 'description' => 'Prefix for auto-generated invoice numbers.'],
            ['key' => 'invoice_due_days', 'value' => '30', 'type' => 'integer', 'group' => 'billing', 'sort_order' => 5, 'label' => 'Invoice Due Days', 'description' => 'Days until invoice is due after issuance.'],

            ['key' => 'default_max_channels', 'value' => '10', 'type' => 'integer', 'group' => 'sip', 'sort_order' => 1, 'label' => 'Default Max Channels', 'description' => 'Default max concurrent calls per SIP account.'],
            ['key' => 'sip_password_length', 'value' => '20', 'type' => 'integer', 'group' => 'sip', 'sort_order' => 2, 'label' => 'Auto Password Length', 'description' => 'Length of auto-generated SIP passwords.'],
            ['key' => 'default_codec_allow', 'value' => 'ulaw,alaw,g729,opus', 'type' => 'string', 'group' => 'sip', 'sort_order' => 3, 'label' => 'Default Codecs', 'description' => 'Default allowed codecs for new SIP accounts.'],
            ['key' => 'sip_pin_prefix', 'value' => '', 'type' => 'string', 'group' => 'sip', 'sort_order' => 4, 'label' => 'SIP PIN Prefix', 'description' => 'Required prefix for SIP account usernames (e.g. 100, 200). Leave empty for no prefix.'],
            ['key' => 'sip_pin_min_length', 'value' => '4', 'type' => 'integer', 'group' => 'sip', 'sort_order' => 5, 'label' => 'SIP PIN Min Length', 'description' => 'Minimum digits after prefix. E.g. prefix "100" + min 4 = total 7 digits (1000001).'],
            ['key' => 'sip_pin_max_length', 'value' => '10', 'type' => 'integer', 'group' => 'sip', 'sort_order' => 6, 'label' => 'SIP PIN Max Length', 'description' => 'Maximum digits after prefix. E.g. prefix "100" + max 10 = total 13 digits.'],

            ['key' => 'cdr_retention_days', 'value' => '365', 'type' => 'integer', 'group' => 'system', 'sort_order' => 1, 'label' => 'CDR Retention (Days)', 'description' => 'Auto-purge call records older than this.'],
            ['key' => 'audit_retention_days', 'value' => '180', 'type' => 'integer', 'group' => 'system', 'sort_order' => 2, 'label' => 'Audit Log Retention (Days)', 'description' => 'Auto-purge audit logs older than this.'],
            ['key' => 'api_rate_limit', 'value' => '60', 'type' => 'integer', 'group' => 'system', 'sort_order' => 3, 'label' => 'API Rate Limit', 'description' => 'Max API requests per minute per user.'],
            ['key' => 'session_timeout', 'value' => '120', 'type' => 'integer', 'group' => 'system', 'sort_order' => 4, 'label' => 'Session Timeout (Minutes)', 'description' => 'Inactive session auto-logout time.'],
            ['key' => 'maintenance_mode', 'value' => '0', 'type' => 'boolean', 'group' => 'system', 'sort_order' => 5, 'label' => 'Maintenance Mode', 'description' => 'Block non-admin users during maintenance.'],
            ['key' => 'enable_api_access', 'value' => '1', 'type' => 'boolean', 'group' => 'system', 'sort_order' => 6, 'label' => 'Enable API Access', 'description' => 'Allow REST API access for external integrations.'],
        ];

        foreach ($defaults as $setting) {
            $existing = static::find($setting['key']);
            if ($existing) {
                // Update metadata without overwriting user-set value
                $existing->update(collect($setting)->except('value')->toArray());
            } else {
                static::create($setting);
            }
        }
    }
}
