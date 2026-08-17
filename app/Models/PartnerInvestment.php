<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PartnerInvestment extends Model
{
    protected $fillable = [
        'project_id',
        'partner_id',
        'amount',
        'amount_base',
        'currency',
        'exchange_rate',
        'agreed_share_percentage',
        'invested_at',
        'type',
        'reference_no',
        'status',
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
            'agreed_share_percentage' => 'decimal:4',
            'invested_at' => 'date',
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
