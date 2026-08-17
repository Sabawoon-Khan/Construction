<?php

namespace App\Models;

use App\Concerns\HasAttachments;
use App\Concerns\Trashable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Sale extends Model
{
    use HasAttachments;
    use SoftDeletes;
    use Trashable;

    protected $fillable = [
        'project_id',
        'unit_id',
        'customer_id',
        'sale_no',
        'contract_date',
        'discount',
        'discount_base',
        'currency',
        'exchange_rate',
        'status',
        'terms',
        'notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'contract_date' => 'date',
            'discount' => 'decimal:2',
            'discount_base' => 'decimal:2',
            'exchange_rate' => 'decimal:6',
        ];
    }

    protected $attributes = [
        'discount' => 0,
        'discount_base' => 0,
        'currency' => 'USD',
        'exchange_rate' => 1,
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function installments(): HasMany
    {
        return $this->hasMany(SaleInstallment::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    protected static function booted(): void
    {
        static::deleting(function (Sale $sale): void {
            if (method_exists($sale, 'isForceDeleting') && $sale->isForceDeleting()) {
                $sale->payments()->withTrashed()->forceDelete();

                return;
            }

            $sale->payments()->delete();
        });

        static::restoring(function (Sale $sale): void {
            $sale->payments()->onlyTrashed()->restore();
        });
    }

    public function getListPriceAttribute(): float
    {
        if ($this->relationLoaded('unit')) {
            return (float) ($this->unit?->list_price ?? 0);
        }

        return (float) ($this->unit()->value('list_price') ?? 0);
    }

    public function getListPriceBaseAttribute(): float
    {
        if ($this->relationLoaded('unit')) {
            return (float) ($this->unit?->list_price_base ?? 0);
        }

        return (float) ($this->unit()->value('list_price_base') ?? 0);
    }

    public function getTotalPriceAttribute(): float
    {
        return max(0, $this->list_price - (float) $this->discount);
    }

    public function getTotalPriceBaseAttribute(): float
    {
        return max(0, $this->list_price_base - (float) $this->discount_base);
    }

    public function getPaidAmountAttribute(): float
    {
        if ($this->relationLoaded('installments')) {
            return (float) $this->installments->sum('paid_amount');
        }

        return (float) $this->installments()->sum('paid_amount');
    }

    public function getPaidAmountBaseAttribute(): float
    {
        if ($this->relationLoaded('installments')) {
            return (float) $this->installments->sum('paid_amount_base');
        }

        return (float) $this->installments()->sum('paid_amount_base');
    }

    public function getRemainingAmountAttribute(): float
    {
        return max(0, $this->total_price - $this->paid_amount);
    }

    public function getRemainingAmountBaseAttribute(): float
    {
        return max(0, $this->total_price_base - $this->paid_amount_base);
    }

    public static function sumListPrice(Builder|HasMany|Relation|null $query = null): float
    {
        return static::sumTotalPrice($query);
    }

    public static function sumTotalPrice(Builder|HasMany|Relation|null $source = null): float
    {
        $query = match (true) {
            $source instanceof Relation => $source->getQuery()->clone(),
            $source instanceof Builder => $source->clone(),
            default => static::query(),
        };

        $table = $query->getModel()->getTable();

        return (float) static::query()
            ->whereIn("{$table}.id", $query->select("{$table}.id"))
            ->join('units', 'units.id', '=', "{$table}.unit_id")
            ->sum(DB::raw("units.list_price - COALESCE({$table}.discount, 0)"));
    }

    public static function sumTotalPriceBase(Builder|HasMany|Relation|null $source = null): float
    {
        $query = match (true) {
            $source instanceof Relation => $source->getQuery()->clone(),
            $source instanceof Builder => $source->clone(),
            default => static::query(),
        };

        $table = $query->getModel()->getTable();

        return (float) static::query()
            ->whereIn("{$table}.id", $query->select("{$table}.id"))
            ->join('units', 'units.id', '=', "{$table}.unit_id")
            ->sum(DB::raw("units.list_price_base - COALESCE({$table}.discount_base, 0)"));
    }

    public static function sumPaidAmount(Builder|HasMany|Relation|null $source = null): float
    {
        $query = match (true) {
            $source instanceof Relation => $source->getQuery()->clone(),
            $source instanceof Builder => $source->clone(),
            default => static::query(),
        };

        $table = $query->getModel()->getTable();

        return (float) SaleInstallment::query()
            ->whereIn('sale_id', $query->select("{$table}.id"))
            ->sum('paid_amount');
    }

    public static function sumPaidAmountBase(Builder|HasMany|Relation|null $source = null): float
    {
        $query = match (true) {
            $source instanceof Relation => $source->getQuery()->clone(),
            $source instanceof Builder => $source->clone(),
            default => static::query(),
        };

        $table = $query->getModel()->getTable();

        return (float) SaleInstallment::query()
            ->whereIn('sale_id', $query->select("{$table}.id"))
            ->sum('paid_amount_base');
    }
}
