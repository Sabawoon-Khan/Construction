<?php

namespace App\Models;

use App\Concerns\HasAttachments;
use App\Concerns\Trashable;
use App\Models\Concerns\HasCreator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use HasAttachments;
    use HasCreator;
    use SoftDeletes;
    use Trashable;

    protected $fillable = [
        'user_id',
        'customer_no',
        'name',
        'takhallus',
        'father_name',
        'phone',
        'email',
        'national_id',
        'address',
        'registered_at',
        'type',
        'status',
        'notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'registered_at' => 'date',
        ];
    }

    protected $attributes = [
        'type' => 'retail',
        'status' => 'active',
    ];

    public static function nextCustomerNo(): string
    {
        $max = static::withTrashed()
            ->pluck('customer_no')
            ->reduce(function (int $carry, mixed $no) {
                if (is_string($no) && preg_match('/^C-(\d+)$/i', $no, $matches)) {
                    return max($carry, (int) $matches[1]);
                }

                return $carry;
            }, 0);

        return 'C-'.str_pad((string) ($max + 1), 3, '0', STR_PAD_LEFT);
    }

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

    protected static function booted(): void
    {
        static::forceDeleting(function (self $customer): void {
            foreach ($customer->sales()->withTrashed()->cursor() as $sale) {
                $sale->forceDelete();
            }

            foreach ($customer->payments()->withTrashed()->cursor() as $payment) {
                $payment->forceDelete();
            }

            foreach ($customer->incomes()->withTrashed()->cursor() as $income) {
                $income->forceDelete();
            }
        });
    }
}
