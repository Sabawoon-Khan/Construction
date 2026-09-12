<?php

namespace App\Models;

use App\Models\Concerns\HasCreator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Loan extends Model
{
    use HasCreator;
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'project_id',
        'loan_no',
        'lender_name',
        'loan_date',
        'due_date',
        'principal',
        'principal_base',
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
            'loan_date' => 'date',
            'due_date' => 'date',
            'principal' => 'decimal:2',
            'principal_base' => 'decimal:2',
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
        'status' => 'unpaid',
    ];

    protected $appends = [
        'remaining_amount',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function getRemainingAmountAttribute(): float
    {
        return max(0, round((float) $this->principal - (float) $this->paid_amount, 2));
    }

    public function getRemainingAmountBaseAttribute(): float
    {
        return max(0, round((float) $this->principal_base - (float) $this->paid_amount_base, 2));
    }

    public static function statusFor(float $principal, float $paid): string
    {
        if ($paid <= 0) {
            return 'unpaid';
        }

        if ($paid + 0.0001 >= $principal) {
            return 'paid';
        }

        return 'partial';
    }

    protected static function booted(): void
    {
        static::deleting(function (Loan $loan): void {
            if (method_exists($loan, 'isForceDeleting') && $loan->isForceDeleting()) {
                $loan->payments()->withTrashed()->forceDelete();

                return;
            }

            $loan->payments()->delete();
        });

        static::restoring(function (Loan $loan): void {
            $loan->payments()->onlyTrashed()->restore();
        });
    }
}
