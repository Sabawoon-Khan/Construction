<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AuthorizesResourcePermissions;
use App\Models\ExpenseUnit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Inertia\Inertia;
use Inertia\Response;

class ExpenseUnitController extends Controller implements HasMiddleware
{
    protected static string $permissionModule = 'expenses';

    use AuthorizesResourcePermissions;

    public function index(): Response
    {
        return Inertia::render('ExpenseUnits/Index', [
            'items' => ExpenseUnit::query()->orderBy('unit_name')->get(['id', 'unit_name']),
        ]);
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
