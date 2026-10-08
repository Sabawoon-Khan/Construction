<?php

namespace App\Models;

use App\Concerns\HasAttachments;
use App\Concerns\Trashable;
use App\Models\Concerns\HasCreator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Expense extends Model
{
    use HasCreator;
    use HasAttachments;
    use SoftDeletes;
    use Trashable;

    protected $fillable = [
        'user_id',
        'project_id',
        'purchase_id',
        'expense_category_id',
        'expense_unit_id',
        'expense_no',
        'expense_date',
        'paid_to',
        'item_amount',
        'item_amount_base',
        'per_price',
        'per_price_base',
        'amount',
        'amount_base',
        'currency',
        'exchange_rate',
        'payment_method',
        'reference_no',
        'status',
        'sort_order',
        'description',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'expense_date' => 'date',
            'item_amount' => 'decimal:2',
            'item_amount_base' => 'decimal:2',
            'per_price' => 'decimal:2',
            'per_price_base' => 'decimal:2',
            'amount' => 'decimal:2',
            'amount_base' => 'decimal:2',
            'exchange_rate' => 'decimal:6',
            'sort_order' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Expense $expense): void {
            if ($expense->sort_order !== null) {
                return;
            }

            $expense->sort_order = static::nextSortOrder($expense->project_id);
        });
    }

    public static function nextSortOrder(?int $projectId): int
    {
        $query = static::query();

        if ($projectId === null) {
            $query->whereNull('project_id');
        } else {
            $query->where('project_id', $projectId);
        }

        return ((int) $query->max('sort_order')) + 1;
    }

    /**
     * @param  Builder<Expense>  $query
     * @return Builder<Expense>
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    /**
     * @return Builder<Expense>
     */
    public function siblingsQuery(): Builder
    {
        $query = static::query();

        if ($this->project_id === null) {
            $query->whereNull('project_id');
        } else {
            $query->where('project_id', $this->project_id);
        }

        return $query;
    }

    public function moveToFirst(): void
    {
        $this->moveBefore(
            $this->siblingsQuery()->ordered()->value('id')
        );
    }

    public function moveToLast(): void
    {
        $this->moveBefore(null);
    }

    /**
     * Place this expense before another sibling (or at the end when $beforeId is null).
     */
    public function moveBefore(?int $beforeId): void
    {
        if ($beforeId === $this->id) {
            return;
        }

        if ($beforeId !== null) {
            $before = $this->siblingsQuery()->whereKey($beforeId)->first();
            if (! $before) {
                return;
            }
        }

        DB::transaction(function () use ($beforeId): void {
            $ids = $this->siblingsQuery()
                ->ordered()
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();

            $expenseId = (int) $this->id;
            $from = array_search($expenseId, $ids, true);
            if ($from === false) {
                return;
            }

            array_splice($ids, $from, 1);

            if ($beforeId === null) {
                $ids[] = $expenseId;
            } else {
                $to = array_search((int) $beforeId, $ids, true);
                if ($to === false) {
                    $ids[] = $expenseId;
                } else {
                    array_splice($ids, $to, 0, [$expenseId]);
                }
            }

            foreach ($ids as $index => $id) {
                static::query()->whereKey($id)->update(['sort_order' => $index + 1]);
            }

            $this->setAttribute('sort_order', array_search($expenseId, $ids, true) + 1);
        });
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    public function isPurchaseLinked(): bool
    {
        return $this->purchase_id !== null;
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class, 'expense_category_id');
    }

    public function expenseUnit(): BelongsTo
    {
        return $this->belongsTo(ExpenseUnit::class);
    }
}
