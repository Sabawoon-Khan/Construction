<?php

namespace App\Support;

use App\Models\LookupItem;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

class LookupTable
{
    /**
     * @return array<string, array{label: string}>
     */
    public static function groups(): array
    {
        return config('lookups.groups', []);
    }

    public static function isAllowed(string $group): bool
    {
        return array_key_exists($group, self::groups());
    }

    public static function routePattern(): string
    {
        return implode('|', array_keys(self::groups()));
    }

    /**
     * @return array<string, mixed>
     */
    public static function storeRules(string $group): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('lookup_items', 'name')->where(fn ($q) => $q->where('group', $group)->whereNull('deleted_at')),
            ],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function updateRules(string $group, LookupItem $item): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('lookup_items', 'name')
                    ->where(fn ($q) => $q->where('group', $group)->whereNull('deleted_at'))
                    ->ignore($item->id),
            ],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function create(string $group, array $data): LookupItem
    {
        return LookupItem::query()->create([
            'group' => $group,
            'name' => $data['name'],
            'sort_order' => $data['sort_order'] ?? 0,
            'is_active' => $data['is_active'] ?? true,
        ]);
    }

    /**
     * @return Collection<int, LookupItem>
     */
    public static function forGroup(string $group): Collection
    {
        return LookupItem::query()
            ->where('group', $group)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }
}
