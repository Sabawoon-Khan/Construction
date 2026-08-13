<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AuthorizesResourcePermissions;
use Illuminate\Routing\Controllers\HasMiddleware;
use App\Models\Material;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MaterialController extends Controller implements HasMiddleware
{
    protected static string $permissionModule = 'materials';

    use AuthorizesResourcePermissions;


    public function index(Request $request): Response
    {
        $query = Material::query();

        if ($search = $request->string('search')->toString()) {
            $query->where(function ($q) use ($search) {
                $q->orWhere('name', 'like', "%{$search}%");
                $q->orWhere('code', 'like', "%{$search}%");
                $q->orWhere('unit', 'like', "%{$search}%");
            });
        }

        return Inertia::render('Materials/Index', [
            'items' => $query->latest()->paginate(12)->withQueryString(),
            'filters' => ['search' => $search],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Materials/Form', ['item' => null]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'code' => 'required|string|max:100',
            'name' => 'required|string|max:255',
            'unit' => 'required|string|max:50',
            'minimum_stock' => 'nullable|numeric|min:0',
            'unit_cost' => 'nullable|numeric|min:0',
            'currency' => 'required|string|size:3',
            'is_active' => 'boolean',
        ]);

        Material::create(array_merge([
            'unit' => 'pcs',
            'minimum_stock' => 0,
            'unit_cost' => 0,
            'currency' => 'USD',
            'is_active' => true,
        ], $data, [            'is_active' => $request->boolean('is_active', true),
        ]));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Material created.']);

        return redirect()->route('materials.index');
    }

    public function show(Material $material): Response
    {
        return Inertia::render('Materials/Show', [
            'item' => $material,
        ]);
    }

    public function edit(Request $request, Material $material): Response
    {

        return Inertia::render('Materials/Form', ['item' => $material]);
    }

    public function update(Request $request, Material $material): RedirectResponse
    {

        $data = $request->validate([
            'code' => 'required|string|max:100',
            'name' => 'required|string|max:255',
            'unit' => 'required|string|max:50',
            'minimum_stock' => 'nullable|numeric|min:0',
            'unit_cost' => 'nullable|numeric|min:0',
            'currency' => 'required|string|size:3',
            'is_active' => 'boolean',
        ]);

        $material->update([
            ...$data,
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
}
