<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\ExchangeRate;
use App\Services\CurrencyService;
use App\Support\CurrentCompany;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CompanyController extends Controller
{
    public function edit(Request $request): Response
    {
        abort_unless($request->user()?->can('companies.view') || $request->user()?->can('companies.edit'), 403);

        $company = Company::query()->first() ?? Company::query()->create([
            'name' => 'شاهین صمیم بارکزی',
            'name_en' => 'Shahin Samim Barakzai',
            'legal_name' => 'شاهین صمیم بارکزی',
            'base_currency' => 'USD',
            'is_active' => true,
        ]);

        $currencies = ['USD', 'AFN'];
        $baseCurrency = CurrencyService::normalize($company->base_currency ?? 'USD');
        $pairs = collect($currencies)
            ->crossJoin($currencies)
            ->filter(fn (array $pair) => $pair[0] !== $pair[1] && $pair[0] === $baseCurrency)
            ->map(fn (array $pair) => ['from' => $pair[0], 'to' => $pair[1]]);

        $exchangeRates = ExchangeRate::query()
            ->whereIn('from_currency', $currencies)
            ->whereIn('to_currency', $currencies)
            ->orderByDesc('effective_date')
            ->orderByDesc('id')
            ->limit(50)
            ->get();

        return Inertia::render('settings/Company', [
            'company' => $company,
            'exchange_rates' => $exchangeRates,
            'currency_pairs' => $pairs->values(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->can('companies.edit'), 403);

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'name_en' => 'nullable|string|max:255',
            'legal_name' => 'nullable|string|max:255',
            'registration_no' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'base_currency' => 'required|string|size:3',
        ]);

        $data['base_currency'] = CurrencyService::normalize($data['base_currency']);

        CurrentCompany::get()->update($data);

        ExchangeRate::clearCache();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Company profile updated.'),
        ]);

        return back();
    }

    public function storeExchangeRate(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->can('companies.edit'), 403);

        $data = $request->validate([
            'from_currency' => 'required|string|size:3',
            'to_currency' => 'required|string|size:3',
            'rate' => 'required|numeric|min:0',
            'effective_date' => 'required|date',
        ]);

        $data['from_currency'] = strtoupper($data['from_currency']);
        $data['to_currency'] = strtoupper($data['to_currency']);
        $data['created_by'] = $request->user()?->id;

        if ($data['from_currency'] === $data['to_currency']) {
            return back()->withErrors(['to_currency' => 'From and to currencies must be different.']);
        }

        ExchangeRate::query()->updateOrCreate(
            [
                'from_currency' => $data['from_currency'],
                'to_currency' => $data['to_currency'],
                'effective_date' => $data['effective_date'],
            ],
            [
                'rate' => $data['rate'],
                'created_by' => $data['created_by'],
            ]
        );

        ExchangeRate::clearCache();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Exchange rate saved.'),
        ]);

        return back();
    }

    public function updateExchangeRate(Request $request, ExchangeRate $rate): RedirectResponse
    {
        abort_unless($request->user()?->can('companies.edit'), 403);

        $data = $request->validate([
            'rate' => 'required|numeric|min:0',
            'effective_date' => 'required|date',
        ]);

        $rate->update($data);
        ExchangeRate::clearCache();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Exchange rate updated.'),
        ]);

        return back();
    }

    public function destroyExchangeRate(Request $request, ExchangeRate $rate): RedirectResponse
    {
        abort_unless($request->user()?->can('companies.edit'), 403);

        $rate->delete();
        ExchangeRate::clearCache();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Exchange rate deleted.'),
        ]);

        return back();
    }
}
