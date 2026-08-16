<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Salary extends Model
{
    protected $fillable = [
        'employee_id',
        'month',
        'year',
        'basic_salary',
        'bonus',
        'deduction',
        'net_salary',
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
            'bonus' => 'decimal:2',
            'deduction' => 'decimal:2',
            'net_salary' => 'decimal:2',
            'payment_date' => 'date',
        ];
    }

    protected $attributes = [
        'basic_salary' => 0,
        'bonus' => 0,
        'deduction' => 0,
        'net_salary' => 0,
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
        });
    }
}
