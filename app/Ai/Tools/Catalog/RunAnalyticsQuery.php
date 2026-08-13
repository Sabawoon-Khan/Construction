<?php

namespace App\Ai\Tools\Catalog;

use App\Models\User;
use App\Services\Ai\AiAnalyticsService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Auth;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;
use Throwable;

class RunAnalyticsQuery implements Tool
{
    public function __construct(
        public ?User $user = null,
        public ?AiAnalyticsService $analytics = null,
    ) {}

    public function description(): Stringable|string
    {
        return 'Run a read-only analytics query. Pass a natural-language question and/or explicit SELECT SQL. Returns SQL used plus a markdown table of results. Never writes to the database.';
    }

    public function handle(Request $request): Stringable|string
    {
        $user = $this->user ?? Auth::user();
        if (! $user instanceof User) {
            return 'Error: Not authenticated.';
        }

        $question = trim((string) ($request['question'] ?? ''));
        $sql = isset($request['sql']) ? trim((string) $request['sql']) : null;
        if ($sql === '') {
            $sql = null;
        }

        try {
            $result = ($this->analytics ?? app(AiAnalyticsService::class))->run(
                $user,
                $question,
                $sql,
            );

            return $result['markdown'];
        } catch (Throwable $e) {
            $message = $e->getMessage();
            if (
                str_contains(strtolower($message), 'access')
                || str_contains(strtolower($message), 'permission')
            ) {
                return 'Error: You do not have access to analyze that data.';
            }

            return 'Error: Analytics query failed for data you can access.';
        }
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'question' => $schema->string()->max(2000),
            'sql' => $schema->string()->max(4000),
        ];
    }
}
