<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AuthorizesResourcePermissions;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Support\CurrencyAmounts;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class SupplierController extends Controller implements HasMiddleware
{
    protected static string $permissionModule = 'suppliers';

    use AuthorizesResourcePermissions;

    public function index(Request $request): Response
    {
        $query = Supplier::query();

        if ($search = $request->string('search')->toString()) {
            $query->where(function ($q) use ($search) {
                $q->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('supplier_no', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('contact_person', 'like', "%{$search}%");
            });
        }

        $ids = (clone $query)->select('id');
        $purchasesQuery = Purchase::query()->whereIn('supplier_id', clone $ids);
        $purchaseTotal = (float) (clone $purchasesQuery)->sum('total_amount');
        $purchasePaid = (float) (clone $purchasesQuery)->sum('paid_amount');
        $totalByCurrency = CurrencyAmounts::sum(clone $purchasesQuery, 'total_amount');
        $paidByCurrency = CurrencyAmounts::sum(clone $purchasesQuery, 'paid_amount');

        $totals = [
            'count' => (clone $query)->count(),
            'purchases_total' => $purchaseTotal,
            'paid_total' => $purchasePaid,
            'remaining_total' => max(0, $purchaseTotal - $purchasePaid),
            'purchases_by_currency' => $totalByCurrency,
            'paid_by_currency' => $paidByCurrency,
            'remaining_by_currency' => CurrencyAmounts::remaining($totalByCurrency, $paidByCurrency),
        ];

        return Inertia::render('Suppliers/Index', [
            'items' => $query->with('creator:id,name')->withCount('purchases')->latest()->paginate(12)->withQueryString(),
            'filters' => ['search' => $search],
            'totals' => $totals,
        ]);
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

        Supplier::query()->create($data);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Supplier created.']);

        return redirect()->route('suppliers.index');
    }

    public function show(Supplier $supplier): Response
    {
        $supplier->load([
            'purchases' => fn ($q) => $q->with('project:id,name')->latest('purchase_date')->limit(20),
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
        return Inertia::render('Suppliers/Form', [
            'item' => $supplier,
        ]);
    }

    public function update(Request $request, Supplier $supplier): RedirectResponse
    {
        $supplier->update($this->validated($request, $supplier->id));

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
        ]);

        if (blank($data['supplier_no'] ?? null)) {
            $data['supplier_no'] = 'SUP-'.Str::upper(Str::random(8));
        }

        return $data;
    }
}
