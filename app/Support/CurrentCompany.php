<?php

namespace App\Support;

use App\Models\Company;
use RuntimeException;

/**
 * This MIS is single-company. The companies table stores the business profile only.
 * Projects, sales, expenses and incomes do not need company_id — they all belong here.
 */
class CurrentCompany
{
    public static function get(): Company
    {
        $company = Company::query()->first();

        if (! $company) {
            throw new RuntimeException('No company has been set up for this MIS.');
        }

        return $company;
    }
}
