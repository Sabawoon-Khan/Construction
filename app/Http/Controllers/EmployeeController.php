<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AuthorizesResourcePermissions;
use App\Http\Controllers\Concerns\StoresRecordAttachments;
use App\Models\Employee;
use App\Services\CurrencyService;
use App\Services\FinanceDefaults;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class EmployeeController extends Controller implements HasMiddleware
{
    protected static string $permissionModule = 'employees';

    use AuthorizesResourcePermissions;
    use StoresRecordAttachments;

    public function index(Request $request): Response
    {
        $query = Employee::query();

        if ($search = $request->string('search')->toString()) {
            $query->where(function ($q) use ($search) {
                $q->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('takhallus', 'like', "%{$search}%")
                    ->orWhere('father_name', 'like', "%{$search}%")
                    ->orWhere('employee_no', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('department', 'like', "%{$search}%")
                    ->orWhere('designation', 'like', "%{$search}%");
            });
        }

        $totals = [
            'count' => (clone $query)->count(),
            'active' => (clone $query)->where('status', 'active')->count(),
            'inactive' => (clone $query)->where('status', '!=', 'active')->count(),
        ];

        return Inertia::render('Employees/Index', [
            'items' => $query->with('creator:id,name')->latest()->paginate(15)->withQueryString(),
            'filters' => ['search' => $search],
            'totals' => $totals,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Employees/Form', [
            'item' => null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        unset($data['documents']);

        $employee = Employee::query()->create($data);
        $this->storeRecordAttachments($request, $employee);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Employee created.']);

        return redirect()->route('employees.index');
    }

    public function show(Employee $employee): Response
    {
        $employee->loadCount(['attendance', 'salaries']);
        $employee->load([
            'attachments',
            'projects' => fn ($q) => $q->orderBy('name'),
            'attendance' => fn ($q) => $q->with('creator:id,name')->latest('date')->limit(20),
            'salaries' => fn ($q) => $q->with('creator:id,name')->latest('year')->latest('month')->limit(20),
        ]);

        return Inertia::render('Employees/Show', [
            'item' => $employee,
            'stats' => [
                'attendance_count' => $employee->attendance_count ?? 0,
                'salaries_count' => $employee->salaries_count ?? 0,
                'projects_count' => $employee->projects->count(),
            ],
        ]);
    }

    public function edit(Employee $employee): Response
    {
        $employee->load('attachments');

        return Inertia::render('Employees/Form', [
            'item' => $employee,
        ]);
    }

    public function update(Request $request, Employee $employee): RedirectResponse
    {
        $data = $this->validated($request, $employee->id);
        unset($data['documents']);

        $employee->update($data);
        $this->storeRecordAttachments($request, $employee);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Employee updated.']);

        return redirect()->route('employees.index');
    }

    public function destroy(Employee $employee): RedirectResponse
    {
        if ($employee->attendance()->exists() || $employee->salaries()->exists()) {
            Inertia::flash('toast', ['type' => 'error', 'message' => 'This employee has attendance or salary records and cannot be deleted.']);

            return back();
        }

        $employee->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Employee deleted.']);

        return redirect()->route('employees.index');
    }

    /** @return array<string, mixed> */
    private function validated(Request $request, ?int $ignoreId = null): array
    {
        $data = $request->validate([
            'employee_no' => 'nullable|string|max:50|unique:employees,employee_no'.($ignoreId ? ','.$ignoreId : ''),
            'name' => 'required|string|max:255',
            'takhallus' => 'nullable|string|max:255',
            'father_name' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'department' => 'nullable|string|max:100',
            'designation' => 'nullable|string|max:100',
            'working_hours' => 'nullable|string|max:100',
            'joining_date' => 'nullable|date',
            'basic_salary' => 'nullable|numeric|min:0',
            'salary_payment_method' => 'nullable|string|in:cash,bank',
            'currency' => 'nullable|string|size:3',
            'status' => 'nullable|string|in:active,inactive,on_leave',
            'notes' => 'nullable|string',
            ...$this->attachmentValidationRules(),
        ]);

        if (blank($data['employee_no'] ?? null)) {
            $data['employee_no'] = 'EMP-'.Str::upper(Str::random(8));
        }

        $currency = strtoupper($data['currency'] ?? CurrencyService::baseCurrency());
        $exchangeRate = FinanceDefaults::exchangeRate($currency, isset($data['joining_date']) ? Carbon::parse($data['joining_date']) : now());
        $basicSalary = round((float) ($data['basic_salary'] ?? 0), 2);

        $data['currency'] = $currency;
        $data['exchange_rate'] = $exchangeRate;
        $data['basic_salary'] = $basicSalary;
        $data['basic_salary_base'] = round($basicSalary * $exchangeRate, 2);

        if (blank($data['status'] ?? null)) {
            $data['status'] = 'active';
        }

        return $data;
    }
}
