<?php

namespace App\Models;

use App\Concerns\HasStatusChangeLogs;
use Database\Factories\UserFactory;
use App\Concerns\Trashable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Laravel\Ai\Concerns\HasConversations;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Permission\Traits\HasRoles;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string|null $google_id
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property bool $is_active
 * @property Carbon|null $disabled_at
 * @property int|null $disabled_by
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property int $session_version
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'email', 'google_id', 'password', 'is_active', 'disabled_at', 'disabled_by', 'session_version'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasConversations, HasFactory, HasRoles, HasStatusChangeLogs, LogsActivity, Notifiable, PasskeyAuthenticatable, SoftDeletes, Trashable, TwoFactorAuthenticatable;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'disabled_at' => 'datetime',
            'two_factor_confirmed_at' => 'datetime',
            'session_version' => 'integer',
        ];
    }

    public function conversations(): MorphMany
    {
        return $this->morphMany(AiConversation::class, 'participant');
    }

    public function partner(): HasOne
    {
        return $this->hasOne(Partner::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'email', 'is_active'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function disabledBy(): BelongsTo
    {
        return $this->belongsTo(self::class, 'disabled_by');
    }

    public function disable(?User $by = null): void
    {
        $from = $this->is_active ? 'active' : 'disabled';

        $this->update([
            'is_active' => false,
            'disabled_at' => now(),
            'disabled_by' => $by?->id ?? auth()->id(),
        ]);

        $this->logStatusChange('disabled', $from, $by);
    }

    public function enable(?User $by = null): void
    {
        $from = $this->is_active ? 'active' : 'disabled';

        $this->update([
            'is_active' => true,
            'disabled_at' => null,
            'disabled_by' => null,
        ]);

        $this->logStatusChange('active', $from, $by);
    }
}
