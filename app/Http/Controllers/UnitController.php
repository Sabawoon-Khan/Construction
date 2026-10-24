<?php

namespace App\Http\Controllers;

use App\Concerns\ExportsSpreadsheet;
use App\Http\Controllers\Concerns\AuthorizesResourcePermissions;
use App\Http\Controllers\Concerns\ResolvesExportScope;
use App\Http\Controllers\Concerns\StoresRecordAttachments;
use App\Models\Project;
use App\Models\Sale;
use App\Models\Unit;
use App\Services\CurrencyService;
use App\Services\FinanceDefaults;
use App\Support\CurrencyAmounts;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class UnitController extends Controller implements HasMiddleware
{
    protected static string $permissionModule = 'units';

    use AuthorizesResourcePermissions;
    use ExportsSpreadsheet;
    use ResolvesExportScope;
    use StoresRecordAttachments;

    public function index(Request $request): Response
    {
        $query = $this->indexQuery($request);

        $ids = (clone $query)->select('id');
        $available = (clone $query)->where('status', 'available')->count();
        $reserved = (clone $query)->where('status', 'reserved')->count();
        $sold = (clone $query)->where('status', 'sold')->count();

        $totals = [
            'count' => (clone $query)->count(),
            'available' => $available,
            'reserved' => $reserved,
            'sold' => $sold,
            'units_available' => $available,
            'units_reserved' => $reserved,
            'units_sold' => $sold,
            'list_price_total' => (float) (clone $query)->sum('list_price'),
            'list_price_by_currency' => CurrencyAmounts::sum(clone $query, 'list_price'),
        ];

        $salesQuery = Sale::query()
            ->whereIn('unit_id', clone $ids)
            ->where('status', '!=', 'cancelled');

        return Inertia::render('Units/Index', [
            'items' => $query->oldest()->paginate(20)->withQueryString(),
            'filters' => $request->only(['search', 'project_id', 'status']),
            'projects' => Project::query()->orderBy('name')->get(['id', 'name']),
            'totals' => $totals,
            'moneyChart' => $this->soldByMonth(clone $salesQuery),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        abort_unless(config('kit.features.exports'), 404);

        $rows = $this->indexQuery($request, $this->exportAppliesFilters($request))
            ->oldest()
            ->lazyById(500)
            ->map(function (Unit $unit) {
                $sale = $unit->sales->first();

                return [
                    $unit->unit_no,
                    $unit->project?->name,
                    $unit->floor_number,
                    $unit->type,
                    $unit->area,
                    $unit->bedrooms,
                    $unit->cost_price,
                    $unit->list_price,
                    $sale?->customer?->name,
                    $unit->status,
                    $unit->creator?->name,
                    $unit->created_at?->toDateTimeString(),
                ];
            });

        return $this->streamExport(
            'units',
            [
                'unit_no', 'project', 'floor', 'type', 'area', 'bedrooms',
                'cost_price', 'list_price', 'buyer', 'status', 'added_by', 'created_at',
            ],
            $rows,
            $this->exportFormat($request),
        );
    }

    protected function indexQuery(Request $request, bool $applyFilters = true): Builder
    {
        $query = Unit::query()
            ->with([
                'project:id,name',
                'creator:id,name',
                'sales' => fn ($q) => $q
                    ->where('status', '!=', 'cancelled')
                    ->with('customer:id,name')
                    ->oldest(),
            ]);

        if ($request->user()?->isRestrictedShareholder()) {
            $query->whereIn('project_id', $request->user()->assignedProjectIds() ?: [0]);
        }

        if (! $applyFilters) {
            return $query;
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

        return $query;
    }

    public function create(Request $request): Response
    {
        return Inertia::render('Units/Form', [
            'item' => null,
            'default_project_id' => $request->integer('project_id') ?: null,
            ...$this->formOptions($request),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        unset($data['documents']);

        $unit = null;

        DB::transaction(function () use ($data, &$unit) {
            Project::query()->findOrFail($data['project_id']);

            $currency = strtoupper($data['currency'] ?? CurrencyService::baseCurrency());
            $exchangeRate = FinanceDefaults::exchangeRate($currency, now());
            $costPrice = round((float) ($data['cost_price'] ?? 0), 2);
            $listPrice = round((float) ($data['list_price'] ?? 0), 2);

            $unit = Unit::create([
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
                'corridors' => $data['corridors'] ?? null,
                'courtyards' => $data['courtyards'] ?? null,
                'parkings' => $data['parkings'] ?? null,
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

        if ($unit) {
            $this->storeRecordAttachments($request, $unit);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Unit created.']);

        return redirect()->route('units.index');
    }

    public function show(Request $request, Unit $unit): Response
    {
        $unit->load([
            'attachments',
            'project:id,name,code',
            'sales' => fn ($q) => $q->with(['customer:id,name', 'unit:id,list_price,currency'])->oldest(),
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
        $unit->load(['project', 'attachments']);

        return Inertia::render('Units/Form', [
            'item' => array_merge($unit->toArray(), [
                'attachments' => $unit->attachments,
            ]),
            ...$this->formOptions($request),
        ]);
    }

    public function update(Request $request, Unit $unit): RedirectResponse
    {
        $data = $this->validated($request);
        unset($data['documents']);

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
            'corridors' => $data['corridors'] ?? null,
            'courtyards' => $data['courtyards'] ?? null,
            'parkings' => $data['parkings'] ?? null,
            'cost_price' => $costPrice,
            'cost_price_base' => round($costPrice * $exchangeRate, 2),
            'list_price' => $listPrice,
            'list_price_base' => round($listPrice * $exchangeRate, 2),
            'currency' => $currency,
            'exchange_rate' => $exchangeRate,
            'status' => $data['status'] ?? 'available',
            'description' => $data['description'] ?? null,
        ]);
        $this->storeRecordAttachments($request, $unit);

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
            'corridors' => 'nullable|integer|min:0',
            'courtyards' => 'nullable|integer|min:0',
            'parkings' => 'nullable|integer|min:0',
            'cost_price' => 'nullable|numeric|min:0',
            'list_price' => 'nullable|numeric|min:0',
            'currency' => 'required|string|size:3',
            'status' => 'required|string|max:50',
            'description' => 'nullable|string',
            ...$this->attachmentValidationRules(),
        ]);
    }

    /** @return array<string, mixed> */
    private function formOptions(Request $request): array
    {
        return [
            'projects' => Project::query()->orderBy('name')->get(['id', 'name']),
        ];
    }

    /**
     * @return list<array{key: string, label: string, by_currency: array{AFN: float, USD: float}}>
     */
    protected function soldByMonth($salesQuery): array
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
