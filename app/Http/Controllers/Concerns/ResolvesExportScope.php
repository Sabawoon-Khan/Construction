<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

trait ResolvesExportScope
{
    /**
     * Whether the export should apply the current list filters.
     * `scope=all` exports everything; anything else (default) uses filters.
     */
    protected function exportAppliesFilters(Request $request): bool
    {
        $validated = $request->validate([
            'scope' => ['nullable', 'string', Rule::in(['all', 'filtered'])],
            'format' => ['nullable', 'string', Rule::in(['csv', 'xlsx', 'xls', 'excel'])],
        ]);

        return ($validated['scope'] ?? 'filtered') !== 'all';
    }

    protected function exportFormat(Request $request, string $default = 'xlsx'): string
    {
        $format = strtolower($request->string('format')->toString() ?: $default);

        return $format !== '' ? $format : $default;
    }
}
