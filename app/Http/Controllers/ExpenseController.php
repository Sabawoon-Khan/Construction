<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AuthorizesResourcePermissions;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\ExpenseUnit;
use App\Services\FinanceDefaults;
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
        $totals = [
            'count' => $count,
            'amount' => $totalAmount,
            'this_month' => (float) (clone $query)->where('expense_date', '>=', now()->startOfMonth())->sum('amount'),
            'average' => $count > 0 ? $totalAmount / $count : 0,
        ];

        return Inertia::render('Expenses/Index', [
            'items' => $query->with('expenseUnit:id,unit_name')->latest('expense_date')->paginate(15)->withQueryString(),
            'filters' => ['search' => $search],
            'totals' => $totals,
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
        $this->storeDocuments($request, $expense);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Expense recorded.']);

        return redirect()->route('expenses.index');
    }

    public function show(Expense $expense): Response
    {
        $expense->load(['project:id,name', 'expenseUnit:id,unit_name', 'attachments']);

        return Inertia::render('Expenses/Show', [
            'item' => $expense,
        ]);
    }

    public function edit(Request $request, Expense $expense): Response|RedirectResponse
    {
        if ($expense->project_id) {
            return redirect()->route('projects.show', $expense->project_id);
        }

        $expense->load('attachments');

        return Inertia::render('Expenses/Form', [
            'item' => $expense,
            'categories' => $this->categories(),
            'units' => ExpenseUnit::options(),
        ]);
    }

    public function update(Request $request, Expense $expense): RedirectResponse
    {
        if ($expense->project_id) {
            return redirect()->route('projects.show', $expense->project_id);
        }

        $data = $this->validated($request);
        unset($data['documents']);

        $expense->update([
            ...$data,
            'expense_category_id' => $request->integer('expense_category_id') ?: $expense->expense_category_id,
            'expense_unit_id' => $request->integer('expense_unit_id') ?: null,
            'reference_no' => $request->input('reference_no'),
        ]);

        $this->storeDocuments($request, $expense);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Expense updated.']);

        return redirect()->route('expenses.index');
    }

    public function destroy(Request $request, Expense $expense): RedirectResponse
    {
        $projectId = $expense->project_id;
        $expense->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Expense deleted.']);

        if ($projectId) {
            return redirect()->route('projects.show', $projectId);
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
            'item_amount' => 'required|numeric|min:0',
            'per_price' => 'required|numeric|min:0',
            'expense_unit_id' => 'nullable|exists:expense_units,id',
            'description' => 'required|string|max:1000',
            'expense_category_id' => 'nullable|exists:expense_categories,id',
            'reference_no' => 'nullable|string|max:100',
            'documents' => 'nullable|array',
            'documents.*' => 'nullable|file|max:20480',
        ]);

        $data['amount'] = FinanceDefaults::totalCost($data);

        return $data;
    }

    private function storeDocuments(Request $request, Expense $expense): void
    {
        if (! $request->hasFile('documents')) {
            return;
        }

        foreach ($request->file('documents', []) as $file) {
            if (! $file) {
                continue;
            }

            $expense->storeAttachment($file, $file->getClientOriginalName(), $request->user()?->id);
        }
    }

    /** @return Collection<int, ExpenseCategory> */
    private function categories()
    {
        ExpenseCategory::ensureDefaults();

        return ExpenseCategory::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'code']);
    }
}
