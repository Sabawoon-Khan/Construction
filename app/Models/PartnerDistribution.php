<?php

namespace App\Models;

use App\Models\Concerns\HasCreator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PartnerDistribution extends Model
{
    use HasCreator;

    protected $fillable = [
        'user_id',
        'project_id',
        'partner_id',
        'amount',
        'amount_base',
        'currency',
        'exchange_rate',
        'type',
        'distributed_at',
        'reference_no',
        'notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'amount_base' => 'decimal:2',
            'exchange_rate' => 'decimal:6',
            'distributed_at' => 'date',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }
}
