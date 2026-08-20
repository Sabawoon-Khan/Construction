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

    /**
     * @param  array<string, float>  $totals
     * @param  array<string, float>  $paid
     * @return array<string, float>
     */
    public static function remaining(array $totals, array $paid): array
    {
        $remaining = [];

        foreach (array_unique([...array_keys($totals), ...array_keys($paid)]) as $code) {
            $remaining[$code] = round(($totals[$code] ?? 0) - ($paid[$code] ?? 0), 2);
        }

        ksort($remaining);

        return $remaining;
    }

    /**
     * @param  iterable<int, object{code?: mixed, total?: mixed}>  $rows
     * @return array<string, float>
     */
    public static function fromRows(iterable $rows): array
    {
        $totals = [];

        foreach ($rows as $row) {
            $code = CurrencyService::normalize((string) ($row->code ?? 'USD'));
            $totals[$code] = round(($totals[$code] ?? 0) + (float) ($row->total ?? 0), 2);
        }

        ksort($totals);

        return $totals;
    }
}
