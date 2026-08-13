<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AuthorizesResourcePermissions;
use App\Http\Controllers\Concerns\ResolvesMaterialUnits;
use App\Models\Material;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Inertia\Inertia;
use Inertia\Response;

class MaterialController extends Controller implements HasMiddleware
{
    protected static string $permissionModule = 'materials';

    use AuthorizesResourcePermissions;
    use ResolvesMaterialUnits;

    public function index(Request $request): Response
    {
        $query = Material::query()->with('materialUnit:id,unit_name');

        if ($search = $request->string('search')->toString()) {
            $query->where(function ($q) use ($search) {
                $q->orWhere('name', 'like', "%{$search}%");
                $q->orWhere('code', 'like', "%{$search}%");
                $q->orWhereHas('materialUnit', fn ($unitQuery) => $unitQuery->where('unit_name', 'like', "%{$search}%"));
            });
        }

        return Inertia::render('Materials/Index', [
            'items' => $query->latest()->paginate(12)->through(fn (Material $material) => [
                ...$material->toArray(),
                'current_stock' => $material->currentStock(),
                'average_unit_cost' => $material->averageUnitCost(),
            ])->withQueryString(),
            'filters' => ['search' => $search],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Materials/Form', [
            'item' => null,
            'units' => $this->materialUnitOptions(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        Material::create([
            ...$data,
            'unit_cost' => 0,
            'currency' => 'USD',
            'is_active' => $request->boolean('is_active', true),
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Material created.']);

        return redirect()->route('materials.index');
    }

    public function show(Material $material): Response
    {
        $material->load('materialUnit:id,unit_name');

        return Inertia::render('Materials/Show', [
            'item' => [
                ...$material->toArray(),
                'current_stock' => $material->currentStock(),
                'average_unit_cost' => $material->averageUnitCost(),
            ],
        ]);
    }

    public function edit(Request $request, Material $material): Response
    {
        $material->load('materialUnit:id,unit_name');

        return Inertia::render('Materials/Form', [
            'item' => $material,
            'units' => $this->materialUnitOptions(),
        ]);
    }

    public function update(Request $request, Material $material): RedirectResponse
    {
        $material->update([
            ...$this->validated($request),
            'is_active' => $request->boolean('is_active', true),
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Material updated.']);

        return redirect()->route('materials.index');
    }

    public function destroy(Request $request, Material $material): RedirectResponse
    {
        $material->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Material deleted.']);

        return redirect()->route('materials.index');
    }

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        return $request->validate([
            'code' => 'required|string|max:100',
            'name' => 'required|string|max:255',
            'material_unit_id' => 'required|exists:material_units,id',
            'minimum_stock' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
        ]);
    }
}
