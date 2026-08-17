<?php

namespace App\Services;

use App\Models\ExchangeRate;
use App\Support\CurrentCompany;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Log;

class CurrencyService
{
    /**
     * Convert an amount from one currency to another.
     *
     * @return float|null Returns null if no rate is available.
     */
    public static function convert(float $amount, string $fromCurrency, string $toCurrency, ?CarbonInterface $date = null): ?float
    {
        $fromCurrency = strtoupper($fromCurrency);
        $toCurrency = strtoupper($toCurrency);

        if ($fromCurrency === $toCurrency) {
            return round($amount, 2);
        }

        $rate = ExchangeRate::getRate($fromCurrency, $toCurrency, $date);

        if ($rate === null) {
            Log::warning("Exchange rate missing: {$fromCurrency} -> {$toCurrency} on " . ($date?->toDateString() ?? now()->toDateString()));

            return null;
        }

        return round($amount * $rate, 2);
    }

    /**
     * Convert an amount to the company base currency.
     *
     * @return float|null Returns null if no rate is available.
     */
    public static function convertToBase(float $amount, string $fromCurrency, ?CarbonInterface $date = null): ?float
    {
        $baseCurrency = self::baseCurrency();

        return self::convert($amount, $fromCurrency, $baseCurrency, $date);
    }

    public static function baseCurrency(): string
    {
        return strtoupper(CurrentCompany::get()?->base_currency ?? 'USD');
    }

    /**
     * Convert an amount using the latest available rate, falling back to 1:1 if no rate is found.
     */
    public static function convertOrFallback(float $amount, string $fromCurrency, string $toCurrency, ?CarbonInterface $date = null): float
    {
        return self::convert($amount, $fromCurrency, $toCurrency, $date) ?? round($amount, 2);
    }

    /**
     * Convert to base currency, falling back to the original amount if no rate is found.
     */
    public static function convertToBaseOrFallback(float $amount, string $fromCurrency, ?CarbonInterface $date = null): float
    {
        $baseCurrency = self::baseCurrency();

        return self::convert($amount, $fromCurrency, $baseCurrency, $date) ?? round($amount, 2);
    }
}
