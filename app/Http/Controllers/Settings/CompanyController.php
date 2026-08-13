<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Company;
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
            'name' => config('app.name', 'Yaqeen Kit'),
            'legal_name' => config('app.name', 'Yaqeen Kit'),
            'base_currency' => 'USD',
            'is_active' => true,
        ]);

        return Inertia::render('settings/Company', [
            'company' => $company,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->can('companies.edit'), 403);

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'legal_name' => 'nullable|string|max:255',
            'registration_no' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'base_currency' => 'required|string|size:3',
        ]);

        CurrentCompany::get()->update($data);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Company profile updated.'),
        ]);

        return back();
    }
}
