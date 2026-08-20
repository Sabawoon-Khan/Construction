<?php

namespace App\Models;

use App\Models\Concerns\HasCreator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class MaterialUnit extends Model
{
    use HasCreator;

    protected $fillable = [
        'user_id',
        'unit_name',
    ];

    public function materials(): HasMany
    {
        return $this->hasMany(Material::class);
    }

    /** @return Collection<int, MaterialUnit> */
    public static function options(): Collection
    {
        return static::query()->orderBy('unit_name')->get(['id', 'unit_name']);
    }

    public static function findOrCreateNamed(string $unitName): self
    {
        $name = trim($unitName);

        return static::query()->firstOrCreate(['unit_name' => $name]);
    }
}
