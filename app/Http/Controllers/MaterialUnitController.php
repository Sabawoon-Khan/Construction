<?php

namespace App\Http\Controllers;

use App\Concerns\ExportsSpreadsheet;
use App\Http\Controllers\Concerns\AuthorizesResourcePermissions;
use App\Http\Controllers\Concerns\ResolvesExportScope;
use App\Models\MaterialUnit;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MaterialUnitController extends Controller implements HasMiddleware
{
    protected static string $permissionModule = 'materials';

    use AuthorizesResourcePermissions;
    use ExportsSpreadsheet;
    use ResolvesExportScope;

    public function index(Request $request): Response
    {
        $search = $request->string('search')->toString();

        $items = $this->indexQuery($request)->get(['id', 'unit_name', 'user_id', 'created_at']);
        $used = $items->filter(fn ($item) => (int) ($item->materials_count ?? 0) > 0)->count();
        $count = $items->count();
        $materialsLinked = (int) $items->sum('materials_count');

        return Inertia::render('MaterialUnits/Index', [
            'items' => $items,
            'filters' => ['search' => $search],
            'totals' => [
                'count' => $count,
                'used' => $used,
                'unused' => max(0, $count - $used),
                'materials_linked' => $materialsLinked,
            ],
            'usageChart' => $items
                ->sortByDesc(fn ($item) => (int) ($item->materials_count ?? 0))
                ->take(6)
                ->values()
                ->map(fn ($item) => [
                    'key' => (string) $item->id,
                    'label' => Str::limit((string) $item->unit_name, 8, ''),
                    'value' => (int) ($item->materials_count ?? 0),
                ])
                ->all(),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        abort_unless(config('kit.features.exports'), 404);

        $rows = $this->indexQuery($request, $this->exportAppliesFilters($request))
            ->lazyById(500)
            ->map(fn (MaterialUnit $unit) => [
                $unit->unit_name,
                $unit->materials_count,
                $unit->creator?->name,
                $unit->created_at?->toDateTimeString(),
            ]);

        return $this->streamExport(
            'material-units',
            ['unit_name', 'materials_count', 'added_by', 'created_at'],
            $rows,
            $this->exportFormat($request),
        );
    }

    protected function indexQuery(Request $request, bool $applyFilters = true): Builder
    {
        $query = MaterialUnit::query()
            ->with('creator:id,name')
            ->withCount('materials')
            ->oldest();

        if ($applyFilters && ($search = $request->string('search')->toString()) !== '') {
            $query->where('unit_name', 'like', "%{$search}%");
        }

        return $query;
    }

    public function store(Request $request): RedirectResponse
    {
        MaterialUnit::query()->create($this->validated($request));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Unit created.']);

        return redirect()->route('material-units.index');
    }

    public function update(Request $request, MaterialUnit $materialUnit): RedirectResponse
    {
        $materialUnit->update($this->validated($request, $materialUnit->id));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Unit updated.']);

        return redirect()->route('material-units.index');
    }

    public function destroy(MaterialUnit $materialUnit): RedirectResponse
    {
        $materialUnit->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Unit deleted.']);

        return redirect()->route('material-units.index');
    }

    /** @return array{unit_name: string} */
    private function validated(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'unit_name' => [
                'required',
                'string',
                'max:100',
                'unique:material_units,unit_name'.($ignoreId ? ','.$ignoreId : ''),
            ],
        ]);
    }
}
