<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AuthorizesResourcePermissions;
use Illuminate\Routing\Controllers\HasMiddleware;
use App\Models\Project;
use App\Models\Sale;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProjectController extends Controller implements HasMiddleware
{
    protected static string $permissionModule = 'projects';

    use AuthorizesResourcePermissions;


    public function index(Request $request): Response
    {
        $query = Project::query();
        $query->withCount(['units', 'sales', 'expenses']);

        if ($search = $request->string('search')->toString()) {
            $query->where(function ($q) use ($search) {
                $q->orWhere('name', 'like', "%{$search}%");
                $q->orWhere('code', 'like', "%{$search}%");
                $q->orWhere('location', 'like', "%{$search}%");
            });
        }

        return Inertia::render('Projects/Index', [
            'items' => $query->latest()->paginate(12)->withQueryString(),
            'filters' => ['search' => $search],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Projects/Form', [
            'item' => null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'code' => 'required|string|max:50',
            'name' => 'required|string|max:255',
            'type' => 'nullable|string|max:50',
            'location' => 'nullable|string|max:255',
            'start_date' => 'nullable|date',
            'expected_completion_date' => 'nullable|date',
            'estimated_budget' => 'nullable|numeric|min:0',
            'estimated_revenue' => 'nullable|numeric|min:0',
            'progress_percent' => 'nullable|numeric|min:0|max:100',
            'status' => 'required|string|max:50',
            'description' => 'nullable|string',
        ]);

        Project::create(array_merge(['type' => 'apartment', 'status' => 'planning', 'estimated_budget' => 0, 'estimated_revenue' => 0, 'progress_percent' => 0], $data));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Projects created.']);

        return redirect()->route('projects.index');
    }

    public function show(Project $project): Response
    {
        $project->loadCount(['units', 'sales', 'expenses', 'incomes']);
        $project->load([
            'units' => fn ($q) => $q->latest()->limit(8),
            'sales' => fn ($q) => $q->with(['customer:id,name', 'unit:id,list_price,currency'])->latest()->limit(8),
        ]);

        return Inertia::render('Projects/Show', [
            'item' => $project,
            'stats' => [
                'units_count' => $project->units_count,
                'sales_count' => $project->sales_count,
                'expenses_total' => $project->expenses()->sum('amount'),
                'incomes_total' => $project->incomes()->sum('amount'),
                'sales_total' => Sale::sumTotalPrice($project->sales()->getQuery()),
            ],
        ]);
    }

    public function edit(Request $request, Project $project): Response
    {

        return Inertia::render('Projects/Form', [
            'item' => $project,
        ]);
    }

    public function update(Request $request, Project $project): RedirectResponse
    {

        $data = $request->validate([
            'code' => 'required|string|max:50',
            'name' => 'required|string|max:255',
            'type' => 'nullable|string|max:50',
            'location' => 'nullable|string|max:255',
            'start_date' => 'nullable|date',
            'expected_completion_date' => 'nullable|date',
            'estimated_budget' => 'nullable|numeric|min:0',
            'estimated_revenue' => 'nullable|numeric|min:0',
            'progress_percent' => 'nullable|numeric|min:0|max:100',
            'status' => 'required|string|max:50',
            'description' => 'nullable|string',
        ]);

        $project->update($data);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Projects updated.']);

        return redirect()->route('projects.index');
    }

    public function destroy(Request $request, Project $project): RedirectResponse
    {
        $project->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Projects deleted.']);

        return redirect()->route('projects.index');
    }
}
