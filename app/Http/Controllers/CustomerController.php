<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AuthorizesResourcePermissions;
use App\Http\Controllers\Concerns\StoresRecordAttachments;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\Sale;
use App\Support\CurrencyAmounts;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class CustomerController extends Controller implements HasMiddleware
{
    protected static string $permissionModule = 'customers';

    use AuthorizesResourcePermissions;
    use StoresRecordAttachments;

    public function index(Request $request): Response
    {
        $query = Customer::query();

        if ($search = $request->string('search')->toString()) {
            $query->where(function ($q) use ($search) {
                $q->orWhere('name', 'like', "%{$search}%");
                $q->orWhere('takhallus', 'like', "%{$search}%");
                $q->orWhere('father_name', 'like', "%{$search}%");
                $q->orWhere('customer_no', 'like', "%{$search}%");
                $q->orWhere('phone', 'like', "%{$search}%");
                $q->orWhere('national_id', 'like', "%{$search}%");
                $q->orWhere('address', 'like', "%{$search}%");
            });
        }

        $ids = (clone $query)->select('id');
        $salesQuery = Sale::query()->whereIn('customer_id', clone $ids);
        $paymentsQuery = Payment::query()
            ->whereIn('customer_id', clone $ids)
            ->where(fn ($q) => $q->whereNull('sale_id')->orWhereHas('sale'));
        $totals = [
            'count' => (clone $query)->count(),
            'with_sales' => (clone $query)->whereHas('sales')->count(),
            'sales_total' => Sale::sumTotalPrice(clone $salesQuery),
            'sales_by_currency' => Sale::sumTotalPriceByCurrency(clone $salesQuery),
            'payments_total' => (float) (clone $paymentsQuery)->sum('amount'),
            'payments_by_currency' => CurrencyAmounts::sum(clone $paymentsQuery),
        ];

        $items = $query->with('creator:id,name')->latest()->paginate(12)->withQueryString();
        $this->appendLedgerStats($items->getCollection());

        return Inertia::render('Customers/Index', [
            'items' => $items,
            'filters' => ['search' => $search],
            'totals' => $totals,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Customers/Form', [
            'item' => null,
            'defaults' => [
                'customer_no' => Customer::nextCustomerNo(),
                'registered_at' => now()->toDateString(),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        unset($data['documents']);

        $customer = Customer::create($data);
        $this->storeRecordAttachments($request, $customer);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Customers created.']);

        return redirect()->route('customers.index');
    }

    public function show(Customer $customer): Response
    {
        $customer->load([
            'attachments',
            'sales:id,customer_id,sale_no,project_id,unit_id,status,contract_date,discount,currency',
            'sales.project:id,name',
            'sales.unit:id,unit_no,list_price,currency',
            'payments' => fn ($q) => $q->latest('paid_at')->limit(10),
        ]);

        $salesQuery = $customer->sales()->getQuery();
        $paymentsQuery = $customer->payments()
            ->where(fn ($q) => $q->whereNull('sale_id')->orWhereHas('sale'))
            ->getQuery();
        $salesTotal = Sale::sumTotalPrice($salesQuery);
        $paymentsTotal = (float) (clone $paymentsQuery)->sum('amount');

        return Inertia::render('Customers/Show', [
            'item' => $customer,
            'stats' => [
                'sales_count' => $customer->sales()->count(),
                'sales_total' => $salesTotal,
                'sales_by_currency' => Sale::sumTotalPriceByCurrency($salesQuery),
                'payments_total' => $paymentsTotal,
                'payments_by_currency' => CurrencyAmounts::sum($paymentsQuery),
                'remaining' => round($salesTotal - $paymentsTotal, 2),
                'last_purchase_at' => $customer->sales()->max('contract_date'),
            ],
        ]);
    }

    public function edit(Request $request, Customer $customer): Response
    {
        $customer->load('attachments');

        return Inertia::render('Customers/Form', [
            'item' => $customer,
        ]);
    }

    public function update(Request $request, Customer $customer): RedirectResponse
    {
        $data = $this->validated($request, $customer->id);
        unset($data['documents']);

        $customer->update($data);
        $this->storeRecordAttachments($request, $customer);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Customers updated.']);

        return redirect()->route('customers.index');
    }

    public function destroy(Request $request, Customer $customer): RedirectResponse
    {
        $customer->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Customers deleted.']);

        return redirect()->route('customers.index');
    }

    /** @return array<string, mixed> */
    private function validated(Request $request, ?int $ignoreId = null): array
    {
        $data = $request->validate([
            'customer_no' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('customers', 'customer_no')->ignore($ignoreId),
            ],
            'name' => 'required|string|max:255',
            'takhallus' => 'nullable|string|max:255',
            'father_name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'national_id' => 'nullable|string|max:100',
            'address' => 'nullable|string',
            'registered_at' => 'nullable|date',
            'type' => 'nullable|string|in:wholesale,retail',
            'status' => 'nullable|string|in:active,inactive',
            'notes' => 'nullable|string',
            ...$this->attachmentValidationRules(),
        ]);

        if (blank($data['customer_no'] ?? null)) {
            $data['customer_no'] = Customer::nextCustomerNo();
        }

        if (blank($data['registered_at'] ?? null)) {
            $data['registered_at'] = now()->toDateString();
        }

        if (blank($data['type'] ?? null)) {
            $data['type'] = 'retail';
        }

        if (blank($data['status'] ?? null)) {
            $data['status'] = 'active';
        }

        return $data;
    }

    /**
     * @param  Collection<int, Customer>  $customers
     */
    private function appendLedgerStats($customers): void
    {
        $ids = $customers->pluck('id');

        if ($ids->isEmpty()) {
            return;
        }

        $salesStats = Sale::query()
            ->whereIn('sales.customer_id', $ids)
            ->join('units', 'units.id', '=', 'sales.unit_id')
            ->selectRaw(
                'sales.customer_id, '
                .'SUM(units.list_price - COALESCE(sales.discount, 0)) as purchase_amount, '
                .'MAX(sales.contract_date) as last_purchase_at'
            )
            ->groupBy('sales.customer_id')
            ->get()
            ->keyBy('customer_id');

        $paymentStats = Payment::query()
            ->whereIn('customer_id', $ids)
            ->where(fn ($q) => $q->whereNull('sale_id')->orWhereHas('sale'))
            ->selectRaw('customer_id, SUM(amount) as paid_amount')
            ->groupBy('customer_id')
            ->get()
            ->keyBy('customer_id');

        $customers->each(function (Customer $customer) use ($salesStats, $paymentStats) {
            $purchase = (float) ($salesStats[$customer->id]->purchase_amount ?? 0);
            $paid = (float) ($paymentStats[$customer->id]->paid_amount ?? 0);

            $customer->setAttribute('purchase_amount', $purchase);
            $customer->setAttribute('paid_amount', $paid);
            $customer->setAttribute('remaining', round($purchase - $paid, 2));
            $customer->setAttribute('last_purchase_at', $salesStats[$customer->id]->last_purchase_at ?? null);
        });
    }
}
