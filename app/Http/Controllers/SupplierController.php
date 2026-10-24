<?php

namespace App\Http\Controllers;

use App\Concerns\ExportsSpreadsheet;
use App\Http\Controllers\Concerns\AuthorizesResourcePermissions;
use App\Http\Controllers\Concerns\ResolvesExportScope;
use App\Http\Controllers\Concerns\StoresRecordAttachments;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Services\CurrencyService;
use App\Support\CurrencyAmounts;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SupplierController extends Controller implements HasMiddleware
{
    protected static string $permissionModule = 'suppliers';

    use AuthorizesResourcePermissions;
    use ExportsSpreadsheet;
    use ResolvesExportScope;
    use StoresRecordAttachments;

    public function index(Request $request): Response
    {
        $search = $request->string('search')->toString();
        $query = $this->indexQuery($request);

        $ids = (clone $query)->select('id');
        $purchasesQuery = Purchase::query()->whereIn('supplier_id', clone $ids);
        $purchaseTotal = (float) (clone $purchasesQuery)->sum('total_amount');
        $purchasePaid = (float) (clone $purchasesQuery)->sum('paid_amount');
        $totalByCurrency = CurrencyAmounts::sum(clone $purchasesQuery, 'total_amount');
        $paidByCurrency = CurrencyAmounts::sum(clone $purchasesQuery, 'paid_amount');

        $totals = [
            'count' => (clone $query)->count(),
            'purchases_count' => (clone $purchasesQuery)->count(),
            'purchases_total' => $purchaseTotal,
            'paid_total' => $purchasePaid,
            'remaining_total' => max(0, $purchaseTotal - $purchasePaid),
            'purchases_by_currency' => $totalByCurrency,
            'paid_by_currency' => $paidByCurrency,
            'remaining_by_currency' => CurrencyAmounts::remaining($totalByCurrency, $paidByCurrency),
        ];

        return Inertia::render('Suppliers/Index', [
            'items' => $query->with('creator:id,name')->withCount('purchases')->oldest()->paginate(12)->withQueryString(),
            'filters' => ['search' => $search],
            'totals' => $totals,
            'moneyChart' => $this->purchasesByMonth(clone $purchasesQuery),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        abort_unless(config('kit.features.exports'), 404);

        $rows = $this->indexQuery($request, $this->exportAppliesFilters($request))
            ->with('creator:id,name')
            ->withCount('purchases')
            ->oldest()
            ->lazyById(500)
            ->map(fn (Supplier $supplier) => [
                $supplier->supplier_no,
                $supplier->name,
                $supplier->contact_person,
                $supplier->phone,
                $supplier->purchases_count,
                $supplier->creator?->name,
                $supplier->created_at?->toDateTimeString(),
            ]);

        return $this->streamExport(
            'suppliers',
            [
                'supplier_no', 'name', 'contact_person', 'phone',
                'purchases', 'added_by', 'created_at',
            ],
            $rows,
            $this->exportFormat($request),
        );
    }

    protected function indexQuery(Request $request, bool $applyFilters = true): Builder
    {
        $query = Supplier::query();

        if ($applyFilters && ($search = $request->string('search')->toString())) {
            $query->where(function ($q) use ($search) {
                $q->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('supplier_no', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('contact_person', 'like', "%{$search}%");
            });
        }

        return $query;
    }

    public function create(): Response
    {
        return Inertia::render('Suppliers/Form', [
            'item' => null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        unset($data['documents']);

        $supplier = Supplier::query()->create($data);
        $this->storeRecordAttachments($request, $supplier);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Supplier created.']);

        return redirect()->route('suppliers.index');
    }

    public function show(Supplier $supplier): Response
    {
        $supplier->load([
            'attachments',
            'purchases' => fn ($q) => $q->with('project:id,name')->oldest()->limit(20),
        ]);

        $purchases = Purchase::query()->where('supplier_id', $supplier->id);
        $purchasesTotal = (float) (clone $purchases)->sum('total_amount');
        $purchasesPaid = (float) (clone $purchases)->sum('paid_amount');
        $totalByCurrency = CurrencyAmounts::sum(clone $purchases, 'total_amount');
        $paidByCurrency = CurrencyAmounts::sum(clone $purchases, 'paid_amount');

        return Inertia::render('Suppliers/Show', [
            'item' => $supplier,
            'stats' => [
                'purchases_count' => (clone $purchases)->count(),
                'purchases_total' => $purchasesTotal,
                'purchases_by_currency' => $totalByCurrency,
                'remaining_total' => max(0, $purchasesTotal - $purchasesPaid),
                'remaining_by_currency' => CurrencyAmounts::remaining($totalByCurrency, $paidByCurrency),
            ],
        ]);
    }

    public function edit(Supplier $supplier): Response
    {
        $supplier->load('attachments');

        return Inertia::render('Suppliers/Form', [
            'item' => $supplier,
        ]);
    }

    public function update(Request $request, Supplier $supplier): RedirectResponse
    {
        $data = $this->validated($request, $supplier->id);
        unset($data['documents']);

        $supplier->update($data);
        $this->storeRecordAttachments($request, $supplier);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Supplier updated.']);

        return redirect()->route('suppliers.index');
    }

    public function destroy(Supplier $supplier): RedirectResponse
    {
        if ($supplier->purchases()->exists()) {
            Inertia::flash('toast', ['type' => 'error', 'message' => 'This supplier has purchases and cannot be deleted.']);

            return back();
        }

        $supplier->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Supplier deleted.']);

        return redirect()->route('suppliers.index');
    }

    /** @return array<string, mixed> */
    private function validated(Request $request, ?int $ignoreId = null): array
    {
        $data = $request->validate([
            'supplier_no' => 'nullable|string|max:50|unique:suppliers,supplier_no'.($ignoreId ? ','.$ignoreId : ''),
            'name' => 'required|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'notes' => 'nullable|string',
            ...$this->attachmentValidationRules(),
        ]);

        if (blank($data['supplier_no'] ?? null)) {
            $data['supplier_no'] = 'SUP-'.Str::upper(Str::random(8));
        }

        return $data;
    }

    /**
     * @return list<array{key: string, label: string, by_currency: array{AFN: float, USD: float}}>
     */
    protected function purchasesByMonth($purchaseQuery): array
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

        $purchases = (clone $purchaseQuery)
            ->whereDate('purchase_date', '>=', $from->toDateString())
            ->whereDate('purchase_date', '<=', $to->toDateString())
            ->get(['id', 'purchase_date', 'total_amount', 'currency']);

        foreach ($purchases as $purchase) {
            $date = $purchase->purchase_date;
            if (! $date) {
                continue;
            }
            $key = $date->format('Y-m');
            if (! isset($buckets[$key])) {
                continue;
            }

            $code = CurrencyService::normalize((string) ($purchase->currency ?: 'USD'));
            $amount = max(0, (float) ($purchase->total_amount ?? 0));
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
