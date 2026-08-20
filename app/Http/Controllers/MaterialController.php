<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AuthorizesResourcePermissions;
use App\Models\Material;
use App\Models\MaterialUnit;
use App\Services\CurrencyService;
use App\Services\FinanceDefaults;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class MaterialController extends Controller implements HasMiddleware
{
    protected static string $permissionModule = 'materials';

    use AuthorizesResourcePermissions;

    public function index(Request $request): Response
    {
        $query = Material::query()->with(['unit:id,unit_name', 'creator:id,name']);

        if ($search = $request->string('search')->toString()) {
            $query->where(function ($q) use ($search) {
                $q->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        $totals = [
            'count' => (clone $query)->count(),
            'active' => (clone $query)->where('is_active', true)->count(),
        ];

        return Inertia::render('Materials/Index', [
            'items' => $query->latest()->paginate(15)->withQueryString(),
            'filters' => ['search' => $search],
            'units' => MaterialUnit::options(),
            'totals' => $totals,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Materials/Form', [
            'item' => null,
            'units' => MaterialUnit::options(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Material::query()->create($this->validated($request));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Material created.']);

        return redirect()->route('materials.index');
    }

    public function edit(Material $material): Response
    {
        $material->load('unit:id,unit_name');

        return Inertia::render('Materials/Form', [
            'item' => $material,
            'units' => MaterialUnit::options(),
        ]);
    }

    public function update(Request $request, Material $material): RedirectResponse
    {
        $material->update($this->validated($request, $material->id));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Material updated.']);

        return redirect()->route('materials.index');
    }

    public function destroy(Material $material): RedirectResponse
    {
        if ($material->purchaseItems()->exists() || $material->inventoryTransactions()->exists()) {
            Inertia::flash('toast', ['type' => 'error', 'message' => 'This material is in use and cannot be deleted.']);

            return back();
        }

        $material->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Material deleted.']);

        return redirect()->route('materials.index');
    }

    /** @return array<string, mixed> */
    private function validated(Request $request, ?int $ignoreId = null): array
    {
        $data = $request->validate([
            'code' => 'nullable|string|max:50|unique:materials,code'.($ignoreId ? ','.$ignoreId : ''),
            'name' => 'required|string|max:255',
            'material_unit_id' => 'nullable|exists:material_units,id',
            'minimum_stock' => 'nullable|numeric|min:0',
            'unit_cost' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|size:3',
            'is_active' => 'nullable|boolean',
        ]);

        if (blank($data['code'] ?? null)) {
            $data['code'] = 'MAT-'.Str::upper(Str::random(8));
        }

        $currency = strtoupper($data['currency'] ?? CurrencyService::baseCurrency());
        $exchangeRate = FinanceDefaults::exchangeRate($currency, now());
        $unitCost = round((float) ($data['unit_cost'] ?? 0), 2);

        $data['minimum_stock'] = $data['minimum_stock'] ?? 0;
        $data['unit_cost'] = $unitCost;
        $data['unit_cost_base'] = round($unitCost * $exchangeRate, 2);
        $data['is_active'] = $request->boolean('is_active');
        $data['currency'] = $currency;
        $data['exchange_rate'] = $exchangeRate;

        return $data;
    }
}
