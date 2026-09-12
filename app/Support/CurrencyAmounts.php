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

        $totals = ['AFN' => 0.0, 'USD' => 0.0];

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

        foreach (array_unique([...array_keys($totals), ...array_keys($paid), 'AFN', 'USD']) as $code) {
            $code = CurrencyService::normalize((string) $code);
            $remaining[$code] = round(($totals[$code] ?? 0) - ($paid[$code] ?? 0), 2);
        }

        ksort($remaining);

        return $remaining;
    }

    /**
     * @param  array<string, float>  ...$buckets
     * @return array<string, float>
     */
    public static function add(array ...$buckets): array
    {
        $totals = ['AFN' => 0.0, 'USD' => 0.0];

        foreach ($buckets as $bucket) {
            foreach ($bucket as $code => $amount) {
                $code = CurrencyService::normalize((string) $code);
                $totals[$code] = round(($totals[$code] ?? 0) + (float) $amount, 2);
            }
        }

        ksort($totals);

        return $totals;
    }

    /**
     * Per-currency net = revenue − expenses (AFN/USD always present).
     *
     * @param  array<string, float>  $revenue
     * @param  array<string, float>  $expenses
     * @return array<string, float>
     */
    public static function net(array $revenue, array $expenses): array
    {
        return self::remaining($revenue, $expenses);
    }

    /**
     * @param  iterable<int, object{code?: mixed, total?: mixed}>  $rows
     * @return array<string, float>
     */
    public static function fromRows(iterable $rows): array
    {
        $totals = ['AFN' => 0.0, 'USD' => 0.0];

        foreach ($rows as $row) {
            $code = CurrencyService::normalize((string) ($row->code ?? 'USD'));
            $totals[$code] = round(($totals[$code] ?? 0) + (float) ($row->total ?? 0), 2);
        }

        ksort($totals);

        return $totals;
    }

    /**
     * Last N calendar months of amount totals by AFN/USD.
     *
     * @return list<array{key: string, label: string, by_currency: array{AFN: float, USD: float}}>
     */
    public static function sumByMonth(
        Builder $query,
        string $dateColumn = 'created_at',
        string $amountColumn = 'amount',
        string $currencyColumn = 'currency',
        int $months = 6,
    ): array {
        $to = now()->endOfMonth();
        $from = now()->subMonths(max(1, $months) - 1)->startOfMonth();

        $buckets = [];
        $cursor = $from->copy();
        while ($cursor->lte($to)) {
            $key = $cursor->format('Y-m');
            $buckets[$key] = [
                'key' => $key,
                'label' => $cursor->format('M'),
                'by_currency' => ['AFN' => 0.0, 'USD' => 0.0],
            ];
            $cursor = $cursor->addMonth();
        }

        $driver = $query->getConnection()->getDriverName();
        $monthExpr = in_array($driver, ['mysql', 'mariadb'], true)
            ? "DATE_FORMAT({$dateColumn}, '%Y-%m')"
            : "strftime('%Y-%m', {$dateColumn})";

        $rows = (clone $query)
            ->reorder()
            ->whereDate($dateColumn, '>=', $from->toDateString())
            ->whereDate($dateColumn, '<=', $to->toDateString())
            ->selectRaw("{$monthExpr} as month_key")
            ->selectRaw("UPPER(COALESCE({$currencyColumn}, 'USD')) as code")
            ->selectRaw("COALESCE(SUM({$amountColumn}), 0) as total")
            ->groupByRaw("{$monthExpr}, UPPER(COALESCE({$currencyColumn}, 'USD'))")
            ->get();

        foreach ($rows as $row) {
            $key = (string) $row->month_key;
            if (! isset($buckets[$key])) {
                continue;
            }
            $code = CurrencyService::normalize((string) $row->code);
            if ($code !== 'AFN' && $code !== 'USD') {
                continue;
            }
            $buckets[$key]['by_currency'][$code] = round(
                (float) ($buckets[$key]['by_currency'][$code] ?? 0) + (float) $row->total,
                2,
            );
        }

        return array_values($buckets);
    }
}
