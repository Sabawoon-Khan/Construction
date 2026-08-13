<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AuthorizesResourcePermissions;
use Illuminate\Routing\Controllers\HasMiddleware;
use App\Models\Partner;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PartnerController extends Controller implements HasMiddleware
{
    protected static string $permissionModule = 'partners';

    use AuthorizesResourcePermissions;


    public function index(Request $request): Response
    {
        $query = Partner::query();

        if ($search = $request->string('search')->toString()) {
            $query->where(function ($q) use ($search) {
                $q->orWhere('name', 'like', "%{$search}%");
                $q->orWhere('partner_no', 'like', "%{$search}%");
                $q->orWhere('phone', 'like', "%{$search}%");
                $q->orWhere('email', 'like', "%{$search}%");
            });
        }

        return Inertia::render('Partners/Index', [
            'items' => $query->latest()->paginate(12)->withQueryString(),
            'filters' => ['search' => $search],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Partners/Form', [
            'item' => null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'partner_no' => 'required|string|max:50',
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'national_id' => 'nullable|string|max:100',
            'ownership_percentage' => 'required|numeric|min:0|max:100',
            'address' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        Partner::create(array_merge(['ownership_percentage' => 0], $data));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Shareholders created.']);

        return redirect()->route('partners.index');
    }

    public function show(Partner $partner): Response
    {
        $partner->load([
            'investments' => fn ($q) => $q->latest()->limit(10),
            'distributions' => fn ($q) => $q->latest()->limit(10),
        ]);

        return Inertia::render('Partners/Show', [
            'item' => $partner,
            'stats' => [
                'investments_total' => $partner->investments()->sum('amount'),
                'distributions_total' => $partner->distributions()->sum('amount'),
            ],
        ]);
    }

    public function edit(Request $request, Partner $partner): Response
    {

        return Inertia::render('Partners/Form', [
            'item' => $partner,
        ]);
    }

    public function update(Request $request, Partner $partner): RedirectResponse
    {

        $data = $request->validate([
            'partner_no' => 'required|string|max:50',
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'national_id' => 'nullable|string|max:100',
            'ownership_percentage' => 'required|numeric|min:0|max:100',
            'address' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $partner->update($data);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Shareholders updated.']);

        return redirect()->route('partners.index');
    }

    public function destroy(Request $request, Partner $partner): RedirectResponse
    {
        $partner->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Shareholders deleted.']);

        return redirect()->route('partners.index');
    }
}
