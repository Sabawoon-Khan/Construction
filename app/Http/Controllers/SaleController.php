<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AuthorizesResourcePermissions;
use Illuminate\Routing\Controllers\HasMiddleware;
use App\Models\BankAccount;
use App\Models\Customer;
use App\Models\Project;
use App\Models\Sale;
use App\Models\SaleInstallment;
use App\Models\Unit;
use App\Services\SaleInstallmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class SaleController extends Controller implements HasMiddleware
{
    protected static string $permissionModule = 'sales';

    use AuthorizesResourcePermissions;


    public function index(Request $request): Response
    {
        $query = Sale::query()
            ->with(['customer:id,name', 'project:id,name', 'unit:id,unit_no,list_price,currency']);

        if ($search = $request->string('search')->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('sale_no', 'like', "%{$search}%")
                    ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', "%{$search}%"));
            });
        }

        $totals = [
            'total_price' => Sale::sumTotalPrice(clone $query),
        ];

        return Inertia::render('Sales/Index', [
            'items' => $query->latest('contract_date')->paginate(15)->withQueryString(),
            'filters' => ['search' => $search],
            'totals' => $totals,
        ]);
    }

    public function create(Request $request): Response
    {
        return Inertia::render('Sales/Form', [
            'item' => null,
            ...$this->formOptions($request),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        unset($data['installments'], $data['documents']);
        $this->ensureUnitHasListPrice((int) $data['unit_id']);

        $sale = DB::transaction(function () use ($request, $data) {
            $sale = Sale::create([
                ...$data,
                'sale_no' => $data['sale_no'] ?: 'SAL-'.Str::upper(Str::random(8)),
                'discount' => $data['discount'] ?? 0,
                'currency' => $data['currency'] ?? 'USD',
            ]);

            Unit::whereKey($sale->unit_id)->update(['status' => 'sold']);
            $sale->load('unit');

            $this->storeInstallments($request, $sale);
            $this->storeDocuments($request, $sale);

            return $sale;
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Sale created. Receive payments on this page.']);

        return redirect()->route('sales.show', $sale);
    }

    public function show(Request $request, Sale $sale): Response
    {

        $sale->load([
            'customer',
            'project',
            'unit',
            'installments',
            'attachments',
            'payments' => fn ($q) => $q->latest('paid_at'),
        ]);

        return Inertia::render('Sales/Show', [
            'sale' => $sale,
            'paid_amount' => $sale->paid_amount,
            'remaining_amount' => $sale->remaining_amount,
            'total_price' => $sale->total_price,
            'bankAccounts' => BankAccount::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'is_cash']),
        ]);
    }

    public function edit(Request $request, Sale $sale): Response
    {
        $sale->load(['installments', 'attachments', 'unit:id,unit_no,list_price,currency']);

        return Inertia::render('Sales/Form', [
            'item' => $sale,
            ...$this->formOptions($request, $sale),
        ]);
    }

    public function update(Request $request, Sale $sale): RedirectResponse
    {
        $data = $this->validated($request);
        unset($data['installments'], $data['documents']);
        $this->ensureUnitHasListPrice((int) $data['unit_id']);

        $sale->update([
            ...$data,
            'discount' => $data['discount'] ?? 0,
            'currency' => $data['currency'] ?? 'USD',
        ]);

        $this->storeDocuments($request, $sale);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Sale updated.']);

        return redirect()->route('sales.show', $sale);
    }

    public function payInstallment(Request $request, Sale $sale, SaleInstallment $installment): RedirectResponse
    {
        abort_unless($installment->sale_id === $sale->id, 404);

        if ($request->input('bank_account_id') === '') {
            $request->merge(['bank_account_id' => null]);
        }

        $data = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'nullable|string|max:50',
            'bank_account_id' => 'nullable|exists:bank_accounts,id',
            'reference_no' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
        ]);

        SaleInstallmentService::recordPayment($sale, $installment, (float) $data['amount'], [
            'payment_method' => $data['payment_method'] ?? 'cash',
            'bank_account_id' => $data['bank_account_id'] ?? null,
            'reference_no' => $data['reference_no'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Installment payment recorded.']);

        return redirect()->route('sales.show', $sale);
    }

    public function destroy(Request $request, Sale $sale): RedirectResponse
    {
        $unitId = $sale->unit_id;
        $sale->loadMissing('payments');
        $sale->delete();
        Unit::whereKey($unitId)->update(['status' => 'available']);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Sale deleted.']);

        return redirect()->route('sales.index');
    }

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        return $request->validate([
            'project_id' => 'required|exists:projects,id',
            'unit_id' => 'required|exists:units,id',
            'customer_id' => 'required|exists:customers,id',
            'sale_no' => 'nullable|string|max:50',
            'contract_date' => 'nullable|date',
            'discount' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|size:3',
            'status' => 'required|string|max:50',
            'terms' => 'nullable|string',
            'notes' => 'nullable|string',
            'installments' => 'nullable|array',
            'installments.*.due_date' => 'nullable|date',
            'installments.*.amount' => 'nullable|numeric|min:0',
            'installments.*.paid_now' => 'nullable|numeric|min:0',
            'installments.*.notes' => 'nullable|string',
            'documents' => 'nullable|array',
            'documents.*' => 'nullable|file|max:20480',
        ]);
    }

    private function storeInstallments(Request $request, Sale $sale): void
    {
        $rows = collect($request->input('installments', []))
            ->filter(fn ($row) => filled($row['amount'] ?? null) && (float) $row['amount'] > 0)
            ->values();

        if ($rows->isEmpty()) {
            $rows = collect([[
                'due_date' => $sale->contract_date?->toDateString() ?? now()->toDateString(),
                'amount' => $sale->total_price,
                'paid_now' => 0,
                'notes' => null,
            ]]);
        }

        foreach ($rows as $index => $row) {
            $installment = SaleInstallment::create([
                'sale_id' => $sale->id,
                'installment_no' => $index + 1,
                'due_date' => $row['due_date'] ?? now()->addMonths($index + 1)->toDateString(),
                'amount' => $row['amount'],
                'paid_amount' => 0,
                'status' => 'pending',
                'notes' => $row['notes'] ?? null,
            ]);

            $paidNow = (float) ($row['paid_now'] ?? 0);

            if ($paidNow > 0) {
                SaleInstallmentService::recordPayment($sale->fresh(['unit', 'installments']), $installment, $paidNow);
            }
        }
    }

    private function storeDocuments(Request $request, Sale $sale): void
    {
        if (! $request->hasFile('documents')) {
            return;
        }

        foreach ($request->file('documents', []) as $file) {
            if (! $file) {
                continue;
            }

            $sale->storeAttachment($file, $file->getClientOriginalName(), $request->user()?->id);
        }
    }

    private function ensureUnitHasListPrice(int $unitId): void
    {
        $unit = Unit::query()->findOrFail($unitId);

        if ((float) $unit->list_price <= 0) {
            throw ValidationException::withMessages([
                'unit_id' => 'Set a list price on the unit first — that is the sale price.',
            ]);
        }
    }

    /** @return array<string, mixed> */
    private function formOptions(Request $request, ?Sale $sale = null): array
    {
        $unitsQuery = Unit::query()
            ->with('project:id,name')
            ->orderBy('unit_no');

        if ($sale) {
            $unitsQuery->where(function ($query) use ($sale) {
                $query->whereIn('status', ['available', 'reserved'])
                    ->orWhere('id', $sale->unit_id);
            });
        } else {
            $unitsQuery->whereIn('status', ['available', 'reserved']);
        }

        return [
            'projects' => Project::query()->orderBy('name')->get(['id', 'name']),
            'customers' => Customer::query()->orderBy('name')->get(['id', 'name']),
            'units' => $unitsQuery->get(['id', 'project_id', 'unit_no', 'list_price', 'currency', 'status']),
        ];
    }
}
