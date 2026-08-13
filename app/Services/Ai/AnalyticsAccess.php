<?php

namespace App\Services\Ai;

use App\Models\User;
use App\Support\AiResourceCatalog;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

/**
 * Restricts analytics schema + SQL to tables the current user may view.
 * Never advertise unauthorized tables to the model or the end user.
 */
class AnalyticsAccess
{
    /**
     * @return list<string>
     */
    public function allowedTables(User $user): array
    {
        $allowed = [];

        foreach (AiResourceCatalog::all() as $key => $resource) {
            if (! is_array($resource)) {
                continue;
            }

            if (! $this->canView($user, $resource)) {
                continue;
            }

            $table = $this->tableForResource($resource);
            if ($table !== null) {
                $allowed[] = $table;
            }

            foreach ($this->relatedTablesForResource($key) as $related) {
                $allowed[] = $related;
            }
        }

        foreach ($this->extraTablePermissions() as $table => $permissions) {
            if ($this->userHasAny($user, $permissions)) {
                $allowed[] = $table;
            }
        }

        $allowed = array_values(array_unique(array_map('strtolower', $allowed)));
        sort($allowed);

        return $allowed;
    }

    /**
     * @param  list<string>  $allowedTables
     * @return array{table: string, user_id_column: string}|null
     */
    public function scopeRuleForTable(string $table, User $user): ?array
    {
        $table = strtolower($table);

        foreach (AiResourceCatalog::all() as $resource) {
            if (! is_array($resource) || ($resource['user_scoped'] ?? false) !== true) {
                continue;
            }

            $resourceTable = $this->tableForResource($resource);
            if ($resourceTable === null || strtolower($resourceTable) !== $table) {
                continue;
            }

            if (! $this->canView($user, $resource)) {
                return null;
            }

            return [
                'table' => $resourceTable,
                'user_id_column' => (string) ($resource['user_id_column'] ?? 'user_id'),
            ];
        }

        return null;
    }

    /**
     * @return list<array{table: string, user_id_column: string}>
     */
    public function scopeRules(User $user): array
    {
        $rules = [];
        foreach ($this->allowedTables($user) as $table) {
            $rule = $this->scopeRuleForTable($table, $user);
            if ($rule !== null) {
                $rules[] = $rule;
            }
        }

        return $rules;
    }

    public function canAccessTable(User $user, string $table): bool
    {
        return in_array(strtolower($table), $this->allowedTables($user), true);
    }

    /**
     * @param  list<string>  $allowedTables
     */
    public function assertSqlUsesOnlyAllowedTables(string $sql, array $allowedTables): void
    {
        $allowedLookup = array_fill_keys(array_map('strtolower', $allowedTables), true);
        $referenced = $this->extractTableNames($sql);

        foreach ($referenced as $table) {
            if (! isset($allowedLookup[strtolower($table)])) {
                throw new InvalidArgumentException(
                    'You do not have access to analyze that data.',
                );
            }
        }
    }

    public function assertSqlRespectsScopes(string $sql, User $user): void
    {
        foreach ($this->scopeRules($user) as $rule) {
            $table = $rule['table'];
            $column = $rule['user_id_column'];
            if (! preg_match('/\b'.preg_quote($table, '/').'\b/i', $sql)) {
                continue;
            }

            $uid = (string) $user->id;
            $hasScope = preg_match(
                '/\b'.preg_quote($column, '/').'\b\s*=\s*[\'"]?'.preg_quote($uid, '/').'[\'"]?/i',
                $sql,
            ) === 1;

            if (! $hasScope) {
                throw new InvalidArgumentException(
                    'You do not have access to analyze that data.',
                );
            }
        }
    }

    /**
     * Compact scope instructions for the SQL author (no unauthorized table names).
     */
    public function scopeInstructions(User $user): string
    {
        $rules = $this->scopeRules($user);
        if ($rules === []) {
            return '';
        }

        $lines = ['Row-scope rules (mandatory):'];
        foreach ($rules as $rule) {
            $lines[] = "- When querying {$rule['table']}, always filter {$rule['user_id_column']} = {$user->id} (this user's own rows only).";
        }

        return implode("\n", $lines);
    }

    public function publicAccessDeniedMessage(): string
    {
        return 'You do not have access to analyze that data.';
    }

    /**
     * @param  array<string, mixed>  $resource
     */
    protected function canView(User $user, array $resource): bool
    {
        $permission = AiResourceCatalog::permissionFor($resource, 'view');
        if ($permission === null || $permission === '') {
            return false;
        }

        return $user->can($permission);
    }

    /**
     * @param  array<string, mixed>  $resource
     */
    protected function tableForResource(array $resource): ?string
    {
        try {
            $modelClass = AiResourceCatalog::modelClass($resource);
            /** @var Model $model */
            $model = new $modelClass;

            return $model->getTable();
        } catch (InvalidArgumentException) {
            return null;
        }
    }

    /**
     * @return list<string>
     */
    protected function relatedTablesForResource(string $key): array
    {
        return match ($key) {
            'users' => ['model_has_roles'],
            'roles' => ['model_has_roles', 'role_has_permissions', 'permissions'],
            default => [],
        };
    }

    /**
     * Tables not in ai_resources but used by analytics.
     *
     * @return array<string, list<string>>
     */
    protected function extraTablePermissions(): array
    {
        $configured = config('ai.analytics.table_permissions', []);
        if (! is_array($configured) || $configured === []) {
            return [
                'authentication_logs' => ['audit.view'],
                'ai_usage_logs' => ['settings.manage', 'audit.view'],
                'email_logs' => ['audit.view', 'settings.manage'],
            ];
        }

        $out = [];
        foreach ($configured as $table => $permissions) {
            if (! is_string($table) || $table === '') {
                continue;
            }
            $perms = is_array($permissions)
                ? array_values(array_filter($permissions, fn ($p) => is_string($p) && $p !== ''))
                : [];
            $out[$table] = $perms;
        }

        return $out;
    }

    /**
     * @param  list<string>  $permissions
     */
    protected function userHasAny(User $user, array $permissions): bool
    {
        if ($permissions === []) {
            return false;
        }

        foreach ($permissions as $permission) {
            if ($user->can($permission)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<string>
     */
    protected function extractTableNames(string $sql): array
    {
        $tables = [];
        $pattern = '/\b(?:FROM|JOIN|UPDATE|INTO)\s+[`"]?([a-zA-Z0-9_]+)[`"]?/i';
        if (preg_match_all($pattern, $sql, $matches) !== false) {
            foreach ($matches[1] as $name) {
                $tables[] = $name;
            }
        }

        return array_values(array_unique($tables));
    }
}
