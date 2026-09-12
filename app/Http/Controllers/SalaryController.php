<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AuthorizesResourcePermissions;
use App\Http\Controllers\Concerns\StoresRecordAttachments;
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
    use StoresRecordAttachments;

    public function index(Request $request): Response
    {
        $query = Salary::query()->with(['employee:id,name,employee_no', 'creator:id,name']);
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

        $paidQuery = (clone $query)->where('status', 'paid');
        $pendingQuery = (clone $query)->where('status', '!=', 'paid');

        $totals = [
            'count' => (clone $query)->count(),
            'paid_count' => (clone $paidQuery)->count(),
            'pending_count' => (clone $pendingQuery)->count(),
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
            'paid_net_total' => (float) (clone $paidQuery)->sum('net_salary'),
            'pending_net_total' => (float) (clone $pendingQuery)->sum('net_salary'),
            'paid_net_by_currency' => CurrencyAmounts::sum(clone $paidQuery, 'net_salary'),
            'pending_net_by_currency' => CurrencyAmounts::sum(clone $pendingQuery, 'net_salary'),
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
            'moneyChart' => $this->salariesByMonth(clone $query),
            'employees' => Employee::query()->where('status', 'active')->orderBy('name')->get(['id', 'name', 'employee_no', 'basic_salary', 'currency']),
        ]);
    }

    /**
     * Net salary by payroll month (AFN/USD) for the last 6 calendar months.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<\App\Models\Salary>  $query
     * @return list<array{key: string, label: string, by_currency: array{AFN: float, USD: float}}>
     */
    protected function salariesByMonth($query): array
    {
        $to = Carbon::now()->endOfMonth();
        $from = Carbon::now()->subMonths(5)->startOfMonth();

        $buckets = [];
        $cursor = $from->copy();
        while ($cursor->lte($to)) {
            $key = $cursor->format('Y-m');
            $buckets[$key] = [
                'key' => $key,
                'label' => $cursor->format('M'),
                'by_currency' => ['AFN' => 0.0, 'USD' => 0.0],
            ];
            $cursor = $cursor->addMonth();
        }

        $salaries = (clone $query)
            ->where(function ($q) use ($from, $to) {
                $q->where(function ($inner) use ($from) {
                    $inner->where('year', '>', $from->year)
                        ->orWhere(function ($sameYear) use ($from) {
                            $sameYear->where('year', $from->year)
                                ->where('month', '>=', $from->month);
                        });
                })->where(function ($inner) use ($to) {
                    $inner->where('year', '<', $to->year)
                        ->orWhere(function ($sameYear) use ($to) {
                            $sameYear->where('year', $to->year)
                                ->where('month', '<=', $to->month);
                        });
                });
            })
            ->get(['year', 'month', 'net_salary', 'currency']);

        foreach ($salaries as $salary) {
            $year = (int) $salary->year;
            $month = (int) $salary->month;
            if ($year < 1 || $month < 1 || $month > 12) {
                continue;
            }
            $key = sprintf('%04d-%02d', $year, $month);
            if (! isset($buckets[$key])) {
                continue;
            }

            $code = CurrencyService::normalize((string) ($salary->currency ?: 'USD'));
            if ($code !== 'AFN' && $code !== 'USD') {
                $code = 'USD';
            }
            $buckets[$key]['by_currency'][$code] = round(
                ($buckets[$key]['by_currency'][$code] ?? 0) + (float) $salary->net_salary,
                2,
            );
        }

        return array_values(array_map(
            static fn (array $bucket) => [
                'key' => $bucket['key'],
                'label' => $bucket['label'],
                'by_currency' => [
                    'AFN' => round((float) ($bucket['by_currency']['AFN'] ?? 0), 2),
                    'USD' => round((float) ($bucket['by_currency']['USD'] ?? 0), 2),
                ],
            ],
            $buckets,
        ));
    }

    public function show(Salary $salary): Response
    {
        $salary->load([
            'attachments',
            'employee:id,name,employee_no,department,designation,salary_payment_method',
            'creator:id,name',
        ]);

        return Inertia::render('Salaries/Show', [
            'item' => $salary,
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
        unset($data['documents']);

        $salary = Salary::query()->create($data);
        $this->storeRecordAttachments($request, $salary);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Salary record created.']);

        return redirect()->route('salaries.index');
    }

    public function edit(Salary $salary): Response
    {
        return Inertia::render('Salaries/Form', [
            'item' => $salary->load('attachments'),
            'employees' => Employee::query()->where('status', 'active')->orderBy('name')->get(['id', 'name', 'employee_no', 'basic_salary', 'currency']),
        ]);
    }

    public function update(Request $request, Salary $salary): RedirectResponse
    {
        $data = $this->validated($request, $salary->id);
        unset($data['documents']);

        $salary->update($data);
        $this->storeRecordAttachments($request, $salary);

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
            ...$this->attachmentValidationRules(),
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

        unset($data['documents']);

        return $data;
    }
}
