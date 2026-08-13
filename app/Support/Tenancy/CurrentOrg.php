<?php

namespace App\Support\Tenancy;

/**
 * Lightweight single-DB org scope holder.
 * Enable with KIT_TENANCY=true and use BelongsToOrg on domain models.
 */
class CurrentOrg
{
    protected static bool $bypassTenantScope = false;

    protected static ?int $orgId = null;

    public static function set(?int $orgId): void
    {
        self::$orgId = $orgId;
    }

    public static function id(): ?int
    {
        return self::$orgId;
    }

    public static function bypassTenantScope(bool $bypass = true): void
    {
        self::$bypassTenantScope = $bypass;
    }

    public static function isBypassingTenantScope(): bool
    {
        return self::$bypassTenantScope;
    }

    public static function reset(): void
    {
        self::$orgId = null;
        self::$bypassTenantScope = false;
    }
}
