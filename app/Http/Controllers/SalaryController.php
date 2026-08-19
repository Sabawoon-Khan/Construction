<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AuthorizesResourcePermissions;
use App\Models\Employee;
use App\Models\Salary;
use App\Services\CurrencyService;
use App\Services\FinanceDefaults;
use App\Support\CurrencyAmounts;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Inertia\Inertia;
use Inertia\Response;

class SalaryController extends Controller implements HasMiddleware
{
    protected static string $permissionModule = 'salaries';

    use AuthorizesResourcePermissions;

    public function index(Request $request): Response
    {
        $query = Salary::query()->with('employee:id,name,employee_no');
        $search = $request->string('search')->toString();

        if ($employeeId = $request->integer('employee_id')) {
            $query->where('employee_id', $employeeId);
        }

        if ($month = $request->integer('month')) {
            $query->where('month', $month);
        }

        if ($year = $request->integer('year')) {
            $query->where('year', $year);
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('status', 'like', "%{$search}%")
                    ->orWhereHas('employee', function ($employee) use ($search) {
                        $employee->where('name', 'like', "%{$search}%")
                            ->orWhere('employee_no', 'like', "%{$search}%");
                    });
            });
        }

        $totals = [
            'count' => (clone $query)->count(),
            'basic_total' => (float) (clone $query)->sum('basic_salary'),
            'basic_total_base' => (float) (clone $query)->sum('basic_salary_base'),
            'bonus_total' => (float) (clone $query)->sum('bonus'),
            'bonus_total_base' => (float) (clone $query)->sum('bonus_base'),
            'deduction_total' => (float) (clone $query)->sum('deduction'),
            'deduction_total_base' => (float) (clone $query)->sum('deduction_base'),
            'net_total' => (float) (clone $query)->sum('net_salary'),
            'net_total_base' => (float) (clone $query)->sum('net_salary_base'),
            'basic_by_currency' => CurrencyAmounts::sum(clone $query, 'basic_salary'),
            'bonus_by_currency' => CurrencyAmounts::sum(clone $query, 'bonus'),
            'deduction_by_currency' => CurrencyAmounts::sum(clone $query, 'deduction'),
            'net_by_currency' => CurrencyAmounts::sum(clone $query, 'net_salary'),
        ];

        return Inertia::render('Salaries/Index', [
            'items' => $query->latest('year')->latest('month')->paginate(15)->withQueryString(),
            'filters' => [
                'search' => $search,
                'employee_id' => $employeeId ?: null,
                'month' => $month ?: null,
                'year' => $year ?: null,
            ],
            'totals' => $totals,
            'employees' => Employee::query()->where('status', 'active')->orderBy('name')->get(['id', 'name', 'employee_no', 'basic_salary', 'currency']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Salaries/Form', [
            'item' => null,
            'employees' => Employee::query()->where('status', 'active')->orderBy('name')->get(['id', 'name', 'employee_no', 'basic_salary', 'currency']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        Salary::query()->create($data);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Salary record created.']);

        return redirect()->route('salaries.index');
    }

    public function edit(Salary $salary): Response
    {
        return Inertia::render('Salaries/Form', [
            'item' => $salary,
            'employees' => Employee::query()->where('status', 'active')->orderBy('name')->get(['id', 'name', 'employee_no', 'basic_salary', 'currency']),
        ]);
    }

    public function update(Request $request, Salary $salary): RedirectResponse
    {
        $salary->update($this->validated($request, $salary->id));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Salary record updated.']);

        return redirect()->route('salaries.index');
    }

    public function destroy(Salary $salary): RedirectResponse
    {
        $salary->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Salary record deleted.']);

        return redirect()->route('salaries.index');
    }

    /** @return array<string, mixed> */
    private function validated(Request $request, ?int $ignoreId = null): array
    {
        $data = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'month' => 'required|integer|between:1,12',
            'year' => 'required|integer|min:2000',
            'basic_salary' => 'required|numeric|min:0',
            'bonus' => 'nullable|numeric|min:0',
            'deduction' => 'nullable|numeric|min:0',
            'payment_date' => 'nullable|date',
            'status' => 'nullable|string|in:pending,paid,cancelled',
            'notes' => 'nullable|string',
            'currency' => 'nullable|string|in:AFN,USD',
        ]);

        $employee = Employee::query()->findOrFail($data['employee_id']);
        $currency = strtoupper($data['currency'] ?? $employee->currency ?? CurrencyService::baseCurrency());
        $paymentDate = isset($data['payment_date']) ? Carbon::parse($data['payment_date']) : now();
        $exchangeRate = FinanceDefaults::exchangeRate($currency, $paymentDate);

        $basicSalary = round((float) $data['basic_salary'], 2);
        $bonus = round((float) ($data['bonus'] ?? 0), 2);
        $deduction = round((float) ($data['deduction'] ?? 0), 2);
        $netSalary = max(0, round($basicSalary + $bonus - $deduction, 2));

        $data['currency'] = $currency;
        $data['exchange_rate'] = $exchangeRate;
        $data['basic_salary'] = $basicSalary;
        $data['basic_salary_base'] = round($basicSalary * $exchangeRate, 2);
        $data['bonus'] = $bonus;
        $data['bonus_base'] = round($bonus * $exchangeRate, 2);
        $data['deduction'] = $deduction;
        $data['deduction_base'] = round($deduction * $exchangeRate, 2);
        $data['net_salary'] = $netSalary;
        $data['net_salary_base'] = round($netSalary * $exchangeRate, 2);

        if (blank($data['status'] ?? null)) {
            $data['status'] = 'pending';
        }

        return $data;
    }
}
