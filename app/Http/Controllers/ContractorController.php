<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AuthorizesResourcePermissions;
use Illuminate\Routing\Controllers\HasMiddleware;
use App\Models\Contractor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ContractorController extends Controller implements HasMiddleware
{
    protected static string $permissionModule = 'contractors';

    use AuthorizesResourcePermissions;


    public function index(Request $request): Response
    {
        $query = Contractor::query();

        if ($search = $request->string('search')->toString()) {
            $query->where(function ($q) use ($search) {
                $q->orWhere('name', 'like', "%{$search}%");
                $q->orWhere('contractor_no', 'like', "%{$search}%");
                $q->orWhere('phone', 'like', "%{$search}%");
                $q->orWhere('contact_person', 'like', "%{$search}%");
            });
        }

        return Inertia::render('Contractors/Index', [
            'items' => $query->latest()->paginate(12)->withQueryString(),
            'filters' => ['search' => $search],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Contractors/Form', [
            'item' => null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'contractor_no' => 'required|string|max:50',
            'name' => 'required|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        Contractor::create(array_merge([], $data));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Contractors created.']);

        return redirect()->route('contractors.index');
    }

    public function show(Contractor $contractor): Response
    {
        $contractor->load([
            'expenses' => fn ($q) => $q->with(['project:id,name', 'category:id,name'])->latest('expense_date')->limit(10),
        ]);

        return Inertia::render('Contractors/Show', [
            'item' => $contractor,
            'stats' => [
                'expenses_total' => $contractor->expenses()->sum('amount'),
                'expenses_count' => $contractor->expenses()->count(),
            ],
        ]);
    }

    public function edit(Request $request, Contractor $contractor): Response
    {

        return Inertia::render('Contractors/Form', [
            'item' => $contractor,
        ]);
    }

    public function update(Request $request, Contractor $contractor): RedirectResponse
    {

        $data = $request->validate([
            'contractor_no' => 'required|string|max:50',
            'name' => 'required|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $contractor->update($data);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Contractors updated.']);

        return redirect()->route('contractors.index');
    }

    public function destroy(Request $request, Contractor $contractor): RedirectResponse
    {
        $contractor->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Contractors deleted.']);

        return redirect()->route('contractors.index');
    }
}
