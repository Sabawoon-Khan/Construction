<?php

namespace App\Models;

use App\Concerns\HasAttachments;
use App\Concerns\Trashable;
use App\Models\Concerns\HasCreator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Material extends Model
{
    use HasAttachments;
    use HasCreator;
    use SoftDeletes;
    use Trashable;

    protected $fillable = [
        'user_id',
        'code',
        'name',
        'material_unit_id',
        'minimum_stock',
        'unit_cost',
        'unit_cost_base',
        'currency',
        'exchange_rate',
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
            'unit_cost_base' => 'decimal:2',
            'exchange_rate' => 'decimal:6',
            'is_active' => 'boolean',
        ];
    }

    protected $attributes = [
        'minimum_stock' => 0,
        'unit_cost' => 0,
        'unit_cost_base' => 0,
        'currency' => 'USD',
        'exchange_rate' => 1,
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

    protected static function booted(): void
    {
        static::forceDeleting(function (self $material): void {
            $purchaseIds = $material->purchaseItems()->pluck('purchase_id')->unique()->filter();

            foreach (Purchase::withTrashed()->whereIn('id', $purchaseIds)->cursor() as $purchase) {
                $purchase->forceDelete();
            }

            $material->purchaseItems()->delete();
            $material->inventoryTransactions()->delete();
        });
    }
}
