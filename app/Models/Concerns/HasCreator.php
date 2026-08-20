<?php

namespace App\Models\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Tracks which authenticated user created the record (user_id by default).
 */
trait HasCreator
{
    public static function bootHasCreator(): void
    {
        static::creating(function (Model $model): void {
            /** @var Model&object{getCreatorColumn(): string} $model */
            $column = $model->getCreatorColumn();

            if ($model->getAttribute($column) !== null) {
                return;
            }

            $userId = auth()->id();

            if ($userId !== null) {
                $model->setAttribute($column, $userId);
            }
        });
    }

    public function getCreatorColumn(): string
    {
        return property_exists($this, 'creatorColumn')
            ? (string) $this->creatorColumn
            : 'user_id';
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, $this->getCreatorColumn());
    }
}
