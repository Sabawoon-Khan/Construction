<?php

namespace App\Http\Controllers\Concerns;

use App\Models\MaterialUnit;

trait ResolvesMaterialUnits
{
    /** @return list<array{id: int, unit_name: string}> */
    protected function materialUnitOptions(): array
    {
        return MaterialUnit::query()
            ->orderBy('unit_name')
            ->get(['id', 'unit_name'])
            ->map(fn (MaterialUnit $unit) => [
                'id' => $unit->id,
                'unit_name' => $unit->unit_name,
            ])
            ->all();
    }
}
