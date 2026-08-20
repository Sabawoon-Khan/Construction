<?php

namespace App\Support;

use App\Models\PortfolioProject;
use Illuminate\Support\Collection;

class PortfolioPresenter
{
    /**
     * @return array<string, mixed>
     */
    public static function card(PortfolioProject $project): array
    {
        $media = self::mediaCollection($project->media);
        $coverModel = $project->media->firstWhere('is_cover', true) ?? $project->media->first();

        return [
            'id' => $project->id,
            'title' => $project->title,
            'slug' => $project->slug,
            'summary' => $project->summary,
            'location' => $project->location,
            'category' => $project->category,
            'completed_on' => $project->completed_on?->toDateString(),
            'media' => $media,
            'cover' => $coverModel ? [
                'id' => $coverModel->id,
                'type' => $coverModel->type,
                'url' => $coverModel->url,
                'mime_type' => $coverModel->mime_type,
            ] : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function detail(PortfolioProject $project): array
    {
        return array_merge(self::card($project), [
            'description' => $project->description,
            'client_name' => $project->client_name,
            'scope' => $project->scope,
            'area_size' => $project->area_size,
            'duration' => $project->duration,
            'total_apartments' => $project->total_apartments,
            'floors' => $project->floors,
            'plot_size' => $project->plot_size,
            'parking_spaces' => $project->parking_spaces,
            'project_status' => $project->project_status,
            'year' => $project->year,
        ]);
    }

    /**
     * @param  Collection<int, PortfolioProject>  $projects
     * @return list<array<string, mixed>>
     */
    public static function cards(Collection $projects): array
    {
        return $projects->map(fn (PortfolioProject $project) => self::card($project))->values()->all();
    }

    /**
     * @param  Collection<int, \App\Models\PortfolioMedia>  $media
     * @return list<array<string, mixed>>
     */
    private static function mediaCollection(Collection $media): array
    {
        return $media->map(fn ($m) => [
            'id' => $m->id,
            'type' => $m->type,
            'url' => $m->url,
            'is_cover' => (bool) $m->is_cover,
            'mime_type' => $m->mime_type,
        ])->values()->all();
    }
}
