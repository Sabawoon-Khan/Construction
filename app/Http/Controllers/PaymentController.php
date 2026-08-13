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
