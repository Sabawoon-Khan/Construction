<?php

namespace App\Services\Ai;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Deterministic analytics queries that never invent columns — used for login shortcuts
 * and as a last-resort fallback when NL→SQL fails.
 */
class AnalyticsSafeFallback
{
    /**
     * Only true for prompts clearly about logins / auth events — not general "users".
     */
    public function isLoginPrompt(?string $prompt): bool
    {
        $prompt = strtolower(trim((string) $prompt));
        if ($prompt === '') {
            return false;
        }

        // "users" alone is NOT a login prompt.
        if (
            (str_contains($prompt, 'user') || str_contains($prompt, 'people') || str_contains($prompt, 'account'))
            && ! str_contains($prompt, 'login')
            && ! str_contains($prompt, 'logged')
            && ! str_contains($prompt, 'signin')
            && ! str_contains($prompt, 'sign-in')
            && ! str_contains($prompt, 'sign in')
            && ! str_contains($prompt, 'authentication')
        ) {
            return false;
        }

        return str_contains($prompt, 'login')
            || str_contains($prompt, 'logins')
            || str_contains($prompt, 'logged in')
            || str_contains($prompt, 'sign-in')
            || str_contains($prompt, 'signin')
            || str_contains($prompt, 'sign in')
            || str_contains($prompt, 'authentication log')
            || str_contains($prompt, 'auth log')
            || (str_contains($prompt, 'auth') && (str_contains($prompt, 'event') || str_contains($prompt, 'activity')));
    }

    /**
     * @return list<array{
     *     title: string,
     *     viz: string,
     *     sql: string,
     *     columns: list<string>,
     *     rows: list<array<string, mixed>>,
     *     row_count: int
     * }>
     */
    public function loginOnly(?array $allowedTables = null): array
    {
        if (! $this->tableAllowed('authentication_logs', $allowedTables)) {
            return [];
        }

        $connection = $this->connectionName();
        if (! $this->hasTable($connection, 'authentication_logs')) {
            return [];
        }

        return array_values(array_filter($this->authenticationLogSections($connection)));
    }

    /**
     * Topic-scoped fallback — never dumps login charts into unrelated prompts.
     *
     * @param  list<string>|null  $allowedTables
     * @return list<array{
     *     title: string,
     *     viz: string,
     *     sql: string,
     *     columns: list<string>,
     *     rows: list<array<string, mixed>>,
     *     row_count: int
     * }>
     */
    public function build(?string $prompt = null, ?array $allowedTables = null): array
    {
        if ($this->isLoginPrompt($prompt)) {
            $login = $this->loginOnly($allowedTables);
            if ($login !== []) {
                return $login;
            }
        }

        $connection = $this->connectionName();
        $topic = $this->topic($prompt);

        $sections = match ($topic) {
            'users' => $this->userSections($connection),
            'field_reports' => $this->fieldReportSections($connection),
            'ai_usage' => $this->aiUsageSections($connection),
            default => $this->genericOverview($connection),
        };

        return array_values(array_filter(
            $sections,
            fn ($item) => is_array($item) && $this->sqlAllowed((string) ($item['sql'] ?? ''), $allowedTables),
        ));
    }

    /**
     * @param  list<string>|null  $allowedTables
     */
    protected function tableAllowed(string $table, ?array $allowedTables): bool
    {
        if ($allowedTables === null) {
            return true;
        }

        return in_array(strtolower($table), array_map('strtolower', $allowedTables), true);
    }

    /**
     * @param  list<string>|null  $allowedTables
     */
    protected function sqlAllowed(string $sql, ?array $allowedTables): bool
    {
        if ($allowedTables === null || $sql === '') {
            return true;
        }

        if (preg_match_all('/\b(?:FROM|JOIN)\s+[`"]?([a-zA-Z0-9_]+)[`"]?/i', $sql, $matches) === false) {
            return true;
        }

        $allowedLookup = array_fill_keys(array_map('strtolower', $allowedTables), true);
        foreach ($matches[1] as $table) {
            if (! isset($allowedLookup[strtolower($table)])) {
                return false;
            }
        }

        return true;
    }

    protected function topic(?string $prompt): string
    {
        $prompt = strtolower(trim((string) $prompt));

        if (
            str_contains($prompt, 'field report')
            || str_contains($prompt, 'field_reports')
            || (str_contains($prompt, 'report') && ! str_contains($prompt, 'login'))
        ) {
            return 'field_reports';
        }

        if (
            str_contains($prompt, 'ai usage')
            || str_contains($prompt, 'token')
            || str_contains($prompt, 'ai_usage')
            || (str_contains($prompt, 'ai') && str_contains($prompt, 'cost'))
        ) {
            return 'ai_usage';
        }

        if (
            str_contains($prompt, 'user')
            || str_contains($prompt, 'people')
            || str_contains($prompt, 'account')
            || str_contains($prompt, 'role')
            || str_contains($prompt, 'active')
        ) {
            return 'users';
        }

        return 'generic';
    }

