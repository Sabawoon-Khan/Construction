<?php

namespace App\Models;

use App\Concerns\Trashable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Partner extends Model
{
    use SoftDeletes;
    use Trashable;

    protected $fillable = [
        'user_id',
        'partner_no',
        'name',
        'phone',
        'email',
        'national_id',
        'ownership_percentage',
        'address',
        'notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'ownership_percentage' => 'decimal:4',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function investments(): HasMany
    {
        return $this->hasMany(PartnerInvestment::class);
    }

    public function distributions(): HasMany
    {
        return $this->hasMany(PartnerDistribution::class);
    }
}
