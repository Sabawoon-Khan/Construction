<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SaleInstallment extends Model
{
    protected $fillable = [
        'sale_id',
        'installment_no',
        'due_date',
        'amount',
        'amount_base',
        'paid_amount',
        'paid_amount_base',
        'currency',
        'exchange_rate',
        'status',
        'notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'amount' => 'decimal:2',
            'amount_base' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'paid_amount_base' => 'decimal:2',
            'exchange_rate' => 'decimal:6',
        ];
    }

    protected $attributes = [
        'paid_amount' => 0,
        'paid_amount_base' => 0,
        'currency' => 'USD',
        'exchange_rate' => 1,
    ];

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function getRemainingAmountAttribute(): float
    {
        return max(0, (float) $this->amount - (float) $this->paid_amount);
    }
}
