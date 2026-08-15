<?php

namespace App\Support;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

class MisPeriod
{
    /**
     * @return array{0: CarbonInterface, 1: CarbonInterface, 2: string}
     */
    public static function resolve(?string $preset, mixed $from = null, mixed $to = null): array
    {
        $preset = in_array($preset, ['today', 'month', 'year', 'custom'], true)
            ? $preset
            : (($from || $to) ? 'custom' : 'month');

        if ($preset === 'today') {
            return [now()->startOfDay(), now()->endOfDay(), 'today'];
        }

        if ($preset === 'year') {
            return [now()->startOfYear(), now()->endOfDay(), 'year'];
        }

        if ($preset === 'custom' && $from && $to) {
            return [
                Carbon::parse($from)->startOfDay(),
                Carbon::parse($to)->endOfDay(),
                'custom',
            ];
        }

        return [now()->startOfMonth(), now()->endOfDay(), $preset === 'custom' ? 'month' : $preset];
    }
}
