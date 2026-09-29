<?php

namespace App\Http\Controllers;

use App\Concerns\ExportsSpreadsheet;
use App\Http\Controllers\Concerns\AuthorizesResourcePermissions;
use App\Http\Controllers\Concerns\ResolvesExportScope;
use App\Http\Controllers\Concerns\StoresRecordAttachments;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\ExpenseUnit;
use App\Services\CurrencyService;
use App\Services\FinanceDefaults;
use App\Support\CurrencyAmounts;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExpenseController extends Controller implements HasMiddleware
{
    protected static string $permissionModule = 'expenses';

    use AuthorizesResourcePermissions;
    use ExportsSpreadsheet;
    use ResolvesExportScope;
    use StoresRecordAttachments;

    public function index(Request $request): Response
    {
        $search = $request->string('search')->toString();
        $query = $this->indexQuery($request);

        $count = (clone $query)->count();
        $totalAmount = (float) (clone $query)->sum('amount');
        $totalAmountBase = (float) (clone $query)->sum('amount_base');
        $totals = [
            'count' => $count,
            'amount' => $totalAmount,
            'amount_base' => $totalAmountBase,
            'this_month' => (float) (clone $query)->where('expense_date', '>=', now()->startOfMonth())->sum('amount'),
            'this_month_base' => (float) (clone $query)->where('expense_date', '>=', now()->startOfMonth())->sum('amount_base'),
            'average' => $count > 0 ? $totalAmount / $count : 0,
            'average_base' => $count > 0 ? $totalAmountBase / $count : 0,
            'amount_by_currency' => CurrencyAmounts::sum(clone $query),
            'this_month_by_currency' => CurrencyAmounts::sum(
                (clone $query)->where('expense_date', '>=', now()->startOfMonth())
            ),
        ];

        return Inertia::render('Expenses/Index', [
            'items' => $query->with(['expenseUnit:id,unit_name', 'creator:id,name', 'attachments'])->oldest()->paginate(15)->withQueryString(),
            'filters' => ['search' => $search],
            'totals' => $totals,
            'moneyChart' => $this->expensesByMonth(clone $query),
            'categories' => $this->categories(),
            'units' => ExpenseUnit::options(),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        abort_unless(config('kit.features.exports'), 404);

        $query = $this->indexQuery($request, $this->exportAppliesFilters($request));

        $rows = (clone $query)
            ->with(['expenseUnit:id,unit_name', 'creator:id,name'])
            ->orderBy('id')
            ->lazyById(500)
            ->map(fn (Expense $expense) => [
                $expense->expense_date?->toDateString(),
                $expense->paid_to,
                $expense->description,
                $expense->expenseUnit?->unit_name,
                (float) ($expense->item_amount ?? 0),
                (float) ($expense->per_price ?? 0),
                (float) ($expense->amount ?? 0),
                $expense->currency,
                (float) ($expense->amount_base ?? 0),
                $expense->creator?->name,
                $expense->created_at?->toDateTimeString(),
            ]);

        return $this->streamExport('expenses', [
            'Date',
            'Paid to',
            'Description',
            'Unit',
            'Item amount',
            'Per price',
            'Total cost',
            'Currency',
            'Total cost (base)',
            'Added by',
            'Created at',
        ], $rows, $this->exportFormat($request));
    }

    /**
     * @return Builder<Expense>
     */
    protected function indexQuery(Request $request, bool $applyFilters = true): Builder
    {
        $query = Expense::query()
            ->whereNull('project_id')
            ->where('status', 'approved');

        if ($applyFilters && ($search = $request->string('search')->toString())) {
            $query->where(function ($q) use ($search) {
                $q->where('expense_no', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('paid_to', 'like', "%{$search}%");
            });
        }

        return $query;
    }

    public function create(Request $request): Response
    {
        return Inertia::render('Expenses/Form', [
            'item' => null,
            'categories' => $this->categories(),
            'units' => ExpenseUnit::options(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        unset($data['documents']);

        $expense = Expense::create(FinanceDefaults::expenseAttributes($data));
        $this->storeRecordAttachments($request, $expense);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Expense recorded.']);

        return redirect()->route('expenses.index');
    }

    public function update(Request $request, Expense $expense): RedirectResponse|Response
    {
        if ($expense->purchase_id) {
            Inertia::flash('toast', ['type' => 'error', 'message' => 'Edit this through the purchase, not as a standalone expense.']);

            return redirect()->route('purchases.show', $expense->purchase_id);
        }

        $projectId = $expense->project_id;

        $data = $this->validated($request);
        unset($data['documents']);

        $expense->update([
            ...FinanceDefaults::expenseAttributes($data, $projectId),
            'expense_category_id' => $request->filled('expense_category_id')
                ? $request->integer('expense_category_id')
                : FinanceDefaults::generalExpenseCategoryId(),
            'expense_unit_id' => $request->integer('expense_unit_id') ?: null,
            'reference_no' => $request->input('reference_no'),
            'payment_method' => $request->input('payment_method', $expense->payment_method) ?: 'cash',
        ]);

        $this->storeRecordAttachments($request, $expense);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Expense updated.']);

        if ($projectId) {
            return redirect()->to(route('projects.show', $projectId).'#expenses');
        }

        return redirect()->route('expenses.index');
    }

    public function show(Expense $expense): Response
    {
        $expense->load([
            'project:id,name,code',
            'category:id,name',
            'expenseUnit:id,unit_name',
            'attachments',
            'purchase:id,purchase_no',
        ]);

        return Inertia::render('Expenses/Show', [
            'item' => $expense,
        ]);
    }

    public function edit(Request $request, Expense $expense): Response|RedirectResponse
    {
        if ($expense->purchase_id) {
            return redirect()->route('purchases.show', $expense->purchase_id);
        }

        $expense->load(['attachments', 'project:id,name']);

        return Inertia::render('Expenses/Form', [
            'item' => $expense,
            'categories' => $this->categories(),
            'units' => ExpenseUnit::options(),
        ]);
    }

    public function destroy(Request $request, Expense $expense): RedirectResponse
    {
        if ($expense->purchase_id) {
            Inertia::flash('toast', ['type' => 'error', 'message' => 'Delete the purchase instead. That also removes this expense.']);

            return redirect()->route('purchases.show', $expense->purchase_id);
        }

        $projectId = $expense->project_id;
        $expense->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Expense deleted.']);

        if ($projectId) {
            return redirect()->to(route('projects.show', $projectId).'#expenses');
        }

        return redirect()->route('expenses.index');
    }

    /** @return array<string, mixed> */
    private function validated(Request $request): array
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
            'expense_unit_id' => 'nullable|exists:expense_units,id',
            'description' => 'required|string|max:1000',
            'expense_category_id' => 'nullable|exists:expense_categories,id',
            'currency' => 'nullable|string|size:3',
            'payment_method' => 'nullable|in:cash,bank',
            'reference_no' => 'nullable|string|max:100',
            'documents' => 'nullable|array',
            'documents.*' => 'nullable|file|max:20480',
        ]);

        if (! empty($data['item_amount']) && isset($data['per_price'])) {
            $data['amount'] = FinanceDefaults::totalCost($data);
        }

        return $data;
    }

    /** @return Collection<int, ExpenseCategory> */
    private function categories()
    {
        return ExpenseCategory::nonMaterialOptions();
    }

    /**
     * @return list<array{key: string, label: string, by_currency: array{AFN: float, USD: float}}>
     */
    protected function expensesByMonth($expenseQuery): array
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

        $expenses = (clone $expenseQuery)
            ->whereDate('expense_date', '>=', $from->toDateString())
            ->whereDate('expense_date', '<=', $to->toDateString())
            ->get(['id', 'expense_date', 'amount', 'currency']);

        foreach ($expenses as $expense) {
            $date = $expense->expense_date;
            if (! $date) {
                continue;
            }
            $key = $date->format('Y-m');
            if (! isset($buckets[$key])) {
                continue;
            }

            $code = CurrencyService::normalize((string) ($expense->currency ?: 'USD'));
            $amount = max(0, (float) ($expense->amount ?? 0));
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
