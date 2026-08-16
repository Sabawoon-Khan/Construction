<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AuthorizesResourcePermissions;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\InventoryTransaction;
use App\Models\Material;
use App\Models\Partner;
use App\Models\PartnerDistribution;
use App\Models\PartnerInvestment;
use App\Models\Project;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\Unit;
use App\Services\FinanceDefaults;
use App\Services\ShareholderProfitService;
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
        $totals = [
            'count' => (clone $query)->count(),
            'active' => (clone $query)->whereIn('status', ['planning', 'under_construction', 'ready_for_sale'])->count(),
            'units' => Unit::query()->whereIn('project_id', clone $ids)->count(),
            'sales_total' => Sale::sumTotalPrice(Sale::query()->whereIn('project_id', clone $ids)),
        ];

        return Inertia::render('Projects/Index', [
            'items' => $query->latest()->paginate(12)->withQueryString(),
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
        ]);

        Project::create(array_merge(['type' => 'apartment', 'status' => 'planning', 'estimated_budget' => 0, 'estimated_revenue' => 0, 'progress_percent' => 0], $data));

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
            'units' => fn ($q) => $q->latest()->limit(8),
            'sales' => fn ($q) => $q->with(['customer:id,name', 'unit:id,list_price,currency'])->latest()->limit(8),
        ]);

        $expensesQuery = Expense::query()
            ->where('project_id', $project->id)
            ->where('status', 'approved');

        $expensesTotal = (float) (clone $expensesQuery)->sum('amount');
        $salesTotal = Sale::sumTotalPrice($project->sales()->getQuery());
        $netProfit = ShareholderProfitService::netProfit(projectId: $project->id);
        $purchasesQuery = Purchase::query()->where('project_id', $project->id);
        $purchasesTotal = (float) (clone $purchasesQuery)->sum('total_amount');
        $purchasesPaid = (float) (clone $purchasesQuery)->sum('paid_amount');
        $stock = InventoryTransaction::stockLevels($project->id);
        $stockIssues = InventoryTransaction::query()
            ->where('project_id', $project->id)
            ->where('type', InventoryTransaction::TYPE_ISSUE)
            ->with('material:id,name,material_unit_id')
            ->latest('transaction_date')
            ->get(['id', 'material_id', 'quantity', 'transaction_date']);

        $shareholders = ShareholderProfitService::projectPartners($project);

        $assignedIds = $shareholders->pluck('id');

        return Inertia::render('Projects/Show', [
            'item' => $project,
            'stats' => [
                'units_count' => $project->units_count,
                'sales_count' => $project->sales_count,
                'expenses_total' => $expensesTotal,
                'incomes_total' => $project->incomes()->sum('amount'),
                'sales_total' => $salesTotal,
                'net_profit' => $netProfit,
                'contributions_total' => (float) PartnerInvestment::query()
                    ->where('project_id', $project->id)
                    ->sum('amount'),
                'payouts_total' => (float) PartnerDistribution::query()
                    ->where('project_id', $project->id)
                    ->sum('amount'),
                'purchases_total' => $purchasesTotal,
                'purchases_remaining' => max(0, $purchasesTotal - $purchasesPaid),
            ],
            'shareholders' => $shareholders,
            'available_shareholders' => Partner::query()
                ->orderBy('name')
                ->when($assignedIds->isNotEmpty(), fn ($q) => $q->whereNotIn('id', $assignedIds))
                ->get(['id', 'name', 'phone']),
            'contributions' => PartnerInvestment::query()
                ->where('project_id', $project->id)
                ->with('partner:id,name')
                ->latest('invested_at')
                ->limit(50)
                ->get(['id', 'partner_id', 'amount', 'currency', 'invested_at']),
            'expenses' => $expensesQuery
                ->latest('expense_date')
                ->limit(50)
                ->get(['id', 'expense_no', 'expense_date', 'paid_to', 'description', 'amount', 'currency', 'purchase_id']),
            'expense_categories' => $this->expenseCategories(),
            'purchases' => $purchasesQuery
                ->with('supplier:id,name')
                ->latest('purchase_date')
                ->limit(50)
                ->get(['id', 'purchase_no', 'purchase_date', 'supplier_id', 'total_amount', 'paid_amount', 'currency', 'status']),
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
            'description' => 'required|string|max:1000',
            'expense_category_id' => [
                'nullable',
                'exists:expense_categories,id',
                Rule::notIn(ExpenseCategory::materialIds()),
            ],
            'reference_no' => 'nullable|string|max:100',
            'documents' => 'nullable|array',
            'documents.*' => 'nullable|file|max:20480',
        ], [
            'expense_category_id.not_in' => 'Materials must be recorded as a purchase so stock and remaining balance are tracked.',
        ]);
        unset($data['documents']);

        $expense = Expense::create(FinanceDefaults::expenseAttributes($data, $project->id));

        if ($request->hasFile('documents')) {
            foreach ($request->file('documents', []) as $file) {
                if (! $file) {
                    continue;
                }

                $expense->storeAttachment($file, $file->getClientOriginalName(), $request->user()?->id);
            }
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Project expense recorded.']);

        return redirect()->route('projects.show', $project);
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

        return redirect()->route('projects.show', $project);
    }

    public function storeShareholderContribution(Request $request, Project $project, Partner $partner): RedirectResponse
    {
        abort_unless($project->partners()->where('partners.id', $partner->id)->exists(), 404);

        $data = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'invested_at' => 'required|date',
            'reference_no' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
        ]);

        PartnerInvestment::create([
            'project_id' => $project->id,
            'partner_id' => $partner->id,
            'amount' => $data['amount'],
            'currency' => 'USD',
            'agreed_share_percentage' => $partner->ownershipOnProject($project->id),
            'invested_at' => $data['invested_at'],
            'type' => 'investment',
            'status' => 'approved',
            'reference_no' => $data['reference_no'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Contribution recorded.']);

        return redirect()->route('projects.show', $project);
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

    public function storeShareholderPayout(Request $request, Project $project, Partner $partner): RedirectResponse
    {
        abort_unless($project->partners()->where('partners.id', $partner->id)->exists(), 404);

        $data = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'distributed_at' => 'required|date',
            'reference_no' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
        ]);

        PartnerDistribution::create([
            'project_id' => $project->id,
            'partner_id' => $partner->id,
            'amount' => $data['amount'],
            'currency' => 'USD',
            'type' => 'profit',
            'distributed_at' => $data['distributed_at'],
            'reference_no' => $data['reference_no'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Payout recorded.']);

        return redirect()->route('projects.show', $project);
    }

    public function edit(Request $request, Project $project): Response
    {
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
        ]);

        $project->update($data);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Projects updated.']);

        return redirect()->route('projects.index');
    }

    public function destroy(Request $request, Project $project): RedirectResponse
    {
        $project->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Projects deleted.']);

        return redirect()->route('projects.index');
    }

    /** @return Collection<int, ExpenseCategory> */
    private function expenseCategories()
    {
        return ExpenseCategory::nonMaterialOptions();
    }
}
