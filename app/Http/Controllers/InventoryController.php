<?php

namespace App\Http\Controllers;

use App\Concerns\ExportsSpreadsheet;
use App\Http\Controllers\Concerns\AuthorizesResourcePermissions;
use App\Http\Controllers\Concerns\ResolvesExportScope;
use App\Models\InventoryTransaction;
use App\Models\Material;
use App\Models\Project;
use App\Services\PurchaseService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InventoryController extends Controller implements HasMiddleware
{
    protected static string $permissionModule = 'inventory';

    use AuthorizesResourcePermissions;
    use ExportsSpreadsheet;
    use ResolvesExportScope;

    public function index(Request $request): Response
    {
        $projectId = $request->integer('project_id') ?: null;
        $search = $request->string('search')->toString();
        $items = $this->indexQuery($request);

        $projects = Project::query()->orderBy('name')->get(['id', 'name']);

        $issuesQuery = InventoryTransaction::query()
            ->with([
                'project:id,name',
                'fromProject:id,name',
                'toProject:id,name',
                'material:id,name,code',
                'material.unit:id,unit_name',
            ])
            ->whereIn('type', [
                InventoryTransaction::TYPE_ISSUE,
                InventoryTransaction::TYPE_TRANSFER_IN,
                InventoryTransaction::TYPE_TRANSFER_OUT,
            ])
            ->oldest()
            ->oldest();

        if ($projectId) {
            $issuesQuery->where(function ($q) use ($projectId) {
                $q->where('project_id', $projectId)
                    ->orWhere('from_project_id', $projectId)
                    ->orWhere('to_project_id', $projectId);
            });
        }

        if ($search !== '') {
            $needle = mb_strtolower($search);
            $issuesQuery->where(function ($query) use ($needle) {
                $query->whereHas('project', fn($q) => $q->whereRaw('LOWER(name) LIKE ?', ["%{$needle}%"]))
                    ->orWhereHas('material', function ($q) use ($needle) {
                        $q->whereRaw('LOWER(name) LIKE ?', ["%{$needle}%"])
                            ->orWhereRaw('LOWER(code) LIKE ?', ["%{$needle}%"]);
                    });
            });
        }

        $issues = $issuesQuery->limit(100)->get()->map(fn (InventoryTransaction $issue) => [
            'id' => $issue->id,
            'project_id' => (int) $issue->project_id,
            'project_name' => $issue->project?->name,
            'type' => $issue->type,
            'transfer_from_id' => $issue->from_project_id ? (int) $issue->from_project_id : null,
            'transfer_from_name' => $issue->fromProject?->name ?? null,
            'transfer_to_id' => $issue->to_project_id ? (int) $issue->to_project_id : null,
            'transfer_to_name' => $issue->toProject?->name ?? null,
            'material_id' => (int) $issue->material_id,
            'material_name' => $issue->material?->name,
            'material_code' => $issue->material?->code,
            'unit_name' => $issue->material?->unit?->unit_name,
            'quantity' => (float) $issue->quantity,
            'transaction_date' => $issue->transaction_date?->toDateString(),
            'reference_no' => $issue->reference_no,
            'notes' => $issue->notes,
        ])->values();

        $issueRowsByTransferKey = [];
        foreach ($issues as $issueRow) {
            if (! in_array($issueRow['type'], [InventoryTransaction::TYPE_TRANSFER_IN, InventoryTransaction::TYPE_TRANSFER_OUT], true)) {
                $issueRowsByTransferKey[] = $issueRow;
                continue;
            }

            $fromProjectId = (int) ($issueRow['transfer_from_id'] ?? $issueRow['project_id']);
            $toProjectId = (int) ($issueRow['transfer_to_id'] ?? $issueRow['project_id']);

            if ($fromProjectId === 0 || $toProjectId === 0 || $fromProjectId === $toProjectId) {
                $issueRowsByTransferKey[] = $issueRow;
                continue;
            }

            $transferKey = implode('|', [
                (string) $issueRow['material_id'],
                (string) $issueRow['transaction_date'],
                number_format((float) $issueRow['quantity'], 3, '.', ''),
                (string) min($fromProjectId, $toProjectId),
                (string) max($fromProjectId, $toProjectId),
            ]);

            if (! isset($issueRowsByTransferKey[$transferKey])) {
                $issueRowsByTransferKey[$transferKey] = [
                    ...$issueRow,
                    'transfer_from_id' => $fromProjectId,
                    'transfer_from_name' => $issueRow['transfer_from_name'] ?? $issueRow['project_name'],
                    'transfer_to_id' => $toProjectId,
                    'transfer_to_name' => $issueRow['transfer_to_name'] ?? $issueRow['project_name'],
                ];
            }
        }

        $issues = array_values($issueRowsByTransferKey);

        $lowStockRows = $items->filter(function (array $row) {
            $minimum = (float) ($row['minimum_stock'] ?? 0);

            return $minimum > 0
                && (float) $row['on_hand'] > 0
                && (float) $row['on_hand'] <= $minimum;
        })->count();

        $onHandRows = $items->where('on_hand', '>', 0)->count();
        $emptyRows = $items->where('on_hand', '<=', 0)->count();

        $purchaseQuery = InventoryTransaction::query()
            ->where('type', InventoryTransaction::TYPE_PURCHASE)
            ->when($projectId, fn($q) => $q->where('project_id', $projectId));
        $issueQuery = InventoryTransaction::query()
            ->where('type', InventoryTransaction::TYPE_ISSUE)
            ->when($projectId, fn($q) => $q->where('project_id', $projectId));

        return Inertia::render('Inventory/Index', [
            'items' => $items,
            'issues' => $issues,
            'projects' => $projects,
            'materials' => Material::query()
                ->where('is_active', true)
                ->with('unit:id,unit_name')
                ->orderBy('name')
                ->get(['id', 'name', 'code', 'material_unit_id']),
            'filters' => [
                'project_id' => $projectId,
                'search' => $search,
            ],
            'totals' => [
                'rows' => $items->count(),
                'on_hand_rows' => $onHandRows,
                'low_stock_rows' => $lowStockRows,
                'empty_rows' => $emptyRows,
                'purchased_qty' => round((float) $items->sum('purchased'), 3),
                'issued_qty' => round((float) $items->sum('issued'), 3),
                'on_hand_qty' => round((float) $items->sum('on_hand'), 3),
                'stock_value' => round((float) $items->sum('stock_value'), 2),
                'purchase_total' => round((float) (clone $purchaseQuery)->sum('total_cost'), 2),
                'issues_count' => (clone $issueQuery)->count(),
            ],
            'volumeChart' => $this->issuesByMonth($projectId),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        abort_unless(config('kit.features.exports'), 404);

        $rows = $this->indexQuery($request, $this->exportAppliesFilters($request))
            ->map(fn(array $row) => [
                $row['project_name'],
                $row['material_name'],
                $row['material_code'],
                $row['unit_name'],
                $row['purchased'],
                $row['issued'],
                $row['on_hand'],
                $row['minimum_stock'],
                $row['stock_value'],
            ]);

        return $this->streamExport(
            'inventory',
            [
                'project',
                'material',
                'code',
                'unit',
                'purchased',
                'issued',
                'on_hand',
                'minimum_stock',
                'stock_value',
            ],
            $rows,
            $this->exportFormat($request),
        );
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    protected function indexQuery(Request $request, bool $applyFilters = true): Collection
    {
        $projectId = $applyFilters ? ($request->integer('project_id') ?: null) : null;
        $search = $applyFilters ? $request->string('search')->toString() : '';

        $levels = InventoryTransaction::stockLevels($projectId);

        $materials = Material::query()
            ->with('unit:id,unit_name')
            ->get(['id', 'name', 'code', 'minimum_stock', 'material_unit_id', 'unit_cost', 'unit_cost_base'])
            ->keyBy('id');

        $projectsById = Project::query()->orderBy('name')->get(['id', 'name'])->keyBy('id');

        $items = $levels->map(function ($row) use ($materials, $projectsById) {
            $material = $materials->get($row->material_id);
            $onHand = (float) $row->on_hand;
            $unitCost = (float) ($material?->unit_cost ?? 0);

            return [
                'project_id' => (int) $row->project_id,
                'project_name' => $projectsById->get($row->project_id)?->name,
                'material_id' => (int) $row->material_id,
                'material_name' => $material?->name,
                'material_code' => $material?->code,
                'unit_name' => $material?->unit?->unit_name,
                'purchased' => (float) $row->purchased,
                'issued' => (float) $row->issued,
                'on_hand' => $onHand,
                'minimum_stock' => (float) ($material?->minimum_stock ?? 0),
                'stock_value' => round($onHand * $unitCost, 2),
            ];
        })->values();

        if ($search !== '') {
            $needle = mb_strtolower($search);
            $items = $items->filter(function (array $row) use ($needle) {
                return str_contains(mb_strtolower((string) ($row['project_name'] ?? '')), $needle)
                    || str_contains(mb_strtolower((string) ($row['material_name'] ?? '')), $needle)
                    || str_contains(mb_strtolower((string) ($row['material_code'] ?? '')), $needle);
            })->values();
        }

        $latestIssues = InventoryTransaction::query()
            ->where('type', InventoryTransaction::TYPE_ISSUE)
            ->when($projectId, fn($q) => $q->where('project_id', $projectId))
            ->orderByDesc('transaction_date')
            ->orderByDesc('id')
            ->get(['id', 'project_id', 'material_id', 'quantity', 'transaction_date'])
            ->unique(fn(InventoryTransaction $issue) => $issue->project_id . '-' . $issue->material_id)
            ->keyBy(fn(InventoryTransaction $issue) => $issue->project_id . '-' . $issue->material_id);

        return $items->map(function (array $row) use ($latestIssues) {
            $latest = $latestIssues->get($row['project_id'] . '-' . $row['material_id']);

            $row['latest_issue'] = $latest ? [
                'id' => $latest->id,
                'quantity' => (float) $latest->quantity,
                'transaction_date' => $latest->transaction_date?->toDateString(),
            ] : null;

            return $row;
        })->values();
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'project_id' => 'required|exists:projects,id',
            'material_id' => 'required|exists:materials,id',
            'quantity' => 'required|numeric|min:0.001',
            'to_project_id' => 'nullable|exists:projects,id',
            'transaction_date' => 'nullable|date',
            'reference_no' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
        ]);

        // If a destination project is provided that's different from the selected project,
        // perform a transfer from the selected project to the destination. Otherwise perform a normal issue.
        $toProjectId = $request->input('to_project_id');

        if ($toProjectId && is_numeric($toProjectId) && (int) $toProjectId !== (int) $data['project_id']) {
            PurchaseService::transfer(
                (int) $data['project_id'],
                (int) $toProjectId,
                (int) $data['material_id'],
                (float) $data['quantity'],
                [
                    'transaction_date' => $data['transaction_date'] ?? now(),
                    'reference_no' => $data['reference_no'] ?? null,
                    'notes' => $data['notes'] ?? null,
                ],
            );
        } else {
            PurchaseService::issue(
                (int) $data['project_id'],
                (int) $data['material_id'],
                (float) $data['quantity'],
                [
                    'transaction_date' => $data['transaction_date'] ?? now(),
                    'reference_no' => $data['reference_no'] ?? null,
                    'notes' => $data['notes'] ?? null,
                ],
            );
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Stock issued. Quantity reduced; project expense was already recorded at purchase.']);

        $redirect = $request->input('redirect_to');

        if (is_string($redirect) && str_starts_with($redirect, '/projects/')) {
            return redirect($redirect);
        }

        return redirect()->route('inventory.index', array_filter([
            'project_id' => $data['project_id'],
        ]));
    }

    public function update(Request $request, InventoryTransaction $inventoryTransaction): RedirectResponse
    {
        $data = $request->validate([
            'quantity' => 'required|numeric|min:0.001',
            'transaction_date' => 'nullable|date',
            'reference_no' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
        ]);

        PurchaseService::updateIssue($inventoryTransaction, $data);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Stock issue updated.']);

        return redirect()->route('inventory.index', array_filter([
            'project_id' => $inventoryTransaction->project_id,
        ]));
    }

    public function destroy(InventoryTransaction $inventoryTransaction): RedirectResponse
    {
        $projectId = $inventoryTransaction->project_id;

        PurchaseService::deleteIssue($inventoryTransaction);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Stock issue deleted.']);

        return redirect()->route('inventory.index', array_filter([
            'project_id' => $projectId,
        ]));
    }

    /**
     * @return list<array{key: string, label: string, value: int}>
     */
    private function issuesByMonth(?int $projectId): array
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
                'value' => 0,
            ];
            $cursor = $cursor->addMonth();
        }

        $rows = InventoryTransaction::query()
            ->where('type', InventoryTransaction::TYPE_ISSUE)
            ->when($projectId, fn($q) => $q->where('project_id', $projectId))
            ->whereDate('transaction_date', '>=', $from->toDateString())
            ->whereDate('transaction_date', '<=', $to->toDateString())
            ->get(['transaction_date']);

        foreach ($rows as $row) {
            if (! $row->transaction_date) {
                continue;
            }
            $key = $row->transaction_date->format('Y-m');
            if (isset($buckets[$key])) {
                $buckets[$key]['value']++;
            }
        }

        return array_values($buckets);
    }
}
