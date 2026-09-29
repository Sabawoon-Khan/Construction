<?php

namespace App\Http\Controllers;

use App\Concerns\ExportsSpreadsheet;
use App\Http\Controllers\Concerns\AuthorizesResourcePermissions;
use App\Http\Controllers\Concerns\ResolvesExportScope;
use App\Http\Controllers\Concerns\StoresRecordAttachments;
use App\Models\Employee;
use App\Models\User;
use App\Services\CurrencyService;
use App\Services\FinanceDefaults;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EmployeeController extends Controller implements HasMiddleware
{
    protected static string $permissionModule = 'employees';

    use AuthorizesResourcePermissions;
    use ExportsSpreadsheet;
    use ResolvesExportScope;
    use StoresRecordAttachments;

    public function index(Request $request): Response
    {
        $search = $request->string('search')->toString();
        $query = $this->indexQuery($request);

        $statusCounts = (clone $query)
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $active = (int) ($statusCounts['active'] ?? 0);
        $inactive = (int) ($statusCounts['inactive'] ?? 0);
        $onLeave = (int) ($statusCounts['on_leave'] ?? 0);
        $count = (clone $query)->count();

        $byDepartment = (clone $query)
            ->selectRaw('department, COUNT(*) as aggregate')
            ->groupBy('department')
            ->get()
            ->groupBy(function ($row) {
                $name = trim((string) ($row->department ?? ''));

                return $name !== '' ? $name : 'Unassigned';
            })
            ->map(fn ($rows) => (int) $rows->sum('aggregate'))
            ->sortDesc()
            ->take(8)
            ->all();

        $totals = [
            'count' => $count,
            'active' => $active,
            'inactive' => max(0, $count - $active),
            'on_leave' => $onLeave,
            'by_status' => [
                'active' => $active,
                'inactive' => $inactive,
                'on_leave' => $onLeave,
            ],
            'by_department' => $byDepartment,
        ];

        return Inertia::render('Employees/Index', [
            'items' => $query->with('creator:id,name')->oldest()->paginate(15)->withQueryString(),
            'printItems' => (clone $query)
                ->with('creator:id,name')
                ->oldest()
                ->get()
                ->map(fn (Employee $employee) => [
                    'id' => $employee->id,
                    'employee_no' => $employee->employee_no,
                    'name' => $employee->name,
                    'takhallus' => $employee->takhallus,
                    'father_name' => $employee->father_name,
                    'department' => $employee->department,
                    'designation' => $employee->designation,
                    'working_hours' => $employee->working_hours,
                    'joining_date' => $employee->joining_date?->toDateString(),
                    'phone' => $employee->phone,
                    'basic_salary' => $employee->basic_salary,
                    'currency' => $employee->currency,
                    'salary_payment_method' => $employee->salary_payment_method,
                    'status' => $employee->status,
                    'creator_name' => $employee->creator?->name,
                    'created_at' => $employee->created_at?->toDateTimeString(),
                ]),
            'filters' => ['search' => $search],
            'totals' => $totals,
            'hireChart' => $this->hiresByMonth(clone $query),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        abort_unless(config('kit.features.exports'), 404);

        $rows = $this->indexQuery($request, $this->exportAppliesFilters($request))
            ->with('creator:id,name')
            ->oldest()
            ->lazyById(500)
            ->map(fn (Employee $employee) => [
                $employee->employee_no,
                $employee->name,
                $employee->takhallus,
                $employee->father_name,
                $employee->department,
                $employee->designation,
                $employee->working_hours,
                $employee->employment_date?->toDateString(),
                $employee->phone,
                $employee->basic_salary,
                $employee->salary_payment_method,
                $employee->status,
                $employee->creator?->name,
                $employee->created_at?->toDateTimeString(),
            ]);

        return $this->streamExport(
            'employees',
            [
                'employee_no', 'name', 'takhallus', 'father_name', 'department', 'designation',
                'working_hours', 'employment_date', 'phone', 'basic_salary',
                'salary_payment_method', 'status', 'added_by', 'created_at',
            ],
            $rows,
            $this->exportFormat($request),
        );
    }

    protected function indexQuery(Request $request, bool $applyFilters = true): Builder
    {
        $query = Employee::query();

        if ($applyFilters && ($search = $request->string('search')->toString())) {
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

        return $query;
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
            'loginUser:id,name,email,is_active',
            'projects' => fn ($q) => $q->orderBy('name'),
            'attendance' => fn ($q) => $q->with('creator:id,name')->oldest()->limit(20),
            'salaries' => fn ($q) => $q->with('creator:id,name')->oldest()->limit(20),
        ]);

        return Inertia::render('Employees/Show', [
            'item' => $employee,
            'stats' => [
                'attendance_count' => $employee->attendance_count ?? 0,
                'salaries_count' => $employee->salaries_count ?? 0,
                'projects_count' => $employee->projects->count(),
            ],
            'passwordRules' => Password::defaults()->toPasswordRulesString(),
        ]);
    }

    public function updatePassword(Request $request, Employee $employee): RedirectResponse
    {
        $data = $request->validate([
            'password' => ['required', 'string', Password::default(), 'confirmed'],
        ]);

        if (blank($employee->email)) {
            return back()->withErrors([
                'password' => __('Add an email on the employee profile before setting a login password.'),
            ]);
        }

        $user = $this->resolveLoginUser($employee);

        if (! $user) {
            return back()->withErrors([
                'password' => __('This email is already linked to another employee login.'),
            ]);
        }

        $user->update([
            'password' => $data['password'],
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Password updated.']);

        return redirect()->route('employees.show', $employee);
    }

    private function resolveLoginUser(Employee $employee): ?User
    {
        if ($employee->login_user_id) {
            return User::query()->find($employee->login_user_id);
        }

        $user = User::query()->where('email', $employee->email)->first();

        if ($user) {
            $alreadyLinked = Employee::query()
                ->where('login_user_id', $user->id)
                ->whereKeyNot($employee->id)
                ->exists();

            if ($alreadyLinked) {
                return null;
            }

            $employee->update(['login_user_id' => $user->id]);

            if (! $user->hasAnyRole(['Owner', 'Admin', 'Finance', 'Project Manager', 'Limited Employee'])) {
                $user->assignRole('Limited Employee');
            }

            return $user->fresh();
        }

        $user = User::query()->create([
            'name' => $employee->name,
            'email' => $employee->email,
            'password' => Str::password(),
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $user->assignRole('Limited Employee');
        $employee->update(['login_user_id' => $user->id]);

        return $user;
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

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<\App\Models\Employee>  $query
     * @return list<array{key: string, label: string, value: int}>
     */
    protected function hiresByMonth($query): array
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
                'value' => 0,
            ];
            $cursor = $cursor->addMonth();
        }

        $rows = (clone $query)
            ->whereNotNull('joining_date')
            ->whereDate('joining_date', '>=', $from->toDateString())
            ->whereDate('joining_date', '<=', $to->toDateString())
            ->get(['joining_date']);

        foreach ($rows as $row) {
            $date = $row->joining_date;
            if (! $date) {
                continue;
            }
            $key = $date->format('Y-m');
            if (! isset($buckets[$key])) {
                continue;
            }
            $buckets[$key]['value']++;
        }

        return array_values($buckets);
    }
}
