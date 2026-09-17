<?php

namespace App\Http\Controllers;

use App\Concerns\ExportsSpreadsheet;
use App\Http\Controllers\Concerns\AuthorizesResourcePermissions;
use App\Http\Controllers\Concerns\ResolvesExportScope;
use App\Http\Controllers\Concerns\StoresRecordAttachments;
use App\Models\Attendance;
use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttendanceController extends Controller implements HasMiddleware
{
    protected static string $permissionModule = 'attendance';

    use AuthorizesResourcePermissions;
    use ExportsSpreadsheet;
    use ResolvesExportScope;
    use StoresRecordAttachments;

    public function index(Request $request): Response
    {
        $search = $request->string('search')->toString();
        $employeeId = $request->integer('employee_id');
        $date = $request->date('date');
        $query = $this->indexQuery($request);

        $totals = [
            'count' => (clone $query)->count(),
            'present' => (clone $query)->where('status', 'present')->count(),
            'absent' => (clone $query)->where('status', 'absent')->count(),
            'leave' => (clone $query)->where('status', 'leave')->count(),
            'half_day' => (clone $query)->where('status', 'half_day')->count(),
        ];

        return Inertia::render('Attendance/Index', [
            'items' => $query->latest('date')->paginate(20)->withQueryString(),
            'filters' => [
                'search' => $search,
                'employee_id' => $employeeId ?: null,
                'date' => $date?->format('Y-m-d'),
            ],
            'totals' => $totals,
            'volumeChart' => $this->recordsByMonth(clone $query),
            'employees' => Employee::query()->where('status', 'active')->orderBy('name')->get(['id', 'name', 'employee_no']),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        abort_unless(config('kit.features.exports'), 404);

        $rows = $this->indexQuery($request, $this->exportAppliesFilters($request))
            ->latest('date')
            ->lazyById(500)
            ->map(fn (Attendance $attendance) => [
                $attendance->date?->toDateString(),
                $attendance->employee?->name,
                $attendance->check_in,
                $attendance->check_out,
                $attendance->status,
                $attendance->notes,
                $attendance->creator?->name,
                $attendance->created_at?->toDateTimeString(),
            ]);

        return $this->streamExport(
            'attendance',
            ['date', 'employee', 'check_in', 'check_out', 'status', 'notes', 'added_by', 'created_at'],
            $rows,
            $this->exportFormat($request),
        );
    }

    protected function indexQuery(Request $request, bool $applyFilters = true): Builder
    {
        $query = Attendance::query()->with(['employee:id,name,employee_no', 'creator:id,name']);

        if (! $applyFilters) {
            return $query;
        }

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

        return $query;
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
            'item' => $attendance->load('attachments'),
            'employees' => Employee::query()->where('status', 'active')->orderBy('name')->get(['id', 'name', 'employee_no']),
        ]);
    }

    public function update(Request $request, Attendance $attendance): RedirectResponse
    {
        $data = $this->validated($request, $attendance->id);
        unset($data['documents']);

        $attendance->update($data);
        $this->storeRecordAttachments($request, $attendance);

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
            ...$this->attachmentValidationRules(),
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

    /**
     * @return list<array{key: string, label: string, value: int}>
     */
    private function recordsByMonth($query): array
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
            ->whereDate('date', '>=', $from->toDateString())
            ->whereDate('date', '<=', $to->toDateString())
            ->get(['date']);

        foreach ($rows as $row) {
            if (! $row->date) {
                continue;
            }
            $key = $row->date->format('Y-m');
            if (isset($buckets[$key])) {
                $buckets[$key]['value']++;
            }
        }

        return array_values($buckets);
    }
}
