<?php

namespace App\Models;

use App\Concerns\Trashable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Purchase extends Model
{
    use SoftDeletes;
    use Trashable;

    protected $fillable = [
        'project_id',
        'supplier_id',
        'purchase_no',
        'purchase_date',
        'total_amount',
        'total_amount_base',
        'paid_amount',
        'paid_amount_base',
        'currency',
        'exchange_rate',
        'status',
        'reference_no',
        'notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'purchase_date' => 'date',
            'total_amount' => 'decimal:2',
            'total_amount_base' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'paid_amount_base' => 'decimal:2',
            'exchange_rate' => 'decimal:6',
        ];
    }

    protected $attributes = [
        'paid_amount' => 0,
        'paid_amount_base' => 0,
        'total_amount' => 0,
        'total_amount_base' => 0,
        'currency' => 'USD',
        'exchange_rate' => 1,
        'status' => 'unpaid',
    ];

    protected $appends = [
        'remaining_amount',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function expense(): HasOne
    {
        return $this->hasOne(Expense::class);
    }

    public function inventoryTransactions(): HasMany
    {
        return $this->hasMany(InventoryTransaction::class);
    }

    public function getRemainingAmountAttribute(): float
    {
        return max(0, round((float) $this->total_amount - (float) $this->paid_amount, 2));
    }

    public static function statusFor(float $total, float $paid): string
    {
        if ($paid <= 0) {
            return 'unpaid';
        }

        if ($paid + 0.0001 >= $total) {
            return 'paid';
        }

        return 'partial';
    }

    protected static function booted(): void
    {
        static::deleting(function (Purchase $purchase): void {
            if (method_exists($purchase, 'isForceDeleting') && $purchase->isForceDeleting()) {
                $purchase->payments()->withTrashed()->forceDelete();
                $purchase->expense()->withTrashed()->forceDelete();
                $purchase->inventoryTransactions()->delete();

                return;
            }

            $purchase->payments()->delete();
            $purchase->expense()->delete();
            $purchase->inventoryTransactions()->where('type', InventoryTransaction::TYPE_PURCHASE)->delete();
        });

        static::restoring(function (Purchase $purchase): void {
            $purchase->payments()->onlyTrashed()->restore();
            $purchase->expense()->onlyTrashed()->restore();
        });

        static::restored(function (Purchase $purchase): void {
            $purchase->loadMissing(['items', 'expense', 'supplier']);

            if ($purchase->inventoryTransactions()->where('type', InventoryTransaction::TYPE_PURCHASE)->exists()) {
                return;
            }

            foreach ($purchase->items as $item) {
                InventoryTransaction::query()->create([
                    'project_id' => $purchase->project_id,
                    'material_id' => $item->material_id,
                    'supplier_id' => $purchase->supplier_id,
                    'expense_id' => $purchase->expense?->id,
                    'purchase_id' => $purchase->id,
                    'type' => InventoryTransaction::TYPE_PURCHASE,
                    'quantity' => $item->quantity,
                    'unit_cost' => $item->unit_cost,
                    'total_cost' => $item->total_cost,
                    'currency' => $purchase->currency,
                    'transaction_date' => $purchase->purchase_date,
                    'reference_no' => $purchase->purchase_no,
                    'notes' => null,
                ]);
            }
        });
    }
}
