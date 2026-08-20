<?php

namespace App\Models;

use App\Models\Concerns\HasCreator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    use HasCreator;

    protected $table = 'attendance';

    protected $fillable = [
        'user_id',
        'employee_id',
        'date',
        'check_in',
        'check_out',
        'status',
        'notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date' => 'date',
            'check_in' => 'datetime:H:i',
            'check_out' => 'datetime:H:i',
        ];
    }

    protected $attributes = [
        'status' => 'present',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
