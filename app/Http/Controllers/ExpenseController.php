<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AuthorizesResourcePermissions;
use Illuminate\Routing\Controllers\HasMiddleware;
use App\Models\Contractor;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Project;
use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

        Expense::create([
            ...$data,
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
            ...$this->formOptions($request),
        ]);
    }

    public function update(Request $request, Expense $expense): RedirectResponse
    {
        $expense->update($this->validated($request));

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
            'paid_to' => 'nullable|string|max:255',
            'amount' => 'required|numeric|min:0',
            'currency' => 'required|string|size:3',
            'payment_method' => 'required|string|max:50',
            'reference_no' => 'nullable|string|max:100',
            'status' => 'required|string|max:50',
            'description' => 'nullable|string',
        ]);
    }

    /** @return array<string, mixed> */
    private function formOptions(Request $request): array
    {

        return [
            'projects' => Project::query()->orderBy('name')->get(['id', 'name']),
            'categories' => ExpenseCategory::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'suppliers' => Supplier::query()->orderBy('name')->get(['id', 'name']),
            'contractors' => Contractor::query()->orderBy('name')->get(['id', 'name']),
        ];
    }
}
