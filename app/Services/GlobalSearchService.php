<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class GlobalSearchService
{
    /**
     * @return list<array{key: string, label: string, results: list<array<string, mixed>>}>
     */
    public function search(User $user, string $term, int $perResource = 8): array
    {
        $term = trim(preg_replace('/\s+/u', ' ', $term) ?? '');

        if ($term === '' || (mb_strlen($term) < 2 && ! ctype_digit($term))) {
            return [];
        }

        $tokens = $this->tokens($term);
        $groups = [];

        foreach ($this->resources() as $key => $resource) {
            if (! $this->canView($user, $resource)) {
                continue;
            }

            $results = $this->searchResource($user, $key, $resource, $term, $tokens, $perResource);

            if ($results === []) {
                continue;
            }

            $groups[] = [
                'key' => $key,
                'label' => (string) ($resource['label'] ?? $key),
                'results' => $results,
            ];
        }

        usort($groups, function (array $a, array $b): int {
            $scoreA = $a['results'][0]['score'] ?? 0;
            $scoreB = $b['results'][0]['score'] ?? 0;

            return $scoreB <=> $scoreA;
        });

        return $groups;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    protected function resources(): array
    {
        $resources = config('global_search', []);

        return is_array($resources) ? $resources : [];
    }

    /**
     * @return list<string>
     */
    protected function tokens(string $term): array
    {
        $parts = preg_split('/\s+/u', $term) ?: [];

        return array_values(array_filter(
            array_map(static fn ($part) => trim((string) $part), $parts),
            static fn ($part) => $part !== ''
        ));
    }

    /**
     * @param  array<string, mixed>  $resource
     */
    protected function canView(User $user, array $resource): bool
    {
        $prefix = $resource['permission'] ?? null;

        if (! is_string($prefix) || $prefix === '') {
            return true;
        }

        return $user->can($prefix.'.view');
    }

    /**
     * @param  array<string, mixed>  $resource
     */
    protected function canEdit(User $user, array $resource): bool
    {
        $prefix = $resource['permission'] ?? null;

        if (! is_string($prefix) || $prefix === '') {
            return false;
        }

        return $user->can($prefix.'.edit');
    }

    /**
     * @param  array<string, mixed>  $resource
     * @param  list<string>  $tokens
     * @return list<array<string, mixed>>
     */
    protected function searchResource(
        User $user,
        string $key,
        array $resource,
        string $term,
        array $tokens,
        int $limit,
    ): array {
        $modelClass = $resource['model'] ?? null;

        if (! is_string($modelClass) || ! is_subclass_of($modelClass, Model::class)) {
            return [];
        }

        /** @var Builder $query */
        $query = $modelClass::query();

        if (! empty($resource['with']) && is_array($resource['with'])) {
            $query->with($resource['with']);
        }

        if (! empty($resource['user_scoped'])) {
            $column = is_string($resource['user_id_column'] ?? null)
                ? $resource['user_id_column']
                : 'user_id';
            $query->where($column, $user->id);
        }

        $this->applySearch($query, $resource, $term, $tokens);

        $candidateLimit = max($limit * 4, 20);

        $records = $query
            ->limit($candidateLimit)
            ->get();

        $canEdit = $this->canEdit($user, $resource);

        return $records
            ->map(fn (Model $record) => $this->mapResult($key, $resource, $record, $canEdit, $term, $tokens))
            ->filter()
            ->sortByDesc(fn (array $item) => [$item['score'], $item['title']])
            ->take($limit)
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $resource
     * @param  list<string>  $tokens
     */
    protected function applySearch(Builder $query, array $resource, string $term, array $tokens): void
    {
        $columns = $this->stringList($resource['search'] ?? []);
        $relations = is_array($resource['relations'] ?? null) ? $resource['relations'] : [];
        $phoneColumns = $this->stringList($resource['phone'] ?? []);

        if ($columns === [] && $relations === [] && $phoneColumns === [] && ! ctype_digit($term)) {
            $query->whereRaw('1 = 0');

            return;
        }
        $query->where(function (Builder $outer) use ($columns, $relations, $phoneColumns, $tokens, $term, $query): void {
            foreach ($tokens as $token) {
                $tokenDigits = preg_replace('/\D+/', '', $token) ?? '';

                $outer->where(function (Builder $tokenQuery) use ($columns, $relations, $phoneColumns, $token, $tokenDigits, $query): void {
                    $like = $this->like($token);
                    $first = true;

                    foreach ($columns as $column) {
                        if ($first) {
                            $tokenQuery->where($column, 'like', $like);
                            $first = false;
                        } else {
                            $tokenQuery->orWhere($column, 'like', $like);
                        }
                    }

                    foreach ($relations as $relation => $relationColumns) {
                        if (! is_string($relation) || ! is_array($relationColumns)) {
                            continue;
                        }

                        $relationColumns = $this->stringList($relationColumns);

                        if ($relationColumns === []) {
                            continue;
                        }

                        $method = $first ? 'whereHas' : 'orWhereHas';
                        $first = false;
                        $tokenQuery->{$method}($relation, function (Builder $rel) use ($relationColumns, $like): void {
                            $rel->where(function (Builder $inner) use ($relationColumns, $like): void {
                                foreach ($relationColumns as $index => $column) {
                                    if ($index === 0) {
                                        $inner->where($column, 'like', $like);
                                    } else {
                                        $inner->orWhere($column, 'like', $like);
                                    }
                                }
                            });
                        });
                    }

                    if (mb_strlen($tokenDigits) >= 3) {
                        foreach ($phoneColumns as $column) {
                            $method = $first ? 'where' : 'orWhere';
                            $first = false;
                            $tokenQuery->{$method}($column, 'like', $this->like($tokenDigits));
                        }
                    }

                    if (ctype_digit($token)) {
                        $method = $first ? 'where' : 'orWhere';
                        $tokenQuery->{$method}($query->getModel()->getQualifiedKeyName(), (int) $token);
                    }
                });
            }

            if (ctype_digit($term) && count($tokens) === 1) {
                $outer->orWhere($query->getModel()->getQualifiedKeyName(), (int) $term);
            }
        });
    }

    /**
     * @param  array<string, mixed>  $resource
     * @param  list<string>  $tokens
     * @return array<string, mixed>|null
     */
    protected function mapResult(
        string $key,
        array $resource,
        Model $record,
        bool $canEdit,
        string $term,
        array $tokens,
    ): ?array {
        $title = $this->resolveTitle($resource, $record);
        $subtitleParts = $this->resolveSubtitle($resource, $record);
        $url = $this->resolveUrl($resource, $record, $canEdit);

        if ($url === null) {
            return null;
        }

        $haystack = $this->haystack($resource, $record, $title, $subtitleParts);
        $score = $this->score($haystack, $term, $tokens, $record);
        $matched = $this->matchedLabel($haystack, $tokens);

        return [
            'id' => $record->getKey(),
            'resource' => $key,
            'label' => (string) ($resource['label'] ?? $key),
            'title' => $title,
            'subtitle' => implode(' · ', array_filter($subtitleParts)),
            'url' => $url,
            'action' => $canEdit && $this->routeExists($resource['edit_route'] ?? null) ? 'edit' : 'view',
            'score' => $score,
            'matched' => $matched,
        ];
    }

    /**
     * @param  array<string, mixed>  $resource
     */
    protected function resolveTitle(array $resource, Model $record): string
    {
        if (is_string($resource['title_from'] ?? null) && $resource['title_from'] !== '') {
            $value = data_get($record, $resource['title_from']);

            if ($value !== null && $value !== '') {
                return (string) $value;
            }
        }

        $titleAttr = is_string($resource['title'] ?? null) ? $resource['title'] : 'id';
        $value = $record->getAttribute($titleAttr);

        if ($titleAttr === 'id' || $value === null || $value === '') {
            $label = (string) ($resource['label'] ?? 'Record');

            return $label.' #'.$record->getKey();
        }

        return (string) $value;
    }

    /**
     * @param  array<string, mixed>  $resource
     * @return list<string>
     */
    protected function resolveSubtitle(array $resource, Model $record): array
    {
        $parts = [];

        foreach ($this->stringList($resource['subtitle'] ?? []) as $attr) {
            $value = $record->getAttribute($attr);

            if ($value !== null && $value !== '') {
                $parts[] = (string) $value;
            }
        }

        foreach ($resource['relations'] ?? [] as $relation => $columns) {
            if (! is_string($relation)) {
                continue;
            }

            $related = $record->relationLoaded($relation) ? $record->getRelation($relation) : null;

            if (! $related instanceof Model) {
                continue;
            }

            foreach ($this->stringList(is_array($columns) ? $columns : []) as $index => $column) {
                if ($index > 0) {
                    break;
                }

                $value = $related->getAttribute($column);

                if ($value !== null && $value !== '') {
                    $parts[] = (string) $value;
                }
            }
        }

        return array_values(array_unique($parts));
    }

    /**
     * @param  array<string, mixed>  $resource
     * @param  list<string>  $subtitleParts
     * @return array{priority: list<string>, all: list<string>}
     */
    protected function haystack(array $resource, Model $record, string $title, array $subtitleParts): array
    {
        $priority = [$title];
        foreach ($this->stringList($resource['priority'] ?? []) as $column) {
            $value = $record->getAttribute($column);
            if ($value !== null && $value !== '') {
                $priority[] = (string) $value;
            }
        }

        $all = [...$priority, ...$subtitleParts];

        foreach ($this->stringList($resource['search'] ?? []) as $column) {
            $value = $record->getAttribute($column);
            if ($value !== null && $value !== '') {
                $all[] = (string) $value;
            }
        }

        foreach ($resource['relations'] ?? [] as $relation => $columns) {
            if (! is_string($relation) || ! is_array($columns)) {
                continue;
            }

            $related = $record->relationLoaded($relation) ? $record->getRelation($relation) : null;
            if (! $related instanceof Model) {
                continue;
            }

            foreach ($this->stringList($columns) as $column) {
                $value = $related->getAttribute($column);
                if ($value !== null && $value !== '') {
                    $all[] = (string) $value;
                    if (in_array($column, ['name', 'code', 'unit_no', 'customer_no', 'supplier_no', 'employee_no', 'phone'], true)) {
                        $priority[] = (string) $value;
                    }
                }
            }
        }

        return [
            'priority' => array_values(array_unique($priority)),
            'all' => array_values(array_unique($all)),
        ];
    }

    /**
     * @param  array{priority: list<string>, all: list<string>}  $haystack
     * @param  list<string>  $tokens
     */
    protected function score(array $haystack, string $term, array $tokens, Model $record): int
    {
        $score = 0;
        $termLower = Str::lower($term);

        foreach ($haystack['priority'] as $value) {
            $valueLower = Str::lower($value);

            if ($valueLower === $termLower) {
                $score += 120;
            } elseif (Str::startsWith($valueLower, $termLower)) {
                $score += 80;
            } elseif (Str::contains($valueLower, $termLower)) {
                $score += 45;
            }
        }

        foreach ($tokens as $token) {
            $tokenLower = Str::lower($token);
            $tokenHit = false;

            foreach ($haystack['priority'] as $value) {
                $valueLower = Str::lower($value);
                if ($valueLower === $tokenLower) {
                    $score += 35;
                    $tokenHit = true;
                } elseif (Str::startsWith($valueLower, $tokenLower)) {
                    $score += 22;
                    $tokenHit = true;
                } elseif (Str::contains($valueLower, $tokenLower)) {
                    $score += 12;
                    $tokenHit = true;
                }
            }

            if (! $tokenHit) {
                foreach ($haystack['all'] as $value) {
                    if (Str::contains(Str::lower($value), $tokenLower)) {
                        $score += 6;
                        break;
                    }
                }
            }
        }

        if (ctype_digit($term) && (string) $record->getKey() === $term) {
            $score += 150;
        }

        return $score;
    }

    /**
     * @param  array{priority: list<string>, all: list<string>}  $haystack
     * @param  list<string>  $tokens
     */
    protected function matchedLabel(array $haystack, array $tokens): ?string
    {
        foreach ($tokens as $token) {
            $tokenLower = Str::lower($token);

            foreach ($haystack['all'] as $value) {
                if (Str::contains(Str::lower($value), $tokenLower)) {
                    return (string) $value;
                }
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $resource
     */
    protected function resolveUrl(array $resource, Model $record, bool $canEdit): ?string
    {
        $param = is_string($resource['route_param'] ?? null)
            ? $resource['route_param']
            : 'id';

        $params = [$param => $record];

        if ($canEdit && $this->routeExists($resource['edit_route'] ?? null)) {
            return route((string) $resource['edit_route'], $params);
        }

        if ($this->routeExists($resource['show_route'] ?? null)) {
            return route((string) $resource['show_route'], $params);
        }

        if ($this->routeExists($resource['index_route'] ?? null)) {
            $searchValue = $record->getAttribute(
                is_string($resource['title'] ?? null) ? $resource['title'] : 'id'
            );

            return route((string) $resource['index_route'], [
                'search' => is_scalar($searchValue) ? (string) $searchValue : null,
            ]);
        }

        return null;
    }

    protected function routeExists(mixed $name): bool
    {
        return is_string($name) && $name !== '' && Route::has($name);
    }

    protected function like(string $term): string
    {
        return '%'.addcslashes($term, '%_\\').'%';
    }

    /**
     * @param  mixed  $values
     * @return list<string>
     */
    protected function stringList(mixed $values): array
    {
        if (! is_array($values)) {
            return [];
        }

        return array_values(array_filter(
            $values,
            static fn ($value) => is_string($value) && $value !== ''
        ));
    }
}
