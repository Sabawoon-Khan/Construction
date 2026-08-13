<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AuthorizesResourcePermissions;
use App\Models\Contractor;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Project;
use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class ExpenseController extends Controller implements HasMiddleware
{
    protected static string $permissionModule = 'expenses';

    use AuthorizesResourcePermissions;

    public function index(Request $request): Response
    {
        $query = Expense::query()
            ->with(['category:id,name', 'project:id,name', 'supplier:id,name']);

        if ($request->filled('project_id')) {
            $query->where('project_id', $request->integer('project_id'));
        }

        if ($search = $request->string('search')->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('expense_no', 'like', "%{$search}%")
                    ->orWhere('paid_to', 'like', "%{$search}%")
                    ->orWhere('reference_no', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $totalAmount = (clone $query)->sum('amount');

        return Inertia::render('Expenses/Index', [
            'items' => $query->latest('expense_date')->paginate(15)->withQueryString(),
            'filters' => $request->only(['search', 'project_id']),
            'projects' => Project::query()->orderBy('name')->get(['id', 'name']),
            'totals' => [
                'amount' => $totalAmount,
            ],
        ]);
    }

    public function create(Request $request): Response
    {
        return Inertia::render('Expenses/Form', [
            'item' => null,
            ...$this->formOptions($request),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $this->assertManualCategoryAllowed($data['expense_category_id']);

        Expense::create([
            ...$data,
            'paid_to' => $this->resolvePaidTo($data),
            'expense_no' => $data['expense_no'] ?: 'EXP-'.Str::upper(Str::random(8)),
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Expense recorded.']);

        return redirect()->route('expenses.index');
    }

    public function show(Expense $expense): Response
    {
        $expense->load([
            'project:id,name',
            'category:id,name',
            'supplier:id,name',
            'contractor:id,name',
            'bankAccount:id,name',
        ]);

        return Inertia::render('Expenses/Show', [
            'item' => $expense,
        ]);
    }

    public function edit(Request $request, Expense $expense): Response
    {

        return Inertia::render('Expenses/Form', [
            'item' => $expense,
            ...$this->formOptions($request, $expense),
        ]);
    }

    public function update(Request $request, Expense $expense): RedirectResponse
    {
        $data = $this->validated($request);
        $this->assertManualCategoryAllowed($data['expense_category_id'], $expense->expense_category_id);

        $expense->update([
            ...$data,
            'paid_to' => $this->resolvePaidTo($data),
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Expense updated.']);

        return redirect()->route('expenses.index');
    }

    public function destroy(Request $request, Expense $expense): RedirectResponse
    {
        $expense->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Expense deleted.']);

        return redirect()->route('expenses.index');
    }

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        return $request->validate([
            'project_id' => 'nullable|exists:projects,id',
            'expense_category_id' => 'required|exists:expense_categories,id',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'contractor_id' => 'nullable|exists:contractors,id',
            'bank_account_id' => 'nullable|exists:bank_accounts,id',
            'expense_no' => 'nullable|string|max:50',
            'expense_date' => 'required|date',
            'amount' => 'required|numeric|min:0',
            'currency' => 'required|string|size:3',
            'payment_method' => 'required|string|max:50',
            'reference_no' => 'nullable|string|max:100',
            'status' => 'required|string|max:50',
            'description' => 'nullable|string',
        ]);
    }

    /** @return array<string, mixed> */
    private function formOptions(Request $request, ?Expense $expense = null): array
    {
        $categories = ExpenseCategory::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        $manualCategories = $categories->filter(function ($category) use ($expense) {
            if ($this->isMaterialsCategory($category)) {
                return $expense && $expense->expense_category_id === $category->id;
            }

            return true;
        })->values();

        return [
            'projects' => Project::query()->orderBy('name')->get(['id', 'name']),
            'categories' => $manualCategories,
            'suppliers' => Supplier::query()->orderBy('name')->get(['id', 'name']),
            'contractors' => Contractor::query()->orderBy('name')->get(['id', 'name']),
        ];
    }

    private function isMaterialsCategory(ExpenseCategory $category): bool
    {
        return $category->code === 'materials'
            || strcasecmp($category->name, 'Materials') === 0;
    }

    private function assertManualCategoryAllowed(int $categoryId, ?int $existingCategoryId = null): void
    {
        if ($existingCategoryId === $categoryId) {
            return;
        }

        $category = ExpenseCategory::query()->find($categoryId);

        if ($category && $this->isMaterialsCategory($category)) {
            abort(422, 'Use Purchases for material costs.');
        }
    }

    /** @param array<string, mixed> $data */
    private function resolvePaidTo(array $data): ?string
    {
        if (! empty($data['supplier_id'])) {
            return Supplier::query()->find($data['supplier_id'])?->name;
        }

        if (! empty($data['contractor_id'])) {
            return Contractor::query()->find($data['contractor_id'])?->name;
        }

        return null;
    }
}
