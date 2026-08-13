<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AuthorizesResourcePermissions;
use Illuminate\Routing\Controllers\HasMiddleware;
use App\Models\Building;
use App\Models\Floor;
use App\Models\Project;
use App\Models\Unit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
            ->with(['project:id,name', 'floor.building']);


        if ($request->filled('project_id')) {
            $query->where('project_id', $request->integer('project_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($search = $request->string('search')->toString()) {
            $query->where('unit_no', 'like', "%{$search}%");
        }

        return Inertia::render('Units/Index', [
            'items' => $query->latest()->paginate(20)->withQueryString(),
            'filters' => $request->only(['search', 'project_id', 'status']),
            'projects' => Project::query()->orderBy('name')->get(['id', 'name']),
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

        DB::transaction(function () use ($request, $data) {
            $project = Project::query()
                ->findOrFail($data['project_id']);

            $building = Building::query()->firstOrCreate(
                ['project_id' => $project->id, 'name' => $data['building_name'] ?: 'Main Building'],
                ['floor_count' => 0]
            );

            $floor = Floor::query()->firstOrCreate(
                ['building_id' => $building->id, 'number' => (int) ($data['floor_number'] ?? 1)],
                ['name' => 'Floor '.((int) ($data['floor_number'] ?? 1))]
            );

            Unit::create([
                'project_id' => $project->id,
                'floor_id' => $floor->id,
                'unit_no' => $data['unit_no'],
                'type' => $data['type'] ?? 'apartment',
                'area' => $data['area'] ?? null,
                'bedrooms' => $data['bedrooms'] ?? null,
                'list_price' => $data['list_price'] ?? 0,
                'currency' => $data['currency'] ?? 'USD',
                'status' => $data['status'] ?? 'available',
                'description' => $data['description'] ?? null,
            ]);
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Unit created.']);

        return redirect()->route('units.index');
    }

    public function show(Unit $unit): Response
    {
        $unit->load([
            'project:id,name,code',
            'floor.building',
            'sales' => fn ($q) => $q->with(['customer:id,name', 'unit:id,list_price,currency'])->latest(),
        ]);

        return Inertia::render('Units/Show', [
            'item' => $unit,
        ]);
    }

    public function edit(Request $request, Unit $unit): Response
    {
        $unit->load(['floor.building', 'project']);

        return Inertia::render('Units/Form', [
            'item' => [
                ...$unit->toArray(),
                'building_name' => $unit->floor?->building?->name,
                'floor_number' => $unit->floor?->number,
            ],
            ...$this->formOptions($request),
        ]);
    }

    public function update(Request $request, Unit $unit): RedirectResponse
    {
        $data = $this->validated($request);

        $unit->update([
            'unit_no' => $data['unit_no'],
            'type' => $data['type'] ?? $unit->type,
            'area' => $data['area'] ?? null,
            'bedrooms' => $data['bedrooms'] ?? null,
            'list_price' => $data['list_price'] ?? 0,
            'currency' => $data['currency'] ?? 'USD',
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
            'building_name' => 'nullable|string|max:255',
            'floor_number' => 'nullable|integer|min:0',
            'unit_no' => 'required|string|max:50',
            'type' => 'nullable|string|max:50',
            'area' => 'nullable|numeric|min:0',
            'bedrooms' => 'nullable|integer|min:0',
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
