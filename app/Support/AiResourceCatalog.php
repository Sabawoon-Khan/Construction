<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class AiResourceCatalog
{
    /**
     * @return array<string, array<string, mixed>>
     */
    public static function all(): array
    {
        $resources = config('ai_resources', []);

        return is_array($resources) ? $resources : [];
    }

    /**
     * @return list<string>
     */
    public static function keys(): array
    {
        return array_keys(self::all());
    }

    /**
     * @return array<string, mixed>
     */
    public static function get(string $key): array
    {
        $resources = self::all();

        if (! isset($resources[$key]) || ! is_array($resources[$key])) {
            throw new InvalidArgumentException("Unknown AI resource [{$key}].");
        }

        $resource = $resources[$key];
        $resource['key'] = $key;

        return $resource;
    }

    public static function has(string $key): bool
    {
        return isset(self::all()[$key]);
    }

    /**
     * @param  array<string, mixed>  $resource
     * @return class-string<Model>
     */
    public static function modelClass(array $resource): string
    {
        $model = $resource['model'] ?? null;

        if (! is_string($model) || ! is_subclass_of($model, Model::class)) {
            throw new InvalidArgumentException('AI resource is missing a valid Eloquent model.');
        }

        return $model;
    }

    /**
     * @param  array<string, mixed>  $resource
     */
    public static function permissionFor(array $resource, string $verb): ?string
    {
        $map = $resource['permissions'] ?? null;
        if (is_array($map) && isset($map[$verb]) && is_string($map[$verb])) {
            return $map[$verb];
        }

        $prefix = $resource['permission'] ?? null;
        if (! is_string($prefix) || $prefix === '') {
            return null;
        }

        return $prefix.'.'.$verb;
    }

    /**
     * @param  array<string, mixed>  $resource
     * @return list<string>
     */
    public static function sensitiveAttributes(array $resource): array
    {
        $sensitive = $resource['sensitive'] ?? [];

        return is_array($sensitive)
            ? array_values(array_filter($sensitive, fn ($v) => is_string($v)))
            : [];
    }

    /**
     * @param  array<string, mixed>  $resource
     * @return list<string>
     */
    public static function displayAttributes(array $resource): array
    {
        $display = $resource['display'] ?? ['id'];

        return is_array($display)
            ? array_values(array_filter($display, fn ($v) => is_string($v)))
            : ['id'];
    }
}
