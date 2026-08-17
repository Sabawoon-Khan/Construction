<?php

namespace App\Models;

use App\Concerns\HasAttachments;
use App\Concerns\Trashable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Income extends Model
{
    use HasAttachments;
    use SoftDeletes;
    use Trashable;

    protected $fillable = [
        'project_id',
        'income_category_id',
        'customer_id',
        'bank_account_id',
        'income_no',
        'income_date',
        'received_from',
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
            'income_date' => 'date',
            'amount' => 'decimal:2',
            'amount_base' => 'decimal:2',
            'exchange_rate' => 'decimal:6',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(IncomeCategory::class, 'income_category_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }
}
