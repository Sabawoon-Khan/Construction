<?php

namespace App\Models;

use App\Models\Concerns\HasCreator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Singleton business profile for this MIS.
 * Operational data (projects, sales, expenses, etc.) does not store company_id —
 * everything in the database belongs to this one company.
 */
class Company extends Model
{
    use HasCreator;

    protected $fillable = [
        'user_id',
        'name',
        'legal_name',
        'registration_no',
        'phone',
        'email',
        'address',
        'base_currency',
        'logo_path',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}
