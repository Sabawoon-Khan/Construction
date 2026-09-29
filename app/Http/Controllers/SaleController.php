<?php

namespace App\Http\Controllers;

use App\Concerns\ExportsSpreadsheet;
use App\Http\Controllers\Concerns\AuthorizesResourcePermissions;
use App\Http\Controllers\Concerns\ResolvesExportScope;
use App\Http\Controllers\Concerns\StoresRecordAttachments;
use App\Models\Customer;
use App\Models\Project;
use App\Models\Sale;
use App\Models\SaleInstallment;
use App\Models\Unit;
use App\Services\CurrencyService;
use App\Services\FinanceDefaults;
use App\Services\SaleInstallmentService;
use App\Support\CurrencyAmounts;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SaleController extends Controller implements HasMiddleware
{
    protected static string $permissionModule = 'sales';

    use AuthorizesResourcePermissions;
    use ExportsSpreadsheet;
    use ResolvesExportScope;
    use StoresRecordAttachments;

    public function index(Request $request): Response
    {
        $search = $request->string('search')->toString();
        $query = $this->indexQuery($request)
            ->with(['customer:id,name', 'project:id,name', 'unit:id,unit_no,list_price,currency', 'creator:id,name'])
            ->withSum('installments as paid_total', 'paid_amount')
            ->withSum('installments as paid_total_base', 'paid_amount_base');

        $totalPrice = Sale::sumTotalPrice(clone $query);
        $totalPriceBase = Sale::sumTotalPriceBase(clone $query);
        $paid = Sale::sumPaidAmount(clone $query);
        $paidBase = Sale::sumPaidAmountBase(clone $query);
        $totalByCurrency = Sale::sumTotalPriceByCurrency(clone $query);
        $paidByCurrency = Sale::sumPaidAmountByCurrency(clone $query);
        $totals = [
            'count' => (clone $query)->count(),
            'total_price' => $totalPrice,
            'total_price_base' => $totalPriceBase,
            'paid' => $paid,
            'paid_base' => $paidBase,
            'remaining' => max(0, $totalPrice - $paid),
            'remaining_base' => max(0, $totalPriceBase - $paidBase),
            'total_by_currency' => $totalByCurrency,
            'paid_by_currency' => $paidByCurrency,
            'remaining_by_currency' => CurrencyAmounts::remaining($totalByCurrency, $paidByCurrency),
        ];

        return Inertia::render('Sales/Index', [
            'items' => $query->oldest()->paginate(15)->withQueryString(),
            'filters' => ['search' => $search],
            'totals' => $totals,
            'moneyChart' => $this->salesByMonth(clone $query),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        abort_unless(config('kit.features.exports'), 404);

        $query = $this->indexQuery($request, $this->exportAppliesFilters($request));

        $rows = (clone $query)
            ->with(['customer:id,name', 'project:id,name', 'unit:id,unit_no,list_price,currency', 'creator:id,name'])
            ->withSum('installments as paid_total', 'paid_amount')
            ->orderBy('id')
            ->lazyById(500)
            ->map(function (Sale $sale) {
                $listPrice = (float) ($sale->unit?->list_price ?? 0);
                $total = max(0, $listPrice - (float) ($sale->discount ?? 0));
                $paid = (float) ($sale->paid_total ?? 0);

                return [
                    $sale->sale_no,
                    $sale->customer?->name,
                    $sale->project?->name,
                    $sale->unit?->unit_no,
                    $listPrice,
                    (float) ($sale->discount ?? 0),
                    $total,
                    max(0, $total - $paid),
                    $paid,
                    $sale->currency ?? $sale->unit?->currency,
                    $sale->status,
                    $sale->creator?->name,
                    $sale->created_at?->toDateTimeString(),
                ];
            });

        return $this->streamExport('sales', [
            'Sale No',
            'Customer',
            'Project',
            'Unit',
            'Sale price',
            'Discount',
            'Total',
            'Remaining',
            'Paid',
            'Currency',
            'Status',
            'Added by',
            'Created at',
        ], $rows, $this->exportFormat($request));
    }

    /**
     * @return Builder<Sale>
     */
    protected function indexQuery(Request $request, bool $applyFilters = true): Builder
    {
        $query = Sale::query();

        if ($applyFilters && ($search = $request->string('search')->toString())) {
            $query->where(function ($q) use ($search) {
                $q->where('sale_no', 'like', "%{$search}%")
                    ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', "%{$search}%"));
            });
        }

        return $query;
    }

    public function create(Request $request): Response
    {
        return Inertia::render('Sales/Form', [
            'item' => null,
            'unit_id' => $request->integer('unit_id') ?: null,
            ...$this->formOptions($request),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        unset($data['installments'], $data['documents'], $data['sale_no']);
        $this->ensureUnitHasListPrice((int) $data['unit_id']);

        $sale = DB::transaction(function () use ($request, $data) {
            $unit = Unit::query()->findOrFail($data['unit_id']);
            $currency = strtoupper($data['currency'] ?? $unit->currency ?? CurrencyService::baseCurrency());
            $contractDate = Carbon::parse($data['contract_date'] ?? now());
            $exchangeRate = FinanceDefaults::exchangeRate($currency, $contractDate);
            $discount = round((float) ($data['discount'] ?? 0), 2);

            $sale = Sale::create([
                ...$data,
                'sale_no' => 'SAL-'.Str::upper(Str::random(8)),
                'discount' => $discount,
                'discount_base' => round($discount * $exchangeRate, 2),
                'currency' => $currency,
                'exchange_rate' => $exchangeRate,
            ]);

            Unit::whereKey($sale->unit_id)->update(['status' => 'sold']);
            $sale->load('unit');

            $this->storeInstallments($request, $sale);
            $this->storeRecordAttachments($request, $sale);

            return $sale;
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Sale created. Receive payments on this page.']);

        return redirect()->route('sales.show', $sale);
    }

    public function show(Request $request, Sale $sale): Response
    {

        $sale->load([
            'customer',
            'project',
            'unit',
            'installments',
            'attachments',
            'payments' => fn ($q) => $q->oldest(),
        ]);

        return Inertia::render('Sales/Show', [
            'sale' => $sale,
            'paid_amount' => $sale->paid_amount,
            'remaining_amount' => $sale->remaining_amount,
            'total_price' => $sale->total_price,
        ]);
    }

    public function edit(Request $request, Sale $sale): Response
    {
        $sale->load(['installments', 'attachments', 'unit:id,unit_no,list_price,currency']);

        return Inertia::render('Sales/Form', [
            'item' => $sale,
            ...$this->formOptions($request, $sale),
        ]);
    }

    public function update(Request $request, Sale $sale): RedirectResponse
    {
        $data = $this->validated($request);
        unset($data['installments'], $data['documents'], $data['sale_no']);
        $this->ensureUnitHasListPrice((int) $data['unit_id']);

        $unit = Unit::query()->findOrFail($data['unit_id']);
        $currency = strtoupper($data['currency'] ?? $unit->currency ?? $sale->currency ?? CurrencyService::baseCurrency());
        $contractDate = Carbon::parse($data['contract_date'] ?? $sale->contract_date ?? now());
        $exchangeRate = FinanceDefaults::exchangeRate($currency, $contractDate);
        $discount = round((float) ($data['discount'] ?? 0), 2);

        $sale->update([
            ...$data,
            'discount' => $discount,
            'discount_base' => round($discount * $exchangeRate, 2),
            'currency' => $currency,
            'exchange_rate' => $exchangeRate,
        ]);

        $this->storeRecordAttachments($request, $sale);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Sale updated.']);

        return redirect()->route('sales.show', $sale);
    }

    public function payInstallment(Request $request, Sale $sale, SaleInstallment $installment): RedirectResponse
    {
        abort_unless($installment->sale_id === $sale->id, 404);

        if ($request->input('bank_account_id') === '') {
            $request->merge(['bank_account_id' => null]);
        }

        $data = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'nullable|string|max:50',
            'reference_no' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
        ]);

        SaleInstallmentService::recordPayment($sale, $installment, (float) $data['amount'], [
            'payment_method' => $data['payment_method'] ?? 'cash',
            'bank_account_id' => null,
            'reference_no' => $data['reference_no'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Installment payment recorded.']);

        return redirect()->route('sales.show', $sale);
    }

    public function destroy(Request $request, Sale $sale): RedirectResponse
    {
        $unitId = $sale->unit_id;
        $sale->loadMissing('payments');
        $sale->delete();
        Unit::whereKey($unitId)->update(['status' => 'available']);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Sale deleted.']);

        return redirect()->route('sales.index');
    }

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        return $request->validate([
            'project_id' => 'required|exists:projects,id',
            'unit_id' => 'required|exists:units,id',
            'customer_id' => 'required|exists:customers,id',
            'sale_no' => 'nullable|string|max:50',
            'contract_date' => 'nullable|date',
            'discount' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|size:3',
            'status' => 'required|string|max:50',
            'terms' => 'nullable|string',
            'notes' => 'nullable|string',
            'installments' => 'nullable|array',
            'installments.*.due_date' => 'nullable|date',
            'installments.*.amount' => 'nullable|numeric|min:0',
            'installments.*.paid_now' => 'nullable|numeric|min:0',
            'installments.*.notes' => 'nullable|string',
            'documents' => 'nullable|array',
            'documents.*' => 'nullable|file|max:20480',
        ]);
    }

    private function storeInstallments(Request $request, Sale $sale): void
    {
        $rows = collect($request->input('installments', []))
            ->filter(fn ($row) => filled($row['amount'] ?? null) && (float) $row['amount'] > 0)
            ->values();

        $currency = strtoupper($sale->currency ?? CurrencyService::baseCurrency());
        $exchangeRate = (float) $sale->exchange_rate;

        if ($rows->isEmpty()) {
            $rows = collect([[
                'due_date' => $sale->contract_date?->toDateString() ?? now()->toDateString(),
                'amount' => $sale->total_price,
                'paid_now' => 0,
                'notes' => null,
            ]]);
        }

        foreach ($rows as $index => $row) {
            $amount = round((float) $row['amount'], 2);
            $amountBase = round($amount * $exchangeRate, 2);

            $installment = SaleInstallment::create([
                'sale_id' => $sale->id,
                'installment_no' => $index + 1,
                'due_date' => $row['due_date'] ?? now()->addMonths($index + 1)->toDateString(),
                'amount' => $amount,
                'amount_base' => $amountBase,
                'paid_amount' => 0,
                'paid_amount_base' => 0,
                'currency' => $currency,
                'exchange_rate' => $exchangeRate,
                'status' => 'pending',
                'notes' => $row['notes'] ?? null,
            ]);

            $paidNow = (float) ($row['paid_now'] ?? 0);

            if ($paidNow > 0) {
                SaleInstallmentService::recordPayment($sale->fresh(['unit', 'installments']), $installment, $paidNow);
            }
        }
    }

    private function ensureUnitHasListPrice(int $unitId): void
    {
        $unit = Unit::query()->findOrFail($unitId);

        if ((float) $unit->list_price <= 0) {
            throw ValidationException::withMessages([
                'unit_id' => 'Set a list price on the unit first — that is the sale price.',
            ]);
        }
    }

    /** @return array<string, mixed> */
    private function formOptions(Request $request, ?Sale $sale = null): array
    {
        $unitsQuery = Unit::query()
            ->with('project:id,name')
            ->orderBy('unit_no');

        if ($sale) {
            $unitsQuery->where(function ($query) use ($sale) {
                $query->whereIn('status', ['available', 'reserved'])
                    ->orWhere('id', $sale->unit_id);
            });
        } else {
            $unitsQuery->whereIn('status', ['available', 'reserved']);
        }

        return [
            'projects' => Project::query()->orderBy('name')->get(['id', 'name']),
            'customers' => Customer::query()->orderBy('name')->get(['id', 'name']),
            'units' => $unitsQuery->get(['id', 'project_id', 'unit_no', 'list_price', 'currency', 'status']),
        ];
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
