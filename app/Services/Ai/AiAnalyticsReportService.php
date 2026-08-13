<?php

namespace App\Services\Ai;

use App\Ai\Agents\AnalyticsInsightAgent;
use App\Ai\Agents\AnalyticsPlanAgent;
use App\Models\AiUsageLog;
use App\Models\User;
use App\Support\AiPricing;
use App\Support\AiSdkBridge;
use App\Support\AiSettings;
use Closure;
use InvalidArgumentException;
use Laravel\Ai\Responses\StructuredAgentResponse;
use RuntimeException;
use Throwable;

class AiAnalyticsReportService
{
    public function __construct(
        public AiAnalyticsService $analytics,
        public AnalyticsVisualizationBuilder $visualizations,
        public AnalyticsSafeFallback $fallback = new AnalyticsSafeFallback,
        public AnalyticsIntentResolver $intent = new AnalyticsIntentResolver,
        public AnalyticsAccess $access = new AnalyticsAccess,
    ) {}

    /**
     * @param  (Closure(array{id: string, label: string, status: string, detail?: string|null}): void)|null  $onProgress
     * @return array<string, mixed>
     */
    public function generate(
        User $user,
        string $prompt,
        ?Closure $onProgress = null,
        string $mode = 'efficient',
        array $history = [],
    ): array
    {
        $prompt = trim($prompt);
        $detailed = $mode === 'detailed';
        $history = $this->normalizeHistory($history);
        if ($prompt === '') {
            throw new InvalidArgumentException('Describe what you want to analyze.');
        }

        app(AiRiskGuard::class)->assertSafeUserText(
            $prompt,
            AiRiskGuard::SURFACE_ANALYTICS,
            (int) config('ai.limits.max_chat_message_chars', 4000),
        );

        if (! AiSettings::enabled()) {
            throw new RuntimeException('AI is disabled.');
        }

        if (! AiSettings::chatAllowAnalytics()) {
            throw new InvalidArgumentException('Analytics is disabled under Settings → AI → Tasks.');
        }

        if (! $user->can('ai.analytics') && ! $user->can('ai.chat')) {
            throw new InvalidArgumentException('You do not have permission to run analytics.');
        }

        $emit = function (string $id, string $label, string $status = 'running', ?string $detail = null) use ($onProgress): void {
            if ($onProgress === null) {
                return;
            }
            $onProgress([
                'id' => $id,
                'label' => $label,
                'status' => $status,
                'detail' => $detail,
            ]);
        };

        $started = hrtime(true);
        $resolved = null;

        try {
            $emit('planning', 'Understanding your question');

            $intent = $this->intent->resolve($prompt, $history);
            if ($intent['intent'] === 'chat') {
                $emit('planning', 'Understood', 'done', 'chat');
                $emit('complete', 'Ready', 'done');

                return [
                    'kind' => 'chat',
                    'message' => (string) ($intent['message'] ?? 'Hello! How can I help with your analytics?'),
                    'prompt' => $prompt,
                    'usage' => [
                        'prompt_tokens' => 0,
                        'completion_tokens' => 0,
                        'total_tokens' => 0,
                    ],
                    'model' => null,
                ];
            }

            $allowedTables = $this->access->allowedTables($user);
            if ($allowedTables === []) {
                $emit('planning', 'Understood', 'done', 'chat');
                $emit('complete', 'Ready', 'done');

                return [
                    'kind' => 'chat',
                    'message' => 'You do not have access to analyze any datasets yet. Ask an admin for the right permissions.',
                    'prompt' => $prompt,
                    'usage' => [
                        'prompt_tokens' => 0,
                        'completion_tokens' => 0,
                        'total_tokens' => 0,
                    ],
                    'model' => null,
                ];
            }

            $resolved = AiSdkBridge::applyDefaultModel();
            $promptTokens = 0;
            $completionTokens = 0;
            $historyContext = $this->historyContext($history);

            if (
                $this->fallback->isLoginPrompt($prompt)
                && $historyContext === ''
            ) {
                if (! $this->access->canAccessTable($user, 'authentication_logs')) {
                    $emit('planning', 'Understood', 'done', 'chat');
                    $emit('complete', 'Ready', 'done');

                    return [
                        'kind' => 'chat',
                        'message' => $this->access->publicAccessDeniedMessage(),
                        'prompt' => $prompt,
                        'usage' => [
                            'prompt_tokens' => 0,
                            'completion_tokens' => 0,
                            'total_tokens' => 0,
                        ],
                        'model' => null,
                    ];
                }

                $loginReport = $this->generateDeterministicLoginReport(
                    $user,
                    $prompt,
                    $resolved,
                    $detailed,
                    $started,
                    $emit,
                    $promptTokens,
                    $completionTokens,
                    $allowedTables,
                );
                $loginReport['kind'] = 'report';

                return $loginReport;
            }

            $deniedTables = config('ai.analytics.deny_tables', []);
            $deniedTables = is_array($deniedTables) ? $deniedTables : [];
            $schema = $this->analytics->schemaDigest($deniedTables, $allowedTables);

            $planResponse = (new AnalyticsPlanAgent($schema, $detailed, $historyContext))->prompt(
                app(AiRiskGuard::class)->fenceUserContent('analytics_request', $prompt),
                provider: $resolved['provider'],
                model: $resolved['model'],
            );
            $plan = $this->structured($planResponse);
            $promptTokens += (int) ($planResponse->usage->promptTokens ?? 0);
            $completionTokens += (int) ($planResponse->usage->completionTokens ?? 0);

            $title = trim((string) ($plan['title'] ?? 'Analytics report')) ?: 'Analytics report';
            $summary = trim((string) ($plan['summary'] ?? ''));
            $plannedQueries = is_array($plan['queries'] ?? null) ? $plan['queries'] : [];
            $plannedQueries = array_values(array_filter(
                $plannedQueries,
                fn ($q) => is_array($q) && trim((string) ($q['question'] ?? '')) !== '',
            ));
            $plannedQueries = array_slice($plannedQueries, 0, $detailed ? 3 : 1);

            if ($plannedQueries === []) {
                $emit('planning', 'Understanding your question', 'done');
            } else {
                $emit('planning', 'Understanding your question', 'done', $title);
                $emit('write', 'Writing safe queries…');
            }

            $kpis = [];
            $charts = [];
            $tables = [];
            $queryMeta = [];
            $insightSnippets = [];
            $totalQueries = count($plannedQueries);
            $skipped = 0;

            foreach ($plannedQueries as $index => $planned) {
                $id = trim((string) ($planned['id'] ?? '')) ?: 'q'.($index + 1);
                $question = trim((string) $planned['question']);
                $sectionTitle = trim((string) ($planned['title'] ?? $question)) ?: $question;
                $viz = trim((string) ($planned['viz'] ?? 'table'));
                $stepId = 'query-'.$id;

                if ($index === 0) {
                    $emit('write', 'Writing safe queries…', 'done');
                }

                $emit(
                    $stepId,
                    'Searching your data…',
                    'running',
                    $sectionTitle,
                );

                try {
                    $result = $this->analytics->run($user, $question);
                } catch (Throwable $queryError) {
                    $skipped++;
                    $emit(
                        $stepId,
                        'Skipped one search',
                        'done',
                        mb_substr($queryError->getMessage(), 0, 120),
                    );
                    report($queryError);
                    continue;
                }

                $section = $this->visualizations->build(
                    $sectionTitle,
                    $viz,
                    $result['columns'],
                    $result['rows'],
                );

                $queryMeta[] = [
                    'id' => $id,
                    'title' => $sectionTitle,
                    'question' => $question,
                    'sql' => $result['sql'],
                    'row_count' => $result['row_count'],
                ];

                $insightSnippets[] = $this->snippet($sectionTitle, $result['columns'], $result['rows']);

                if ($section['type'] === 'kpi' && isset($section['kpi'])) {
                    $kpis[] = [
                        'id' => $id,
                        'label' => (string) $section['kpi']['label'],
                        'value' => (string) $section['kpi']['value'],
                        'hint' => $section['kpi']['hint'] !== null && $section['kpi']['hint'] !== ''
                            ? (string) $section['kpi']['hint']
                            : null,
                    ];
                } elseif ($section['type'] === 'chart') {
                    $charts[] = [
                        'id' => $id,
                        'title' => (string) $section['title'],
                        'chart_type' => (string) ($section['chart_type'] ?? 'bar'),
                        'labels' => $section['labels'] ?? [],
                        'datasets' => $section['datasets'] ?? [],
                    ];
                } else {
                    $tables[] = [
                        'id' => $id,
                        'title' => (string) $section['title'],
                        'columns' => $section['columns'] ?? [],
                        'rows' => $section['rows'] ?? [],
                        'truncated' => (bool) ($section['truncated'] ?? false),
                        'total_rows' => (int) ($section['total_rows'] ?? count($section['rows'] ?? [])),
                    ];
                }

                $emit(
                    $stepId,
                    'Found matching results',
                    'done',
                    $result['row_count'].' rows · '.$sectionTitle,
                );
            }

            if ($kpis === [] && $charts === [] && $tables === []) {
                $emit('fallback', 'Building a safe overview…', 'running');
                $this->applyFallbackSections(
                    $kpis,
                    $charts,
                    $tables,
                    $queryMeta,
                    $insightSnippets,
                    $prompt,
                    $allowedTables,
                );
                $emit('fallback', 'Safe overview ready', 'done');

                if ($kpis === [] && $charts === [] && $tables === []) {
                    throw new RuntimeException(
                        $skipped > 0
                            ? 'Could not complete any analytics queries for data you can access.'
                            : 'No analytics results were produced for data you can access.'
                    );
                }

                if ($title === '' || $title === 'Analytics report') {
                    $title = 'Data overview';
                }
                if ($summary === '') {
                    $summary = 'Some queries failed, so Kit built a safe overview from data you can access.';
                }
            }

            if ($detailed) {
                $emit('insights', 'Writing insights…');
                $insights = $this->insights(
                    $user,
                    $resolved,
                    $prompt,
                    $title,
                    $insightSnippets,
                    $promptTokens,
                    $completionTokens,
                    true,
                );
                $emit('insights', 'Finishing your report', 'done');
            } else {
                $insights = [];
                $emit('insights', 'Finishing your report', 'done');
            }

            $this->logSuccess($user, $prompt, $resolved, $started, $promptTokens, $completionTokens, $title);

            $report = [
                'kind' => 'report',
                'title' => $title,
                'summary' => $summary,
                'prompt' => $prompt,
                'kpis' => $kpis,
                'charts' => $charts,
                'tables' => $tables,
                'insights' => $insights,
                'queries' => $queryMeta,
                'usage' => [
                    'prompt_tokens' => $promptTokens,
                    'completion_tokens' => $completionTokens,
                    'total_tokens' => $promptTokens + $completionTokens,
                ],
                'model' => $resolved['model'],
            ];

            $emit('complete', 'Report ready', 'done');

            return $report;
        } catch (Throwable $e) {
            if (is_array($resolved)) {
                $this->logFailure($user, $prompt, $resolved, $started, $e);
            }
            throw $e;
        }
    }

