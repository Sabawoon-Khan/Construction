<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AuthorizesResourcePermissions;
use App\Http\Controllers\Concerns\StoresRecordAttachments;
use App\Models\Material;
use App\Models\Project;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Services\PurchaseService;
use App\Support\CurrencyAmounts;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class PurchaseController extends Controller implements HasMiddleware
{
    protected static string $permissionModule = 'purchases';

    use AuthorizesResourcePermissions {
        middleware as resourceMiddleware;
    }
    use StoresRecordAttachments;

    /**
     * @return list<Middleware>
     */
    public static function middleware(): array
    {
        return [
            ...static::resourceMiddleware(),
            new Middleware('permission:purchases.edit', only: ['pay']),
        ];
    }

    public function index(Request $request): Response
    {
        $query = Purchase::query()
            ->with(['supplier:id,name', 'project:id,name', 'creator:id,name']);

        if ($search = $request->string('search')->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('purchase_no', 'like', "%{$search}%")
                    ->orWhere('reference_no', 'like', "%{$search}%")
                    ->orWhereHas('supplier', fn ($s) => $s->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('project', fn ($p) => $p->where('name', 'like', "%{$search}%"));
            });
        }

        $totalAmount = (float) (clone $query)->sum('total_amount');
        $totalAmountBase = (float) (clone $query)->sum('total_amount_base');
        $paid = (float) (clone $query)->sum('paid_amount');
        $paidBase = (float) (clone $query)->sum('paid_amount_base');
        $totalByCurrency = CurrencyAmounts::sum(clone $query, 'total_amount');
        $paidByCurrency = CurrencyAmounts::sum(clone $query, 'paid_amount');
        $remainingByCurrency = CurrencyAmounts::remaining($totalByCurrency, $paidByCurrency);

        $totals = [
            'count' => (clone $query)->count(),
            'total_amount' => $totalAmount,
            'total_amount_base' => $totalAmountBase,
            'paid' => $paid,
            'paid_base' => $paidBase,
            'remaining' => max(0, $totalAmount - $paid),
            'remaining_base' => max(0, $totalAmountBase - $paidBase),
            'total_by_currency' => $totalByCurrency,
            'paid_by_currency' => $paidByCurrency,
            'remaining_by_currency' => $remainingByCurrency,
        ];

        return Inertia::render('Purchases/Index', [
            'items' => $query->latest('purchase_date')->paginate(15)->withQueryString(),
            'filters' => ['search' => $search],
            'totals' => $totals,
        ]);
    }

    public function create(Request $request): Response
    {
        return Inertia::render('Purchases/Form', [
            'item' => null,
            ...$this->formOptions($request),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        unset($data['documents']);

        $purchase = PurchaseService::create($data);
        $this->storeRecordAttachments($request, $purchase);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Purchase recorded. Stock increased and project expense posted.']);

        return redirect()->route('purchases.show', $purchase);
    }

    public function show(Purchase $purchase): Response
    {
        $purchase->load([
            'attachments',
            'supplier',
            'project',
            'items.material.unit:id,unit_name',
            'expense:id,purchase_id,expense_no,amount,amount_base,currency',
            'payments' => fn ($q) => $q->latest('paid_at'),
        ]);

        return Inertia::render('Purchases/Show', [
            'purchase' => $purchase,
            'paid_amount' => (float) $purchase->paid_amount,
            'paid_amount_base' => (float) $purchase->paid_amount_base,
            'remaining_amount' => $purchase->remaining_amount,
            'remaining_amount_base' => $purchase->remaining_amount_base,
            'total_amount' => (float) $purchase->total_amount,
            'total_amount_base' => (float) $purchase->total_amount_base,
        ]);
    }

    public function edit(Request $request, Purchase $purchase): Response
    {
        $purchase->load(['items', 'attachments']);

        return Inertia::render('Purchases/Form', [
            'item' => $purchase,
            ...$this->formOptions($request),
        ]);
    }

    public function update(Request $request, Purchase $purchase): RedirectResponse
    {
        $data = $request->validate([
            'purchase_date' => 'required|date',
            'reference_no' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
            ...$this->attachmentValidationRules(),
        ]);
        unset($data['documents']);

        $purchase->update($data);
        $this->storeRecordAttachments($request, $purchase);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Purchase updated.']);

        return redirect()->route('purchases.show', $purchase);
    }

    public function pay(Request $request, Purchase $purchase): RedirectResponse
    {
        $data = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'nullable|string|max:50',
            'reference_no' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
        ]);

        PurchaseService::recordPayment($purchase, (float) $data['amount'], [
            'payment_method' => $data['payment_method'] ?? 'cash',
            'reference_no' => $data['reference_no'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Supplier payment recorded.']);

        return redirect()->route('purchases.show', $purchase);
    }

    public function destroy(Purchase $purchase): RedirectResponse
    {
        try {
            PurchaseService::assertDeletable($purchase);
        } catch (ValidationException $e) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => collect($e->errors())->flatten()->first() ?: 'This purchase cannot be deleted.',
            ]);

            return back();
        }

        $projectId = $purchase->project_id;
        $fromProject = $projectId && str_contains((string) url()->previous(), '/projects/'.$projectId);

        $purchase->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Purchase deleted.']);

        if ($fromProject) {
            return redirect()->to(route('projects.show', $projectId).'#expenses');
        }

        return redirect()->route('purchases.index');
    }

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'project_id' => 'required|exists:projects,id',
            'supplier_id' => 'required|exists:suppliers,id',
            'purchase_date' => 'required|date',
            'reference_no' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
            'currency' => 'nullable|string|size:3',
            'paid_now' => 'nullable|numeric|min:0',
            'payment_method' => 'nullable|string|max:50',
            'items' => 'required|array|min:1',
            'items.*.material_id' => 'required|exists:materials,id',
            'items.*.quantity' => 'required|numeric|min:0.001',
            'items.*.unit_cost' => 'required|numeric|min:0',
            ...$this->attachmentValidationRules(),
        ]);

        $materialIds = collect($data['items'])->pluck('material_id');

        if ($materialIds->count() !== $materialIds->unique()->count()) {
            throw ValidationException::withMessages([
                'items' => 'Each material can only appear once on a purchase.',
            ]);
        }

        return $data;
    }

    /** @return array<string, mixed> */
    private function formOptions(Request $request): array
    {
        return [
            'projects' => Project::query()->orderBy('name')->get(['id', 'name']),
            'suppliers' => Supplier::query()->orderBy('name')->get(['id', 'name', 'supplier_no']),
            'materials' => Material::query()
                ->where('is_active', true)
                ->with('unit:id,unit_name')
                ->orderBy('name')
                ->get(['id', 'name', 'code', 'unit_cost', 'material_unit_id']),
            'project_id' => $request->integer('project_id') ?: null,
        ];
    }
}
