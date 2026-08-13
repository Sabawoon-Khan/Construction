<?php

namespace App\Services\Ai;

use App\Ai\Agents\KitAgent;
use App\Models\AiConversationMessage;
use App\Models\AiUsageLog;
use App\Models\User;
use App\Support\AiChatActivity;
use App\Support\AiPricing;
use App\Support\AiSdkBridge;
use App\Support\AiSettings;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Laravel\Ai\Approvals\Decision;
use Laravel\Ai\Approvals\Decisions;
use Laravel\Ai\Models\Conversation;
use Laravel\Ai\Models\ConversationMessage;
use Laravel\Ai\Responses\AgentResponse;
use RuntimeException;
use Throwable;

class AiChatService
{
    /**
     * @return list<array{id: string, title: string, updated_at: string|null, preview: string|null}>
     */
    public function conversations(User $user): array
    {
        $this->assertChatAllowed($user);

        return $user->conversations()
            ->orderByDesc('updated_at')
            ->limit(40)
            ->get()
            ->map(function (Conversation $conversation): array {
                $preview = ConversationMessage::query()
                    ->where('conversation_id', $conversation->id)
                    ->where('role', 'user')
                    ->orderByDesc('created_at')
                    ->value('content');

                return [
                    'id' => (string) $conversation->id,
                    'title' => filled($conversation->title)
                        ? (string) $conversation->title
                        : 'Chat',
                    'updated_at' => optional($conversation->updated_at)?->toIso8601String(),
                    'preview' => filled($preview) ? mb_substr((string) $preview, 0, 120) : null,
                ];
            })
            ->all();
    }

    /**
     * @return array{
     *     conversation_id: string,
     *     title: string,
     *     messages: list<array{id: string, role: string, content: string, pending_approvals?: list<array{id: string, tool: string, arguments: array<string, mixed>, reason: string|null}>}>
     * }
     */
    public function conversation(User $user, string $conversationId): array
    {
        $this->assertChatAllowed($user);

        $conversation = $user->conversations()->whereKey($conversationId)->first();

        if ($conversation === null) {
            throw new RuntimeException('Conversation not found.');
        }

        $records = ConversationMessage::query()
            ->where('conversation_id', $conversation->id)
            ->orderBy('created_at')
            ->get();

        $messages = [];

        foreach ($records as $record) {
            $role = (string) $record->role;

            if (! in_array($role, ['user', 'assistant'], true)) {
                continue;
            }

            $content = trim((string) $record->content);
            $pending = [];

            if ($role === 'assistant') {
                $state = is_array($record->approval_state) ? $record->approval_state : [];
                $pendingMap = is_array($state['pending'] ?? null) ? $state['pending'] : [];
                $toolCalls = is_array($record->tool_calls) ? $record->tool_calls : [];

                foreach ($toolCalls as $toolCall) {
                    if (! is_array($toolCall) || ! isset($toolCall['id'], $toolCall['name'])) {
                        continue;
                    }

                    $id = (string) $toolCall['id'];
                    if (! array_key_exists($id, $pendingMap)) {
                        continue;
                    }

                    $pending[] = [
                        'id' => $id,
                        'tool' => (string) $toolCall['name'],
                        'arguments' => is_array($toolCall['arguments'] ?? null) ? $toolCall['arguments'] : [],
                        'reason' => is_string($pendingMap[$id] ?? null) ? $pendingMap[$id] : null,
                    ];
                }
            }

            if ($content === '' && $pending === []) {
                continue;
            }

            $toolCalls = is_array($record->tool_calls) ? $record->tool_calls : [];
            $toolResults = is_array($record->tool_results) ? $record->tool_results : [];
            $steps = $role === 'assistant'
                ? AiChatActivity::stepsFromStored($toolCalls, $toolResults, $pending)
                : [];

            $linkBag = [];
            if ($role === 'assistant') {
                foreach (AiChatActivity::extractMarkdownLinks($content) as $link) {
                    $linkBag[] = $link;
                }
                foreach ($toolResults as $row) {
                    if (! is_array($row) || ! is_string($row['result'] ?? null)) {
                        continue;
                    }
                    foreach (AiChatActivity::extractMarkdownLinks($row['result']) as $link) {
                        $linkBag[] = $link;
                    }
                }
            }
            $links = AiChatActivity::uniqueLinks($linkBag);

            $messages[] = [
                'id' => (string) $record->id,
                'role' => $role,
                'content' => $role === 'assistant' ? AiChatActivity::sanitizeText($content) : $content,
                ...(count($pending) > 0 ? ['pending_approvals' => $pending] : []),
                ...(count($steps) > 0 ? ['steps' => $steps] : []),
                ...(count($links) > 0 ? ['links' => $links] : []),
            ];
        }

        return [
            'conversation_id' => (string) $conversation->id,
            'title' => filled($conversation->title) ? (string) $conversation->title : 'Chat',
            'messages' => $messages,
        ];
    }

