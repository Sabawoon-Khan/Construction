<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MaterialUnit extends Model
{
    protected $fillable = [
        'unit_name',
    ];

    public function materials(): HasMany
    {
        return $this->hasMany(Material::class);
    }
}
