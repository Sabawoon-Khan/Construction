<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AuthorizesResourcePermissions;
use Illuminate\Routing\Controllers\HasMiddleware;
use App\Models\ExpenseCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ExpenseCategoryController extends Controller implements HasMiddleware
{
    protected static string $permissionModule = 'expense_categories';

    use AuthorizesResourcePermissions;


    public function index(Request $request): Response
    {
        $query = ExpenseCategory::query();

        if ($search = $request->string('search')->toString()) {
            $query->where(function ($q) use ($search) {
                $q->orWhere('name', 'like', "%{$search}%");
                $q->orWhere('code', 'like', "%{$search}%");
            });
        }

        return Inertia::render('ExpenseCategories/Index', [
            'items' => $query->latest()->paginate(12)->withQueryString(),
            'filters' => ['search' => $search],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('ExpenseCategories/Form', [
            'item' => null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        ExpenseCategory::create(array_merge(['is_active' => true], $data));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Expense Categories created.']);

        return redirect()->route('expense-categories.index');
    }

    public function show(ExpenseCategory $expenseCategory): Response
    {
        $expenseCategory->load([
            'expenses' => fn ($q) => $q->with('project:id,name')->latest('expense_date')->limit(10),
        ]);

        return Inertia::render('ExpenseCategories/Show', [
            'item' => $expenseCategory,
            'stats' => [
                'expenses_total' => $expenseCategory->expenses()->sum('amount'),
                'expenses_count' => $expenseCategory->expenses()->count(),
            ],
        ]);
    }

    public function edit(Request $request, ExpenseCategory $expenseCategory): Response
    {

        return Inertia::render('ExpenseCategories/Form', [
            'item' => $expenseCategory,
        ]);
    }

    public function update(Request $request, ExpenseCategory $expenseCategory): RedirectResponse
    {

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $expenseCategory->update($data);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Expense Categories updated.']);

        return redirect()->route('expense-categories.index');
    }

    public function destroy(Request $request, ExpenseCategory $expenseCategory): RedirectResponse
    {
        $expenseCategory->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Expense Categories deleted.']);

        return redirect()->route('expense-categories.index');
    }
}
