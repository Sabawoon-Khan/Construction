<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AuthorizesResourcePermissions;
use App\Models\Expense;
use App\Services\FinanceDefaults;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
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
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $totalAmount = (float) (clone $query)->sum('amount');

        return Inertia::render('Expenses/Index', [
            'items' => $query->latest('expense_date')->paginate(15)->withQueryString(),
            'filters' => ['search' => $search],
            'totals' => ['amount' => $totalAmount],
        ]);
    }

    public function create(Request $request): Response
    {
        return Inertia::render('Expenses/Form', ['item' => null]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        Expense::create(FinanceDefaults::expenseAttributes($data));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Expense recorded.']);

        return redirect()->route('expenses.index');
    }

    public function show(Expense $expense): Response
    {
        $expense->load(['project:id,name']);

        return Inertia::render('Expenses/Show', [
            'item' => $expense,
        ]);
    }

    public function edit(Request $request, Expense $expense): Response|RedirectResponse
    {
        if ($expense->project_id) {
            return redirect()->route('projects.show', $expense->project_id);
        }

        return Inertia::render('Expenses/Form', ['item' => $expense]);
    }

    public function update(Request $request, Expense $expense): RedirectResponse
    {
        if ($expense->project_id) {
            return redirect()->route('projects.show', $expense->project_id);
        }

        $expense->update($this->validated($request));

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
        return $request->validate([
            'expense_date' => 'required|date',
            'amount' => 'required|numeric|min:0',
            'description' => 'required|string|max:1000',
        ]);
    }
}
