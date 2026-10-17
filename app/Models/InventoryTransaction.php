<?php

namespace App\Models;

use App\Models\Concerns\HasCreator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

class InventoryTransaction extends Model
{
    use HasCreator;

    public const TYPE_PURCHASE = 'purchase';

    public const TYPE_ISSUE = 'issue';
    
    public const TYPE_TRANSFER_IN = 'transfer_in';
    public const TYPE_TRANSFER_OUT = 'transfer_out';

    protected $fillable = [
        'user_id',
        'project_id',
        'from_project_id',
        'to_project_id',
        'material_id',
        'supplier_id',
        'expense_id',
        'purchase_id',
        'type',
        'quantity',
        'unit_cost',
        'unit_cost_base',
        'total_cost',
        'total_cost_base',
        'currency',
        'exchange_rate',
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
            'unit_cost_base' => 'decimal:2',
            'total_cost' => 'decimal:2',
            'total_cost_base' => 'decimal:2',
            'exchange_rate' => 'decimal:6',
            'transaction_date' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function fromProject(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'from_project_id');
    }

    public function toProject(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'to_project_id');
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
            ->selectRaw(
                'COALESCE(SUM(CASE WHEN type IN (?, ?) THEN quantity WHEN type IN (?, ?) THEN -quantity ELSE 0 END), 0) as on_hand',
                [
                    self::TYPE_PURCHASE,
                    self::TYPE_TRANSFER_IN,
                    self::TYPE_ISSUE,
                    self::TYPE_TRANSFER_OUT,
                ],
            )
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
            ->selectRaw(
                'COALESCE(SUM(CASE WHEN type IN (?, ?) THEN quantity ELSE 0 END), 0) as purchased',
                [self::TYPE_PURCHASE, self::TYPE_TRANSFER_IN],
            )
            ->selectRaw(
                'COALESCE(SUM(CASE WHEN type IN (?, ?) THEN quantity ELSE 0 END), 0) as issued',
                [self::TYPE_ISSUE, self::TYPE_TRANSFER_OUT],
            )
            ->selectRaw(
                'COALESCE(SUM(CASE WHEN type IN (?, ?) THEN quantity WHEN type IN (?, ?) THEN -quantity ELSE 0 END), 0) as on_hand',
                [
                    self::TYPE_PURCHASE,
                    self::TYPE_TRANSFER_IN,
                    self::TYPE_ISSUE,
                    self::TYPE_TRANSFER_OUT,
                ],
            )
            ->selectRaw('MAX(CASE WHEN type IN (?, ?) THEN transaction_date ELSE NULL END) as last_issue_at', [self::TYPE_ISSUE, self::TYPE_TRANSFER_OUT])
            ->groupBy('project_id', 'material_id')
            ->orderBy('project_id')
            ->orderBy('material_id');

        if ($projectId) {
            $query->where('project_id', $projectId);
        }

        return $query->get();
    }
}
