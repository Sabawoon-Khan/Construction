<?php

namespace App\Models\Concerns;

use App\Support\Tenancy\CurrentOrg;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Optional single-DB tenant scope (org_id). Off unless kit.features.tenancy is true
 * and CurrentOrg::id() is set (typically from the authenticated user).
 */
trait BelongsToOrg
{
    public static function bootBelongsToOrg(): void
    {
        static::addGlobalScope('org', function (Builder $builder): void {
            if (! config('kit.features.tenancy')) {
                return;
            }

            if (CurrentOrg::isBypassingTenantScope()) {
                return;
            }

            $orgId = CurrentOrg::id();

            if ($orgId === null) {
                return;
            }

            $table = $builder->getModel()->getTable();
            $builder->where($table.'.org_id', $orgId);
        });

        static::creating(function (Model $model): void {
            if (! config('kit.features.tenancy')) {
                return;
            }

            if ($model->getAttribute('org_id') !== null) {
                return;
            }

            $orgId = CurrentOrg::id();

            if ($orgId !== null) {
                $model->setAttribute('org_id', $orgId);
            }
        });
    }
}
