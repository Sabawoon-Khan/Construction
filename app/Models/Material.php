<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Material extends Model
{
    public const STOCK_IN_TYPES = ['purchase', 'adjustment'];

    public const STOCK_OUT_TYPES = ['issue'];

    protected $fillable = [
        'code',
        'name',
        'material_unit_id',
        'minimum_stock',
        'unit_cost',
        'currency',
        'is_active',
    ];

    protected $appends = [
        'unit',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'minimum_stock' => 'decimal:3',
            'unit_cost' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    /** @return Attribute<string|null, never> */
    protected function unit(): Attribute
    {
        return Attribute::get(fn () => $this->materialUnit?->unit_name);
    }

    public function materialUnit(): BelongsTo
    {
        return $this->belongsTo(MaterialUnit::class);
    }

    public function inventoryTransactions(): HasMany
    {
        return $this->hasMany(InventoryTransaction::class);
    }

    public function currentStock(): float
    {
        $in = (float) $this->inventoryTransactions()
            ->whereIn('type', self::STOCK_IN_TYPES)
            ->sum('quantity');

        $out = (float) $this->inventoryTransactions()
            ->whereIn('type', self::STOCK_OUT_TYPES)
            ->sum('quantity');

        return round($in - $out, 3);
    }

    public function averageUnitCost(): float
    {
        $purchases = $this->inventoryTransactions()
            ->where('type', 'purchase')
            ->get(['quantity', 'total_cost']);

        if ($purchases->isEmpty()) {
            return 0.0;
        }

        $totalQty = (float) $purchases->sum('quantity');
        $totalCost = (float) $purchases->sum('total_cost');

        if ($totalQty <= 0) {
            return 0.0;
        }

        return round($totalCost / $totalQty, 2);
    }
}
