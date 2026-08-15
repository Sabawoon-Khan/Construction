<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AuthorizesResourcePermissions;
use App\Models\Expense;
use App\Models\Partner;
use App\Models\PartnerDistribution;
use App\Models\PartnerInvestment;
use App\Models\Project;
use App\Models\Sale;
use App\Models\Unit;
use App\Services\FinanceDefaults;
use App\Services\ShareholderProfitService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Support\Str;
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

    public function show(Project $project): Response
    {
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
                ->get(['id', 'partner_id', 'amount', 'currency', 'invested_at', 'reference_no', 'notes']),
            'expenses' => $expensesQuery
                ->latest('expense_date')
                ->limit(50)
                ->get(['id', 'expense_no', 'expense_date', 'description', 'amount', 'currency']),
        ]);
    }

    public function storeExpense(Request $request, Project $project): RedirectResponse
    {
        $data = $request->validate([
            'expense_date' => 'required|date',
            'amount' => 'required|numeric|min:0',
            'description' => 'required|string|max:1000',
        ]);

        Expense::create(FinanceDefaults::expenseAttributes($data, $project->id));

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
}
