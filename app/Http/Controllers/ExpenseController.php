<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AuthorizesResourcePermissions;
use App\Http\Controllers\Concerns\StoresRecordAttachments;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\ExpenseUnit;
use App\Services\FinanceDefaults;
use App\Support\CurrencyAmounts;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class ExpenseController extends Controller implements HasMiddleware
{
    protected static string $permissionModule = 'expenses';

    use AuthorizesResourcePermissions;
    use StoresRecordAttachments;

    public function index(Request $request): Response
    {
        $query = Expense::query()
            ->whereNull('project_id')
            ->where('status', 'approved');

        if ($search = $request->string('search')->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('expense_no', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('paid_to', 'like', "%{$search}%");
            });
        }

        $count = (clone $query)->count();
        $totalAmount = (float) (clone $query)->sum('amount');
        $totalAmountBase = (float) (clone $query)->sum('amount_base');
        $totals = [
            'count' => $count,
            'amount' => $totalAmount,
            'amount_base' => $totalAmountBase,
            'this_month' => (float) (clone $query)->where('expense_date', '>=', now()->startOfMonth())->sum('amount'),
            'this_month_base' => (float) (clone $query)->where('expense_date', '>=', now()->startOfMonth())->sum('amount_base'),
            'average' => $count > 0 ? $totalAmount / $count : 0,
            'average_base' => $count > 0 ? $totalAmountBase / $count : 0,
            'amount_by_currency' => CurrencyAmounts::sum(clone $query),
            'this_month_by_currency' => CurrencyAmounts::sum(
                (clone $query)->where('expense_date', '>=', now()->startOfMonth())
            ),
        ];

        return Inertia::render('Expenses/Index', [
            'items' => $query->with(['expenseUnit:id,unit_name', 'creator:id,name', 'attachments'])->latest('expense_date')->paginate(15)->withQueryString(),
            'filters' => ['search' => $search],
            'totals' => $totals,
            'categories' => $this->categories(),
            'units' => ExpenseUnit::options(),
        ]);
    }

    public function create(Request $request): Response
    {
        return Inertia::render('Expenses/Form', [
            'item' => null,
            'categories' => $this->categories(),
            'units' => ExpenseUnit::options(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        unset($data['documents']);

        $expense = Expense::create(FinanceDefaults::expenseAttributes($data));
        $this->storeRecordAttachments($request, $expense);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Expense recorded.']);

        return redirect()->route('expenses.index');
    }

    public function update(Request $request, Expense $expense): RedirectResponse|Response
    {
        if ($expense->purchase_id) {
            Inertia::flash('toast', ['type' => 'error', 'message' => 'Edit this through the purchase, not as a standalone expense.']);

            return redirect()->route('purchases.show', $expense->purchase_id);
        }

        $projectId = $expense->project_id;

        $data = $this->validated($request);
        unset($data['documents']);

        $expense->update([
            ...FinanceDefaults::expenseAttributes($data, $projectId),
            'expense_category_id' => $request->filled('expense_category_id')
                ? $request->integer('expense_category_id')
                : FinanceDefaults::generalExpenseCategoryId(),
            'expense_unit_id' => $request->integer('expense_unit_id') ?: null,
            'reference_no' => $request->input('reference_no'),
            'payment_method' => $request->input('payment_method', $expense->payment_method) ?: 'cash',
        ]);

        $this->storeRecordAttachments($request, $expense);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Expense updated.']);

        if ($projectId) {
            return redirect()->to(route('projects.show', $projectId).'#expenses');
        }

        return redirect()->route('expenses.index');
    }

    public function show(Expense $expense): Response
    {
        $expense->load(['project:id,name', 'expenseUnit:id,unit_name', 'attachments', 'purchase:id,purchase_no']);

        return Inertia::render('Expenses/Show', [
            'item' => $expense,
        ]);
    }

    public function edit(Request $request, Expense $expense): Response|RedirectResponse
    {
        if ($expense->purchase_id) {
            return redirect()->route('purchases.show', $expense->purchase_id);
        }

        $expense->load(['attachments', 'project:id,name']);

        return Inertia::render('Expenses/Form', [
            'item' => $expense,
            'categories' => $this->categories(),
            'units' => ExpenseUnit::options(),
        ]);
    }

    public function destroy(Request $request, Expense $expense): RedirectResponse
    {
        if ($expense->purchase_id) {
            Inertia::flash('toast', ['type' => 'error', 'message' => 'Delete the purchase instead. That also removes this expense.']);

            return redirect()->route('purchases.show', $expense->purchase_id);
        }

        $projectId = $expense->project_id;
        $expense->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Expense deleted.']);

        if ($projectId) {
            return redirect()->to(route('projects.show', $projectId).'#expenses');
        }

        return redirect()->route('expenses.index');
    }

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        $request->merge([
            'expense_category_id' => $request->filled('expense_category_id') ? $request->input('expense_category_id') : null,
            'expense_unit_id' => $request->filled('expense_unit_id') ? $request->input('expense_unit_id') : null,
        ]);

        $data = $request->validate([
            'expense_date' => 'required|date',
            'paid_to' => 'required|string|max:255',
            'item_amount' => 'nullable|numeric|min:0',
            'per_price' => 'nullable|numeric|min:0',
            'amount' => 'nullable|numeric|min:0',
            'expense_unit_id' => 'nullable|exists:expense_units,id',
            'description' => 'required|string|max:1000',
            'expense_category_id' => 'nullable|exists:expense_categories,id',
            'currency' => 'nullable|string|size:3',
            'payment_method' => 'nullable|in:cash,bank',
            'reference_no' => 'nullable|string|max:100',
            'documents' => 'nullable|array',
            'documents.*' => 'nullable|file|max:20480',
        ]);

        if (! empty($data['item_amount']) && isset($data['per_price'])) {
            $data['amount'] = FinanceDefaults::totalCost($data);
        }

        return $data;
    }

    /** @return Collection<int, ExpenseCategory> */
    private function categories()
    {
        return ExpenseCategory::nonMaterialOptions();
    }
}
