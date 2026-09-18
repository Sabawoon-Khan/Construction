<?php

namespace App\Http\Controllers;

use App\Concerns\ExportsSpreadsheet;
use App\Http\Controllers\Concerns\AuthorizesResourcePermissions;
use App\Http\Controllers\Concerns\ResolvesExportScope;
use App\Models\ExpenseUnit;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExpenseUnitController extends Controller implements HasMiddleware
{
    protected static string $permissionModule = 'expenses';

    use AuthorizesResourcePermissions;
    use ExportsSpreadsheet;
    use ResolvesExportScope;

    public function index(Request $request): Response
    {
        $search = $request->string('search')->toString();

        $items = $this->indexQuery($request)->get(['id', 'unit_name', 'user_id', 'created_at']);
        $used = $items->filter(fn ($item) => (int) ($item->expenses_count ?? 0) > 0)->count();
        $count = $items->count();
        $expensesLinked = (int) $items->sum('expenses_count');

        return Inertia::render('ExpenseUnits/Index', [
            'items' => $items,
            'filters' => ['search' => $search],
            'totals' => [
                'count' => $count,
                'used' => $used,
                'unused' => max(0, $count - $used),
                'expenses_linked' => $expensesLinked,
            ],
            'usageChart' => $items
                ->sortByDesc(fn ($item) => (int) ($item->expenses_count ?? 0))
                ->take(6)
                ->values()
                ->map(fn ($item) => [
                    'key' => (string) $item->id,
                    'label' => Str::limit((string) $item->unit_name, 8, ''),
                    'value' => (int) ($item->expenses_count ?? 0),
                ])
                ->all(),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        abort_unless(config('kit.features.exports'), 404);

        $rows = $this->indexQuery($request, $this->exportAppliesFilters($request))
            ->lazyById(500)
            ->map(fn (ExpenseUnit $unit) => [
                $unit->unit_name,
                $unit->expenses_count,
                $unit->creator?->name,
                $unit->created_at?->toDateTimeString(),
            ]);

        return $this->streamExport(
            'expense-units',
            ['unit_name', 'expenses_count', 'added_by', 'created_at'],
            $rows,
            $this->exportFormat($request),
        );
    }

    protected function indexQuery(Request $request, bool $applyFilters = true): Builder
    {
        $query = ExpenseUnit::query()
            ->with('creator:id,name')
            ->withCount('expenses')
            ->latest();

        if ($applyFilters && ($search = $request->string('search')->toString()) !== '') {
            $query->where('unit_name', 'like', "%{$search}%");
        }

        return $query;
    }

    public function store(Request $request): RedirectResponse
    {
        ExpenseUnit::query()->create($this->validated($request));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Unit created.']);

        return redirect()->route('expense-units.index');
    }

    public function update(Request $request, ExpenseUnit $expenseUnit): RedirectResponse
    {
        $expenseUnit->update($this->validated($request, $expenseUnit->id));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Unit updated.']);

        return redirect()->route('expense-units.index');
    }

    public function destroy(ExpenseUnit $expenseUnit): RedirectResponse
    {
        $expenseUnit->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Unit deleted.']);

        return redirect()->route('expense-units.index');
    }

    /** @return array{unit_name: string} */
    private function validated(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'unit_name' => [
                'required',
                'string',
                'max:100',
                'unique:expense_units,unit_name'.($ignoreId ? ','.$ignoreId : ''),
            ],
        ]);
    }
}