    public function deleteConversation(User $user, string $conversationId): void
    {
        $this->assertChatAllowed($user);

        $conversation = $user->conversations()->whereKey($conversationId)->first();

        if ($conversation === null) {
            throw new RuntimeException('Conversation not found.');
        }

        AiConversationMessage::query()->where('conversation_id', $conversation->id)->each(function (AiConversationMessage $message): void {
            $message->delete();
        });
        $conversation->delete();
    }

    /**
     * @param  list<UploadedFile>  $uploads
     * @return array{
     *     conversation_id: string|null,
     *     text: string,
     *     pending_approvals: list<array{id: string, tool: string, arguments: array<string, mixed>, reason: string|null}>,
     *     steps: list<array{type: string, tool: string, label: string, summary: string, status: string, detail: string|null}>,
     *     links: list<array{label: string, href: string}>,
     *     usage: array{prompt_tokens: int, completion_tokens: int, total_tokens: int},
     *     model: string|null
     * }
     */
    public function chat(
        User $user,
        string $message,
        ?string $conversationId = null,
        array $uploads = [],
    ): array {
        $this->assertChatAllowed($user);

        try {
            app(AiRiskGuard::class)->assertSafeChatMessage($message);
        } catch (InvalidArgumentException $e) {
            $this->logBlocked($user, $message, $e->getMessage());

            throw new RuntimeException($e->getMessage(), previous: $e);
        }

        $attachments = [];
        $attachmentMeta = [];

        if ($uploads !== []) {
            try {
                $stored = app(AiChatAttachmentService::class)->storeForUser($user->id, $uploads);
            } catch (InvalidArgumentException $e) {
                throw new RuntimeException($e->getMessage(), previous: $e);
            }

            foreach ($stored as $row) {
                $attachments[] = $row['attachment'];
                $attachmentMeta[] = [
                    'name' => $row['name'],
                    'mime' => $row['mime'],
                    'size' => $row['size'],
                ];
            }
        }

        $candidates = AiSdkBridge::candidateModels();
        $started = hrtime(true);
        /** @var list<Throwable> $failures */
        $failures = [];

        foreach ($candidates as $index => $model) {
            $resolved = AiSdkBridge::applyModel($model);
            $agent = KitAgent::make(user: $user);

            try {
                $this->enrichLangfuseTrace($user, $conversationId);

                $promptable = $conversationId
                    ? $agent->continue($conversationId, as: $user)
                    : $agent->forUser($user);

                $response = $promptable->prompt(
                    $message,
                    $attachments,
                    provider: $resolved['provider'],
                    model: $resolved['model'],
                );

                $this->logSuccess($user, $message, $resolved, $started, $response);

                $payload = $this->formatResponse($response);
                if ($attachmentMeta !== []) {
                    $payload['attachments'] = $attachmentMeta;
                }

                app(LangfuseObservability::class)->flush();

                return $payload;
            } catch (Throwable $e) {
                $failures[] = $e;
                $this->logFailure($user, $message, $resolved, $started, $e);
                app(LangfuseObservability::class)->flush();

                if ($index >= count($candidates) - 1) {
                    break;
                }

                Log::info('AI chat primary model failed; trying failover', [
                    'failed_model' => $model['id'],
                    'provider' => $model['provider'],
                    'error' => $e->getMessage(),
                ]);
            }
        }

        throw new RuntimeException(
            AiSdkBridge::userMessageFromFailures($failures),
            previous: $failures === [] ? null : $failures[array_key_last($failures)],
        );
    }