    /**
     * Fixed login report from authentication_logs — no planner/SQL author (same every time).
     *
     * @param  array{provider: string, model: string, model_id: string}  $resolved
     * @param  Closure(string, string, string, string|null): void  $emit
     * @return array<string, mixed>
     */
    protected function generateDeterministicLoginReport(
        User $user,
        string $prompt,
        array $resolved,
        bool $detailed,
        int $started,
        Closure $emit,
        int &$promptTokens,
        int &$completionTokens,
        array $allowedTables = [],
    ): array {
        $title = 'Login activity';
        $summary = 'Login activity from data you can access.';

        $emit('planning', 'Understanding your question', 'done', $title);
        $emit('write', 'Using fixed login queries', 'done');
        $emit('search', 'Searching login data…', 'running');

        $kpis = [];
        $charts = [];
        $tables = [];
        $queryMeta = [];
        $insightSnippets = [];

        $this->applyFallbackSections(
            $kpis,
            $charts,
            $tables,
            $queryMeta,
            $insightSnippets,
            $prompt,
            $allowedTables,
        );

        $emit('search', 'Login data ready', 'done', count($queryMeta).' queries');

        if ($kpis === [] && $charts === [] && $tables === []) {
            throw new RuntimeException($this->access->publicAccessDeniedMessage());
        }

        if ($detailed) {
            $emit('insights', 'Writing insights…');
            $insights = $this->insights(
                $user,
                $resolved,
                $prompt,
                $title,
                $insightSnippets,
                $promptTokens,
                $completionTokens,
                true,
            );
            $emit('insights', 'Finishing your report', 'done');
        } else {
            $insights = [];
            $emit('insights', 'Finishing your report', 'done');
        }

        $this->logSuccess($user, $prompt, $resolved, $started, $promptTokens, $completionTokens, $title);

        $report = [
            'kind' => 'report',
            'title' => $title,
            'summary' => $summary,
            'prompt' => $prompt,
            'kpis' => $kpis,
            'charts' => $charts,
            'tables' => $tables,
            'insights' => $insights,
            'queries' => $queryMeta,
            'usage' => [
                'prompt_tokens' => $promptTokens,
                'completion_tokens' => $completionTokens,
                'total_tokens' => $promptTokens + $completionTokens,
            ],
            'model' => $resolved['model'],
        ];

        $emit('complete', 'Report ready', 'done');

        return $report;
    }

