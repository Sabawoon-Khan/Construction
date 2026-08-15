<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AuthorizesResourcePermissions;
use Illuminate\Routing\Controllers\HasMiddleware;
use App\Models\Payment;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PaymentController extends Controller implements HasMiddleware
{
    protected static string $permissionModule = 'payments';

    use AuthorizesResourcePermissions;


    public function index(Request $request): Response
    {
        $query = Payment::query()
            ->where(function ($q) {
                $q->whereNull('sale_id')->orWhereHas('sale');
            })
            ->with(['customer:id,name', 'project:id,name', 'sale:id,sale_no']);

        if ($search = $request->string('search')->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('payment_no', 'like', "%{$search}%")
                    ->orWhere('reference_no', 'like', "%{$search}%");
            });
        }

        $totals = [
            'count' => (clone $query)->count(),
            'amount' => (float) (clone $query)->sum('amount'),
            'this_month' => (float) (clone $query)->where('paid_at', '>=', now()->startOfMonth())->sum('amount'),
            'cash' => (float) (clone $query)->where('payment_method', 'cash')->sum('amount'),
        ];

        return Inertia::render('Payments/Index', [
            'items' => $query->latest('paid_at')->paginate(15)->withQueryString(),
            'filters' => ['search' => $search],
            'totals' => $totals,
        ]);
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
}
