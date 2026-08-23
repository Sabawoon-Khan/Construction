<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AuthorizesResourcePermissions;
use App\Http\Controllers\Concerns\StoresRecordAttachments;
use App\Models\Employee;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\InventoryTransaction;
use App\Models\Material;
use App\Models\Partner;
use App\Models\PartnerDistribution;
use App\Models\PartnerInvestment;
use App\Models\Project;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Sale;
use App\Models\Unit;
use App\Services\CurrencyService;
use App\Services\FinanceDefaults;
use App\Services\PurchaseService;
use App\Services\ShareholderProfitService;
use App\Support\CurrencyAmounts;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ProjectController extends Controller implements HasMiddleware
{
    protected static string $permissionModule = 'projects';

    use AuthorizesResourcePermissions;
    use StoresRecordAttachments;

    public function index(Request $request): Response
    {
        $query = Project::query();
        $query->withCount(['units', 'sales', 'expenses']);

        if ($request->user()?->isRestrictedShareholder()) {
            $query->whereIn('id', $request->user()->assignedProjectIds() ?: [0]);
        }

        if ($search = $request->string('search')->toString()) {
            $query->where(function ($q) use ($search) {
                $q->orWhere('name', 'like', "%{$search}%");
                $q->orWhere('code', 'like', "%{$search}%");
                $q->orWhere('location', 'like', "%{$search}%");
            });
        }

        $ids = (clone $query)->select('id');
        $salesQuery = Sale::query()->whereIn('project_id', clone $ids);
        $totals = [
            'count' => (clone $query)->count(),
            'active' => (clone $query)->whereIn('status', ['planning', 'under_construction', 'ready_for_sale'])->count(),
            'units' => Unit::query()->whereIn('project_id', clone $ids)->count(),
            'sales_total' => Sale::sumTotalPrice(clone $salesQuery),
            'sales_total_base' => Sale::sumTotalPriceBase(clone $salesQuery),
            'sales_by_currency' => Sale::sumTotalPriceByCurrency(clone $salesQuery),
        ];

        return Inertia::render('Projects/Index', [
            'items' => $query->with('creator:id,name')->latest()->paginate(12)->withQueryString(),
            'filters' => ['search' => $search],
            'totals' => $totals,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Projects/Form', [
            'item' => null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'code' => 'required|string|max:50',
            'name' => 'required|string|max:255',
            'type' => 'nullable|string|max:50',
            'location' => 'nullable|string|max:255',
            'start_date' => 'nullable|date',
            'expected_completion_date' => 'nullable|date',
            'estimated_budget' => 'nullable|numeric|min:0',
            'estimated_revenue' => 'nullable|numeric|min:0',
            'progress_percent' => 'nullable|numeric|min:0|max:100',
            'status' => 'required|string|max:50',
            'description' => 'nullable|string',
            ...$this->attachmentValidationRules(),
        ]);
        unset($data['documents']);

        $project = Project::create($this->withProjectDefaults($data));
        $this->storeRecordAttachments($request, $project);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Projects created.']);

        return redirect()->route('projects.index');
    }

    public function show(Request $request, Project $project): Response
    {
        abort_if(
            $request->user()?->isRestrictedShareholder()
                && ! in_array((int) $project->id, $request->user()->assignedProjectIds(), true),
            403,
        );

        $project->loadCount(['units', 'sales', 'expenses', 'incomes']);
        $project->load([
            'attachments',
            'units' => fn ($q) => $q->latest()->limit(8),
            'sales' => fn ($q) => $q->with(['customer:id,name', 'unit:id,list_price,currency'])->latest()->limit(8),
        ]);

        $expensesQuery = Expense::query()
            ->where('project_id', $project->id)
            ->where('status', 'approved');

        $expensesTotal = (float) (clone $expensesQuery)->sum('amount');
        $expensesTotalBase = (float) (clone $expensesQuery)->sum('amount_base');
        $expensesByCurrency = CurrencyAmounts::sum(clone $expensesQuery);
        $salesTotal = Sale::sumTotalPrice($project->sales()->getQuery());
        $salesTotalBase = Sale::sumTotalPriceBase($project->sales()->getQuery());
        $salesByCurrency = Sale::sumTotalPriceByCurrency($project->sales()->getQuery());
        $netProfit = ShareholderProfitService::netProfit(projectId: $project->id);
        $purchasesQuery = Purchase::query()->where('project_id', $project->id);
        $purchasesTotal = (float) (clone $purchasesQuery)->sum('total_amount');
        $purchasesTotalBase = (float) (clone $purchasesQuery)->sum('total_amount_base');
        $purchasesPaid = (float) (clone $purchasesQuery)->sum('paid_amount');
        $purchasesPaidBase = (float) (clone $purchasesQuery)->sum('paid_amount_base');
        $purchasesTotalByCurrency = CurrencyAmounts::sum(clone $purchasesQuery, 'total_amount');
        $purchasesPaidByCurrency = CurrencyAmounts::sum(clone $purchasesQuery, 'paid_amount');
        $purchasesRemainingByCurrency = CurrencyAmounts::remaining($purchasesTotalByCurrency, $purchasesPaidByCurrency);
        $stock = InventoryTransaction::stockLevels($project->id);
        $stockIssues = InventoryTransaction::query()
            ->where('project_id', $project->id)
            ->where('type', InventoryTransaction::TYPE_ISSUE)
            ->with('material:id,name,material_unit_id')
            ->latest('transaction_date')
            ->get(['id', 'material_id', 'quantity', 'transaction_date']);

        $shareholders = ShareholderProfitService::projectPartners($project);

        $assignedIds = $shareholders->pluck('id');
        $projectEmployees = $project->employees()
            ->orderBy('name')
            ->get([
                'employees.id',
                'employees.created_at',
                'employees.employee_no',
                'employees.name',
                'employees.department',
                'employees.designation',
                'employees.phone',
                'employees.basic_salary',
                'employees.currency',
                'employees.status',
            ])
            ->map(fn (Employee $employee) => [
                'id' => $employee->id,
                'created_at' => $employee->created_at,
                'employee_no' => $employee->employee_no,
                'name' => $employee->name,
                'department' => $employee->department,
                'designation' => $employee->designation,
                'phone' => $employee->phone,
                'basic_salary' => $employee->basic_salary,
                'currency' => $employee->currency,
                'status' => $employee->status,
                'started_at' => $employee->pivot?->started_at,
                'role_on_project' => $employee->pivot?->role_on_project,
                'working_hours' => $employee->pivot?->working_hours,
                'notes' => $employee->pivot?->notes,
            ])
            ->values();
        $assignedEmployeeIds = $projectEmployees->pluck('id');

        return Inertia::render('Projects/Show', [
            'item' => $project,
            'stats' => [
                'units_count' => $project->units_count,
                'sales_count' => $project->sales_count,
                'expenses_total' => $expensesTotal,
                'expenses_total_base' => $expensesTotalBase,
                'expenses_by_currency' => $expensesByCurrency,
                'incomes_total' => $project->incomes()->sum('amount'),
                'incomes_total_base' => $project->incomes()->sum('amount_base'),
                'sales_total' => $salesTotal,
                'sales_total_base' => $salesTotalBase,
                'sales_by_currency' => $salesByCurrency,
                'net_profit' => $netProfit,
                'contributions_total' => (float) PartnerInvestment::query()
                    ->where('project_id', $project->id)
                    ->sum('amount'),
                'contributions_total_base' => (float) PartnerInvestment::query()
                    ->where('project_id', $project->id)
                    ->sum('amount_base'),
                'payouts_total' => (float) PartnerDistribution::query()
                    ->where('project_id', $project->id)
                    ->sum('amount'),
                'payouts_total_base' => (float) PartnerDistribution::query()
                    ->where('project_id', $project->id)
                    ->sum('amount_base'),
                'purchases_total' => $purchasesTotal,
                'purchases_total_base' => $purchasesTotalBase,
                'purchases_remaining' => max(0, $purchasesTotal - $purchasesPaid),
                'purchases_remaining_base' => max(0, $purchasesTotalBase - $purchasesPaidBase),
                'purchases_remaining_by_currency' => $purchasesRemainingByCurrency,
            ],
            'shareholders' => $shareholders,
            'available_shareholders' => Partner::query()
                ->orderBy('name')
                ->when($assignedIds->isNotEmpty(), fn ($q) => $q->whereNotIn('id', $assignedIds))
                ->get(['id', 'name', 'phone']),
            'employees' => $projectEmployees,
            'available_employees' => Employee::query()
                ->where('status', 'active')
                ->orderBy('name')
                ->when($assignedEmployeeIds->isNotEmpty(), fn ($q) => $q->whereNotIn('id', $assignedEmployeeIds))
                ->get(['id', 'name', 'employee_no', 'department', 'designation', 'working_hours', 'phone']),
            'contributions' => PartnerInvestment::query()
                ->where('project_id', $project->id)
                ->with('partner:id,name')
                ->latest('invested_at')
                ->limit(50)
                ->get(['id', 'partner_id', 'amount', 'amount_base', 'currency', 'invested_at', 'created_at']),
            'expenses' => $expensesQuery
                ->with(['expenseUnit:id,unit_name', 'category:id,name', 'attachments'])
                ->latest('expense_date')
                ->limit(50)
                ->get([
                    'id',
                    'expense_no',
                    'expense_date',
                    'paid_to',
                    'description',
                    'amount',
                    'amount_base',
                    'currency',
                    'purchase_id',
                    'project_id',
                    'item_amount',
                    'per_price',
                    'reference_no',
                    'payment_method',
                    'expense_unit_id',
                    'expense_category_id',
                    'created_at',
                ]),
            'expense_categories' => $this->expenseCategories(),
            'purchases' => $purchasesQuery
                ->with('supplier:id,name')
                ->latest('purchase_date')
                ->limit(50)
                ->get(['id', 'purchase_no', 'purchase_date', 'supplier_id', 'total_amount', 'total_amount_base', 'paid_amount', 'paid_amount_base', 'currency', 'status', 'created_at']),
            'purchase_materials' => $this->purchaseMaterialsForProject($project),
            'stock' => $stock->map(function ($row) {
                return [
                    'material_id' => (int) $row->material_id,
                    'purchased' => (float) $row->purchased,
                    'issued' => (float) $row->issued,
                    'on_hand' => (float) $row->on_hand,
                    'last_issue_at' => $row->last_issue_at,
                ];
            })->values(),
            'stock_materials' => Material::query()
                ->with('unit:id,unit_name')
                ->whereIn('id', $stock->pluck('material_id')->filter()->all() ?: [0])
                ->get(['id', 'name', 'code', 'material_unit_id']),
            'stock_issues' => $stockIssues,
        ]);
    }

    public function storeExpense(Request $request, Project $project): RedirectResponse
    {
        $request->merge([
            'expense_category_id' => $request->filled('expense_category_id') ? $request->input('expense_category_id') : null,
        ]);

        $data = $request->validate([
            'expense_date' => 'required|date',
            'paid_to' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0.01',
            'currency' => 'nullable|string|size:3',
            'description' => 'required|string|max:1000',
            'expense_category_id' => [
                'nullable',
                'exists:expense_categories,id',
                Rule::notIn(ExpenseCategory::materialIds()),
            ],
            'payment_method' => 'nullable|in:cash,bank',
            'reference_no' => 'nullable|string|max:100',
            'documents' => 'nullable|array',
            'documents.*' => 'nullable|file|max:20480',
        ], [
            'expense_category_id.not_in' => 'Materials must be recorded as a purchase so stock and remaining balance are tracked.',
        ]);
        unset($data['documents']);

        $expense = Expense::create(FinanceDefaults::expenseAttributes($data, $project->id));
        $this->storeRecordAttachments($request, $expense);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Project expense recorded.']);

        return redirect()->to(route('projects.show', $project).'#expenses');
    }

    public function storeShareholder(Request $request, Project $project): RedirectResponse
    {
        $data = $request->validate([
            'partner_id' => 'required|exists:partners,id',
            'ownership_percentage' => 'required|numeric|min:0|max:100',
        ]);

        if ($project->partners()->where('partners.id', $data['partner_id'])->exists()) {
            return back()->withErrors(['partner_id' => 'This shareholder is already on the project.']);
        }

        $project->partners()->attach($data['partner_id'], [
            'ownership_percentage' => $data['ownership_percentage'],
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Shareholder added to project.']);

        return redirect()->to(route('projects.show', $project).'#shareholders');
    }

    public function storeEmployee(Request $request, Project $project): RedirectResponse
    {
        $data = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'started_at' => 'required|date',
            'role_on_project' => 'nullable|string|max:100',
            'working_hours' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
        ]);

        if ($project->employees()->where('employees.id', $data['employee_id'])->exists()) {
            return back()->withErrors(['employee_id' => 'This employee is already on the project.']);
        }

        $employee = Employee::query()->findOrFail($data['employee_id']);

        $project->employees()->attach($data['employee_id'], [
            'started_at' => $data['started_at'],
            'role_on_project' => $data['role_on_project'] ?: $employee->designation,
            'working_hours' => $data['working_hours'] ?: $employee->working_hours,
            'notes' => $data['notes'] ?? null,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Employee added to project.']);

        return redirect()->to(route('projects.show', $project).'#employees');
    }

    public function destroyEmployee(Project $project, Employee $employee): RedirectResponse
    {
        abort_unless($project->employees()->where('employees.id', $employee->id)->exists(), 404);

        $project->employees()->detach($employee->id);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Employee removed from project.']);

        return redirect()->to(route('projects.show', $project).'#employees');
    }

    public function storeShareholderContribution(Request $request, Project $project, Partner $partner): RedirectResponse
    {
        abort_unless($project->partners()->where('partners.id', $partner->id)->exists(), 404);

        $data = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'currency' => 'nullable|string|size:3',
            'invested_at' => 'required|date',
            'reference_no' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
        ]);

        $currency = strtoupper($data['currency'] ?? CurrencyService::baseCurrency());
        $exchangeRate = FinanceDefaults::exchangeRate($currency, Carbon::parse($data['invested_at']));
        $amount = round((float) $data['amount'], 2);

        PartnerInvestment::create([
            'project_id' => $project->id,
            'partner_id' => $partner->id,
            'amount' => $amount,
            'amount_base' => round($amount * $exchangeRate, 2),
            'currency' => $currency,
            'exchange_rate' => $exchangeRate,
            'agreed_share_percentage' => $partner->ownershipOnProject($project->id),
            'invested_at' => $data['invested_at'],
            'type' => 'investment',
            'status' => 'approved',
            'reference_no' => $data['reference_no'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Contribution recorded.']);

        return redirect()->to(route('projects.show', $project).'#shareholders');
    }

    public function showContribution(Project $project, PartnerInvestment $investment): Response
    {
        abort_unless($investment->project_id === $project->id, 404);

        $investment->load(['partner:id,name', 'project:id,name,code']);

        return Inertia::render('Projects/ContributionVoucher', [
            'item' => $investment,
            'project' => $project->only(['id', 'name', 'code']),
        ]);
    }

    public function printExpenses(Request $request, Project $project): Response
    {
        abort_if(
            $request->user()?->isRestrictedShareholder()
                && ! in_array((int) $project->id, $request->user()->assignedProjectIds(), true),
            403,
        );

        $expensesQuery = Expense::query()
            ->where('project_id', $project->id)
            ->where('status', 'approved');

        $category = null;

        if ($request->filled('category_id')) {
            $categoryId = $request->input('category_id');

            if ($categoryId === 'uncategorized') {
                $expensesQuery->whereNull('expense_category_id');
                $category = ['id' => null, 'name' => __('General')];
            } else {
                $categoryModel = ExpenseCategory::query()->find($request->integer('category_id'), ['id', 'name']);
                abort_unless($categoryModel, 404);
                $expensesQuery->where('expense_category_id', $categoryModel->id);
                $category = $categoryModel->only(['id', 'name']);
            }
        }

        $expenses = (clone $expensesQuery)
            ->with(['category:id,name', 'expenseUnit:id,unit_name'])
            ->orderBy('expense_date')
            ->orderBy('id')
            ->get([
                'id',
                'expense_no',
                'expense_date',
                'paid_to',
                'description',
                'amount',
                'amount_base',
                'currency',
                'purchase_id',
                'reference_no',
                'payment_method',
                'expense_category_id',
                'expense_unit_id',
                'created_at',
            ]);

        return Inertia::render('Projects/ExpensesPrint', [
            'item' => $project->only(['id', 'name', 'code', 'location']),
            'category' => $category,
            'expenses' => $expenses,
            'summary' => [
                'count' => $expenses->count(),
                'total' => (float) (clone $expensesQuery)->sum('amount'),
                'by_currency' => CurrencyAmounts::sum(clone $expensesQuery),
            ],
        ]);
    }

    public function storeShareholderPayout(Request $request, Project $project, Partner $partner): RedirectResponse
    {
        abort_unless($project->partners()->where('partners.id', $partner->id)->exists(), 404);

        $data = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'currency' => 'nullable|string|size:3',
            'distributed_at' => 'required|date',
            'reference_no' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
        ]);

        $currency = strtoupper($data['currency'] ?? CurrencyService::baseCurrency());
        $exchangeRate = FinanceDefaults::exchangeRate($currency, Carbon::parse($data['distributed_at']));
        $amount = round((float) $data['amount'], 2);

        PartnerDistribution::create([
            'project_id' => $project->id,
            'partner_id' => $partner->id,
            'amount' => $amount,
            'amount_base' => round($amount * $exchangeRate, 2),
            'currency' => $currency,
            'exchange_rate' => $exchangeRate,
            'type' => 'profit',
            'distributed_at' => $data['distributed_at'],
            'reference_no' => $data['reference_no'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Payout recorded.']);

        return redirect()->to(route('projects.show', $project).'#shareholders');
    }

    public function payPurchaseRemaining(Request $request, Project $project, Purchase $purchase): RedirectResponse
    {
        abort_unless($purchase->project_id === $project->id, 404);

        $data = $request->validate([
            'payment_method' => 'nullable|string|max:50',
            'reference_no' => 'nullable|string|max:100',
            'paid_at' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        $remaining = (float) $purchase->remaining_amount;

        if ($remaining <= 0) {
            return back()->withErrors(['amount' => 'This purchase has no remaining balance.']);
        }

        PurchaseService::recordPayment($purchase, $remaining, [
            'payment_method' => $data['payment_method'] ?? 'cash',
            'reference_no' => $data['reference_no'] ?? null,
            'notes' => $data['notes'] ?? null,
            'paid_at' => $data['paid_at'] ?? now(),
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Supplier payment recorded.']);

        return redirect()->route('projects.show', $project);
    }

    public function edit(Request $request, Project $project): Response
    {
        $project->load('attachments');

        return Inertia::render('Projects/Form', [
            'item' => $project,
        ]);
    }

    public function update(Request $request, Project $project): RedirectResponse
    {
        $data = $request->validate([
            'code' => 'required|string|max:50',
            'name' => 'required|string|max:255',
            'type' => 'nullable|string|max:50',
            'location' => 'nullable|string|max:255',
            'start_date' => 'nullable|date',
            'expected_completion_date' => 'nullable|date',
            'estimated_budget' => 'nullable|numeric|min:0',
            'estimated_revenue' => 'nullable|numeric|min:0',
            'progress_percent' => 'nullable|numeric|min:0|max:100',
            'status' => 'required|string|max:50',
            'description' => 'nullable|string',
            ...$this->attachmentValidationRules(),
        ]);
        unset($data['documents']);

        $project->update($this->withProjectDefaults($data));
        $this->storeRecordAttachments($request, $project);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Projects updated.']);

        return redirect()->route('projects.index');
    }

    public function destroy(Request $request, Project $project): RedirectResponse
    {
        $project->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Projects deleted.']);

        return redirect()->route('projects.index');
    }

    /**
     * Empty form fields arrive as null and would overwrite DB defaults.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function withProjectDefaults(array $data): array
    {
        $data['type'] = $data['type'] ?? 'apartment';
        $data['status'] = $data['status'] ?? 'planning';
        $data['estimated_budget'] = $data['estimated_budget'] ?? 0;
        $data['estimated_revenue'] = $data['estimated_revenue'] ?? 0;
        $data['progress_percent'] = $data['progress_percent'] ?? 0;

        return $data;
    }

    /** @return Collection<int, ExpenseCategory> */
    private function expenseCategories()
    {
        return ExpenseCategory::nonMaterialOptions();
    }

    /**
     * @return list<array{
     *     material_id: int,
     *     name: string,
     *     code: ?string,
     *     unit: ?string,
     *     purchase_count: int,
     *     quantity: float,
     *     total_cost_base: float,
     *     total_cost_by_currency: array<string, float>,
     *     lines: list<array<string, mixed>>
     * }>
     */
    private function purchaseMaterialsForProject(Project $project): array
    {
        $items = PurchaseItem::query()
            ->select('purchase_items.*')
            ->join('purchases', 'purchases.id', '=', 'purchase_items.purchase_id')
            ->where('purchases.project_id', $project->id)
            ->whereNull('purchases.deleted_at')
            ->with([
                'material:id,name,code,material_unit_id',
                'material.unit:id,unit_name',
                'purchase:id,purchase_no,purchase_date,supplier_id,currency,status',
                'purchase.supplier:id,name',
            ])
            ->orderByDesc('purchases.purchase_date')
            ->orderByDesc('purchase_items.id')
            ->get();

        return $items
            ->groupBy('material_id')
            ->map(function (Collection $lines) {
                $material = $lines->first()?->material;
                $byCurrency = CurrencyAmounts::fromRows(
                    $lines->map(fn (PurchaseItem $line) => (object) [
                        'code' => $line->purchase?->currency,
                        'total' => $line->total_cost,
                    ]),
                );

                return [
                    'material_id' => (int) $lines->first()->material_id,
                    'name' => $material?->name ?? '—',
                    'code' => $material?->code,
                    'unit' => $material?->unit?->unit_name,
                    'purchase_count' => $lines->pluck('purchase_id')->unique()->count(),
                    'quantity' => round((float) $lines->sum('quantity'), 3),
                    'total_cost_base' => round((float) $lines->sum('total_cost_base'), 2),
                    'total_cost_by_currency' => $byCurrency,
                    'lines' => $lines->map(fn (PurchaseItem $line) => [
                        'id' => $line->id,
                        'purchase_id' => $line->purchase_id,
                        'purchase_no' => $line->purchase?->purchase_no,
                        'purchase_date' => optional($line->purchase?->purchase_date)?->toDateString(),
                        'supplier' => $line->purchase?->supplier?->name,
                        'quantity' => (float) $line->quantity,
                        'unit_cost' => (float) $line->unit_cost,
                        'total_cost' => (float) $line->total_cost,
                        'currency' => $line->purchase?->currency,
                        'status' => $line->purchase?->status,
                    ])->values()->all(),
                ];
            })
            ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
            ->values()
            ->all();
    }
}
