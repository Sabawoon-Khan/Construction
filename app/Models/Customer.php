<?php

namespace App\Models;

use App\Concerns\HasAttachments;
use App\Concerns\Trashable;
use App\Models\Concerns\HasCreator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use HasCreator;
    use HasAttachments;
    use SoftDeletes;
    use Trashable;

    protected $fillable = [
        'user_id',
        'customer_no',
        'name',
        'father_name',
        'phone',
        'email',
        'national_id',
        'address',
        'notes',
    ];

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function incomes(): HasMany
    {
        return $this->hasMany(Income::class);
    }
}
