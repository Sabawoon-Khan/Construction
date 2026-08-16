<?php

namespace App\Models;

use App\Concerns\Trashable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    use SoftDeletes;
    use Trashable;

    protected $fillable = [
        'employee_no',
        'name',
        'email',
        'phone',
        'department',
        'designation',
        'joining_date',
        'basic_salary',
        'currency',
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
        ];
    }

    protected $attributes = [
        'currency' => 'USD',
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
