<?php

namespace App\Models;

use App\Concerns\Trashable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;

class Project extends Model
{
    use SoftDeletes;
    use Trashable;

    protected $fillable = [
        'code',
        'name',
        'type',
        'location',
        'start_date',
        'expected_completion_date',
        'actual_completion_date',
        'estimated_budget',
        'estimated_revenue',
        'progress_percent',
        'status',
        'description',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'expected_completion_date' => 'date',
            'actual_completion_date' => 'date',
            'estimated_budget' => 'decimal:2',
            'estimated_revenue' => 'decimal:2',
            'progress_percent' => 'decimal:2',
        ];
    }

    public function buildings(): HasMany
    {
        return $this->hasMany(Building::class);
    }

    public function floors(): HasManyThrough
    {
        return $this->hasManyThrough(Floor::class, Building::class);
    }

    public function units(): HasMany
    {
        return $this->hasMany(Unit::class);
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    public function incomes(): HasMany
    {
        return $this->hasMany(Income::class);
    }

    public function partnerInvestments(): HasMany
    {
        return $this->hasMany(PartnerInvestment::class);
    }

    public function partners(): BelongsToMany
    {
        return $this->belongsToMany(Partner::class, 'project_partner')
            ->withPivot('ownership_percentage')
            ->withTimestamps();
    }

    public function partnerDistributions(): HasMany
    {
        return $this->hasMany(PartnerDistribution::class);
    }

    public function financialTransactions(): HasMany
    {
        return $this->hasMany(FinancialTransaction::class);
    }

    public function vouchers(): HasMany
    {
        return $this->hasMany(Voucher::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(ProjectDocument::class);
    }
}
