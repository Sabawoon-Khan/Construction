<?php

namespace App\Models;

use App\Concerns\HasAttachments;
use App\Models\Concerns\HasCreator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Salary extends Model
{
    use HasAttachments;
    use HasCreator;

    protected $fillable = [
        'user_id',
        'employee_id',
        'month',
        'year',
        'basic_salary',
        'basic_salary_base',
        'bonus',
        'bonus_base',
        'deduction',
        'deduction_base',
        'net_salary',
        'net_salary_base',
        'currency',
        'exchange_rate',
        'payment_date',
        'status',
        'notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'month' => 'integer',
            'year' => 'integer',
            'basic_salary' => 'decimal:2',
            'basic_salary_base' => 'decimal:2',
            'bonus' => 'decimal:2',
            'bonus_base' => 'decimal:2',
            'deduction' => 'decimal:2',
            'deduction_base' => 'decimal:2',
            'net_salary' => 'decimal:2',
            'net_salary_base' => 'decimal:2',
            'exchange_rate' => 'decimal:6',
            'payment_date' => 'date',
        ];
    }

    protected $attributes = [
        'basic_salary' => 0,
        'basic_salary_base' => 0,
        'bonus' => 0,
        'bonus_base' => 0,
        'deduction' => 0,
        'deduction_base' => 0,
        'net_salary' => 0,
        'net_salary_base' => 0,
        'currency' => 'USD',
        'exchange_rate' => 1,
        'status' => 'pending',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    protected static function booted(): void
    {
        static::saving(function (Salary $salary): void {
            $salary->net_salary = max(0, round(
                (float) $salary->basic_salary
                + (float) $salary->bonus
                - (float) $salary->deduction,
                2
            ));

            $salary->net_salary_base = max(0, round(
                (float) $salary->basic_salary_base
                + (float) $salary->bonus_base
                - (float) $salary->deduction_base,
                2
            ));
        });
    }
}
