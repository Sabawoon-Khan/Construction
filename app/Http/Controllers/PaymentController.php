<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AuthorizesResourcePermissions;
use Illuminate\Routing\Controllers\HasMiddleware;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class PaymentController extends Controller implements HasMiddleware
{
    protected static string $permissionModule = 'payments';

    use AuthorizesResourcePermissions;


    public function index(Request $request): Response
    {
        $query = Payment::query()
            ->with(['customer:id,name', 'project:id,name', 'sale:id,sale_no']);

        if ($search = $request->string('search')->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('payment_no', 'like', "%{$search}%")
                    ->orWhere('reference_no', 'like', "%{$search}%");
            });
        }

        $totalAmount = (clone $query)->sum('amount');

        return Inertia::render('Payments/Index', [
            'items' => $query->latest('paid_at')->paginate(15)->withQueryString(),
            'filters' => ['search' => $search],
            'totals' => ['amount' => $totalAmount],
        ]);
    }

    public function create(Request $request): Response
    {
        return Inertia::render('Payments/Form', [
            'item' => null,
            ...$this->formOptions($request),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        DB::transaction(function () use ($data) {
            Payment::create([
                ...$data,
                'payment_no' => ($data['payment_no'] ?? null) ?: 'PAY-'.Str::upper(Str::random(8)),
                'type' => $data['type'] ?? 'receipt',
                'status' => $data['status'] ?? 'posted',
            ]);
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Payment recorded.']);

        return redirect()->route('payments.index');
    }

    public function show(Payment $payment): Response
    {
        $payment->load([
            'customer:id,name',
            'project:id,name',
            'sale:id,sale_no',
            'installment',
            'bankAccount:id,name',
        ]);

        return Inertia::render('Payments/Show', [
            'item' => $payment,
        ]);
    }

    public function destroy(Request $request, Payment $payment): RedirectResponse
    {
        $payment->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Payment deleted.']);

        return redirect()->route('payments.index');
    }

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        return $request->validate([
            'project_id' => 'nullable|exists:projects,id',
            'sale_id' => 'nullable|exists:sales,id',
            'sale_installment_id' => 'nullable|exists:sale_installments,id',
            'customer_id' => 'nullable|exists:customers,id',
            'bank_account_id' => 'nullable|exists:bank_accounts,id',
            'payment_no' => 'nullable|string|max:50',
            'type' => 'required|string|max:50',
            'amount' => 'required|numeric|min:0',
            'currency' => 'required|string|size:3',
            'paid_at' => 'required|date',
            'payment_method' => 'required|string|max:50',
            'reference_no' => 'nullable|string|max:100',
            'status' => 'required|string|max:50',
            'notes' => 'nullable|string',
        ]);
    }

    /** @return array<string, mixed> */
    private function formOptions(Request $request): array
    {

        return [
            'projects' => Project::query()->orderBy('name')->get(['id', 'name']),
            'customers' => Customer::query()->orderBy('name')->get(['id', 'name']),
        ];
    }
}
