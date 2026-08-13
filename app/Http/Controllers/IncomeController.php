<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AuthorizesResourcePermissions;
use Illuminate\Routing\Controllers\HasMiddleware;
use App\Models\Customer;
use App\Models\Income;
use App\Models\IncomeCategory;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class IncomeController extends Controller implements HasMiddleware
{
    protected static string $permissionModule = 'incomes';

    use AuthorizesResourcePermissions;


    public function index(Request $request): Response
    {
        $query = Income::query()
            ->with(['category:id,name', 'project:id,name', 'customer:id,name']);

        if ($search = $request->string('search')->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('income_no', 'like', "%{$search}%")
                    ->orWhere('received_from', 'like', "%{$search}%")
                    ->orWhere('reference_no', 'like', "%{$search}%");
            });
        }

        $totalAmount = (clone $query)->sum('amount');

        return Inertia::render('Incomes/Index', [
            'items' => $query->latest('income_date')->paginate(15)->withQueryString(),
            'filters' => ['search' => $search],
            'totals' => ['amount' => $totalAmount],
        ]);
    }

    public function create(Request $request): Response
    {
        return Inertia::render('Incomes/Form', [
            'item' => null,
            ...$this->formOptions($request),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        Income::create([
            ...$data,
            'income_no' => $data['income_no'] ?: 'INC-'.Str::upper(Str::random(8)),
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Income recorded.']);

        return redirect()->route('incomes.index');
    }

    public function show(Income $income): Response
    {
        $income->load([
            'project:id,name',
            'category:id,name',
            'customer:id,name',
            'bankAccount:id,name',
        ]);

        return Inertia::render('Incomes/Show', [
            'item' => $income,
        ]);
    }

    public function edit(Request $request, Income $income): Response
    {

        return Inertia::render('Incomes/Form', [
            'item' => $income,
            ...$this->formOptions($request),
        ]);
    }

    public function update(Request $request, Income $income): RedirectResponse
    {
        $income->update($this->validated($request));

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
            'project_id' => 'nullable|exists:projects,id',
            'income_category_id' => 'required|exists:income_categories,id',
            'customer_id' => 'nullable|exists:customers,id',
            'bank_account_id' => 'nullable|exists:bank_accounts,id',
            'income_no' => 'nullable|string|max:50',
            'income_date' => 'required|date',
            'received_from' => 'nullable|string|max:255',
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
            'categories' => IncomeCategory::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'customers' => Customer::query()->orderBy('name')->get(['id', 'name']),
        ];
    }
}
