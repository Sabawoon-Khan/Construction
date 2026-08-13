<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AuthorizesResourcePermissions;
use Illuminate\Routing\Controllers\HasMiddleware;
use App\Models\IncomeCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class IncomeCategoryController extends Controller implements HasMiddleware
{
    protected static string $permissionModule = 'income_categories';

    use AuthorizesResourcePermissions;


    public function index(Request $request): Response
    {
        $query = IncomeCategory::query();

        if ($search = $request->string('search')->toString()) {
            $query->where(function ($q) use ($search) {
                $q->orWhere('name', 'like', "%{$search}%");
                $q->orWhere('code', 'like', "%{$search}%");
            });
        }

        return Inertia::render('IncomeCategories/Index', [
            'items' => $query->latest()->paginate(12)->withQueryString(),
            'filters' => ['search' => $search],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('IncomeCategories/Form', [
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

        IncomeCategory::create(array_merge(['is_active' => true], $data));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Income Categories created.']);

        return redirect()->route('income-categories.index');
    }

    public function show(IncomeCategory $incomeCategory): Response
    {
        $incomeCategory->load([
            'incomes' => fn ($q) => $q->with('project:id,name')->latest('income_date')->limit(10),
        ]);

        return Inertia::render('IncomeCategories/Show', [
            'item' => $incomeCategory,
            'stats' => [
                'incomes_total' => $incomeCategory->incomes()->sum('amount'),
                'incomes_count' => $incomeCategory->incomes()->count(),
            ],
        ]);
    }

    public function edit(Request $request, IncomeCategory $incomeCategory): Response
    {

        return Inertia::render('IncomeCategories/Form', [
            'item' => $incomeCategory,
        ]);
    }

    public function update(Request $request, IncomeCategory $incomeCategory): RedirectResponse
    {

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $incomeCategory->update($data);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Income Categories updated.']);

        return redirect()->route('income-categories.index');
    }

    public function destroy(Request $request, IncomeCategory $incomeCategory): RedirectResponse
    {
        $incomeCategory->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Income Categories deleted.']);

        return redirect()->route('income-categories.index');
    }
}
