<?php

namespace App\Support;

use App\Services\CurrencyService;
use Illuminate\Database\Eloquent\Builder;

class CurrencyAmounts
{
    /**
     * @return array<string, float>
     */
    public static function sum(Builder $query, string $amountColumn = 'amount', string $currencyColumn = 'currency'): array
    {
        $rows = (clone $query)
            ->reorder()
            ->selectRaw("UPPER(COALESCE({$currencyColumn}, 'USD')) as code, COALESCE(SUM({$amountColumn}), 0) as total")
            ->groupByRaw("UPPER(COALESCE({$currencyColumn}, 'USD'))")
            ->get();

        $totals = [];

        foreach ($rows as $row) {
            $code = CurrencyService::normalize((string) $row->code);
            $totals[$code] = round(($totals[$code] ?? 0) + (float) $row->total, 2);
        }

        ksort($totals);

        return $totals;
    }
}