    /**
     * @param  list<array{role?: mixed, content?: mixed}>  $history
     * @return list<array{role: string, content: string}>
     */
    protected function normalizeHistory(array $history): array
    {
        $out = [];
        foreach (array_slice($history, -12) as $turn) {
            if (! is_array($turn)) {
                continue;
            }
            $role = (string) ($turn['role'] ?? '');
            $content = trim((string) ($turn['content'] ?? ''));
            if (! in_array($role, ['user', 'assistant'], true) || $content === '') {
                continue;
            }
            $out[] = [
                'role' => $role,
                'content' => mb_substr($content, 0, 1200),
            ];
        }

        return $out;
    }

    /**
     * @param  list<array{role: string, content: string}>  $history
     */
    protected function historyContext(array $history): string
    {
        if ($history === []) {
            return '';
        }

        $lines = [];
        foreach ($history as $turn) {
            $lines[] = $turn['role'].': '.$turn['content'];
        }

        return implode("\n", $lines);
    }

    /**
     * @param  array{provider: string, model: string, model_id: string}  $resolved
     * @param  list<string>  $snippets
     * @return list<array{title: string, body: string, tone: string}>
     */
    protected function insights(
        User $user,
        array $resolved,
        string $prompt,
        string $title,
        array $snippets,
        int &$promptTokens,
        int &$completionTokens,
        bool $detailed = false,
    ): array {
        if ($snippets === []) {
            return [];
        }

        $guard = app(AiRiskGuard::class);
        $payload = "Dashboard: {$title}\n"
            .$guard->fenceUserContent('user_request', $prompt)
            ."\n\nResults:\n".implode("\n---\n", $snippets);

        try {
            $response = (new AnalyticsInsightAgent($detailed))->prompt(
                $payload,
                provider: $resolved['provider'],
                model: $resolved['model'],
            );
            $data = $this->structured($response);
            $promptTokens += (int) ($response->usage->promptTokens ?? 0);
            $completionTokens += (int) ($response->usage->completionTokens ?? 0);

            $insights = is_array($data['insights'] ?? null) ? $data['insights'] : [];
            $out = [];
            foreach ($insights as $insight) {
                if (! is_array($insight)) {
                    continue;
                }
                $insightTitle = trim((string) ($insight['title'] ?? ''));
                $body = trim((string) ($insight['body'] ?? ''));
                if ($insightTitle === '' || $body === '') {
                    continue;
                }
                $tone = (string) ($insight['tone'] ?? 'info');
                if (! in_array($tone, ['info', 'positive', 'warning'], true)) {
                    $tone = 'info';
                }
                $out[] = [
                    'title' => $insightTitle,
                    'body' => $body,
                    'tone' => $tone,
                ];
            }

            return $out;
        } catch (Throwable $e) {
            report($e);

            return [[
                'title' => 'Results ready',
                'body' => 'Queries ran successfully. Review the KPIs, charts, and tables below.',
                'tone' => 'info',
            ]];
        }
    }

