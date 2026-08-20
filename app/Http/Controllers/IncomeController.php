<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AuthorizesResourcePermissions;
use App\Http\Controllers\Concerns\StoresRecordAttachments;
use App\Models\Income;
use App\Services\FinanceDefaults;
use App\Support\CurrencyAmounts;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Inertia\Inertia;
use Inertia\Response;

class IncomeController extends Controller implements HasMiddleware
{
    protected static string $permissionModule = 'incomes';

    use AuthorizesResourcePermissions;
    use StoresRecordAttachments;

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
            'amount_by_currency' => CurrencyAmounts::sum(clone $query),
            'this_month_by_currency' => CurrencyAmounts::sum(
                (clone $query)->where('income_date', '>=', now()->startOfMonth())
            ),
        ];

        return Inertia::render('Incomes/Index', [
            'items' => $query->with('creator:id,name')->latest('income_date')->paginate(15)->withQueryString(),
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
        unset($data['documents']);

        $income = Income::create(FinanceDefaults::incomeAttributes($data));
        $this->storeRecordAttachments($request, $income);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Income recorded.']);

        return redirect()->route('incomes.index');
    }

    public function show(Income $income): Response
    {
        $income->load('attachments');

        return Inertia::render('Incomes/Show', ['item' => $income]);
    }

    public function edit(Request $request, Income $income): Response
    {
        $income->load('attachments');

        return Inertia::render('Incomes/Form', ['item' => $income]);
    }

    public function update(Request $request, Income $income): RedirectResponse
    {
        $data = $this->validated($request);
        unset($data['documents']);

        $income->update(FinanceDefaults::incomeAttributes($data));
        $this->storeRecordAttachments($request, $income);

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
            ...$this->attachmentValidationRules(),
        ]);
    }
}