    /**
     * @return list<array{title: string, viz: string, sql: string, columns: list<string>, rows: list<array<string, mixed>>, row_count: int}|null>
     */
    protected function userSections(string $connection): array
    {
        if (! $this->hasTable($connection, 'users')) {
            return [];
        }

        $cols = $this->columns($connection, 'users');
        $sections = [];

        $sections[] = $this->run(
            $connection,
            'Total users',
            'kpi',
            'SELECT COUNT(*) AS total FROM users LIMIT 1',
        );

        if (in_array('is_active', $cols, true)) {
            $sections[] = $this->run(
                $connection,
                'Active vs inactive users',
                'pie',
                'SELECT CASE WHEN is_active = 1 THEN \'Active\' ELSE \'Inactive\' END AS label, COUNT(*) AS total FROM users GROUP BY is_active LIMIT 100',
            );
        }

        if (in_array('created_at', $cols, true)) {
            $sections[] = $this->run(
                $connection,
                'Users created by month',
                'line',
                $this->monthlyBucketSql($connection, 'users', 'created_at'),
            );
        }

        return $sections;
    }

    /**
     * @return list<array{title: string, viz: string, sql: string, columns: list<string>, rows: list<array<string, mixed>>, row_count: int}|null>
     */
    protected function fieldReportSections(string $connection): array
    {
        if (! $this->hasTable($connection, 'field_reports')) {
            return [];
        }

        $cols = $this->columns($connection, 'field_reports');
        $sections = [];

        $sections[] = $this->run(
            $connection,
            'Total field reports',
            'kpi',
            'SELECT COUNT(*) AS total FROM field_reports LIMIT 1',
        );

        if (in_array('status', $cols, true)) {
            $sections[] = $this->run(
                $connection,
                'Field reports by status',
                'bar',
                'SELECT status AS label, COUNT(*) AS total FROM field_reports GROUP BY status ORDER BY total DESC LIMIT 100',
            );
        }

        return $sections;
    }

    /**
     * @return list<array{title: string, viz: string, sql: string, columns: list<string>, rows: list<array<string, mixed>>, row_count: int}|null>
     */
    protected function aiUsageSections(string $connection): array
    {
        if (! $this->hasTable($connection, 'ai_usage_logs')) {
            return [];
        }

        return [
            $this->run(
                $connection,
                'AI usage calls',
                'kpi',
                'SELECT COUNT(*) AS total FROM ai_usage_logs LIMIT 1',
            ),
        ];
    }

    /**
     * Small generic overview — users only (no login dump).
     *
     * @return list<array{title: string, viz: string, sql: string, columns: list<string>, rows: list<array<string, mixed>>, row_count: int}|null>
     */
    protected function genericOverview(string $connection): array
    {
        $sections = $this->userSections($connection);

        if ($sections === [] && $this->hasTable($connection, 'field_reports')) {
            $sections = $this->fieldReportSections($connection);
        }

        return $sections;
    }

    protected function connectionName(): string
    {
        $connection = config('ai.analytics.connection');

        return is_string($connection) && $connection !== ''
            ? $connection
            : (string) config('database.default');
    }

