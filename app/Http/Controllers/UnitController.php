<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AuthorizesResourcePermissions;
use App\Models\Project;
use App\Models\Unit;
use App\Services\CurrencyService;
use App\Services\FinanceDefaults;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class UnitController extends Controller implements HasMiddleware
{
    protected static string $permissionModule = 'units';

    use AuthorizesResourcePermissions;

    public function index(Request $request): Response
    {
        $query = Unit::query()
            ->with([
                'project:id,name',
                'sales' => fn ($q) => $q
                    ->where('status', '!=', 'cancelled')
                    ->with('customer:id,name')
                    ->latest('contract_date'),
            ]);

        if ($request->user()?->isRestrictedShareholder()) {
            $query->whereIn('project_id', $request->user()->assignedProjectIds() ?: [0]);
        }

        if ($request->filled('project_id')) {
            $query->where('project_id', $request->integer('project_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($search = $request->string('search')->toString()) {
            $query->where('unit_no', 'like', "%{$search}%");
        }

        $totals = [
            'count' => (clone $query)->count(),
            'available' => (clone $query)->where('status', 'available')->count(),
            'reserved' => (clone $query)->where('status', 'reserved')->count(),
            'sold' => (clone $query)->where('status', 'sold')->count(),
        ];

        return Inertia::render('Units/Index', [
            'items' => $query->latest()->paginate(20)->withQueryString(),
            'filters' => $request->only(['search', 'project_id', 'status']),
            'projects' => Project::query()->orderBy('name')->get(['id', 'name']),
            'totals' => $totals,
        ]);
    }

    public function create(Request $request): Response
    {
        return Inertia::render('Units/Form', [
            'item' => null,
            ...$this->formOptions($request),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        DB::transaction(function () use ($data) {
            Project::query()->findOrFail($data['project_id']);

            $currency = strtoupper($data['currency'] ?? CurrencyService::baseCurrency());
            $exchangeRate = FinanceDefaults::exchangeRate($currency, now());
            $costPrice = round((float) ($data['cost_price'] ?? 0), 2);
            $listPrice = round((float) ($data['list_price'] ?? 0), 2);

            Unit::create([
                'project_id' => $data['project_id'],
                'floor_number' => (int) ($data['floor_number'] ?? 1),
                'unit_no' => $data['unit_no'],
                'type' => $data['type'] ?? 'apartment',
                'area' => $data['area'] ?? null,
                'bedrooms' => $data['bedrooms'] ?? null,
                'toilets' => $data['toilets'] ?? null,
                'bathrooms' => $data['bathrooms'] ?? null,
                'halls' => $data['halls'] ?? null,
                'kitchens' => $data['kitchens'] ?? null,
                'balconies' => $data['balconies'] ?? null,
                'cost_price' => $costPrice,
                'cost_price_base' => round($costPrice * $exchangeRate, 2),
                'list_price' => $listPrice,
                'list_price_base' => round($listPrice * $exchangeRate, 2),
                'currency' => $currency,
                'exchange_rate' => $exchangeRate,
                'status' => $data['status'] ?? 'available',
                'description' => $data['description'] ?? null,
            ]);
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Unit created.']);

        return redirect()->route('units.index');
    }

    public function show(Request $request, Unit $unit): Response
    {
        $unit->load([
            'project:id,name,code',
            'sales' => fn ($q) => $q->with(['customer:id,name', 'unit:id,list_price,currency'])->latest(),
        ]);

        abort_if(
            $request->user()?->isRestrictedShareholder()
                && ! in_array((int) $unit->project_id, $request->user()->assignedProjectIds(), true),
            403,
        );

        return Inertia::render('Units/Show', [
            'item' => $unit,
        ]);
    }

    public function edit(Request $request, Unit $unit): Response
    {
        $unit->load('project');

        return Inertia::render('Units/Form', [
            'item' => $unit->toArray(),
            ...$this->formOptions($request),
        ]);
    }

    public function update(Request $request, Unit $unit): RedirectResponse
    {
        $data = $this->validated($request);

        $currency = strtoupper($data['currency'] ?? $unit->currency ?? CurrencyService::baseCurrency());
        $exchangeRate = FinanceDefaults::exchangeRate($currency, now());
        $costPrice = round((float) ($data['cost_price'] ?? $unit->cost_price ?? 0), 2);
        $listPrice = round((float) ($data['list_price'] ?? $unit->list_price ?? 0), 2);

        $unit->update([
            'unit_no' => $data['unit_no'],
            'floor_number' => (int) ($data['floor_number'] ?? $unit->floor_number),
            'type' => $data['type'] ?? $unit->type,
            'area' => $data['area'] ?? null,
            'bedrooms' => $data['bedrooms'] ?? null,
            'toilets' => $data['toilets'] ?? null,
            'bathrooms' => $data['bathrooms'] ?? null,
            'halls' => $data['halls'] ?? null,
            'kitchens' => $data['kitchens'] ?? null,
            'balconies' => $data['balconies'] ?? null,
            'cost_price' => $costPrice,
            'cost_price_base' => round($costPrice * $exchangeRate, 2),
            'list_price' => $listPrice,
            'list_price_base' => round($listPrice * $exchangeRate, 2),
            'currency' => $currency,
            'exchange_rate' => $exchangeRate,
            'status' => $data['status'] ?? 'available',
            'description' => $data['description'] ?? null,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Unit updated.']);

        return redirect()->route('units.index');
    }

    public function destroy(Request $request, Unit $unit): RedirectResponse
    {
        $unit->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Unit deleted.']);

        return redirect()->route('units.index');
    }

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        return $request->validate([
            'project_id' => 'required|exists:projects,id',
            'floor_number' => 'nullable|integer|min:0',
            'unit_no' => 'required|string|max:50',
            'type' => 'required|string|max:50',
            'area' => 'nullable|numeric|min:0',
            'bedrooms' => 'nullable|integer|min:0',
            'toilets' => 'nullable|integer|min:0',
            'bathrooms' => 'nullable|integer|min:0',
            'halls' => 'nullable|integer|min:0',
            'kitchens' => 'nullable|integer|min:0',
            'balconies' => 'nullable|integer|min:0',
            'cost_price' => 'nullable|numeric|min:0',
            'list_price' => 'nullable|numeric|min:0',
            'currency' => 'required|string|size:3',
            'status' => 'required|string|max:50',
            'description' => 'nullable|string',
        ]);
    }

    /** @return array<string, mixed> */
    private function formOptions(Request $request): array
    {
        return [
            'projects' => Project::query()->orderBy('name')->get(['id', 'name']),
        ];
    }
}
