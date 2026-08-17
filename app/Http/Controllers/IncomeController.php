<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AuthorizesResourcePermissions;
use App\Models\Income;
use App\Services\FinanceDefaults;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Inertia\Inertia;
use Inertia\Response;

class IncomeController extends Controller implements HasMiddleware
{
    protected static string $permissionModule = 'incomes';

    use AuthorizesResourcePermissions;

    public function index(Request $request): Response
    {
        $query = Income::query()
            ->whereNull('project_id')
            ->where('status', 'approved');

        if ($search = $request->string('search')->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('income_no', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $count = (clone $query)->count();
        $totalAmount = (float) (clone $query)->sum('amount');
        $totalAmountBase = (float) (clone $query)->sum('amount_base');
        $totals = [
            'count' => $count,
            'amount' => $totalAmount,
            'amount_base' => $totalAmountBase,
            'this_month' => (float) (clone $query)->where('income_date', '>=', now()->startOfMonth())->sum('amount'),
            'this_month_base' => (float) (clone $query)->where('income_date', '>=', now()->startOfMonth())->sum('amount_base'),
            'average' => $count > 0 ? $totalAmount / $count : 0,
            'average_base' => $count > 0 ? $totalAmountBase / $count : 0,
        ];

        return Inertia::render('Incomes/Index', [
            'items' => $query->latest('income_date')->paginate(15)->withQueryString(),
            'filters' => ['search' => $search],
            'totals' => $totals,
        ]);
    }

    public function create(Request $request): Response
    {
        return Inertia::render('Incomes/Form', ['item' => null]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        Income::create(FinanceDefaults::incomeAttributes($data));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Income recorded.']);

        return redirect()->route('incomes.index');
    }

    public function show(Income $income): Response
    {
        return Inertia::render('Incomes/Show', ['item' => $income]);
    }

    public function edit(Request $request, Income $income): Response
    {
        return Inertia::render('Incomes/Form', ['item' => $income]);
    }

    public function update(Request $request, Income $income): RedirectResponse
    {
        $income->update(FinanceDefaults::incomeAttributes($this->validated($request)));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Income updated.']);

        return redirect()->route('incomes.index');
    }

    public function destroy(Request $request, Income $income): RedirectResponse
    {
        $income->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Income deleted.']);

        return redirect()->route('incomes.index');
    }

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        return $request->validate([
            'income_date' => 'required|date',
            'amount' => 'required|numeric|min:0',
            'currency' => 'nullable|string|size:3',
            'description' => 'required|string|max:1000',
        ]);
    }
}
