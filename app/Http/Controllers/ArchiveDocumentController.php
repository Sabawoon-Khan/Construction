<?php

namespace App\Http\Controllers;

use App\Enums\ArchiveDocumentCategory;
use App\Http\Controllers\Concerns\AuthorizesResourcePermissions;
use App\Http\Controllers\Concerns\StoresRecordAttachments;
use App\Models\ArchiveDocument;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ArchiveDocumentController extends Controller implements HasMiddleware
{
    protected static string $permissionModule = 'archive';

    use AuthorizesResourcePermissions;
    use StoresRecordAttachments;

    public function index(Request $request): Response
    {
        $search = $request->string('search')->toString();
        $category = $request->string('category')->toString();

        $query = $this->indexQuery($request);
        $count = (clone $query)->count();

        return Inertia::render('Archive/Index', [
            'items' => $query->latest()->paginate(15)->withQueryString(),
            'filters' => [
                'search' => $search,
                'category' => $category !== '' ? $category : null,
            ],
            'categories' => ArchiveDocumentCategory::options(),
            'totals' => [
                'count' => $count,
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Archive/Form', [
            'item' => null,
            'categories' => ArchiveDocumentCategory::options(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'category' => ['required', Rule::enum(ArchiveDocumentCategory::class)],
            'document_date' => 'nullable|date',
            'notes' => 'nullable|string',
            ...$this->attachmentValidationRules(),
        ]);
        unset($data['documents']);

        $document = ArchiveDocument::create($data);
        $this->storeRecordAttachments($request, $document);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Archive document saved.')]);

        return redirect()->route('archive.index');
    }

    public function show(ArchiveDocument $archiveDocument): Response
    {
        $archiveDocument->load(['attachments', 'creator:id,name']);

        return Inertia::render('Archive/Show', [
            'item' => $archiveDocument,
            'categories' => ArchiveDocumentCategory::options(),
        ]);
    }

    public function edit(ArchiveDocument $archiveDocument): Response
    {
        $archiveDocument->load('attachments');

        return Inertia::render('Archive/Form', [
            'item' => $archiveDocument,
            'categories' => ArchiveDocumentCategory::options(),
        ]);
    }

    public function update(Request $request, ArchiveDocument $archiveDocument): RedirectResponse
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'category' => ['required', Rule::enum(ArchiveDocumentCategory::class)],
            'document_date' => 'nullable|date',
            'notes' => 'nullable|string',
            ...$this->attachmentValidationRules(),
        ]);
        unset($data['documents']);

        $archiveDocument->update($data);
        $this->storeRecordAttachments($request, $archiveDocument);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Archive document updated.')]);

        return redirect()->route('archive.index');
    }

    public function destroy(ArchiveDocument $archiveDocument): RedirectResponse
    {
        $archiveDocument->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Archive document deleted.')]);

        return redirect()->route('archive.index');
    }

    protected function indexQuery(Request $request, bool $applyFilters = true): Builder
    {
        $query = ArchiveDocument::query()
            ->with('creator:id,name')
            ->withCount('attachments');

        if ($applyFilters && ($search = $request->string('search')->toString())) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%");
            });
        }

        if ($applyFilters && ($category = $request->string('category')->toString())) {
            $query->where('category', $category);
        }

        return $query;
    }
}
