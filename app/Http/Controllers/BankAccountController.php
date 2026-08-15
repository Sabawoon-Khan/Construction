<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AuthorizesResourcePermissions;
use Illuminate\Routing\Controllers\HasMiddleware;
use App\Models\BankAccount;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BankAccountController extends Controller implements HasMiddleware
{
    protected static string $permissionModule = 'bank_accounts';

    use AuthorizesResourcePermissions;


    public function index(Request $request): Response
    {
        $query = BankAccount::query();

        if ($search = $request->string('search')->toString()) {
            $query->where(function ($q) use ($search) {
                $q->orWhere('name', 'like', "%{$search}%");
                $q->orWhere('bank_name', 'like', "%{$search}%");
                $q->orWhere('account_number', 'like', "%{$search}%");
            });
        }

        return Inertia::render('BankAccounts/Index', [
            'items' => $query->latest()->paginate(12)->withQueryString(),
            'filters' => ['search' => $search],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('BankAccounts/Form', [
            'item' => null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        BankAccount::create($this->validated($request));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Bank account created.']);

        return redirect()->route('bank-accounts.index');
    }

    public function show(BankAccount $bankAccount): Response
    {
        return Inertia::render('BankAccounts/Show', [
            'item' => $bankAccount,
            'stats' => [
                'payments_total' => $bankAccount->payments()->sum('amount'),
                'incomes_total' => $bankAccount->incomes()->sum('amount'),
                'expenses_total' => $bankAccount->expenses()->sum('amount'),
            ],
        ]);
    }

    public function edit(Request $request, BankAccount $bankAccount): Response
    {

        return Inertia::render('BankAccounts/Form', [
            'item' => $bankAccount,
        ]);
    }

    public function update(Request $request, BankAccount $bankAccount): RedirectResponse
    {

        $bankAccount->update($this->validated($request));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Bank account updated.']);

        return redirect()->route('bank-accounts.show', $bankAccount);
    }

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        $request->merge([
            'opening_balance' => $request->filled('opening_balance') ? $request->input('opening_balance') : 0,
            'iban' => $request->filled('iban') ? preg_replace('/\s+/', '', (string) $request->input('iban')) : null,
            'currency' => strtoupper(substr(trim((string) $request->input('currency', 'USD')) ?: 'USD', 0, 3)),
            'is_cash' => $request->boolean('is_cash'),
            'is_active' => $request->boolean('is_active'),
        ]);

        return $request->validate([
            'name' => 'required|string|max:255',
            'bank_name' => 'nullable|string|max:255',
            'account_number' => 'nullable|string|max:100',
            'iban' => 'nullable|string|max:34',
            'currency' => 'required|string|size:3',
            'opening_balance' => 'nullable|numeric',
            'is_cash' => 'boolean',
            'is_active' => 'boolean',
        ]);
    }

    public function destroy(Request $request, BankAccount $bankAccount): RedirectResponse
    {
        $bankAccount->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Bank Accounts deleted.']);

        return redirect()->route('bank-accounts.index');
    }
}
