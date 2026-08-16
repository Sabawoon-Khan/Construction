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

        return Inertia::render('Inventory/Index', [
            'items' => $items,
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
}