    /**
     * @param  list<array{id: string, label: string, value: string, hint: string|null}>  $kpis
     * @param  list<array{id: string, title: string, chart_type: string, labels: list<string>, datasets: list<array{label: string, data: list<float|int>}}}>  $charts
     * @param  list<array{id: string, title: string, columns: list<string>, rows: list<array<string, mixed>>, truncated: bool, total_rows: int}>  $tables
     * @param  list<array{id: string, title: string, question: string, sql: string, row_count: int}>  $queryMeta
     * @param  list<string>  $insightSnippets
     * @param  list<string>|null  $allowedTables
     */
    protected function applyFallbackSections(
        array &$kpis,
        array &$charts,
        array &$tables,
        array &$queryMeta,
        array &$insightSnippets,
        ?string $prompt = null,
        ?array $allowedTables = null,
    ): void {
        foreach ($this->fallback->build($prompt, $allowedTables) as $index => $item) {
            $id = 'safe-'.($index + 1);
            $section = $this->visualizations->build(
                $item['title'],
                $item['viz'],
                $item['columns'],
                $item['rows'],
            );

            $queryMeta[] = [
                'id' => $id,
                'title' => $item['title'],
                'question' => 'Safe fallback query',
                'sql' => $item['sql'],
                'row_count' => $item['row_count'],
            ];
            $insightSnippets[] = $this->snippet($item['title'], $item['columns'], $item['rows']);

            if ($section['type'] === 'kpi' && isset($section['kpi'])) {
                $kpis[] = [
                    'id' => $id,
                    'label' => (string) $section['kpi']['label'],
                    'value' => (string) $section['kpi']['value'],
                    'hint' => $section['kpi']['hint'] !== null && $section['kpi']['hint'] !== ''
                        ? (string) $section['kpi']['hint']
                        : null,
                ];
            } elseif ($section['type'] === 'chart') {
                $charts[] = [
                    'id' => $id,
                    'title' => (string) $section['title'],
                    'chart_type' => (string) ($section['chart_type'] ?? 'bar'),
                    'labels' => $section['labels'] ?? [],
                    'datasets' => $section['datasets'] ?? [],
                ];
            } else {
                $tables[] = [
                    'id' => $id,
                    'title' => (string) $section['title'],
                    'columns' => $section['columns'] ?? [],
                    'rows' => $section['rows'] ?? [],
                    'truncated' => (bool) ($section['truncated'] ?? false),
                    'total_rows' => (int) ($section['total_rows'] ?? count($section['rows'] ?? [])),
                ];
            }
        }
    }

