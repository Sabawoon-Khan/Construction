<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AuthorizesResourcePermissions;
use Illuminate\Routing\Controllers\HasMiddleware;
use App\Models\Partner;
use App\Models\PartnerDistribution;
use App\Models\PartnerInvestment;
use App\Models\Project;
use App\Services\ShareholderProfitService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class PartnerController extends Controller implements HasMiddleware
{
    protected static string $permissionModule = 'partners';

    use AuthorizesResourcePermissions;


    public function index(Request $request): Response
    {
        $query = Partner::query()->withCount('projects');

        if ($search = $request->string('search')->toString()) {
            $query->where(function ($q) use ($search) {
                $q->orWhere('name', 'like', "%{$search}%");
                $q->orWhere('partner_no', 'like', "%{$search}%");
                $q->orWhere('phone', 'like', "%{$search}%");
                $q->orWhere('email', 'like', "%{$search}%");
            });
        }

        $ids = (clone $query)->select('id');
        $totals = [
            'count' => (clone $query)->count(),
            'with_projects' => (clone $query)->whereHas('projects')->count(),
            'contributions' => (float) PartnerInvestment::query()->whereIn('partner_id', clone $ids)->sum('amount'),
            'payouts' => (float) PartnerDistribution::query()->whereIn('partner_id', clone $ids)->sum('amount'),
        ];

        return Inertia::render('Partners/Index', [
            'items' => $query->latest()->paginate(12)->withQueryString(),
            'filters' => ['search' => $search],
            'totals' => $totals,
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
            'partner_no' => 'nullable|string|max:50|unique:partners,partner_no',
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'national_id' => 'nullable|string|max:100',
            'address' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        Partner::create([
            ...$data,
            'partner_no' => ($data['partner_no'] ?? null) ?: 'PTR-'.Str::upper(Str::random(8)),
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Shareholder saved.']);

        return redirect()->route('partners.index');
    }

    public function show(Partner $partner): Response
    {
        $partner->load([
            'projects:id,name',
            'distributions' => fn ($q) => $q->latest('distributed_at')->limit(20),
            'investments' => fn ($q) => $q->latest('invested_at')->limit(20),
        ]);

        $projectRows = $partner->projects->map(function (Project $project) use ($partner) {
            $share = ShareholderProfitService::partnerShare($partner, $project->id);

            return [
                'id' => $project->id,
                'name' => $project->name,
                'ownership_percentage' => (float) $project->pivot->ownership_percentage,
                ...$share,
            ];
        });

        return Inertia::render('Partners/Show', [
            'item' => $partner,
            'projects' => $projectRows,
            'stats' => [
                'projects_count' => $partner->projects->count(),
                'contributions_total' => (float) $partner->investments()->sum('amount'),
                'payouts_total' => (float) $partner->distributions()->sum('amount'),
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
            'partner_no' => 'required|string|max:50|unique:partners,partner_no,'.$partner->id,
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'national_id' => 'nullable|string|max:100',
            'address' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $partner->update($data);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Shareholder updated.']);

        return redirect()->route('partners.index');
    }

    public function destroy(Request $request, Partner $partner): RedirectResponse
    {
        $partner->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Shareholder deleted.']);

        return redirect()->route('partners.index');
    }
}
