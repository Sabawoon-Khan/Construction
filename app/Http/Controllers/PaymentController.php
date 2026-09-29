<?php

namespace App\Http\Controllers;

use App\Concerns\ExportsSpreadsheet;
use App\Http\Controllers\Concerns\AuthorizesResourcePermissions;
use App\Http\Controllers\Concerns\ResolvesExportScope;
use App\Models\Payment;
use App\Support\CurrencyAmounts;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PaymentController extends Controller implements HasMiddleware
{
    protected static string $permissionModule = 'payments';

    use AuthorizesResourcePermissions;
    use ExportsSpreadsheet;
    use ResolvesExportScope;

    public function index(Request $request): Response
    {
        $search = $request->string('search')->toString();
        $query = $this->indexQuery($request)
            ->with(['customer:id,name', 'supplier:id,name', 'project:id,name', 'sale:id,sale_no', 'purchase:id,purchase_no', 'creator:id,name']);

        $receipts = (clone $query)->where('type', 'receipt');
        $disbursements = (clone $query)->where('type', 'disbursement');
        $totals = [
            'count' => (clone $query)->count(),
            'receipts_count' => (clone $receipts)->count(),
            'disbursements_count' => (clone $disbursements)->count(),
            'amount' => (float) (clone $receipts)->sum('amount'),
            'amount_base' => (float) (clone $receipts)->sum('amount_base'),
            'paid_out' => (float) (clone $disbursements)->sum('amount'),
            'paid_out_base' => (float) (clone $disbursements)->sum('amount_base'),
            'this_month' => (float) (clone $query)->where('paid_at', '>=', now()->startOfMonth())->sum('amount'),
            'this_month_base' => (float) (clone $query)->where('paid_at', '>=', now()->startOfMonth())->sum('amount_base'),
            'cash' => (float) (clone $query)->where('payment_method', 'cash')->sum('amount'),
            'cash_base' => (float) (clone $query)->where('payment_method', 'cash')->sum('amount_base'),
            'amount_by_currency' => CurrencyAmounts::sum(clone $receipts),
            'paid_out_by_currency' => CurrencyAmounts::sum(clone $disbursements),
            'this_month_by_currency' => CurrencyAmounts::sum(
                (clone $query)->where('paid_at', '>=', now()->startOfMonth())
            ),
        ];

        return Inertia::render('Payments/Index', [
            'items' => $query->oldest()->paginate(15)->withQueryString(),
            'filters' => ['search' => $search],
            'totals' => $totals,
            'moneyChart' => CurrencyAmounts::sumByMonth(clone $receipts, 'paid_at'),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        abort_unless(config('kit.features.exports'), 404);

        $query = $this->indexQuery($request, $this->exportAppliesFilters($request));

        $rows = (clone $query)
            ->with(['customer:id,name', 'supplier:id,name', 'sale:id,sale_no', 'purchase:id,purchase_no', 'creator:id,name'])
            ->orderBy('id')
            ->lazyById(500)
            ->map(fn (Payment $payment) => [
                $payment->payment_no,
                $payment->paid_at?->toDateTimeString(),
                $payment->type,
                $payment->supplier?->name ?? $payment->customer?->name,
                $payment->purchase?->purchase_no ?? $payment->sale?->sale_no,
                (float) ($payment->amount ?? 0),
                $payment->currency,
                (float) ($payment->amount_base ?? 0),
                $payment->payment_method,
                $payment->status,
                $payment->creator?->name,
                $payment->created_at?->toDateTimeString(),
            ]);

        return $this->streamExport('payments', [
            'No',
            'Date',
            'Type',
            'Party',
            'Linked',
            'Amount',
            'Currency',
            'Amount (base)',
            'Method',
            'Status',
            'Added by',
            'Created at',
        ], $rows, $this->exportFormat($request));
    }

    /**
     * @return Builder<Payment>
     */
    protected function indexQuery(Request $request, bool $applyFilters = true): Builder
    {
        $query = Payment::query()
            ->where(function ($q) {
                $q->whereNull('sale_id')->orWhereHas('sale');
            })
            ->where(function ($q) {
                $q->whereNull('purchase_id')->orWhereHas('purchase');
            });

        if ($applyFilters && ($search = $request->string('search')->toString())) {
            $query->where(function ($q) use ($search) {
                $q->where('payment_no', 'like', "%{$search}%")
                    ->orWhere('reference_no', 'like', "%{$search}%");
            });
        }

        return $query;
    }

    public function show(Payment $payment): Response
    {
        $payment->load([
            'attachments',
            'customer:id,name',
            'supplier:id,name',
            'project:id,name',
            'sale:id,sale_no',
            'purchase:id,purchase_no',
            'installment',
        ]);

        return Inertia::render('Payments/Show', [
            'item' => $payment,
        ]);
    }
}
