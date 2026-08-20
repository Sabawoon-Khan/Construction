<?php

namespace App\Models;

use App\Models\Concerns\HasCreator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseItem extends Model
{
    use HasCreator;

    protected $fillable = [
        'user_id',
        'purchase_id',
        'material_id',
        'quantity',
        'unit_cost',
        'unit_cost_base',
        'total_cost',
        'total_cost_base',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:3',
            'unit_cost' => 'decimal:2',
            'unit_cost_base' => 'decimal:2',
            'total_cost' => 'decimal:2',
            'total_cost_base' => 'decimal:2',
        ];
    }

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }
}
