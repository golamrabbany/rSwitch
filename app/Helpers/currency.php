<?php

use App\Models\SystemSetting;

if (!function_exists('format_currency')) {
    /**
     * Format a value as currency based on system settings.
     *
     * @param float|int|string $amount
     * @param int $decimals
     * @param bool $showSymbol
     * @return string
     */
    function format_currency(float|int|string $amount, int $decimals = 2, bool $showSymbol = true): string
    {
        $currency = SystemSetting::get('default_currency', 'USD');
        $amount = (float) $amount;

        $formatted = number_format($amount, $decimals);

        if (!$showSymbol) {
            return $formatted;
        }

        return match ($currency) {
            'BDT' => '৳' . $formatted,
            'USD' => '$' . $formatted,
            'EUR' => '€' . $formatted,
            'GBP' => '£' . $formatted,
            default => $currency . ' ' . $formatted,
        };
    }
}

if (!function_exists('currency_symbol')) {
    /**
     * Get the currency symbol based on system settings.
     *
     * @return string
     */
    function currency_symbol(): string
    {
        $currency = SystemSetting::get('default_currency', 'USD');

        return match ($currency) {
            'BDT' => '৳',
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£',
            default => $currency,
        };
    }
}

if (!function_exists('currency_code')) {
    /**
     * Get the currency code from system settings.
     *
     * @return string
     */
    function currency_code(): string
    {
        return SystemSetting::get('default_currency', 'USD');
    }
}
