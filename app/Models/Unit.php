<?php

namespace App\Models;

use App\Concerns\HasAttachments;
use App\Models\Concerns\HasCreator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Unit extends Model
{
    use HasAttachments;
    use HasCreator;

    protected $fillable = [
        'user_id',
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
        'corridors',
        'courtyards',
        'parkings',
        'cost_price',
        'cost_price_base',
        'list_price',
        'list_price_base',
        'currency',
        'exchange_rate',
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
            'cost_price_base' => 'decimal:2',
            'list_price' => 'decimal:2',
            'list_price_base' => 'decimal:2',
            'exchange_rate' => 'decimal:6',
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
