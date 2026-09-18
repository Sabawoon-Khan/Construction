<?php

namespace App\Http\Controllers;

use App\Concerns\ExportsSpreadsheet;
use App\Http\Controllers\Concerns\AuthorizesResourcePermissions;
use App\Http\Controllers\Concerns\ResolvesExportScope;
use App\Http\Controllers\Concerns\StoresRecordAttachments;
use App\Models\Employee;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Income;
use App\Models\InventoryTransaction;
use App\Models\Loan;
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
use App\Services\LoanService;
use App\Services\PurchaseService;
use App\Services\ShareholderProfitService;
use App\Support\CurrencyAmounts;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProjectController extends Controller implements HasMiddleware
{
    protected static string $permissionModule = 'projects';

    use AuthorizesResourcePermissions;
    use ExportsSpreadsheet;
    use ResolvesExportScope;
    use StoresRecordAttachments;

    public function index(Request $request): Response
    {
        $search = $request->string('search')->toString();
        $status = $request->string('status')->toString();
        $allowedStatuses = [
            'planning',
            'under_construction',
            'ready_for_sale',
            'sold',
            'completed',
            'on_hold',
        ];
        if ($status !== '' && ! in_array($status, $allowedStatuses, true)) {
            $status = '';
        }

        $query = $this->indexQuery($request);

        $ids = (clone $query)->select('id');
        $salesQuery = Sale::query()->whereIn('project_id', clone $ids);
        $unitStatusCounts = Unit::query()
            ->whereIn('project_id', clone $ids)
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $unitsAvailable = (int) ($unitStatusCounts['available'] ?? 0);
        $unitsReserved = (int) ($unitStatusCounts['reserved'] ?? 0);
        $unitsSold = (int) ($unitStatusCounts['sold'] ?? 0);
        $unitsTotal = $unitsAvailable + $unitsReserved + $unitsSold;

        $totals = [
            'count' => (clone $query)->count(),
            'active' => (clone $query)->whereIn('status', ['planning', 'under_construction', 'ready_for_sale'])->count(),
            'units' => $unitsTotal > 0
                ? $unitsTotal
                : Unit::query()->whereIn('project_id', clone $ids)->count(),
            'units_available' => $unitsAvailable,
            'units_reserved' => $unitsReserved,
            'units_sold' => $unitsSold,
            'sales_total' => Sale::sumTotalPrice(clone $salesQuery),
            'sales_total_base' => Sale::sumTotalPriceBase(clone $salesQuery),
            'sales_by_currency' => Sale::sumTotalPriceByCurrency(clone $salesQuery),
        ];

        return Inertia::render('Projects/Index', [
            'items' => $query->with('creator:id,name')->latest()->paginate(10)->withQueryString(),
            'filters' => [
                'search' => $search,
                'status' => $status,
            ],
            'totals' => $totals,
            'moneyChart' => $this->salesByMonth(clone $salesQuery),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        abort_unless(config('kit.features.exports'), 404);

        $rows = $this->indexQuery($request, $this->exportAppliesFilters($request))
            ->with('creator:id,name')
            ->latest()
            ->lazyById(500)
            ->map(fn (Project $project) => [
                $project->code,
                $project->name,
                $project->location,
                $project->status,
                $project->progress_percent,
                $project->units_count,
                $project->creator?->name,
                $project->created_at?->toDateTimeString(),
            ]);

        return $this->streamExport(
            'projects',
            [
                'code', 'name', 'location', 'status', 'progress_percent',
                'units', 'added_by', 'created_at',
            ],
            $rows,
            $this->exportFormat($request),
        );
    }

    protected function indexQuery(Request $request, bool $applyFilters = true): Builder
    {
        $query = Project::query()->withCount(['units', 'sales', 'expenses']);

        if ($request->user()?->isRestrictedShareholder()) {
            $query->whereIn('id', $request->user()->assignedProjectIds() ?: [0]);
        }

        if (! $applyFilters) {
            return $query;
        }

        if ($search = $request->string('search')->toString()) {
            $query->where(function ($q) use ($search) {
                $q->orWhere('name', 'like', "%{$search}%");
                $q->orWhere('code', 'like', "%{$search}%");
                $q->orWhere('location', 'like', "%{$search}%");
            });
        }

        $status = $request->string('status')->toString();
        $allowedStatuses = [
            'planning',
            'under_construction',
            'ready_for_sale',
            'sold',
            'completed',
            'on_hold',
        ];
        if ($status !== '' && in_array($status, $allowedStatuses, true)) {
            $query->where('status', $status);
        }

        return $query;
    }

    public function create(): Response
    {
        return Inertia::render('Projects/Form', [
            'item' => null,
            'suggestedCode' => $this->nextProjectCode(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate(
            $this->projectValidationRules(),
            $this->projectValidationMessages(),
        );
        unset($data['documents']);

        try {
            $project = Project::create($this->withProjectDefaults($data));
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages([
                'code' => __('This project code is already in use.'),
            ]);
        }

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
        $incomesQuery = $project->incomes()->getQuery()->where('status', 'approved');
        $incomesByCurrency = CurrencyAmounts::sum(clone $incomesQuery);
        $revenueByCurrency = CurrencyAmounts::add($salesByCurrency, $incomesByCurrency);
        $netProfit = ShareholderProfitService::netProfit(projectId: $project->id);
        $netProfitByCurrency = CurrencyAmounts::net($revenueByCurrency, $expensesByCurrency);
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

        $unitStatusCounts = Unit::query()
            ->where('project_id', $project->id)
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');
        $unitsAvailable = (int) ($unitStatusCounts['available'] ?? 0);
        $unitsReserved = (int) ($unitStatusCounts['reserved'] ?? 0);
        $unitsSold = (int) ($unitStatusCounts['sold'] ?? 0);

        $loansQuery = Loan::query()->where('project_id', $project->id);
        $loansPrincipalBase = (float) (clone $loansQuery)->sum('principal_base');
        $loansPaidBase = (float) (clone $loansQuery)->sum('paid_amount_base');
        $loansRemainingBase = max(0, $loansPrincipalBase - $loansPaidBase);
        $loansPrincipalByCurrency = CurrencyAmounts::sum(clone $loansQuery, 'principal');
        $loansPaidByCurrency = CurrencyAmounts::sum(clone $loansQuery, 'paid_amount');
        $loansRemainingByCurrency = CurrencyAmounts::remaining($loansPrincipalByCurrency, $loansPaidByCurrency);

        $incomesTotalBase = (float) (clone $incomesQuery)->sum('amount_base');
        $revenueTotalBase = $salesTotalBase + $incomesTotalBase;
        $estimatedBudget = (float) ($project->estimated_budget ?? 0);
        $budgetUsedPercent = $estimatedBudget > 0
            ? round(min(999, ($expensesTotalBase / $estimatedBudget) * 100), 1)
            : null;
        $marginPercent = $revenueTotalBase > 0
            ? round(($netProfit / $revenueTotalBase) * 100, 1)
            : null;

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
                'units_available' => $unitsAvailable,
                'units_reserved' => $unitsReserved,
                'units_sold' => $unitsSold,
                'sales_count' => $project->sales_count,
                'expenses_total' => $expensesTotal,
                'expenses_total_base' => $expensesTotalBase,
                'expenses_by_currency' => $expensesByCurrency,
                'incomes_total' => (float) (clone $incomesQuery)->sum('amount'),
                'incomes_total_base' => $incomesTotalBase,
                'incomes_by_currency' => $incomesByCurrency,
                'sales_total' => $salesTotal,
                'sales_total_base' => $salesTotalBase,
                'sales_by_currency' => $salesByCurrency,
                'net_profit' => $netProfit,
                'net_profit_by_currency' => $netProfitByCurrency,
                'margin_percent' => $marginPercent,
                'budget_used_percent' => $budgetUsedPercent,
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
                'loans_remaining' => $loansRemainingBase,
                'loans_remaining_base' => $loansRemainingBase,
                'loans_remaining_by_currency' => $loansRemainingByCurrency,
                'charts' => $this->projectChartSeries($project),
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
            'expense_units' => \App\Models\ExpenseUnit::options(),
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
            'loans' => Loan::query()
                ->where('project_id', $project->id)
                ->with(['payments' => fn ($q) => $q->latest('paid_at')->limit(20)])
                ->latest('loan_date')
                ->get([
                    'id',
                    'loan_no',
                    'lender_name',
                    'loan_date',
                    'due_date',
                    'principal',
                    'principal_base',
                    'paid_amount',
                    'paid_amount_base',
                    'currency',
                    'status',
                    'reference_no',
                    'notes',
                    'created_at',
                ]),
        ]);
    }

    public function storeExpense(Request $request, Project $project): RedirectResponse
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
            'currency' => 'nullable|string|size:3',
            'description' => 'required|string|max:1000',
            'expense_category_id' => [
                'nullable',
                'exists:expense_categories,id',
                Rule::notIn(ExpenseCategory::materialIds()),
            ],
            'expense_unit_id' => 'nullable|exists:expense_units,id',
            'payment_method' => 'nullable|in:cash,bank',
            'reference_no' => 'nullable|string|max:100',
            'documents' => 'nullable|array',
            'documents.*' => 'nullable|file|max:20480',
        ], [
            'expense_category_id.not_in' => 'Materials must be recorded as a purchase so stock and remaining balance are tracked.',
        ]);
        unset($data['documents']);

        if (! empty($data['item_amount']) && isset($data['per_price'])) {
            $data['amount'] = FinanceDefaults::totalCost($data);
        }

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

        return redirect()->to(route('projects.show', $project).'#money-in');
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

    public function storeLoan(Request $request, Project $project): RedirectResponse
    {
        $data = $request->validate([
            'lender_name' => 'required|string|max:255',
            'loan_date' => 'required|date',
            'due_date' => 'nullable|date|after_or_equal:loan_date',
            'principal' => 'required|numeric|min:0.01',
            'currency' => 'nullable|string|size:3',
            'reference_no' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:1000',
        ]);

        LoanService::create($project->id, $data);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Loan recorded.']);

        return redirect()->to(route('projects.show', $project).'#loans');
    }

    public function payLoan(Request $request, Project $project, Loan $loan): RedirectResponse
    {
        abort_unless($loan->project_id === $project->id, 404);

        $data = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'nullable|string|max:50',
            'reference_no' => 'nullable|string|max:100',
            'paid_at' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        LoanService::recordPayment($loan, (float) $data['amount'], [
            'payment_method' => $data['payment_method'] ?? 'cash',
            'reference_no' => $data['reference_no'] ?? null,
            'notes' => $data['notes'] ?? null,
            'paid_at' => $data['paid_at'] ?? now(),
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Loan payout recorded.']);

        return redirect()->to(route('projects.show', $project).'#loans');
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
        $data = $request->validate(
            $this->projectValidationRules($project->id),
            $this->projectValidationMessages(),
        );
        unset($data['documents']);

        try {
            $project->update($this->withProjectDefaults($data));
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages([
                'code' => __('This project code is already in use.'),
            ]);
        }

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

    /** @return array<string, string> */
    private function projectValidationMessages(): array
    {
        return [
            'code.unique' => __('This project code is already in use.'),
        ];
    }

    /** @return array<string, mixed> */
    private function projectValidationRules(?int $ignoreId = null): array
    {
        return [
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('projects', 'code')->ignore($ignoreId),
            ],
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
        ];
    }

    private function nextProjectCode(): string
    {
        $max = Project::withTrashed()
            ->pluck('code')
            ->map(fn (string $code): ?int => is_numeric($code) ? (int) $code : null)
            ->filter(fn (?int $code): bool => $code !== null && $code > 0)
            ->max();

        return (string) (($max ?? 0) + 1);
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
     * @return array{
     *     monthly: array{labels: list<string>, sales: list<float>, expenses: list<float>, income: list<float>, purchases: list<float>},
     *     expenses_by_category: array{labels: list<string>, amounts: list<float>}
     * }
     */
    private function projectChartSeries(Project $project): array
    {
        $to = now()->endOfDay();
        $from = $project->start_date
            ? Carbon::parse($project->start_date)->startOfDay()
            : $to->copy()->subMonths(11)->startOfMonth();

        if ($from->gt($to)) {
            $from = $to->copy()->subMonths(11)->startOfMonth();
        }

        $monthly = $this->projectMonthlySeries($project->id, $from, $to);
        $byCategory = $this->projectExpensesByCategory($project->id);

        return [
            'monthly' => $monthly,
            'expenses_by_category' => $byCategory,
        ];
    }

    /**
     * @return array{labels: list<string>, sales: list<float>, expenses: list<float>, income: list<float>, purchases: list<float>}
     */
    private function projectMonthlySeries(int $projectId, CarbonInterface $from, CarbonInterface $to): array
    {
        $chartFrom = $from->copy()->startOfMonth();
        $chartTo = $to->copy()->endOfMonth();

        if ($chartFrom->diffInMonths($chartTo) < 1) {
            $chartFrom = $to->copy()->subMonths(5)->startOfMonth();
            $chartTo = $to->copy()->endOfMonth();
        }

        $useYearly = $chartFrom->diffInMonths($chartTo) >= 18;
        if ($useYearly && $chartFrom->diffInYears($chartTo) > 8) {
            $chartFrom = $to->copy()->subYears(7)->startOfYear();
            $chartTo = $to->copy()->endOfYear();
        }

        $buckets = [];
        $cursor = $useYearly ? $chartFrom->copy()->startOfYear() : $chartFrom->copy();
        while ($cursor->lte($chartTo)) {
            $key = $useYearly ? $cursor->format('Y') : $cursor->format('Y-m');
            $buckets[$key] = [
                'label' => $useYearly ? $cursor->format('Y') : $cursor->format('M Y'),
                'sales' => 0.0,
                'expenses' => 0.0,
                'income' => 0.0,
                'purchases' => 0.0,
            ];
            $cursor = $useYearly ? $cursor->addYear() : $cursor->addMonth();
        }

        $sales = Sale::query()
            ->with(['unit:id,list_price_base'])
            ->where('project_id', $projectId)
            ->where('status', '!=', 'cancelled')
            ->whereDate('contract_date', '>=', $chartFrom->toDateString())
            ->whereDate('contract_date', '<=', $chartTo->toDateString())
            ->get(['id', 'unit_id', 'contract_date', 'discount_base']);

        foreach ($sales as $sale) {
            $date = $sale->contract_date;
            $key = $date
                ? ($useYearly ? $date->format('Y') : $date->format('Y-m'))
                : null;
            if (! $key || ! isset($buckets[$key])) {
                continue;
            }
            $buckets[$key]['sales'] += max(
                0,
                (float) ($sale->unit?->list_price_base ?? 0) - (float) ($sale->discount_base ?? 0),
            );
        }

        $expenses = Expense::query()
            ->where('project_id', $projectId)
            ->where('status', 'approved')
            ->whereDate('expense_date', '>=', $chartFrom->toDateString())
            ->whereDate('expense_date', '<=', $chartTo->toDateString())
            ->get(['expense_date', 'amount_base']);

        foreach ($expenses as $expense) {
            $date = $expense->expense_date;
            $key = $date
                ? ($useYearly ? $date->format('Y') : $date->format('Y-m'))
                : null;
            if (! $key || ! isset($buckets[$key])) {
                continue;
            }
            $buckets[$key]['expenses'] += (float) ($expense->amount_base ?? 0);
        }

        $incomes = Income::query()
            ->where('project_id', $projectId)
            ->where('status', 'approved')
            ->whereDate('income_date', '>=', $chartFrom->toDateString())
            ->whereDate('income_date', '<=', $chartTo->toDateString())
            ->get(['income_date', 'amount_base']);

        foreach ($incomes as $income) {
            $date = $income->income_date;
            $key = $date
                ? ($useYearly ? $date->format('Y') : $date->format('Y-m'))
                : null;
            if (! $key || ! isset($buckets[$key])) {
                continue;
            }
            $buckets[$key]['income'] += (float) ($income->amount_base ?? 0);
        }

        $purchases = Purchase::query()
            ->where('project_id', $projectId)
            ->whereDate('purchase_date', '>=', $chartFrom->toDateString())
            ->whereDate('purchase_date', '<=', $chartTo->toDateString())
            ->get(['purchase_date', 'total_amount_base']);

        foreach ($purchases as $purchase) {
            $date = $purchase->purchase_date;
            $key = $date
                ? ($useYearly ? $date->format('Y') : $date->format('Y-m'))
                : null;
            if (! $key || ! isset($buckets[$key])) {
                continue;
            }
            $buckets[$key]['purchases'] += (float) ($purchase->total_amount_base ?? 0);
        }

        foreach ($buckets as &$bucket) {
            $bucket['sales'] = round($bucket['sales'], 2);
            $bucket['expenses'] = round($bucket['expenses'], 2);
            $bucket['income'] = round($bucket['income'], 2);
            $bucket['purchases'] = round($bucket['purchases'], 2);
        }
        unset($bucket);

        $rows = array_values($buckets);

        return [
            'labels' => array_column($rows, 'label'),
            'sales' => array_column($rows, 'sales'),
            'expenses' => array_column($rows, 'expenses'),
            'income' => array_column($rows, 'income'),
            'purchases' => array_column($rows, 'purchases'),
        ];
    }

    /**
     * @return array{labels: list<string>, amounts: list<float>}
     */
    private function projectExpensesByCategory(int $projectId): array
    {
        $rows = Expense::query()
            ->where('expenses.project_id', $projectId)
            ->where('expenses.status', 'approved')
            ->leftJoin('expense_categories', 'expense_categories.id', '=', 'expenses.expense_category_id')
            ->selectRaw(
                'expenses.expense_category_id, COALESCE(expense_categories.name, ?) as category_name, SUM(expenses.amount_base) as total',
                ['Other'],
            )
            ->groupBy('expenses.expense_category_id', 'expense_categories.name')
            ->orderByDesc('total')
            ->limit(8)
            ->get();

        return [
            'labels' => $rows->map(fn ($row) => (string) $row->category_name)->all(),
            'amounts' => $rows->map(fn ($row) => round((float) $row->total, 2))->all(),
        ];
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

    /**
     * @return list<array{key: string, label: string, by_currency: array{AFN: float, USD: float}}>
     */
    protected function salesByMonth($salesQuery): array
    {
        $to = Carbon::now()->endOfMonth();
        $from = Carbon::now()->subMonths(5)->startOfMonth();

        $buckets = [];
        $cursor = $from->copy();
        while ($cursor->lte($to)) {
            $key = $cursor->format('Y-m');
            $buckets[$key] = [
                'key' => $key,
                'label' => $cursor->format('M'),
                'by_currency' => ['AFN' => 0.0, 'USD' => 0.0],
            ];
            $cursor = $cursor->addMonth();
        }

        $sales = (clone $salesQuery)
            ->with(['unit:id,list_price,currency'])
            ->where('status', '!=', 'cancelled')
            ->whereDate('contract_date', '>=', $from->toDateString())
            ->whereDate('contract_date', '<=', $to->toDateString())
            ->get(['id', 'unit_id', 'contract_date', 'discount', 'currency']);

        foreach ($sales as $sale) {
            $date = $sale->contract_date;
            if (! $date) {
                continue;
            }
            $key = $date->format('Y-m');
            if (! isset($buckets[$key])) {
                continue;
            }

            $code = CurrencyService::normalize(
                (string) ($sale->currency ?: $sale->unit?->currency ?: 'USD'),
            );
            $amount = max(
                0,
                (float) ($sale->unit?->list_price ?? 0) - (float) ($sale->discount ?? 0),
            );
            $buckets[$key]['by_currency'][$code] = round(
                ($buckets[$key]['by_currency'][$code] ?? 0) + $amount,
                2,
            );
        }

        return array_values(array_map(
            static fn (array $bucket) => [
                'key' => $bucket['key'],
                'label' => $bucket['label'],
                'by_currency' => [
                    'AFN' => round((float) ($bucket['by_currency']['AFN'] ?? 0), 2),
                    'USD' => round((float) ($bucket['by_currency']['USD'] ?? 0), 2),
                ],
            ],
            $buckets,
        ));
    }
}
