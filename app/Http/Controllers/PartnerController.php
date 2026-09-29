<?php

namespace App\Http\Controllers;

use App\Concerns\ExportsSpreadsheet;
use App\Http\Controllers\Concerns\AuthorizesResourcePermissions;
use App\Http\Controllers\Concerns\ResolvesExportScope;
use App\Http\Controllers\Concerns\StoresRecordAttachments;
use App\Models\Partner;
use App\Models\PartnerDistribution;
use App\Models\PartnerInvestment;
use App\Models\Project;
use App\Models\User;
use App\Services\CurrencyService;
use App\Services\ShareholderProfitService;
use App\Support\CurrencyAmounts;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PartnerController extends Controller implements HasMiddleware
{
    protected static string $permissionModule = 'partners';

    use AuthorizesResourcePermissions;
    use ExportsSpreadsheet;
    use ResolvesExportScope;
    use StoresRecordAttachments;

    public function index(Request $request): Response
    {
        $search = $request->string('search')->toString();
        $query = $this->indexQuery($request);

        $ids = (clone $query)->select('id');
        $contributionsQuery = PartnerInvestment::query()->whereIn('partner_id', clone $ids);
        $payoutsQuery = PartnerDistribution::query()->whereIn('partner_id', clone $ids);
        $count = (clone $query)->count();
        $withProjects = (clone $query)->whereHas('projects')->count();
        $totals = [
            'count' => $count,
            'with_projects' => $withProjects,
            'without_projects' => max(0, $count - $withProjects),
            'contributions' => (float) (clone $contributionsQuery)->sum('amount'),
            'contributions_by_currency' => CurrencyAmounts::sum(clone $contributionsQuery),
            'payouts' => (float) (clone $payoutsQuery)->sum('amount'),
            'payouts_by_currency' => CurrencyAmounts::sum(clone $payoutsQuery),
        ];

        return Inertia::render('Partners/Index', [
            'items' => $query->oldest()->paginate(12)->withQueryString(),
            'filters' => ['search' => $search],
            'totals' => $totals,
            'moneyChart' => $this->investmentsByMonth(clone $contributionsQuery),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        abort_unless(config('kit.features.exports'), 404);

        $rows = $this->indexQuery($request, $this->exportAppliesFilters($request))
            ->oldest()
            ->lazyById(500)
            ->map(fn (Partner $partner) => [
                $partner->partner_no,
                $partner->name,
                $partner->phone,
                $partner->projects_count,
                $partner->creator?->name,
                $partner->created_at?->toDateTimeString(),
            ]);

        return $this->streamExport(
            'partners',
            ['partner_no', 'name', 'phone', 'projects', 'added_by', 'created_at'],
            $rows,
            $this->exportFormat($request),
        );
    }

    protected function indexQuery(Request $request, bool $applyFilters = true): Builder
    {
        $query = Partner::query()->with('creator:id,name')->withCount('projects');

        if ($request->user()?->isRestrictedShareholder() && $request->user()->partner) {
            $query->whereKey($request->user()->partner->id);
        }

        if ($applyFilters && ($search = $request->string('search')->toString())) {
            $query->where(function ($q) use ($search) {
                $q->orWhere('name', 'like', "%{$search}%");
                $q->orWhere('partner_no', 'like', "%{$search}%");
                $q->orWhere('phone', 'like', "%{$search}%");
                $q->orWhere('email', 'like', "%{$search}%");
            });
        }

        return $query;
    }

    public function create(): Response
    {
        return Inertia::render('Partners/Form', [
            'item' => null,
            'users' => $this->linkableUsers(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->merge(['user_id' => $request->input('user_id') ?: null]);

        $data = $request->validate([
            'partner_no' => 'nullable|string|max:50|unique:partners,partner_no',
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'national_id' => 'nullable|string|max:100',
            'address' => 'nullable|string',
            'notes' => 'nullable|string',
            'user_id' => 'nullable|exists:users,id|unique:partners,user_id',
            ...$this->attachmentValidationRules(),
        ]);
        unset($data['documents']);

        $partner = Partner::create([
            ...$data,
            'partner_no' => ($data['partner_no'] ?? null) ?: 'PTR-'.Str::upper(Str::random(8)),
        ]);
        $this->storeRecordAttachments($request, $partner);

        $this->syncShareholderRole($partner);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Shareholder saved.']);

        return redirect()->route('partners.index');
    }

    public function show(Request $request, Partner $partner): Response
    {
        abort_if(
            $request->user()?->isRestrictedShareholder()
                && (int) $request->user()->partner?->id !== (int) $partner->id,
            403,
        );

        $partner->load([
            'attachments',
            'projects:id,name',
            'distributions' => fn ($q) => $q->oldest()->limit(20),
            'investments' => fn ($q) => $q->oldest()->limit(20),
        ]);

        $projectRows = $partner->projects->map(function (Project $project) use ($partner) {
            $share = ShareholderProfitService::partnerShare($partner, $project->id);

            return [
                'id' => $project->id,
                'name' => $project->name,
                'ownership_percentage' => (float) $project->pivot->ownership_percentage,
                'created_at' => $project->created_at,
                ...$share,
            ];
        });

        return Inertia::render('Partners/Show', [
            'item' => $partner,
            'projects' => $projectRows,
            'stats' => [
                'projects_count' => $partner->projects->count(),
                'contributions_total' => (float) $partner->investments()->sum('amount'),
                'contributions_by_currency' => CurrencyAmounts::sum($partner->investments()->getQuery()),
                'payouts_total' => (float) $partner->distributions()->sum('amount'),
                'payouts_by_currency' => CurrencyAmounts::sum($partner->distributions()->getQuery()),
            ],
        ]);
    }

    public function edit(Request $request, Partner $partner): Response
    {
        $partner->load('attachments');

        return Inertia::render('Partners/Form', [
            'item' => $partner,
            'users' => $this->linkableUsers($partner->id),
        ]);
    }

    public function update(Request $request, Partner $partner): RedirectResponse
    {
        $request->merge(['user_id' => $request->input('user_id') ?: null]);

        $data = $request->validate([
            'partner_no' => 'required|string|max:50|unique:partners,partner_no,'.$partner->id,
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'national_id' => 'nullable|string|max:100',
            'address' => 'nullable|string',
            'notes' => 'nullable|string',
            'user_id' => 'nullable|exists:users,id|unique:partners,user_id,'.$partner->id,
            ...$this->attachmentValidationRules(),
        ]);
        unset($data['documents']);

        $partner->update($data);
        $this->storeRecordAttachments($request, $partner);
        $this->syncShareholderRole($partner);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Shareholder updated.']);

        return redirect()->route('partners.index');
    }

    public function destroy(Request $request, Partner $partner): RedirectResponse
    {
        $partner->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Shareholder deleted.']);

        return redirect()->route('partners.index');
    }

    private function syncShareholderRole(Partner $partner): void
    {
        if (! $partner->user_id) {
            return;
        }

        $user = User::query()->find($partner->user_id);

        if ($user && ! $user->hasAnyRole(['Owner', 'Admin', 'Finance', 'Project Manager', 'Shareholder'])) {
            $user->assignRole('Shareholder');
        }
    }

    /** @return Collection<int, User> */
    private function linkableUsers(?int $exceptPartnerId = null)
    {
        return User::query()
            ->orderBy('name')
            ->where(function ($q) use ($exceptPartnerId) {
                $q->whereDoesntHave('partner');

                if ($exceptPartnerId) {
                    $q->orWhereHas('partner', fn ($p) => $p->whereKey($exceptPartnerId));
                }
            })
            ->get(['id', 'name', 'email']);
    }

    /**
     * @return list<array{key: string, label: string, by_currency: array{AFN: float, USD: float}}>
     */
    protected function investmentsByMonth($investmentQuery): array
    {
        $to = Carbon::now()->endOfMonth();
        $from = Carbon::now()->subMonths(5)->startOfMonth();

        $buckets = [];
        $cursor = $from->copy();
        while ($cursor->lte($to)) {
            $key = $cursor->format('Y-m');
            $buckets[$key] = [
                'key' => $key,
                'label' => $cursor->format('M'),
                'by_currency' => ['AFN' => 0.0, 'USD' => 0.0],
            ];
            $cursor = $cursor->addMonth();
        }

        $investments = (clone $investmentQuery)
            ->whereDate('invested_at', '>=', $from->toDateString())
            ->whereDate('invested_at', '<=', $to->toDateString())
            ->get(['id', 'invested_at', 'amount', 'currency']);

        foreach ($investments as $investment) {
            $date = $investment->invested_at;
            if (! $date) {
                continue;
            }
            $key = $date->format('Y-m');
            if (! isset($buckets[$key])) {
                continue;
            }

            $code = CurrencyService::normalize((string) ($investment->currency ?: 'USD'));
            $amount = max(0, (float) ($investment->amount ?? 0));
            $buckets[$key]['by_currency'][$code] = round(
                ($buckets[$key]['by_currency'][$code] ?? 0) + $amount,
                2,
            );
        }

        return array_values(array_map(
            static fn (array $bucket) => [
                'key' => $bucket['key'],
                'label' => $bucket['label'],
                'by_currency' => [
                    'AFN' => round((float) ($bucket['by_currency']['AFN'] ?? 0), 2),
                    'USD' => round((float) ($bucket['by_currency']['USD'] ?? 0), 2),
                ],
            ],
            $buckets,
        ));
    }
}
