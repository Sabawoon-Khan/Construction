<?php

namespace App\Models;

use App\Models\Concerns\HasCreator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class ExpenseUnit extends Model
{
    use HasCreator;

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'unit_name',
    ];

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    /** @return Collection<int, ExpenseUnit> */
    public static function options(): Collection
    {
        return static::query()->orderBy('unit_name')->get(['id', 'unit_name']);
    }
}
