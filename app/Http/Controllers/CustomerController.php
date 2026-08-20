<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AuthorizesResourcePermissions;
use Illuminate\Routing\Controllers\HasMiddleware;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\Sale;
use App\Support\CurrencyAmounts;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CustomerController extends Controller implements HasMiddleware
{
    protected static string $permissionModule = 'customers';

    use AuthorizesResourcePermissions;


    public function index(Request $request): Response
    {
        $query = Customer::query();

        if ($search = $request->string('search')->toString()) {
            $query->where(function ($q) use ($search) {
                $q->orWhere('name', 'like', "%{$search}%");
                $q->orWhere('customer_no', 'like', "%{$search}%");
                $q->orWhere('phone', 'like', "%{$search}%");
                $q->orWhere('national_id', 'like', "%{$search}%");
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

        return Inertia::render('Customers/Index', [
            'items' => $query->with('creator:id,name')->latest()->paginate(12)->withQueryString(),
            'filters' => ['search' => $search],
            'totals' => $totals,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Customers/Form', [
            'item' => null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'customer_no' => 'required|string|max:50',
            'name' => 'required|string|max:255',
            'father_name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'national_id' => 'nullable|string|max:100',
            'address' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        Customer::create(array_merge([], $data));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Customers created.']);

        return redirect()->route('customers.index');
    }

    public function show(Customer $customer): Response
    {
        $customer->load([
            'sales:id,customer_id,sale_no,project_id,unit_id,status,contract_date,discount,currency',
            'sales.project:id,name',
            'sales.unit:id,unit_no,list_price,currency',
            'payments' => fn ($q) => $q->latest('paid_at')->limit(10),
        ]);

        return Inertia::render('Customers/Show', [
            'item' => $customer,
            'stats' => [
                'sales_count' => $customer->sales()->count(),
                'sales_total' => Sale::sumTotalPrice($customer->sales()->getQuery()),
                'sales_by_currency' => Sale::sumTotalPriceByCurrency($customer->sales()->getQuery()),
                'payments_total' => $customer->payments()
                    ->where(fn ($q) => $q->whereNull('sale_id')->orWhereHas('sale'))
                    ->sum('amount'),
                'payments_by_currency' => CurrencyAmounts::sum(
                    $customer->payments()
                        ->where(fn ($q) => $q->whereNull('sale_id')->orWhereHas('sale'))
                        ->getQuery(),
                ),
            ],
        ]);
    }

    public function edit(Request $request, Customer $customer): Response
    {

        return Inertia::render('Customers/Form', [
            'item' => $customer,
        ]);
    }

    public function update(Request $request, Customer $customer): RedirectResponse
    {

        $data = $request->validate([
            'customer_no' => 'required|string|max:50',
            'name' => 'required|string|max:255',
            'father_name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'national_id' => 'nullable|string|max:100',
            'address' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $customer->update($data);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Customers updated.']);

        return redirect()->route('customers.index');
    }

    public function destroy(Request $request, Customer $customer): RedirectResponse
    {
        $customer->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Customers deleted.']);

        return redirect()->route('customers.index');
    }
}
