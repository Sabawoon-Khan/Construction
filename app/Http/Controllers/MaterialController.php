<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AuthorizesResourcePermissions;
use App\Http\Controllers\Concerns\StoresRecordAttachments;
use App\Models\InventoryTransaction;
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
    use StoresRecordAttachments;

    public function index(Request $request): Response
    {
        $query = Material::query()->with(['unit:id,unit_name', 'creator:id,name']);

        if ($search = $request->string('search')->toString()) {
            $query->where(function ($q) use ($search) {
                $q->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        $count = (clone $query)->count();
        $active = (clone $query)->where('is_active', true)->count();
        $onHandByMaterial = InventoryTransaction::stockLevels()
            ->groupBy('material_id')
            ->map(fn ($rows) => round((float) $rows->sum('on_hand'), 3));

        $lowStock = (clone $query)
            ->get(['id', 'minimum_stock'])
            ->filter(function (Material $material) use ($onHandByMaterial) {
                $minimum = (float) $material->minimum_stock;
                if ($minimum <= 0) {
                    return false;
                }

                $onHand = (float) ($onHandByMaterial[$material->id] ?? 0);

                return $onHand < $minimum;
            })
            ->count();

        $totals = [
            'count' => $count,
            'active' => $active,
            'inactive' => max(0, $count - $active),
            'with_cost' => (clone $query)->where('unit_cost', '>', 0)->count(),
            'low_stock' => $lowStock,
            'this_month' => (clone $query)
                ->where('created_at', '>=', now()->startOfMonth())
                ->count(),
        ];

        return Inertia::render('Materials/Index', [
            'items' => $query->latest()->paginate(15)->withQueryString(),
            'filters' => ['search' => $search],
            'units' => MaterialUnit::options(),
            'totals' => $totals,
            'volumeChart' => $this->materialsVolumeByMonth(clone $query),
        ]);
    }

    /**
     * Materials created per month for the last 6 months (count volume).
     *
     * @param  \Illuminate\Database\Eloquent\Builder<\App\Models\Material>  $query
     * @return list<array{key: string, label: string, count: int}>
     */
    protected function materialsVolumeByMonth($query): array
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
                'count' => 0,
            ];
            $cursor = $cursor->addMonth();
        }

        $rows = (clone $query)
            ->reorder()
            ->where('created_at', '>=', $from)
            ->where('created_at', '<=', $to)
            ->get(['created_at']);

        foreach ($rows as $row) {
            $date = $row->created_at;
            if (! $date) {
                continue;
            }
            $key = $date->format('Y-m');
            if (isset($buckets[$key])) {
                $buckets[$key]['count']++;
            }
        }

        return array_values($buckets);
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
        $data = $this->validated($request);
        unset($data['documents']);

        $material = Material::query()->create($data);
        $this->storeRecordAttachments($request, $material);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Material created.']);

        return redirect()->route('materials.index');
    }

    public function edit(Material $material): Response
    {
        $material->load(['unit:id,unit_name', 'attachments']);

        return Inertia::render('Materials/Form', [
            'item' => $material,
            'units' => MaterialUnit::options(),
        ]);
    }

    public function update(Request $request, Material $material): RedirectResponse
    {
        $data = $this->validated($request, $material->id);
        unset($data['documents']);

        $material->update($data);
        $this->storeRecordAttachments($request, $material);

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
            ...$this->attachmentValidationRules(),
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
