<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

class InventoryTransaction extends Model
{
    public const TYPE_PURCHASE = 'purchase';

    public const TYPE_ISSUE = 'issue';

    protected $fillable = [
        'project_id',
        'material_id',
        'supplier_id',
        'expense_id',
        'purchase_id',
        'type',
        'quantity',
        'unit_cost',
        'total_cost',
        'currency',
        'transaction_date',
        'reference_no',
        'notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:3',
            'unit_cost' => 'decimal:2',
            'total_cost' => 'decimal:2',
            'transaction_date' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function expense(): BelongsTo
    {
        return $this->belongsTo(Expense::class);
    }

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    public static function onHand(int $projectId, int $materialId): float
    {
        $row = static::query()
            ->where('project_id', $projectId)
            ->where('material_id', $materialId)
            ->selectRaw('COALESCE(SUM(CASE WHEN type = ? THEN quantity WHEN type = ? THEN -quantity ELSE 0 END), 0) as on_hand', [
                self::TYPE_PURCHASE,
                self::TYPE_ISSUE,
            ])
            ->first();

        return round((float) ($row?->on_hand ?? 0), 3);
    }

    /**
     * @return Collection<int, object{project_id: int, material_id: int, on_hand: float, purchased: float, issued: float, last_issue_at: string|null}>
     */
    public static function stockLevels(?int $projectId = null): Collection
    {
        $query = static::query()
            ->select([
                'project_id',
                'material_id',
            ])
            ->selectRaw('COALESCE(SUM(CASE WHEN type = ? THEN quantity ELSE 0 END), 0) as purchased', [self::TYPE_PURCHASE])
            ->selectRaw('COALESCE(SUM(CASE WHEN type = ? THEN quantity ELSE 0 END), 0) as issued', [self::TYPE_ISSUE])
            ->selectRaw('COALESCE(SUM(CASE WHEN type = ? THEN quantity WHEN type = ? THEN -quantity ELSE 0 END), 0) as on_hand', [
                self::TYPE_PURCHASE,
                self::TYPE_ISSUE,
            ])
            ->selectRaw('MAX(CASE WHEN type = ? THEN transaction_date ELSE NULL END) as last_issue_at', [self::TYPE_ISSUE])
            ->groupBy('project_id', 'material_id')
            ->orderBy('project_id')
            ->orderBy('material_id');

        if ($projectId) {
            $query->where('project_id', $projectId);
        }

        return $query->get();
    }
}
