<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\AuthorizesMisPermissions;
use App\Http\Controllers\Concerns\PaginatesInertia;
use App\Http\Controllers\Concerns\SharesOfflineInertiaProps;
use App\Http\Controllers\Controller;
use App\Models\FieldReport;
use App\Services\OfflineAttachmentService;
use App\Support\OfflineSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class FieldReportController extends Controller
{
    use AuthorizesMisPermissions;
    use PaginatesInertia;
    use SharesOfflineInertiaProps;

    public function __construct(protected OfflineAttachmentService $attachments) {}

    public function index(Request $request): Response
    {
        $this->authorizePermission($request, 'field_reports.view');

        $search = $request->string('search')->toString();
        $status = $request->string('status')->toString();

        $query = FieldReport::query()
            ->where('user_id', $request->user()->id);

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('location', 'like', "%{$search}%")
                    ->orWhere('status', 'like', "%{$search}%");
            });
        }

        if (in_array($status, ['draft', 'submitted'], true)) {
            $query->where('status', $status);
        }

        $items = $query
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('resources/field-reports/Index', [
            'items' => $this->inertiaPagination($items),
            'filters' => [
                'search' => $search,
                'status' => in_array($status, ['draft', 'submitted'], true) ? $status : '',
            ],
            ...$this->offlineInertiaProps('field_reports'),
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorizePermission($request, 'field_reports.create');

        return Inertia::render('resources/field-reports/Create', [
            ...$this->offlineInertiaProps('field_reports'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizePermission($request, 'field_reports.create');

        $validated = $request->validate([
            'client_uuid' => ['nullable', 'uuid'],
            'title' => ['required', 'string', 'max:255'],
            'body' => ['nullable', 'string', 'max:10000'],
            'location' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', 'in:draft,submitted'],
            'attachments' => ['nullable', 'array', 'max:5'],
            'attachments.*' => ['file'],
        ]);

        $report = FieldReport::query()->create([
            'client_uuid' => $validated['client_uuid'] ?? (string) Str::uuid(),
            'user_id' => $request->user()->id,
            'title' => $validated['title'],
            'body' => $validated['body'] ?? null,
            'location' => $validated['location'] ?? null,
            'status' => $validated['status'] ?? 'draft',
            'client_updated_at' => now(),
        ]);

        $this->storeUploadedFiles($request, $report);

        return redirect()->route('field-reports.index')->with('success', __('Created.'));
    }

    public function edit(Request $request, FieldReport $fieldReport): Response
    {
        $this->authorizePermission($request, 'field_reports.edit');
        abort_unless($fieldReport->user_id === $request->user()->id, 404);

        $fieldReport->load('attachments');

        return Inertia::render('resources/field-reports/Edit', [
            'item' => [
                ...$fieldReport->toArray(),
                'attachments' => $fieldReport->attachments->map(fn ($attachment) => [
                    'id' => $attachment->id,
                    'original_filename' => $attachment->original_filename,
                    'mime_type' => $attachment->mime_type,
                    'file_size' => $attachment->file_size,
                    'download_url' => $attachment->download_url,
                ]),
            ],
            ...$this->offlineInertiaProps('field_reports'),
        ]);
    }

    public function show(Request $request, FieldReport $fieldReport): Response
    {
        $this->authorizePermission($request, 'field_reports.view');
        abort_unless($fieldReport->user_id === $request->user()->id, 404);

        $fieldReport->load('attachments');

        return Inertia::render('resources/field-reports/Show', [
            'item' => [
                ...$fieldReport->toArray(),
                'attachments' => $fieldReport->attachments->map(fn ($attachment) => [
                    'id' => $attachment->id,
                    'original_filename' => $attachment->original_filename,
                    'mime_type' => $attachment->mime_type,
                    'file_size' => $attachment->file_size,
                    'download_url' => $attachment->download_url,
                ]),
            ],
            ...$this->offlineInertiaProps('field_reports'),
        ]);
    }

    public function update(Request $request, FieldReport $fieldReport): RedirectResponse
    {
        $this->authorizePermission($request, 'field_reports.edit');
        abort_unless($fieldReport->user_id === $request->user()->id, 404);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'body' => ['nullable', 'string', 'max:10000'],
            'location' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', 'in:draft,submitted'],
            'attachments' => ['nullable', 'array', 'max:5'],
            'attachments.*' => ['file'],
        ]);

        $fieldReport->update([
            'title' => $validated['title'],
            'body' => $validated['body'] ?? null,
            'location' => $validated['location'] ?? null,
            'status' => $validated['status'] ?? $fieldReport->status,
            'client_updated_at' => now(),
        ]);

        $this->storeUploadedFiles($request, $fieldReport);

        return back()->with('success', __('Updated.'));
    }

    public function destroy(Request $request, FieldReport $fieldReport): RedirectResponse
    {
        $this->authorizePermission($request, 'field_reports.delete');
        abort_unless($fieldReport->user_id === $request->user()->id, 404);

        $fieldReport->delete();

        return back()->with('success', __('Deleted.'));
    }

    protected function storeUploadedFiles(Request $request, FieldReport $report): void
    {
        if (! $request->hasFile('attachments')) {
            return;
        }

        foreach ($request->file('attachments', []) as $file) {
            if (! $file) {
                continue;
            }

            if (OfflineSettings::attachmentsEnabled()) {
                $this->attachments->validateFile($file);
            } else {
                $maxKb = (int) ceil(((int) config('uploads.max_bytes')) / 1024);
                $request->validate([
                    'attachments.*' => ['file', 'max:'.$maxKb],
                ]);
            }

            $report->storeAttachment($file, null, $request->user()->id);
        }
    }
}
