<?php

namespace App\Http\Controllers;

use App\Models\PortfolioProject;
use App\Support\PortfolioPresenter;
use App\Support\Seo;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PublicPortfolioController extends Controller
{
    public function index(Request $request): Response
    {
        $query = PortfolioProject::query()
            ->published()
            ->ordered()
            ->with(['media' => fn ($q) => $q->orderByDesc('is_cover')->orderBy('sort_order')->orderBy('id')]);

        $search = $request->string('search')->trim()->toString();
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('category', 'like', "%{$search}%")
                    ->orWhere('location', 'like', "%{$search}%")
                    ->orWhere('summary', 'like', "%{$search}%");
            });
        }

        $category = $request->string('category')->trim()->toString();
        if ($category !== '') {
            $query->where('category', $category);
        }

        $categories = PortfolioProject::query()
            ->published()
            ->whereNotNull('category')
            ->where('category', '!=', '')
            ->distinct()
            ->orderBy('category')
            ->pluck('category')
            ->values()
            ->all();

        $projects = $query->paginate(12)->withQueryString();

        $props = [
            'projects' => [
                'data' => PortfolioPresenter::cards(collect($projects->items())),
                'links' => $projects->linkCollection()->toArray(),
                'meta' => [
                    'current_page' => $projects->currentPage(),
                    'last_page' => $projects->lastPage(),
                    'per_page' => $projects->perPage(),
                    'total' => $projects->total(),
                ],
            ],
            'filters' => [
                'search' => $search !== '' ? $search : null,
                'category' => $category !== '' ? $category : null,
            ],
            'categories' => $categories,
        ];

        if (config('kit.features.seo')) {
            $props['documentSeo'] = Seo::document([
                'title' => __('Our work'),
                'description' => __('Selected construction projects delivered with disciplined planning and on-site supervision.'),
                'path' => '/work',
            ]);
        }

        return Inertia::render('PublicPortfolio/Index', $props);
    }

    public function show(string $slug): Response
    {
        $project = PortfolioProject::query()
            ->published()
            ->where('slug', $slug)
            ->with(['media' => fn ($q) => $q->orderByDesc('is_cover')->orderBy('sort_order')->orderBy('id')])
            ->firstOrFail();

        $relatedQuery = PortfolioProject::query()
            ->published()
            ->ordered()
            ->where('id', '!=', $project->id)
            ->with(['media' => fn ($q) => $q->orderByDesc('is_cover')->orderBy('sort_order')->orderBy('id')]);

        if (filled($project->category)) {
            $relatedQuery->orderByRaw('CASE WHEN category = ? THEN 0 ELSE 1 END', [$project->category]);
        }

        $related = $relatedQuery->limit(3)->get();

        $detail = PortfolioPresenter::detail($project);
        $coverUrl = $detail['cover']['url'] ?? null;

        $props = [
            'project' => $detail,
            'related' => PortfolioPresenter::cards($related),
        ];

        if (config('kit.features.seo')) {
            $props['documentSeo'] = Seo::document([
                'title' => $project->title,
                'description' => $project->summary ?: $project->description,
                'path' => '/portfolio/'.$project->slug,
                'image' => $coverUrl,
                'image_alt' => $project->title,
                'type' => 'article',
            ]);
        }

        return Inertia::render('PublicPortfolio/Show', $props);
    }
}