    /**
     * @param  array<string, bool|string>  $decisions  Map of tool call id => true/false or 'approve'/'reject'
     * @return array{
     *     conversation_id: string|null,
     *     text: string,
     *     pending_approvals: list<array{id: string, tool: string, arguments: array<string, mixed>, reason: string|null}>,
     *     usage: array{prompt_tokens: int, completion_tokens: int, total_tokens: int},
     *     model: string|null
     * }
     */
    public function approve(User $user, string $conversationId, array $decisions): array
    {
        $this->assertChatAllowed($user);

        if ($conversationId === '') {
            throw new RuntimeException('conversation_id is required to approve tool calls.');
        }

        $normalized = [];
        foreach ($decisions as $id => $value) {
            $normalized[(string) $id] = match (true) {
                $value === true, $value === 'approve', $value === 'approved' => Decision::approve(),
                $value === false, $value === 'reject', $value === 'rejected' => Decision::reject('Rejected by user.'),
                default => Decision::reject('Rejected by user.'),
            };
        }

        $candidates = AiSdkBridge::candidateModels();
        $started = hrtime(true);
        /** @var list<Throwable> $failures */
        $failures = [];

        foreach ($candidates as $index => $model) {
            $resolved = AiSdkBridge::applyModel($model);
            $agent = KitAgent::make(user: $user);

            try {
                $this->enrichLangfuseTrace($user, $conversationId);

                $response = $agent
                    ->continue($conversationId, as: $user)
                    ->prompt(
                        Decisions::from($normalized)->rejectRemaining('Not approved.'),
                        provider: $resolved['provider'],
                        model: $resolved['model'],
                    );

                $this->logSuccess($user, '[tool approval]', $resolved, $started, $response);

                app(LangfuseObservability::class)->flush();

                return $this->formatResponse($response);
            } catch (Throwable $e) {
                $failures[] = $e;
                $this->logFailure($user, '[tool approval]', $resolved, $started, $e);
                app(LangfuseObservability::class)->flush();

                if ($index >= count($candidates) - 1) {
                    break;
                }

                Log::info('AI chat approval primary model failed; trying failover', [
                    'failed_model' => $model['id'],
                    'provider' => $model['provider'],
                    'error' => $e->getMessage(),
                ]);
            }
        }

        throw new RuntimeException(
            AiSdkBridge::userMessageFromFailures($failures !== [] ? $failures : [new RuntimeException('AI chat approval failed.')]),
            previous: $failures === [] ? null : $failures[array_key_last($failures)],
        );
    }

    protected function assertChatAllowed(User $user): void
    {
        if (! AiSettings::enabled()) {
            throw new RuntimeException('AI is disabled.');
        }

        if (! AiSettings::chatEnabled()) {
            throw new RuntimeException('General Chat is disabled.');
        }

        if (! $user->can('ai.chat')) {
            throw new RuntimeException('You do not have permission to use General Chat.');
        }

        if (AiSettings::defaultModel() === null || ! AiSettings::defaultModel()['api_key_set']) {
            throw new RuntimeException('No AI model with an API key is configured.');
        }
    }

    /**
     * @return array{
     *     conversation_id: string|null,
     *     text: string,
     *     pending_approvals: list<array{id: string, tool: string, arguments: array<string, mixed>, reason: string|null}>,
     *     steps: list<array{type: string, tool: string, label: string, summary: string, status: string, detail: string|null}>,
     *     links: list<array{label: string, href: string}>,
     *     usage: array{prompt_tokens: int, completion_tokens: int, total_tokens: int},
     *     model: string|null
     * }
     */
    protected function formatResponse(AgentResponse $response): array
    {
        $pending = [];
        foreach ($response->pendingApprovals ?? [] as $approval) {
            $pending[] = $approval->toArray();
        }

        $text = AiChatActivity::sanitizeText((string) $response->text);
        $steps = AiChatActivity::stepsFromResponse($response);
        $links = AiChatActivity::linksFromResponse($response);

        return [
            'conversation_id' => $response->conversationId,
            'text' => $text,
            'pending_approvals' => $pending,
            'steps' => $steps,
            'links' => $links,
            'usage' => [
                'prompt_tokens' => (int) ($response->usage->promptTokens ?? 0),
                'completion_tokens' => (int) ($response->usage->completionTokens ?? 0),
                'total_tokens' => (int) (($response->usage->promptTokens ?? 0) + ($response->usage->completionTokens ?? 0)),
            ],
            'model' => $response->meta->model ?? null,
        ];
    }

