<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AuthorizesResourcePermissions;
use App\Models\ExpenseCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ExpenseCategoryController extends Controller implements HasMiddleware
{
    protected static string $permissionModule = 'expenses';

    use AuthorizesResourcePermissions;

    public function index(Request $request): Response
    {
        $search = $request->string('search')->toString();

        ExpenseCategory::ensureDefaults();

        $query = ExpenseCategory::query()
            ->with('creator:id,name')
            ->withCount('expenses')
            ->whereNotIn('id', ExpenseCategory::materialIds())
            ->orderBy('name');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        $items = $query->get(['id', 'name', 'code', 'description', 'is_active', 'user_id', 'created_at']);
        $used = $items->filter(fn ($item) => (int) ($item->expenses_count ?? 0) > 0)->count();
        $active = $items->filter(fn ($item) => (bool) ($item->is_active ?? true))->count();
        $count = $items->count();
        $expensesLinked = (int) $items->sum('expenses_count');

        return Inertia::render('ExpenseCategories/Index', [
            'items' => $items,
            'filters' => ['search' => $search],
            'totals' => [
                'count' => $count,
                'active' => $active,
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
                    'label' => Str::limit((string) $item->name, 8, ''),
                    'value' => (int) ($item->expenses_count ?? 0),
                ])
                ->all(),
        ]);
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $category = ExpenseCategory::query()->create($this->validated($request));

        if ($request->wantsJson()) {
            return response()->json([
                'id' => $category->id,
                'name' => $category->name,
                'code' => $category->code,
            ]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Category created.']);

        return redirect()->route('expense-categories.index');
    }

    public function update(Request $request, ExpenseCategory $expenseCategory): RedirectResponse
    {
        abort_if(in_array($expenseCategory->id, ExpenseCategory::materialIds(), true), 403);

        $expenseCategory->update($this->validated($request, $expenseCategory->id));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Category updated.']);

        return redirect()->route('expense-categories.index');
    }

    public function destroy(ExpenseCategory $expenseCategory): RedirectResponse
    {
        abort_if(in_array($expenseCategory->id, ExpenseCategory::materialIds(), true), 403);

        if ($expenseCategory->expenses()->exists()) {
            Inertia::flash('toast', ['type' => 'error', 'message' => 'Category is in use and cannot be deleted.']);

            return redirect()->route('expense-categories.index');
        }

        $expenseCategory->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Category deleted.']);

        return redirect()->route('expense-categories.index');
    }

    /** @return array<string, mixed> */
    private function validated(Request $request, ?int $ignoreId = null): array
    {
        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('expense_categories', 'name')->ignore($ignoreId),
            ],
            'description' => 'nullable|string|max:500',
            'is_active' => 'nullable|boolean',
        ]);

        if (! $ignoreId) {
            $data['code'] = $this->uniqueCode($data['name']);
            $data['is_active'] = true;
        }

        return $data;
    }

    private function uniqueCode(string $name): string
    {
        $base = Str::slug($name, '_') ?: 'category';
        $code = $base;
        $suffix = 1;

        while (
            ExpenseCategory::query()
                ->where('code', $code)
                ->exists()
        ) {
            $code = $base.'_'.$suffix;
            $suffix++;
        }

        return $code;
    }
}