    /**
     * @return list<array{title: string, viz: string, sql: string, columns: list<string>, rows: list<array<string, mixed>>, row_count: int}|null>
     */
    protected function authenticationLogSections(string $connection): array
    {
        $cols = $this->columns($connection, 'authentication_logs');
        $hasEvent = in_array('event', $cols, true);
        $hasUserId = in_array('user_id', $cols, true);
        $dateCol = null;
        foreach (['logged_at', 'created_at', 'login_at'] as $candidate) {
            if (in_array($candidate, $cols, true)) {
                $dateCol = $candidate;
                break;
            }
        }

        $sections = [];

        if ($hasEvent) {
            $sections[] = $this->run(
                $connection,
                'Successful logins',
                'kpi',
                "SELECT COUNT(*) AS total FROM authentication_logs WHERE event = 'login_success' LIMIT 1",
            );
            $sections[] = $this->run(
                $connection,
                'Failed logins',
                'kpi',
                "SELECT COUNT(*) AS total FROM authentication_logs WHERE event = 'login_failed' LIMIT 1",
            );
        } else {
            $sections[] = $this->run(
                $connection,
                'Total auth events',
                'kpi',
                'SELECT COUNT(*) AS total FROM authentication_logs LIMIT 1',
            );
        }

        if ($hasUserId && $hasEvent) {
            $sections[] = $this->run(
                $connection,
                'Users who logged in',
                'kpi',
                "SELECT COUNT(DISTINCT user_id) AS total FROM authentication_logs WHERE event = 'login_success' AND user_id IS NOT NULL LIMIT 1",
            );
        }

        if ($hasEvent) {
            $sections[] = $this->run(
                $connection,
                'Auth events by type',
                'bar',
                'SELECT event AS label, COUNT(*) AS total FROM authentication_logs GROUP BY event ORDER BY total DESC LIMIT 20',
            );
        } elseif (in_array('success', $cols, true)) {
            $sections[] = $this->run(
                $connection,
                'Successful vs failed auth',
                'pie',
                'SELECT CASE WHEN success = 1 THEN \'Success\' ELSE \'Failed\' END AS label, COUNT(*) AS total FROM authentication_logs GROUP BY success LIMIT 100',
            );
        }

        if ($dateCol !== null && $hasEvent) {
            $driver = $this->driver($connection);
            if (in_array($driver, ['mysql', 'mariadb'], true)) {
                $sql = "SELECT DATE({$dateCol}) AS label, COUNT(*) AS total FROM authentication_logs WHERE event = 'login_success' AND {$dateCol} >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) GROUP BY DATE({$dateCol}) ORDER BY label ASC LIMIT 31";
            } else {
                $sql = "SELECT date({$dateCol}) AS label, COUNT(*) AS total FROM authentication_logs WHERE event = 'login_success' AND {$dateCol} >= date('now', '-30 days') GROUP BY label ORDER BY label ASC LIMIT 31";
            }
            $sections[] = $this->run(
                $connection,
                'Successful logins by day (30d)',
                'line',
                $sql,
            );
        } elseif ($dateCol !== null) {
            $sections[] = $this->run(
                $connection,
                'Logins by day (recent)',
                'line',
                $this->dailyBucketSql($connection, 'authentication_logs', $dateCol),
            );
        }

        return $sections;
    }

    /**
     * @return array{
     *     title: string,
     *     viz: string,
     *     sql: string,
     *     columns: list<string>,
     *     rows: list<array<string, mixed>>,
     *     row_count: int
     * }|null
     */
    protected function run(string $connection, string $title, string $viz, string $sql): ?array
    {
        try {
            $rows = array_map(fn ($row) => (array) $row, DB::connection($connection)->select($sql));
            $columns = $rows === [] ? [] : array_keys($rows[0]);

            return [
                'title' => $title,
                'viz' => $viz,
                'sql' => $sql,
                'columns' => $columns,
                'rows' => $rows,
                'row_count' => count($rows),
            ];
        } catch (Throwable) {
            return null;
        }
    }

    protected function hasTable(string $connection, string $table): bool
    {
        try {
            return Schema::connection($connection)->hasTable($table);
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * @return list<string>
     */
    protected function columns(string $connection, string $table): array
    {
        try {
            return Schema::connection($connection)->getColumnListing($table);
        } catch (Throwable) {
            return [];
        }
    }

    protected function monthlyBucketSql(string $connection, string $table, string $column): string
    {
        $driver = $this->driver($connection);

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            return "SELECT DATE_FORMAT({$column}, '%Y-%m') AS label, COUNT(*) AS total FROM {$table} GROUP BY DATE_FORMAT({$column}, '%Y-%m') ORDER BY label DESC LIMIT 12";
        }

        return "SELECT strftime('%Y-%m', {$column}) AS label, COUNT(*) AS total FROM {$table} GROUP BY label ORDER BY label DESC LIMIT 12";
    }

    protected function dailyBucketSql(string $connection, string $table, string $column): string
    {
        $driver = $this->driver($connection);

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            return "SELECT DATE({$column}) AS label, COUNT(*) AS total FROM {$table} WHERE {$column} >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) GROUP BY DATE({$column}) ORDER BY label ASC LIMIT 31";
        }

        return "SELECT date({$column}) AS label, COUNT(*) AS total FROM {$table} WHERE {$column} >= date('now', '-30 days') GROUP BY label ORDER BY label ASC LIMIT 31";
    }

    protected function driver(string $connection): string
    {
        try {
            return Schema::connection($connection)->getConnection()->getDriverName();
        } catch (Throwable) {
            return 'mysql';
        }
    }
}
