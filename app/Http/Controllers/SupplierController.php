<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AuthorizesResourcePermissions;
use Illuminate\Routing\Controllers\HasMiddleware;
use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
                $q->orWhere('name', 'like', "%{$search}%");
                $q->orWhere('supplier_no', 'like', "%{$search}%");
                $q->orWhere('phone', 'like', "%{$search}%");
                $q->orWhere('contact_person', 'like', "%{$search}%");
            });
        }

        return Inertia::render('Suppliers/Index', [
            'items' => $query->latest()->paginate(12)->withQueryString(),
            'filters' => ['search' => $search],
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
        $data = $request->validate([
            'supplier_no' => 'required|string|max:50',
            'name' => 'required|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        Supplier::create(array_merge([], $data));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Suppliers created.']);

        return redirect()->route('suppliers.index');
    }

    public function show(Supplier $supplier): Response
    {
        $supplier->load([
            'expenses' => fn ($q) => $q->with(['project:id,name', 'category:id,name'])->latest('expense_date')->limit(10),
        ]);

        return Inertia::render('Suppliers/Show', [
            'item' => $supplier,
            'stats' => [
                'expenses_total' => $supplier->expenses()->sum('amount'),
                'expenses_count' => $supplier->expenses()->count(),
            ],
        ]);
    }

    public function edit(Request $request, Supplier $supplier): Response
    {

        return Inertia::render('Suppliers/Form', [
            'item' => $supplier,
        ]);
    }

    public function update(Request $request, Supplier $supplier): RedirectResponse
    {

        $data = $request->validate([
            'supplier_no' => 'required|string|max:50',
            'name' => 'required|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $supplier->update($data);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Suppliers updated.']);

        return redirect()->route('suppliers.index');
    }

    public function destroy(Request $request, Supplier $supplier): RedirectResponse
    {
        $supplier->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Suppliers deleted.']);

        return redirect()->route('suppliers.index');
    }
}