    /**
     * @param  list<string>  $columns
     * @param  list<array<string, mixed>>  $rows
     */
    protected function snippet(string $title, array $columns, array $rows): string
    {
        $lines = ["## {$title}", 'columns: '.implode(', ', $columns), 'rows: '.count($rows)];
        foreach (array_slice($rows, 0, 8) as $row) {
            $cells = [];
            foreach ($columns as $col) {
                $value = $row[$col] ?? '';
                if (is_bool($value)) {
                    $value = $value ? 'true' : 'false';
                }
                if (is_array($value)) {
                    $value = json_encode($value);
                }
                $cells[] = $col.'='.mb_substr((string) $value, 0, 60);
            }
            $lines[] = '- '.implode('; ', $cells);
        }

        return implode("\n", $lines);
    }

    /**
     * @return array<string, mixed>
     */
    protected function structured(mixed $response): array
    {
        if ($response instanceof StructuredAgentResponse) {
            return $response->toArray();
        }

        if (is_array($response)) {
            return $response;
        }

        $text = trim((string) ($response->text ?? ''));
        if ($text !== '' && str_starts_with($text, '{')) {
            $decoded = json_decode($text, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        throw new RuntimeException('Analytics planner returned an unexpected response.');
    }

    /**
     * @param  array{provider: string, model: string, model_id: string}  $resolved
     */
    protected function logSuccess(
        User $user,
        string $prompt,
        array $resolved,
        int $started,
        int $promptTokens,
        int $completionTokens,
        string $title,
    ): void {
        $durationMs = (int) ((hrtime(true) - $started) / 1_000_000);
        $total = $promptTokens + $completionTokens;

        AiUsageLog::query()->create([
            'user_id' => $user->id,
            'action' => 'analytics_report',
            'status' => AiUsageLog::STATUS_SUCCESS,
            'provider' => $resolved['provider'],
            'model_id' => $resolved['model_id'] ?? null,
            'model_name' => $resolved['model'],
            'prompt_tokens' => $promptTokens,
            'completion_tokens' => $completionTokens,
            'total_tokens' => $total,
            'estimated_cost_usd' => AiPricing::estimateUsd($resolved['provider'], $resolved['model'], $promptTokens, $completionTokens),
            'duration_ms' => $durationMs,
            'instruction_preview' => mb_substr($prompt, 0, (int) config('ai.limits.log_preview_chars', 240)),
            'output_preview' => mb_substr($title, 0, (int) config('ai.limits.log_preview_chars', 240)),
            'logged_at' => now(),
        ]);
    }

    /**
     * @param  array{provider: string, model: string, model_id: string}  $resolved
     */
    protected function logFailure(
        User $user,
        string $prompt,
        array $resolved,
        int $started,
        Throwable $e,
    ): void {
        $durationMs = (int) ((hrtime(true) - $started) / 1_000_000);

        try {
            AiUsageLog::query()->create([
                'user_id' => $user->id,
                'action' => 'analytics_report',
                'status' => AiUsageLog::STATUS_FAILED,
                'provider' => $resolved['provider'],
                'model_id' => $resolved['model_id'] ?? null,
                'model_name' => $resolved['model'],
                'prompt_tokens' => 0,
                'completion_tokens' => 0,
                'total_tokens' => 0,
                'estimated_cost_usd' => 0,
                'duration_ms' => $durationMs,
                'instruction_preview' => mb_substr($prompt, 0, (int) config('ai.limits.log_preview_chars', 240)),
                'error' => mb_substr($e->getMessage(), 0, 500),
                'logged_at' => now(),
            ]);
        } catch (Throwable) {
            // Logging must not mask the original failure.
        }
    }
}
