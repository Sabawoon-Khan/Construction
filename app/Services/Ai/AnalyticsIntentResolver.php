<?php

namespace App\Services\Ai;

/**
 * Cheap intent gate (no API): chat vs analytics before spending tokens.
 */
class AnalyticsIntentResolver
{
    /**
     * @param  list<array{role?: string, content?: string}>  $history
     * @return array{intent: 'chat'|'analytics', message: string|null}
     */
    public function resolve(string $prompt, array $history = []): array
    {
        $raw = trim($prompt);
        $text = mb_strtolower($raw);
        $text = preg_replace('/[^\p{L}\p{N}\s\?\'\-]/u', ' ', $text) ?? $text;
        $text = trim(preg_replace('/\s+/u', ' ', $text) ?? $text);

        if ($text === '') {
            return [
                'intent' => 'chat',
                'message' => 'Hi — ask me something about your data, like user totals, login trends, or field report status.',
            ];
        }

        if ($this->looksLikeAnalytics($text) || $this->isAnalyticsFollowUp($text, $history)) {
            return ['intent' => 'analytics', 'message' => null];
        }

        if ($this->looksLikeChat($text)) {
            return [
                'intent' => 'chat',
                'message' => $this->chatReply($text),
            ];
        }

        // Ambiguous short messages → chat, don't invent a dashboard.
        if (str_word_count($text) <= 3 && ! $this->hasAnalyticsSignal($text)) {
            return [
                'intent' => 'chat',
                'message' => $this->chatReply($text),
            ];
        }

        return ['intent' => 'analytics', 'message' => null];
    }

    /**
     * @param  list<array{role?: string, content?: string}>  $history
     */
    protected function isAnalyticsFollowUp(string $text, array $history): bool
    {
        if ($history === []) {
            return false;
        }

        $hadAnalytics = false;
        foreach ($history as $turn) {
            $content = mb_strtolower((string) ($turn['content'] ?? ''));
            if (($turn['role'] ?? '') === 'assistant' && (
                str_contains($content, '[report]')
                || str_contains($content, 'kpi')
                || str_contains($content, 'chart')
                || str_contains($content, 'users')
                || str_contains($content, 'login')
            )) {
                $hadAnalytics = true;
                break;
            }
        }

        if (! $hadAnalytics) {
            return false;
        }

        $followUps = [
            'more', 'also', 'and', 'same', 'that', 'those', 'this', 'again',
            'pie', 'bar', 'line', 'chart', 'table', 'breakdown', 'compare',
            'filter', 'only', 'instead', 'what about', 'how about', 'show',
            'now', 'last', 'previous', 'detail', 'details', 'why', 'which',
        ];

        foreach ($followUps as $word) {
            if (str_contains($text, $word)) {
                return true;
            }
        }

        return str_word_count($text) <= 8;
    }

    protected function looksLikeAnalytics(string $text): bool
    {
        return $this->hasAnalyticsSignal($text);
    }

    protected function hasAnalyticsSignal(string $text): bool
    {
        $signals = [
            'user', 'users', 'people', 'account', 'accounts', 'role', 'roles',
            'login', 'logins', 'logged', 'auth', 'authentication', 'signin', 'sign-in',
            'report', 'reports', 'field', 'status', 'active', 'inactive',
            'chart', 'charts', 'kpi', 'metric', 'metrics', 'trend', 'trends',
            'count', 'counts', 'total', 'totals', 'overview', 'summary', 'analytics',
            'usage', 'token', 'tokens', 'cost', 'month', 'weekly', 'daily', 'year',
            'how many', 'breakdown', 'compare', 'top', 'growth', 'activity',
            'table', 'data', 'database', 'show me', 'give me', 'analyze', 'analysis',
        ];

        foreach ($signals as $signal) {
            if (str_contains($text, $signal)) {
                return true;
            }
        }

        return (bool) preg_match('/\b(vs|versus|per|by)\b/u', $text);
    }

    protected function looksLikeChat(string $text): bool
    {
        $exact = [
            'hi', 'hi!', 'hello', 'hello!', 'hey', 'hey!', 'yo', 'sup',
            'good morning', 'good afternoon', 'good evening', 'good night',
            'thanks', 'thank you', 'thx', 'ty', 'ok', 'okay', 'cool', 'nice',
            'bye', 'goodbye', 'see you', 'help', 'help me', 'what can you do',
            'who are you', 'how are you', 'how r you', 'whats up', "what's up",
            'test', 'testing',
        ];

        if (in_array($text, $exact, true)) {
            return true;
        }

        return (bool) preg_match(
            '/^(hi|hello|hey|yo|thanks|thank you|help|bye|goodbye)\b/u',
            $text,
        );
    }

    protected function chatReply(string $text): string
    {
        if (preg_match('/^(hi|hello|hey|yo|good morning|good afternoon|good evening)\b/u', $text)) {
            return 'Hello! How can I help with analytics on data you can access?';
        }

        if (preg_match('/^(thanks|thank you|thx|ty)\b/u', $text)) {
            return "You're welcome. Ask whenever you want numbers or charts from data you can access.";
        }

        if (preg_match('/^(bye|goodbye|see you)\b/u', $text)) {
            return 'Bye! Come back anytime you need analytics.';
        }

        if (str_contains($text, 'how are you') || str_contains($text, 'how r you') || str_contains($text, "what's up") || str_contains($text, 'whats up')) {
            return "I'm ready to help. What would you like to analyze?";
        }

        if (str_contains($text, 'who are you') || str_contains($text, 'what can you do') || $text === 'help' || str_starts_with($text, 'help ')) {
            return 'I turn questions into analytics — KPIs, charts, and tables — only from data your account can access.';
        }

        return 'Hi — tell me what you want to know, and I’ll build charts from data you can access.';
    }
}
