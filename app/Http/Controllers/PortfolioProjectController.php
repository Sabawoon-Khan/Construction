<?php

namespace App\Http\Controllers;

use App\Concerns\ExportsSpreadsheet;
use App\Http\Controllers\Concerns\AuthorizesResourcePermissions;
use App\Http\Controllers\Concerns\ResolvesExportScope;
use App\Models\PortfolioMedia;
use App\Models\PortfolioProject;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PortfolioProjectController extends Controller implements HasMiddleware
{
    protected static string $permissionModule = 'portfolio';

    use AuthorizesResourcePermissions;
    use ExportsSpreadsheet;
    use ResolvesExportScope;

    private const ACCEPTED_MIMES = [
        'image/jpeg',
        'image/png',
        'image/webp',
        'video/mp4',
        'video/webm',
    ];

    private const IMAGE_MAX_KB = 10240; // 10 MB

    private const VIDEO_MAX_KB = 102400; // 100 MB

    public function index(Request $request): Response
    {
        $search = $request->string('search')->toString();
        $query = $this->indexQuery($request);

        $count = (clone $query)->count();
        $published = (clone $query)->where('is_published', true)->count();
        $totals = [
            'count' => $count,
            'published' => $published,
            'draft' => max(0, $count - $published),
            'with_media' => (clone $query)->has('media')->count(),
        ];

        return Inertia::render('Portfolio/Index', [
            'items' => $query->latest()->paginate(15)->withQueryString(),
            'filters' => ['search' => $search],
            'totals' => $totals,
            'categoryChart' => $this->categoryBreakdown(clone $query),
            'volumeChart' => $this->projectsByMonth(clone $query),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        abort_unless(config('kit.features.exports'), 404);

        $rows = $this->indexQuery($request, $this->exportAppliesFilters($request))
            ->latest()
            ->lazyById(500)
            ->map(fn (PortfolioProject $project) => [
                $project->title,
                $project->category,
                $project->location,
                $project->media_count,
                $project->is_published ? 'yes' : 'no',
                $project->created_at?->toDateTimeString(),
            ]);

        return $this->streamExport(
            'portfolio',
            ['title', 'category', 'location', 'media', 'published', 'created_at'],
            $rows,
            $this->exportFormat($request),
        );
    }

    protected function indexQuery(Request $request, bool $applyFilters = true): Builder
    {
        $query = PortfolioProject::query()->withCount('media')->with([
            'media' => fn ($q) => $q->where('is_cover', true)->limit(1),
        ]);

        if ($applyFilters && ($search = $request->string('search')->toString())) {
            $query->where(function ($q) use ($search) {
                $q->orWhere('title', 'like', "%{$search}%")
                    ->orWhere('category', 'like', "%{$search}%")
                    ->orWhere('location', 'like', "%{$search}%");
            });
        }

        return $query;
    }

    public function create(): Response
    {
        return Inertia::render('Portfolio/Form', [
            'item' => null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        $project = DB::transaction(function () use ($request, $data) {
            $project = PortfolioProject::query()->create($data);
            $this->syncMedia($request, $project);

            return $project;
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Portfolio project created.')]);

        return redirect()->route('portfolio.index');
    }

    public function edit(PortfolioProject $portfolio): Response
    {
        $portfolio->load(['media' => fn ($q) => $q->orderByDesc('is_cover')->orderBy('sort_order')->orderBy('id')]);

        return Inertia::render('Portfolio/Form', [
            'item' => $portfolio,
        ]);
    }

    public function update(Request $request, PortfolioProject $portfolio): RedirectResponse
    {
        $data = $this->validated($request, $portfolio->id);

        DB::transaction(function () use ($request, $portfolio, $data) {
            $portfolio->update($data);
            $this->syncMedia($request, $portfolio);
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Portfolio project updated.')]);

        return redirect()->route('portfolio.index');
    }

    public function destroy(PortfolioProject $portfolio): RedirectResponse
    {
        $portfolio->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Portfolio project deleted.')]);

        return redirect()->route('portfolio.index');
    }

    /** @return array<string, mixed> */
    private function validated(Request $request, ?int $ignoreId = null): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('portfolio_projects', 'slug')->ignore($ignoreId),
            ],
            'summary' => ['nullable', 'string', 'max:5000'],
            'description' => ['nullable', 'string', 'max:50000'],
            'location' => ['nullable', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:255'],
            'client_name' => ['nullable', 'string', 'max:255'],
            'scope' => ['nullable', 'string', 'max:500'],
            'area_size' => ['nullable', 'string', 'max:255'],
            'duration' => ['nullable', 'string', 'max:255'],
            'total_apartments' => ['nullable', 'integer', 'min:0', 'max:999999'],
            'floors' => ['nullable', 'integer', 'min:0', 'max:500'],
            'plot_size' => ['nullable', 'string', 'max:255'],
            'parking_spaces' => ['nullable', 'integer', 'min:0', 'max:999999'],
            'project_status' => ['nullable', 'string', Rule::in(['completed', 'ongoing', 'planned'])],
            'year' => ['nullable', 'integer', 'min:1950', 'max:2100'],
            'completed_on' => ['nullable', 'date'],
            'is_published' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999999'],
            'cover' => ['nullable', 'file', 'mimetypes:'.implode(',', self::ACCEPTED_MIMES), 'max:'.self::VIDEO_MAX_KB],
            'media' => ['nullable', 'array', 'max:30'],
            'media.*' => ['file', 'mimetypes:'.implode(',', self::ACCEPTED_MIMES), 'max:'.self::VIDEO_MAX_KB],
            'delete_media_ids' => ['nullable', 'array'],
            'delete_media_ids.*' => ['integer'],
            'cover_media_id' => ['nullable', 'integer'],
        ]);

        $title = $data['title'];
        $slug = filled($data['slug'] ?? null)
            ? Str::slug($data['slug'])
            : Str::slug($title);

        if ($slug === '') {
            $slug = 'project-'.Str::lower(Str::random(8));
        }

        $base = $slug;
        $i = 1;
        while (
            PortfolioProject::withTrashed()
                ->where('slug', $slug)
                ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = $base.'-'.$i++;
        }

        return [
            'title' => $title,
            'slug' => $slug,
            'summary' => $data['summary'] ?? null,
            'description' => $data['description'] ?? null,
            'location' => $data['location'] ?? null,
            'category' => $data['category'] ?? null,
            'client_name' => $data['client_name'] ?? null,
            'scope' => $data['scope'] ?? null,
            'area_size' => $data['area_size'] ?? null,
            'duration' => $data['duration'] ?? null,
            'total_apartments' => isset($data['total_apartments']) ? (int) $data['total_apartments'] : null,
            'floors' => isset($data['floors']) ? (int) $data['floors'] : null,
            'plot_size' => $data['plot_size'] ?? null,
            'parking_spaces' => isset($data['parking_spaces']) ? (int) $data['parking_spaces'] : null,
            'project_status' => $data['project_status'] ?? null,
            'year' => isset($data['year']) ? (int) $data['year'] : null,
            'completed_on' => $data['completed_on'] ?? null,
            'is_published' => $request->boolean('is_published'),
            'sort_order' => (int) ($data['sort_order'] ?? 0),
        ];
    }

    private function syncMedia(Request $request, PortfolioProject $project): void
    {
        $deleteIds = collect($request->input('delete_media_ids', []))
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values();

        if ($deleteIds->isNotEmpty()) {
            $project->media()
                ->whereIn('id', $deleteIds)
                ->get()
                ->each(fn (PortfolioMedia $media) => $media->delete());
        }

        $nextOrder = (int) $project->media()->max('sort_order');

        if ($request->hasFile('cover')) {
            $this->assertFileSize($request->file('cover'));
            $project->media()->where('is_cover', true)->update(['is_cover' => false]);
            $this->storeUploadedMedia($project, $request->file('cover'), ++$nextOrder, true);
        }

        foreach ($request->file('media', []) as $file) {
            if (! $file instanceof UploadedFile) {
                continue;
            }
            $this->assertFileSize($file);
            $this->storeUploadedMedia($project, $file, ++$nextOrder, false);
        }

        $coverMediaId = $request->integer('cover_media_id') ?: null;
        if ($coverMediaId) {
            $exists = $project->media()->whereKey($coverMediaId)->exists();
            if ($exists) {
                $project->media()->where('is_cover', true)->update(['is_cover' => false]);
                $project->media()->whereKey($coverMediaId)->update(['is_cover' => true]);
            }
        }

        if (! $project->media()->where('is_cover', true)->exists()) {
            $first = $project->media()->orderBy('sort_order')->orderBy('id')->first();
            if ($first) {
                $first->update(['is_cover' => true]);
            }
        }
    }

    private function storeUploadedMedia(
        PortfolioProject $project,
        UploadedFile $file,
        int $sortOrder,
        bool $isCover,
    ): PortfolioMedia {
        $mime = $file->getMimeType() ?: $file->getClientMimeType() ?: 'application/octet-stream';
        $type = str_starts_with($mime, 'video/') ? 'video' : 'image';
        $path = $file->store('portfolio/'.$project->id, 'public');

        return $project->media()->create([
            'type' => $type,
            'path' => $path,
            'original_filename' => $file->getClientOriginalName(),
            'mime_type' => $mime,
            'file_size' => $file->getSize() ?: null,
            'sort_order' => $sortOrder,
            'is_cover' => $isCover,
        ]);
    }

    private function assertFileSize(UploadedFile $file): void
    {
        $mime = $file->getMimeType() ?: $file->getClientMimeType() ?: '';
        $maxKb = str_starts_with($mime, 'video/') ? self::VIDEO_MAX_KB : self::IMAGE_MAX_KB;
        $sizeKb = (int) ceil(($file->getSize() ?: 0) / 1024);

        if ($sizeKb > $maxKb) {
            abort(422, __('The uploaded file is too large.'));
        }
    }

    /**
     * @return list<array{key: string, label: string, value: int}>
     */
    private function categoryBreakdown($query): array
    {
        return (clone $query)
            ->get(['category'])
            ->groupBy(function ($row) {
                $category = trim((string) ($row->category ?? ''));

                return $category !== '' ? $category : 'Uncategorized';
            })
            ->map(fn ($group, $label) => [
                'key' => Str::slug((string) $label) ?: 'uncategorized',
                'label' => Str::limit((string) $label, 8, ''),
                'value' => $group->count(),
            ])
            ->sortByDesc('value')
            ->take(6)
            ->values()
            ->all();
    }

    /**
     * @return list<array{key: string, label: string, value: int}>
     */
    private function projectsByMonth($query): array
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
            ->whereDate('created_at', '>=', $from->toDateString())
            ->whereDate('created_at', '<=', $to->toDateString())
            ->get(['created_at']);

        foreach ($rows as $row) {
            if (! $row->created_at) {
                continue;
            }
            $key = $row->created_at->format('Y-m');
            if (isset($buckets[$key])) {
                $buckets[$key]['value']++;
            }
        }

        return array_values($buckets);
    }
}
