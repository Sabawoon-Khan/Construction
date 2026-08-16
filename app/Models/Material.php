<?php

namespace App\Models;

use App\Concerns\Trashable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Material extends Model
{
    use SoftDeletes;
    use Trashable;

    protected $fillable = [
        'code',
        'name',
        'material_unit_id',
        'minimum_stock',
        'unit_cost',
        'currency',
        'is_active',
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

    protected $attributes = [
        'minimum_stock' => 0,
        'unit_cost' => 0,
        'currency' => 'USD',
        'is_active' => true,
    ];

    public function unit(): BelongsTo
    {
        return $this->belongsTo(MaterialUnit::class, 'material_unit_id');
    }

    public function purchaseItems(): HasMany
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function inventoryTransactions(): HasMany
    {
        return $this->hasMany(InventoryTransaction::class);
    }
}