    /**
     * @param  array{provider: string, model: string, model_id?: string}  $resolved
     */
    protected function logSuccess(
        User $user,
        string $message,
        array $resolved,
        int $started,
        AgentResponse $response,
    ): void {
        $durationMs = (int) ((hrtime(true) - $started) / 1_000_000);
        $prompt = (int) ($response->usage->promptTokens ?? 0);
        $completion = (int) ($response->usage->completionTokens ?? 0);
        $total = $prompt + $completion;

        AiUsageLog::query()->create([
            'user_id' => $user->id,
            'action' => 'chat',
            'status' => AiUsageLog::STATUS_SUCCESS,
            'provider' => $resolved['provider'],
            'model_id' => $resolved['model_id'] ?? AiSettings::defaultModel()['id'] ?? null,
            'model_name' => $resolved['model'],
            'prompt_tokens' => $prompt,
            'completion_tokens' => $completion,
            'total_tokens' => $total,
            'estimated_cost_usd' => AiPricing::estimateUsd($resolved['provider'], $resolved['model'], $prompt, $completion),
            'duration_ms' => $durationMs,
            'instruction_preview' => mb_substr($message, 0, (int) config('ai.limits.log_preview_chars', 240)),
            'input_preview' => null,
            'output_preview' => mb_substr((string) $response->text, 0, (int) config('ai.limits.log_preview_chars', 240)),
            'logged_at' => now(),
        ]);
    }

    /**
     * @param  array{provider: string, model: string, model_id?: string}  $resolved
     */
    protected function logFailure(
        User $user,
        string $message,
        array $resolved,
        int $started,
        Throwable $e,
    ): void {
        $durationMs = (int) ((hrtime(true) - $started) / 1_000_000);

        AiUsageLog::query()->create([
            'user_id' => $user->id,
            'action' => 'chat',
            'status' => AiUsageLog::STATUS_FAILED,
            'provider' => $resolved['provider'],
            'model_id' => $resolved['model_id'] ?? AiSettings::defaultModel()['id'] ?? null,
            'model_name' => $resolved['model'],
            'prompt_tokens' => 0,
            'completion_tokens' => 0,
            'total_tokens' => 0,
            'estimated_cost_usd' => 0,
            'duration_ms' => $durationMs,
            'instruction_preview' => mb_substr($message, 0, (int) config('ai.limits.log_preview_chars', 240)),
            'error' => mb_substr($e->getMessage(), 0, 500),
            'logged_at' => now(),
        ]);
    }

    protected function logBlocked(User $user, string $message, string $reason): void
    {
        $default = AiSettings::defaultModel();

        AiUsageLog::query()->create([
            'user_id' => $user->id,
            'action' => 'chat_blocked',
            'status' => AiUsageLog::STATUS_FAILED,
            'provider' => $default['provider'] ?? null,
            'model_id' => $default['id'] ?? null,
            'model_name' => $default['model'] ?? null,
            'prompt_tokens' => 0,
            'completion_tokens' => 0,
            'total_tokens' => 0,
            'estimated_cost_usd' => 0,
            'duration_ms' => 0,
            'instruction_preview' => mb_substr($message, 0, (int) config('ai.limits.log_preview_chars', 240)),
            'error' => mb_substr($reason, 0, 500),
            'logged_at' => now(),
        ]);
    }

    protected function enrichLangfuseTrace(User $user, ?string $conversationId): void
    {
        app(LangfuseObservability::class)->startChatTrace(
            userId: (string) $user->id,
            conversationId: $conversationId,
        );
    }
}
