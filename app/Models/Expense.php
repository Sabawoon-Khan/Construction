<?php

namespace App\Models;

use App\Concerns\HasAttachments;
use App\Concerns\Trashable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Expense extends Model
{
    use HasAttachments;
    use SoftDeletes;
    use Trashable;

    protected $fillable = [
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
        ];
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
