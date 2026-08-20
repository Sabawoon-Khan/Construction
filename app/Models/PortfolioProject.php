<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PortfolioProject extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'title',
        'slug',
        'summary',
        'description',
        'location',
        'category',
        'client_name',
        'scope',
        'area_size',
        'duration',
        'total_apartments',
        'floors',
        'plot_size',
        'parking_spaces',
        'project_status',
        'year',
        'completed_on',
        'is_published',
        'sort_order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'completed_on' => 'date',
            'is_published' => 'boolean',
            'sort_order' => 'integer',
            'total_apartments' => 'integer',
            'floors' => 'integer',
            'parking_spaces' => 'integer',
            'year' => 'integer',
        ];
    }

    protected $attributes = [
        'is_published' => false,
        'sort_order' => 0,
    ];

    protected static function booted(): void
    {
        static::forceDeleting(function (PortfolioProject $project): void {
            $project->media()->each(fn (PortfolioMedia $media) => $media->delete());
        });
    }

    public function media(): HasMany
    {
        return $this->hasMany(PortfolioMedia::class)->orderBy('sort_order')->orderBy('id');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderByDesc('id');
    }
}
