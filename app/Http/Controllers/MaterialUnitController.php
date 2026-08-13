<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AuthorizesResourcePermissions;
use App\Models\MaterialUnit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class MaterialUnitController extends Controller implements HasMiddleware
{
    protected static string $permissionModule = 'material_units';

    use AuthorizesResourcePermissions;

    public function index(Request $request): Response
    {
        $query = MaterialUnit::query()->withCount('materials');

        if ($search = $request->string('search')->toString()) {
            $query->where('unit_name', 'like', "%{$search}%");
        }

        return Inertia::render('MaterialUnits/Index', [
            'items' => $query->orderBy('unit_name')->paginate(12)->withQueryString(),
            'filters' => ['search' => $search],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('MaterialUnits/Form', ['item' => null]);
    }

    public function store(Request $request): RedirectResponse
    {
        MaterialUnit::create($request->validate([
            'unit_name' => 'required|string|max:50|unique:material_units,unit_name',
        ]));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Unit created.']);

        return redirect()->route('material-units.index');
    }

    public function edit(MaterialUnit $materialUnit): Response
    {
        return Inertia::render('MaterialUnits/Form', ['item' => $materialUnit]);
    }

    public function update(Request $request, MaterialUnit $materialUnit): RedirectResponse
    {
        $materialUnit->update($request->validate([
            'unit_name' => 'required|string|max:50|unique:material_units,unit_name,'.$materialUnit->id,
        ]));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Unit updated.']);

        return redirect()->route('material-units.index');
    }

    public function destroy(MaterialUnit $materialUnit): RedirectResponse
    {
        if ($materialUnit->materials()->exists()) {
            throw ValidationException::withMessages([
                'unit_name' => 'This unit is used by materials and cannot be deleted.',
            ]);
        }

        $materialUnit->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Unit deleted.']);

        return redirect()->route('material-units.index');
    }
}
