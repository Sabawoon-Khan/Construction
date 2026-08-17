<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;

class ExchangeRate extends Model
{
    protected $fillable = [
        'from_currency',
        'to_currency',
        'rate',
        'effective_date',
        'created_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'rate' => 'decimal:6',
            'effective_date' => 'date',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public static function getRate(string $fromCurrency, string $toCurrency, ?CarbonInterface $date = null): ?float
    {
        if (strtoupper($fromCurrency) === strtoupper($toCurrency)) {
            return 1.0;
        }

        $date ??= now();
        $cacheKey = "exchange_rate:{$fromCurrency}:{$toCurrency}:{$date->toDateString()}";

        return Cache::remember($cacheKey, 3600, function () use ($fromCurrency, $toCurrency, $date) {
            $rate = static::query()
                ->where('from_currency', $fromCurrency)
                ->where('to_currency', $toCurrency)
                ->where('effective_date', '<=', $date->toDateString())
                ->orderByDesc('effective_date')
                ->orderByDesc('id')
                ->value('rate');

            if ($rate !== null) {
                return (float) $rate;
            }

            // Try inverse rate
            $inverse = static::query()
                ->where('from_currency', $toCurrency)
                ->where('to_currency', $fromCurrency)
                ->where('effective_date', '<=', $date->toDateString())
                ->orderByDesc('effective_date')
                ->orderByDesc('id')
                ->value('rate');

            if ($inverse !== null && (float) $inverse !== 0.0) {
                return round(1 / (float) $inverse, 6);
            }

            return null;
        });
    }

    public static function clearCache(): void
    {
        Cache::flush();
    }
}
