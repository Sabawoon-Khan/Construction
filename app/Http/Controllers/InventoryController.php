<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AuthorizesResourcePermissions;
use App\Models\InventoryTransaction;
use App\Models\Material;
use App\Models\Project;
use App\Services\PurchaseService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Inertia\Inertia;
use Inertia\Response;

class InventoryController extends Controller implements HasMiddleware
{
    protected static string $permissionModule = 'inventory';

    use AuthorizesResourcePermissions;

    public function index(Request $request): Response
    {
        $projectId = $request->integer('project_id') ?: null;
        $search = $request->string('search')->toString();
        $levels = InventoryTransaction::stockLevels($projectId);

        $materials = Material::query()
            ->with('unit:id,unit_name')
            ->get(['id', 'name', 'code', 'minimum_stock', 'material_unit_id'])
            ->keyBy('id');

        $projects = Project::query()->orderBy('name')->get(['id', 'name']);
        $projectsById = $projects->keyBy('id');

        $items = $levels->map(function ($row) use ($materials, $projectsById) {
            $material = $materials->get($row->material_id);

            return [
                'project_id' => (int) $row->project_id,
                'project_name' => $projectsById->get($row->project_id)?->name,
                'material_id' => (int) $row->material_id,
                'material_name' => $material?->name,
                'material_code' => $material?->code,
                'unit_name' => $material?->unit?->unit_name,
                'purchased' => (float) $row->purchased,
                'issued' => (float) $row->issued,
                'on_hand' => (float) $row->on_hand,
                'minimum_stock' => (float) ($material?->minimum_stock ?? 0),
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

        $issuesQuery = InventoryTransaction::query()
            ->with([
                'project:id,name',
                'material:id,name,code',
                'material.unit:id,unit_name',
            ])
            ->where('type', InventoryTransaction::TYPE_ISSUE)
            ->latest('transaction_date')
            ->latest('id');

        if ($projectId) {
            $issuesQuery->where('project_id', $projectId);
        }

        if ($search !== '') {
            $needle = mb_strtolower($search);
            $issuesQuery->where(function ($query) use ($needle) {
                $query->whereHas('project', fn ($q) => $q->whereRaw('LOWER(name) LIKE ?', ["%{$needle}%"]))
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
            'material_id' => (int) $issue->material_id,
            'material_name' => $issue->material?->name,
            'material_code' => $issue->material?->code,
            'unit_name' => $issue->material?->unit?->unit_name,
            'quantity' => (float) $issue->quantity,
            'transaction_date' => $issue->transaction_date?->toDateString(),
            'reference_no' => $issue->reference_no,
            'notes' => $issue->notes,
        ])->values();

        $latestIssues = InventoryTransaction::query()
            ->where('type', InventoryTransaction::TYPE_ISSUE)
            ->when($projectId, fn ($q) => $q->where('project_id', $projectId))
            ->orderByDesc('transaction_date')
            ->orderByDesc('id')
            ->get(['id', 'project_id', 'material_id', 'quantity', 'transaction_date'])
            ->unique(fn (InventoryTransaction $issue) => $issue->project_id.'-'.$issue->material_id)
            ->keyBy(fn (InventoryTransaction $issue) => $issue->project_id.'-'.$issue->material_id);

        $items = $items->map(function (array $row) use ($latestIssues) {
            $latest = $latestIssues->get($row['project_id'].'-'.$row['material_id']);

            $row['latest_issue'] = $latest ? [
                'id' => $latest->id,
                'quantity' => (float) $latest->quantity,
                'transaction_date' => $latest->transaction_date?->toDateString(),
            ] : null;

            return $row;
        })->values();

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
                'on_hand_rows' => $items->where('on_hand', '>', 0)->count(),
                'low_stock_rows' => $items->filter(function (array $row) {
                    $minimum = (float) ($row['minimum_stock'] ?? 0);

                    return $minimum > 0
                        && (float) $row['on_hand'] > 0
                        && (float) $row['on_hand'] <= $minimum;
                })->count(),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'project_id' => 'required|exists:projects,id',
            'material_id' => 'required|exists:materials,id',
            'quantity' => 'required|numeric|min:0.001',
            'transaction_date' => 'nullable|date',
            'reference_no' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
        ]);

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
}
