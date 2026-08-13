<?php

namespace App\Support\Tenancy;

class OrgStorage
{
    public static function pathFor(int|string $orgKey, string $relativePath): string
    {
        $prefix = config('tenancy.storage_prefix', 'orgs');

        return trim($prefix.'/'.$orgKey.'/'.ltrim($relativePath, '/'), '/');
    }

    public static function disk(): string
    {
        return (string) config('tenancy.disk', 'public');
    }
}
