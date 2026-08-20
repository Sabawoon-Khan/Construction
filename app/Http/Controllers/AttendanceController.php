<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AuthorizesResourcePermissions;
use App\Models\Attendance;
use App\Models\Employee;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Inertia\Inertia;
use Inertia\Response;

class AttendanceController extends Controller implements HasMiddleware
{
    protected static string $permissionModule = 'attendance';

    use AuthorizesResourcePermissions;

    public function index(Request $request): Response
    {
        $query = Attendance::query()->with(['employee:id,name,employee_no', 'creator:id,name']);

        if ($employeeId = $request->integer('employee_id')) {
            $query->where('employee_id', $employeeId);
        }

        if ($date = $request->date('date')) {
            $query->whereDate('date', $date);
        }

        if ($search = $request->string('search')->toString()) {
            $query->whereHas('employee', fn ($q) => $q
                ->where('name', 'like', "%{$search}%")
                ->orWhere('employee_no', 'like', "%{$search}%"));
        }

        $totals = [
            'count' => (clone $query)->count(),
            'present' => (clone $query)->where('status', 'present')->count(),
            'absent' => (clone $query)->where('status', 'absent')->count(),
            'leave' => (clone $query)->where('status', 'leave')->count(),
        ];

        return Inertia::render('Attendance/Index', [
            'items' => $query->latest('date')->paginate(20)->withQueryString(),
            'filters' => [
                'search' => $search,
                'employee_id' => $employeeId ?: null,
                'date' => $date?->format('Y-m-d'),
            ],
            'totals' => $totals,
            'employees' => Employee::query()->where('status', 'active')->orderBy('name')->get(['id', 'name', 'employee_no']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Attendance/Form', [
            'item' => null,
            'employees' => Employee::query()->where('status', 'active')->orderBy('name')->get(['id', 'name', 'employee_no']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedBulk($request);

        foreach ($data['employee_ids'] as $employeeId) {
            Attendance::query()->updateOrCreate(
                [
                    'employee_id' => $employeeId,
                    'date' => $data['date'],
                ],
                [
                    'check_in' => $data['check_in'] ?: null,
                    'check_out' => $data['check_out'] ?: null,
                    'status' => $data['status'],
                    'notes' => $data['notes'] ?: null,
                ],
            );
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Attendance recorded for selected employees.']);

        return redirect()->route('attendance.index');
    }

    public function edit(Attendance $attendance): Response
    {
        return Inertia::render('Attendance/Form', [
            'item' => $attendance,
            'employees' => Employee::query()->where('status', 'active')->orderBy('name')->get(['id', 'name', 'employee_no']),
        ]);
    }

    public function update(Request $request, Attendance $attendance): RedirectResponse
    {
        $attendance->update($this->validated($request, $attendance->id));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Attendance updated.']);

        return redirect()->route('attendance.index');
    }

    public function destroy(Attendance $attendance): RedirectResponse
    {
        $attendance->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Attendance record deleted.']);

        return redirect()->route('attendance.index');
    }

    /** @return array<string, mixed> */
    private function validated(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'date' => 'required|date',
            'check_in' => 'nullable|date_format:H:i',
            'check_out' => 'nullable|date_format:H:i|after_or_equal:check_in',
            'status' => 'required|string|in:present,absent,leave,half_day',
            'notes' => 'nullable|string',
        ]);
    }

    /** @return array<string, mixed> */
    private function validatedBulk(Request $request): array
    {
        return $request->validate([
            'employee_ids' => 'required|array|min:1',
            'employee_ids.*' => 'required|exists:employees,id',
            'date' => 'required|date',
            'check_in' => 'nullable|date_format:H:i',
            'check_out' => 'nullable|date_format:H:i|after_or_equal:check_in',
            'status' => 'required|string|in:present,absent,leave,half_day',
            'notes' => 'nullable|string',
        ]);
    }
}
