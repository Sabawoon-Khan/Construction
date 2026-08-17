<?php

namespace App\Models;

use App\Concerns\HasAttachments;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Voucher extends Model
{
    use HasAttachments;

    protected $fillable = [
        'project_id',
        'bank_account_id',
        'voucher_no',
        'type',
        'voucher_date',
        'party_name',
        'amount',
        'amount_base',
        'currency',
        'exchange_rate',
        'payment_method',
        'reference_no',
        'status',
        'description',
        'created_by',
        'approved_by',
        'approved_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'voucher_date' => 'date',
            'amount' => 'decimal:2',
            'amount_base' => 'decimal:2',
            'exchange_rate' => 'decimal:6',
            'approved_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
