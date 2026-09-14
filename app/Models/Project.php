<?php

namespace App\Models;

use App\Concerns\HasAttachments;
use App\Concerns\Trashable;
use App\Models\Concerns\HasCreator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Project extends Model
{
    use HasAttachments;
    use HasCreator;
    use SoftDeletes;
    use Trashable;

    protected $fillable = [
        'user_id',
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

    protected $attributes = [
        'type' => 'apartment',
        'status' => 'planning',
        'estimated_budget' => 0,
        'estimated_revenue' => 0,
        'progress_percent' => 0,
    ];

    protected static function booted(): void
    {
        static::saving(function (self $project): void {
            $project->type ??= 'apartment';
            $project->status ??= 'planning';
            $project->estimated_budget ??= 0;
            $project->estimated_revenue ??= 0;
            $project->progress_percent ??= 0;
        });

        static::forceDeleting(function (self $project): void {
            $project->sales()->withTrashed()->each(
                fn (Sale $sale) => $sale->forceDelete()
            );

            $project->purchases()->withTrashed()->each(
                fn (Purchase $purchase) => $purchase->forceDelete()
            );
        });
    }

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

    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class);
    }

    public function loans(): HasMany
    {
        return $this->hasMany(Loan::class);
    }

    public function inventoryTransactions(): HasMany
    {
        return $this->hasMany(InventoryTransaction::class);
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

    public function employees(): BelongsToMany
    {
        return $this->belongsToMany(Employee::class, 'project_employee')
            ->withPivot(['started_at', 'role_on_project', 'working_hours', 'notes'])
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
