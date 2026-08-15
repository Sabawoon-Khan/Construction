<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Unit extends Model
{
    protected $fillable = [
        'project_id',
        'floor_number',
        'unit_no',
        'type',
        'area',
        'bedrooms',
        'toilets',
        'bathrooms',
        'halls',
        'kitchens',
        'balconies',
        'cost_price',
        'list_price',
        'currency',
        'status',
        'description',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'area' => 'decimal:2',
            'cost_price' => 'decimal:2',
            'list_price' => 'decimal:2',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    public function activeSale(): HasOne
    {
        return $this->hasOne(Sale::class)->latestOfMany();
    }
}
