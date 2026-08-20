<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class PortfolioMedia extends Model
{
    protected $fillable = [
        'portfolio_project_id',
        'type',
        'path',
        'original_filename',
        'mime_type',
        'file_size',
        'sort_order',
        'is_cover',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
            'sort_order' => 'integer',
            'is_cover' => 'boolean',
        ];
    }

    protected $appends = ['url'];

    protected static function booted(): void
    {
        static::deleting(function (PortfolioMedia $media): void {
            if ($media->path && Storage::disk('public')->exists($media->path)) {
                Storage::disk('public')->delete($media->path);
            }
        });
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(PortfolioProject::class, 'portfolio_project_id');
    }

    protected function url(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->path
            ? Storage::disk('public')->url($this->path)
            : null);
    }
}
