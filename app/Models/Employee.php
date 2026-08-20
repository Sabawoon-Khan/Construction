<?php

namespace App\Models;

use App\Concerns\HasAttachments;
use App\Concerns\Trashable;
use App\Models\Concerns\HasCreator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    use HasAttachments;
    use HasCreator;
    use SoftDeletes;
    use Trashable;

    protected $fillable = [
        'user_id',
        'employee_no',
        'name',
        'email',
        'phone',
        'department',
        'designation',
        'joining_date',
        'basic_salary',
        'basic_salary_base',
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
            'joining_date' => 'date',
            'basic_salary' => 'decimal:2',
            'basic_salary_base' => 'decimal:2',
            'exchange_rate' => 'decimal:6',
        ];
    }

    protected $attributes = [
        'currency' => 'USD',
        'exchange_rate' => 1,
        'status' => 'active',
    ];

    public function attendance(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function salaries(): HasMany
    {
        return $this->hasMany(Salary::class);
    }
}
