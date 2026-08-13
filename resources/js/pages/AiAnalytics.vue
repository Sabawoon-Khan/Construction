<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    ArrowLeft,
    ArrowUpRight,
    BookOpen,
    Brain,
    ChartColumn,
    Check,
    ChevronDown,
    Code2,
    Database,
    History,
    Lightbulb,
    Loader2,
    Plus,
    Search,
    Send,
    Sparkles,
    Trash2,
    Zap,
} from '@lucide/vue';
import type { Component } from 'vue';
import { computed, nextTick, onMounted, ref, watch } from 'vue';
import {
    MisChartCard,
    MisDataTable,
    MisStatCard,
    MisStatGrid,
} from '@/components/mis';
import { Button } from '@/components/ui/button';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import { useTranslations } from '@/composables/useTranslations';

type Insight = { title: string; body: string; tone: 'info' | 'positive' | 'warning' };
type Kpi = { id: string; label: string; value: string; hint: string | null };
type ChartSection = {
    id: string;
    title: string;
    chart_type: 'bar' | 'line' | 'pie';
    labels: string[];
    datasets: Array<{ label: string; data: number[] }>;
};
type TableSection = {
    id: string;
    title: string;
    columns: string[];
    rows: Array<Record<string, unknown>>;
    truncated?: boolean;
    total_rows?: number;
};
type QueryMeta = {
    id: string;
    title: string;
    question: string;
    sql: string;
    row_count: number;
};
type Report = {
    title: string;
    summary: string;
    prompt: string;
    kpis: Kpi[];
    charts: ChartSection[];
    tables: TableSection[];
    insights: Insight[];
    queries: QueryMeta[];
    usage?: {
        prompt_tokens: number;
        completion_tokens: number;
        total_tokens: number;
    } | null;
    model?: string | null;
};

type Example = { label: string; prompt: string };

type ChatMessage = {
    id: string;
    role: 'user' | 'assistant';
    content: string;
    kind: 'text' | 'report';
    report?: Report | null;
};

type StoredConversation = {
    id: string;
    title: string;
    updatedAt: number;
    messages: ChatMessage[];
};

type ProgressStep = {
    id: string;
    label: string;
    status: 'running' | 'done' | 'error' | 'pending';
    detail?: string | null;
};

type StageId = 'understand' | 'write' | 'search' | 'insights';

const STORAGE_KEY = 'kit-ai-analytics-chats-v2';
const LEGACY_STORAGE_KEY = 'kit-ai-analytics-chat-v1';

const STAGE_DEFS: Array<{
    id: StageId;
    label: string;
    icon: Component;
}> = [
    { id: 'understand', label: 'Understanding', icon: Brain },
    { id: 'write', label: 'Writing SQL', icon: Code2 },
    { id: 'search', label: 'Searching', icon: Search },
    { id: 'insights', label: 'Insights', icon: Sparkles },
];

const props = defineProps<{
    canGenerate: boolean;
    analyticsEnabled: boolean;
    aiEnabled: boolean;
    setupUrl: string;
    examples: Example[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'AI Analytics', href: '/ai/analytics' }],
    },
});

const { t } = useTranslations();

const prompt = ref('');
const mode = ref<'efficient' | 'detailed'>('efficient');
const loading = ref(false);
const error = ref<string | null>(null);
const messages = ref<ChatMessage[]>([]);
const conversations = ref<StoredConversation[]>([]);
const activeConversationId = ref<string | null>(null);
const view = ref<'chat' | 'history'>('chat');
const threadEl = ref<HTMLElement | null>(null);
const promptFocused = ref(false);
const steps = ref<ProgressStep[]>([]);
const statusDetail = ref<string | null>(null);

const setupNeeded = computed(() => !props.aiEnabled || !props.analyticsEnabled);
const isEmpty = computed(() => messages.value.length === 0 && !loading.value);
const sortedConversations = computed(() =>
    [...conversations.value]
        .filter((c) => c.messages.length > 0)
        .sort((a, b) => b.updatedAt - a.updatedAt),
);
const activeTitle = computed(() => {
    const current = conversations.value.find(
        (c) => c.id === activeConversationId.value,
    );
    return current?.title || t('AI Analytics');
});

const activeStage = computed<StageId>(() => {
    const running = [...steps.value].reverse().find((s) => s.status === 'running');
    const id = running?.id ?? steps.value.at(-1)?.id ?? 'planning';

    if (id === 'insights' || id === 'complete') {
        return 'insights';
    }
    if (id === 'write') {
        return 'write';
    }
    if (id === 'fallback' || id.startsWith('query') || id === 'search') {
        return 'search';
    }
    if (id === 'planning' && running?.status === 'running') {
        return 'understand';
    }
    if (id === 'planning') {
        return 'write';
    }

    return 'search';
});

const activeStageIndex = computed(() =>
    Math.max(
        0,
        STAGE_DEFS.findIndex((s) => s.id === activeStage.value),
    ),
);

const progressFill = computed(() => {
    const i = activeStageIndex.value;
    const n = STAGE_DEFS.length - 1;
    const base = n <= 0 ? 100 : (i / n) * 100;

    return Math.min(96, Math.max(8, base + 4));
});

const activeHeadline = computed(() => {
    const running = [...steps.value].reverse().find((s) => s.status === 'running');
    if (running?.label) {
        return running.label;
    }

    return (
        {
            understand: t('Understanding your question…'),
            write: t('Writing safe queries…'),
            search: t('Searching your data…'),
            insights: t('Writing insights…'),
        }[activeStage.value] ?? t('Working…')
    );
});

function csrfToken(): string {
    return (
        document
            .querySelector('meta[name="csrf-token"]')
            ?.getAttribute('content') ?? ''
    );
}

function newId(): string {
    return `${Date.now()}-${Math.random().toString(36).slice(2, 8)}`;
}

function historyPayload(): Array<{ role: 'user' | 'assistant'; content: string }> {
    return messages.value.slice(-12).map((m) => {
        if (m.kind === 'report' && m.report) {
            const kpiBits = m.report.kpis
                .slice(0, 4)
                .map((k) => `${k.label}=${k.value}`)
                .join(', ');
            const chartBits = m.report.charts
                .slice(0, 3)
                .map((c) => `${c.chart_type}:${c.title}`)
                .join(', ');
            const content = `[report] ${m.report.title}. ${m.report.summary} KPIs: ${kpiBits}. Charts: ${chartBits}`;

            return { role: m.role, content: content.slice(0, 1200) };
        }

        return { role: m.role, content: m.content.slice(0, 1200) };
    });
}

function titleFromMessages(list: ChatMessage[]): string {
    const firstUser = list.find((m) => m.role === 'user' && m.content.trim());
    if (!firstUser) {
        return t('New chat');
    }
    const text = firstUser.content.trim().replace(/\s+/g, ' ');
    return text.length > 42 ? `${text.slice(0, 42)}…` : text;
}

function persistStore(): void {
    try {
        const payload = {
            activeId: activeConversationId.value,
            conversations: conversations.value.map((c) => ({
                id: c.id,
                title: c.title,
                updatedAt: c.updatedAt,
                messages: c.messages.map((m) => ({
                    id: m.id,
                    role: m.role,
                    content: m.content,
                    kind: m.kind,
                    report: m.report ?? null,
                })),
            })),
        };
        localStorage.setItem(STORAGE_KEY, JSON.stringify(payload));
    } catch {
        // ignore quota / private mode
    }
}

function syncActiveConversation(): void {
    if (!activeConversationId.value) {
        return;
    }
    const index = conversations.value.findIndex(
        (c) => c.id === activeConversationId.value,
    );
    if (index === -1) {
        return;
    }
    conversations.value[index] = {
        ...conversations.value[index],
        title: titleFromMessages(messages.value),
        updatedAt: Date.now(),
        messages: messages.value.map((m) => ({ ...m })),
    };
    persistStore();
}

function ensureActiveConversation(): void {
    if (activeConversationId.value) {
        return;
    }
    const id = newId();
    conversations.value.unshift({
        id,
        title: t('New chat'),
        updatedAt: Date.now(),
        messages: [],
    });
    activeConversationId.value = id;
    persistStore();
}

function persistMessages(): void {
    ensureActiveConversation();
    syncActiveConversation();
}

function loadStore(): void {
    try {
        const raw = localStorage.getItem(STORAGE_KEY);
        if (raw) {
            const parsed = JSON.parse(raw) as {
                activeId?: string | null;
                conversations?: StoredConversation[];
            };
            if (Array.isArray(parsed.conversations)) {
                conversations.value = parsed.conversations.filter(
                    (c) => c && typeof c.id === 'string' && Array.isArray(c.messages),
                );
            }
            activeConversationId.value =
                typeof parsed.activeId === 'string' ? parsed.activeId : null;
            const active = conversations.value.find(
                (c) => c.id === activeConversationId.value,
            );
            if (active) {
                messages.value = active.messages;
            } else if (conversations.value[0]) {
                activeConversationId.value = conversations.value[0].id;
                messages.value = conversations.value[0].messages;
            }
            return;
        }

        // Migrate single-thread legacy session storage once.
        const legacy = sessionStorage.getItem(LEGACY_STORAGE_KEY);
        if (!legacy) {
            return;
        }
        const parsed = JSON.parse(legacy) as ChatMessage[];
        if (!Array.isArray(parsed) || parsed.length === 0) {
            return;
        }
        const id = newId();
        const list = parsed.filter(
            (m) => m && (m.role === 'user' || m.role === 'assistant'),
        );
        conversations.value = [
            {
                id,
                title: titleFromMessages(list),
                updatedAt: Date.now(),
                messages: list,
            },
        ];
        activeConversationId.value = id;
        messages.value = list;
        persistStore();
        sessionStorage.removeItem(LEGACY_STORAGE_KEY);
    } catch {
        conversations.value = [];
        messages.value = [];
        activeConversationId.value = null;
    }
}

function openConversation(id: string): void {
    if (loading.value) {
        return;
    }
    const found = conversations.value.find((c) => c.id === id);
    if (!found) {
        return;
    }
    activeConversationId.value = id;
    messages.value = found.messages.map((m) => ({ ...m }));
    error.value = null;
    steps.value = [];
    statusDetail.value = null;
    view.value = 'chat';
    persistStore();
    void scrollToBottom();
}

function clearChat(): void {
    if (loading.value) {
        return;
    }
    conversations.value = conversations.value.filter(
        (c) => c.messages.length > 0,
    );
    if (messages.value.length === 0 && activeConversationId.value) {
        view.value = 'chat';
        persistStore();
        return;
    }
    const id = newId();
    conversations.value.unshift({
        id,
        title: t('New chat'),
        updatedAt: Date.now(),
        messages: [],
    });
    activeConversationId.value = id;
    messages.value = [];
    error.value = null;
    steps.value = [];
    statusDetail.value = null;
    view.value = 'chat';
    persistStore();
}

function deleteConversation(id: string): void {
    if (loading.value) {
        return;
    }
    conversations.value = conversations.value.filter((c) => c.id !== id);
    if (activeConversationId.value === id) {
        const next =
            conversations.value.find((c) => c.messages.length > 0) ?? null;
        activeConversationId.value = next?.id ?? null;
        messages.value = next ? next.messages.map((m) => ({ ...m })) : [];
        if (!next) {
            view.value = 'chat';
        }
    }
    persistStore();
}

function confirmDelete(id: string): void {
    if (!window.confirm(t('Delete this chat from history?'))) {
        return;
    }
    deleteConversation(id);
}

function conversationPreview(item: StoredConversation): string {
    const last = [...item.messages]
        .reverse()
        .find((m) => m.role === 'assistant' || m.role === 'user');
    if (!last) {
        return '';
    }
    if (last.kind === 'report' && last.report) {
        return last.report.summary || last.report.title;
    }
    return last.content.trim();
}

function showHistory(): void {
    view.value = 'history';
}

function showChat(): void {
    view.value = 'chat';
}

function formatChatTime(ts: number): string {
    try {
        return new Intl.DateTimeFormat(undefined, {
            month: 'short',
            day: 'numeric',
            hour: 'numeric',
            minute: '2-digit',
        }).format(new Date(ts));
    } catch {
        return '';
    }
}

function useExample(example: Example): void {
    prompt.value = example.prompt;
    error.value = null;
}

function upsertStep(step: ProgressStep): void {
    const index = steps.value.findIndex((s) => s.id === step.id);
    if (index === -1) {
        steps.value.push(step);
    } else {
        steps.value[index] = { ...steps.value[index], ...step };
    }
    if (step.detail) {
        statusDetail.value = step.detail;
    }
    if (step.status === 'running' && step.label) {
        statusDetail.value = step.detail ?? step.label;
    }
}

async function scrollToBottom(): Promise<void> {
    await nextTick();
    threadEl.value?.scrollTo({
        top: threadEl.value.scrollHeight,
        behavior: 'smooth',
    });
}

async function send(): Promise<void> {
    const text = prompt.value.trim();
    if (!text) {
        error.value = t('Type a question to generate analytics.');
        return;
    }
    if (setupNeeded.value || !props.canGenerate) {
        error.value = t(
            'Enable AI analytics under Settings → AI → Tasks, then try again.',
        );
        return;
    }
    if (loading.value) {
        return;
    }

    const history = historyPayload();
    messages.value.push({
        id: newId(),
        role: 'user',
        content: text,
        kind: 'text',
    });
    prompt.value = '';
    persistMessages();

    loading.value = true;
    error.value = null;
    statusDetail.value = null;
    steps.value = [
        {
            id: 'planning',
            label: t('Understanding your question…'),
            status: 'running',
        },
    ];
    await scrollToBottom();

    try {
        const response = await fetch('/ai/analytics/generate?stream=1', {
            method: 'POST',
            headers: {
                Accept: 'text/event-stream',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken(),
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
            body: JSON.stringify({
                prompt: text,
                mode: mode.value,
                history,
            }),
        });

        if (!response.ok) {
            const data = (await response.json().catch(() => ({}))) as {
                message?: string;
            };
            throw new Error(
                data.message || `Request failed (${response.status})`,
            );
        }

        if (!response.body) {
            throw new Error(t('Could not generate analytics. Try again.'));
        }

        const reader = response.body.getReader();
        const decoder = new TextDecoder();
        let buffer = '';
        let streamError: string | null = null;
        let gotResult = false;

        while (true) {
            const { done, value } = await reader.read();
            if (done) {
                break;
            }

            buffer += decoder.decode(value, { stream: true });
            const chunks = buffer.split('\n\n');
            buffer = chunks.pop() ?? '';

            for (const chunk of chunks) {
                const lines = chunk.split('\n');
                let eventName = 'message';
                const dataLines: string[] = [];

                for (const line of lines) {
                    if (line.startsWith('event:')) {
                        eventName = line.slice(6).trim();
                    } else if (line.startsWith('data:')) {
                        dataLines.push(line.slice(5).trim());
                    }
                }

                if (dataLines.length === 0) {
                    continue;
                }

                const payload = JSON.parse(dataLines.join('\n')) as Record<
                    string,
                    unknown
                >;

                if (eventName === 'step') {
                    upsertStep({
                        id: String(payload.id ?? 'step'),
                        label: String(payload.label ?? ''),
                        status:
                            (payload.status as ProgressStep['status']) ??
                            'running',
                        detail:
                            typeof payload.detail === 'string'
                                ? payload.detail
                                : null,
                    });
                    await scrollToBottom();
                } else if (eventName === 'done') {
                    if (payload.kind === 'chat') {
                        messages.value.push({
                            id: newId(),
                            role: 'assistant',
                            content:
                                typeof payload.message === 'string'
                                    ? payload.message
                                    : t('Hello! How can I help?'),
                            kind: 'text',
                        });
                        gotResult = true;
                    } else {
                        const report = (payload.report as Report) ?? null;
                        if (report) {
                            messages.value.push({
                                id: newId(),
                                role: 'assistant',
                                content: report.summary || report.title,
                                kind: 'report',
                                report,
                            });
                            gotResult = true;
                        }
                    }
                } else if (eventName === 'error') {
                    streamError =
                        typeof payload.message === 'string'
                            ? payload.message
                            : t('Could not generate analytics. Try again.');
                }
            }
        }

        if (streamError) {
            throw new Error(streamError);
        }

        if (!gotResult) {
            throw new Error(t('Could not generate analytics. Try again.'));
        }

        persistMessages();
        await scrollToBottom();
    } catch (e) {
        error.value =
            e instanceof Error
                ? e.message
                : t('Could not generate analytics. Try again.');
        await scrollToBottom();
    } finally {
        loading.value = false;
        steps.value = [];
        statusDetail.value = null;
    }
}

function onKeydown(event: KeyboardEvent): void {
    if ((event.metaKey || event.ctrlKey) && event.key === 'Enter') {
        event.preventDefault();
        void send();
    }
}

function insightTone(tone: string): string {
    if (tone === 'positive') {
        return 'border-emerald-500/20 bg-emerald-500/[0.05]';
    }
    if (tone === 'warning') {
        return 'border-amber-500/25 bg-amber-500/[0.05]';
    }

    return 'border-border/60 bg-background/60';
}

function cellValue(value: unknown): string {
    if (value === null || value === undefined) {
        return '—';
    }
    if (typeof value === 'boolean') {
        return value ? 'Yes' : 'No';
    }
    if (typeof value === 'object') {
        return JSON.stringify(value);
    }
    if (typeof value === 'number') {
        return value.toLocaleString();
    }

    return String(value);
}

onMounted(() => {
    loadStore();
    void scrollToBottom();
});

watch(
    () => messages.value.length,
    () => {
        void scrollToBottom();
    },
);
</script>

<template>
    <Head :title="t('AI Analytics')" />

    <div class="mx-auto flex h-[calc(100dvh-7.5rem)] w-full max-w-4xl flex-col pb-2">
        <header class="flex shrink-0 items-center justify-between gap-3 pb-4">
            <div class="flex min-w-0 items-center gap-2.5">
                <button
                    v-if="view === 'history'"
                    type="button"
                    class="inline-flex size-9 shrink-0 items-center justify-center rounded-full text-muted-foreground transition hover:bg-muted hover:text-foreground"
                    :aria-label="t('Back')"
                    @click="showChat"
                >
                    <ArrowLeft class="size-4 rtl:rotate-180" />
                </button>
                <div class="min-w-0 space-y-0.5">
                    <h1 class="truncate text-xl font-semibold tracking-tight sm:text-2xl">
                        {{
                            view === 'history'
                                ? t('Chat history')
                                : t('AI Analytics')
                        }}
                    </h1>
                    <p
                        v-if="view === 'chat'"
                        class="truncate text-sm text-muted-foreground"
                    >
                        {{
                            messages.length
                                ? activeTitle
                                : t('Ask a question about your school data.')
                        }}
                    </p>
                </div>
            </div>
            <div class="flex shrink-0 items-center gap-1">
                <button
                    v-if="view === 'chat'"
                    type="button"
                    class="inline-flex size-9 items-center justify-center rounded-full text-muted-foreground transition hover:bg-muted hover:text-foreground"
                    :title="t('History')"
                    :aria-label="t('History')"
                    :disabled="loading"
                    @click="showHistory"
                >
                    <History class="size-4" />
                </button>
                <button
                    type="button"
                    class="inline-flex size-9 items-center justify-center rounded-full text-muted-foreground transition hover:bg-muted hover:text-foreground"
                    :title="t('New chat')"
                    :aria-label="t('New chat')"
                    :disabled="loading"
                    @click="clearChat"
                >
                    <Plus class="size-4" />
                </button>
            </div>
        </header>

        <div
            v-if="setupNeeded && view === 'chat'"
            class="mb-3 flex shrink-0 flex-col gap-3 rounded-2xl border border-[var(--school-navy)]/15 bg-[var(--school-navy)]/[0.04] px-4 py-3.5 sm:flex-row sm:items-center sm:justify-between"
        >
            <p class="text-sm text-foreground/90">
                <template v-if="!aiEnabled">
                    {{ t('Turn AI on, then enable analytics under Tasks.') }}
                </template>
                <template v-else>
                    {{
                        t(
                            'Analytics is off. Enable “Allow analytics” under Settings → AI → Tasks.',
                        )
                    }}
                </template>
            </p>
            <Button
                as-child
                variant="outline"
                class="shrink-0 rounded-xl border-[var(--school-navy)]/25 bg-background"
            >
                <Link :href="setupUrl" class="gap-1.5">
                    {{ t('Open AI settings') }}
                    <ArrowUpRight class="size-3.5" />
                </Link>
            </Button>
        </div>

        <!-- History -->
        <div
            v-if="view === 'history'"
            class="ai-analytics-scroll min-h-0 flex-1 overflow-y-auto px-0.5 py-1"
        >
            <div
                v-if="sortedConversations.length === 0"
                class="flex h-full min-h-[16rem] flex-col items-center justify-center gap-3 px-4 text-center"
            >
                <div
                    class="flex size-12 items-center justify-center rounded-2xl border border-border/70 bg-card text-[var(--school-navy)]"
                >
                    <History class="size-5" />
                </div>
                <div class="space-y-1">
                    <p class="text-sm font-semibold">{{ t('No chats yet') }}</p>
                    <p class="text-[12px] text-muted-foreground">
                        {{ t('Start a conversation and it will show up here.') }}
                    </p>
                </div>
                <button
                    type="button"
                    class="mt-1 inline-flex h-9 items-center gap-1.5 rounded-full bg-[var(--school-navy)] px-4 text-sm font-medium text-white"
                    @click="clearChat"
                >
                    <Plus class="size-3.5" />
                    {{ t('New chat') }}
                </button>
            </div>

            <div v-else class="space-y-1.5">
                <div
                    v-for="item in sortedConversations"
                    :key="item.id"
                    class="group flex w-full items-start gap-2 rounded-2xl border px-3 py-2.5 transition hover:border-border/70 hover:bg-card/80"
                    :class="
                        item.id === activeConversationId
                            ? 'border-border/70 bg-card/80'
                            : 'border-transparent'
                    "
                >
                    <button
                        type="button"
                        class="flex min-w-0 flex-1 items-start gap-2.5 text-start"
                        @click="openConversation(item.id)"
                    >
                        <span
                            class="mt-0.5 flex size-8 shrink-0 items-center justify-center rounded-xl bg-[var(--school-navy)]/8 text-[var(--school-navy)]"
                        >
                            <ChartColumn class="size-3.5" />
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="flex items-center justify-between gap-2">
                                <span
                                    class="truncate text-[13px] font-semibold text-foreground"
                                >
                                    {{ item.title }}
                                </span>
                                <span
                                    class="shrink-0 text-[10px] text-muted-foreground"
                                >
                                    {{ formatChatTime(item.updatedAt) }}
                                </span>
                            </span>
                            <span
                                v-if="conversationPreview(item)"
                                class="mt-0.5 line-clamp-2 text-[12px] leading-snug text-muted-foreground"
                            >
                                {{ conversationPreview(item) }}
                            </span>
                        </span>
                    </button>
                    <button
                        type="button"
                        class="mt-0.5 size-7 shrink-0 rounded-full text-muted-foreground opacity-0 transition hover:bg-destructive/10 hover:text-destructive group-hover:opacity-100"
                        :aria-label="t('Delete')"
                        @click="confirmDelete(item.id)"
                    >
                        <Trash2 class="mx-auto size-3.5" />
                    </button>
                </div>
            </div>
        </div>

        <!-- Thread -->
        <div
            v-else
            ref="threadEl"
            class="ai-analytics-scroll min-h-0 flex-1 space-y-5 overflow-y-auto px-0.5 py-2"
        >
            <div
                v-if="isEmpty"
                class="flex h-full min-h-[16rem] flex-col items-center justify-center px-4 text-center"
            >
                <div
                    class="mb-4 flex size-11 items-center justify-center rounded-2xl bg-[var(--school-navy)]/[0.06] text-[var(--school-navy)]"
                >
                    <Sparkles class="size-5" :stroke-width="1.5" />
                </div>
                <p class="text-sm font-semibold">{{ t('Ask anything') }}</p>
                <p class="mt-1.5 max-w-sm text-sm text-muted-foreground">
                    {{
                        t(
                            'Chat naturally. Follow-ups remember this thread — try users, logins, or say hi.',
                        )
                    }}
                </p>
                <div class="mt-6 flex flex-wrap justify-center gap-2">
                    <button
                        v-for="example in examples"
                        :key="example.label"
                        type="button"
                        class="rounded-full border border-border/70 bg-card px-3.5 py-1.5 text-xs font-medium text-muted-foreground transition hover:border-[var(--school-navy)]/25 hover:text-foreground"
                        :disabled="loading || setupNeeded"
                        @click="useExample(example)"
                    >
                        {{ t(example.label) }}
                    </button>
                </div>
            </div>

            <template v-for="message in messages" :key="message.id">
                <!-- User -->
                <div
                    v-if="message.role === 'user'"
                    class="flex justify-end"
                >
                    <div
                        class="max-w-[85%] rounded-2xl rounded-br-md bg-[var(--school-navy)] px-4 py-2.5 text-[14px] leading-relaxed text-white"
                    >
                        {{ message.content }}
                    </div>
                </div>

                <!-- Assistant text -->
                <div
                    v-else-if="message.kind === 'text'"
                    class="flex gap-2.5"
                >
                    <span
                        class="mt-0.5 flex size-7 shrink-0 items-center justify-center rounded-full bg-[var(--school-navy)]/10 text-[var(--school-navy)]"
                    >
                        <Sparkles class="size-3.5" :stroke-width="1.75" />
                    </span>
                    <div class="min-w-0 max-w-[90%] space-y-1 pt-0.5">
                        <p class="text-[14px] leading-relaxed text-foreground">
                            {{ message.content }}
                        </p>
                    </div>
                </div>

                <!-- Assistant report -->
                <div v-else-if="message.report" class="flex gap-2.5">
                    <span
                        class="mt-0.5 flex size-7 shrink-0 items-center justify-center rounded-full bg-[var(--school-navy)]/10 text-[var(--school-navy)]"
                    >
                        <ChartColumn class="size-3.5" :stroke-width="1.75" />
                    </span>
                    <div
                        class="min-w-0 flex-1 space-y-4 rounded-2xl border border-border/50 bg-card/80 px-4 py-4 sm:px-5"
                    >
                        <div class="space-y-1">
                            <div class="flex flex-wrap items-baseline justify-between gap-2">
                                <h2 class="text-base font-semibold tracking-tight">
                                    {{ message.report.title }}
                                </h2>
                                <span
                                    v-if="message.report.usage"
                                    class="text-[11px] tabular-nums text-muted-foreground"
                                >
                                    {{
                                        message.report.usage.total_tokens.toLocaleString()
                                    }}
                                    {{ t('tokens') }}
                                </span>
                            </div>
                            <p
                                v-if="message.report.summary"
                                class="text-sm leading-relaxed text-muted-foreground"
                            >
                                {{ message.report.summary }}
                            </p>
                        </div>

                        <div v-if="message.report.kpis.length" class="space-y-2">
                            <MisStatGrid :columns="4">
                                <MisStatCard
                                    v-for="kpi in message.report.kpis"
                                    :key="kpi.id"
                                    :label="kpi.label"
                                    :value="kpi.value"
                                    :hint="kpi.hint"
                                />
                            </MisStatGrid>
                        </div>

                        <div
                            v-if="message.report.insights.length"
                            class="grid gap-2 md:grid-cols-2"
                        >
                            <article
                                v-for="insight in message.report.insights"
                                :key="insight.title + insight.body"
                                class="rounded-xl border px-3.5 py-3"
                                :class="insightTone(insight.tone)"
                            >
                                <h4
                                    class="flex items-center gap-1.5 text-sm font-semibold"
                                >
                                    <Lightbulb class="size-3.5 opacity-70" />
                                    {{ insight.title }}
                                </h4>
                                <p
                                    class="mt-1 text-sm leading-relaxed text-muted-foreground"
                                >
                                    {{ insight.body }}
                                </p>
                            </article>
                        </div>

                        <div
                            v-if="message.report.charts.length"
                            class="grid gap-3 lg:grid-cols-2"
                        >
                            <MisChartCard
                                v-for="chart in message.report.charts"
                                :key="chart.id"
                                :title="chart.title"
                                :type="chart.chart_type"
                                :labels="chart.labels"
                                :datasets="chart.datasets"
                            />
                        </div>

                        <div
                            v-if="message.report.tables.length"
                            class="space-y-4"
                        >
                            <div
                                v-for="table in message.report.tables"
                                :key="table.id"
                                class="space-y-2"
                            >
                                <h4 class="text-sm font-semibold">
                                    {{ table.title }}
                                </h4>
                                <MisDataTable
                                    :columns="
                                        table.columns.map((col) => ({
                                            key: col,
                                            label: col,
                                            class: 'tabular-nums',
                                        }))
                                    "
                                    :rows="
                                        table.rows.map((row) =>
                                            Object.fromEntries(
                                                table.columns.map((col) => [
                                                    col,
                                                    cellValue(row[col]),
                                                ]),
                                            ),
                                        )
                                    "
                                    :empty-label="t('No rows')"
                                />
                            </div>
                        </div>

                        <Collapsible
                            v-if="message.report.queries.length"
                            class="rounded-xl border border-border/50"
                        >
                            <CollapsibleTrigger
                                class="group flex w-full items-center justify-between px-3.5 py-2.5 text-start text-xs font-medium text-muted-foreground transition hover:text-foreground"
                            >
                                {{ t('Queries used') }}
                                <ChevronDown
                                    class="size-3.5 transition group-data-[state=open]:rotate-180"
                                />
                            </CollapsibleTrigger>
                            <CollapsibleContent
                                class="space-y-3 border-t border-border/50 px-3.5 py-3"
                            >
                                <div
                                    v-for="query in message.report.queries"
                                    :key="query.id"
                                    class="space-y-1.5"
                                >
                                    <p class="text-xs font-medium">
                                        {{ query.title }}
                                    </p>
                                    <pre
                                        class="overflow-x-auto rounded-lg bg-muted/40 px-3 py-2 font-mono text-[10px] leading-relaxed text-foreground/70"
                                    ><code>{{ query.sql }}</code></pre>
                                </div>
                            </CollapsibleContent>
                        </Collapsible>
                    </div>
                </div>
            </template>

            <!-- Live progress in thread -->
            <div
                v-if="loading"
                class="ai-analytics-progress flex gap-2.5"
                aria-busy="true"
                aria-live="polite"
            >
                <span
                    class="ai-analytics-orb relative mt-0.5 flex size-7 shrink-0 items-center justify-center rounded-full bg-[var(--school-navy)] text-white"
                >
                    <component
                        :is="STAGE_DEFS[activeStageIndex]?.icon ?? Database"
                        class="relative z-[1] size-3.5"
                        :stroke-width="2"
                    />
                </span>
                <div
                    class="min-w-0 flex-1 rounded-2xl border border-border/50 bg-card/80 px-4 py-3.5"
                >
                    <div class="flex items-center justify-between gap-3">
                        <p
                            :key="activeHeadline"
                            class="ai-analytics-fade truncate text-sm font-medium"
                        >
                            {{ activeHeadline }}
                        </p>
                        <span
                            class="ai-analytics-dots text-[var(--school-navy)]"
                            aria-hidden="true"
                        >
                            <i /><i /><i />
                        </span>
                    </div>
                    <div class="relative mt-4 px-1">
                        <div
                            class="absolute top-[14px] right-4 left-4 h-[2px] overflow-hidden rounded-full bg-border/70"
                        >
                            <div
                                class="ai-analytics-fill relative h-full rounded-full bg-[var(--school-navy)] transition-[width] duration-700 ease-[cubic-bezier(0.22,1,0.36,1)]"
                                :style="{ width: `${progressFill}%` }"
                            >
                                <span class="ai-analytics-sheen absolute inset-y-0 w-1/2" />
                            </div>
                        </div>
                        <ol class="relative z-[1] flex justify-between">
                            <li
                                v-for="(stage, index) in STAGE_DEFS"
                                :key="stage.id"
                                class="flex w-[22%] flex-col items-center gap-1.5"
                            >
                                <span
                                    class="relative flex size-7 items-center justify-center rounded-full border transition-all duration-500"
                                    :class="
                                        index < activeStageIndex
                                            ? 'border-[var(--school-navy)] bg-[var(--school-navy)] text-white'
                                            : index === activeStageIndex
                                              ? 'ai-analytics-node-active border-[var(--school-navy)] bg-card text-[var(--school-navy)]'
                                              : 'border-border bg-card text-muted-foreground/40'
                                    "
                                >
                                    <Check
                                        v-if="index < activeStageIndex"
                                        class="size-3"
                                        :stroke-width="2.5"
                                    />
                                    <component
                                        :is="stage.icon"
                                        v-else
                                        class="size-3"
                                        :stroke-width="2"
                                    />
                                </span>
                                <span
                                    class="text-[10px] font-medium"
                                    :class="
                                        index <= activeStageIndex
                                            ? 'text-foreground/80'
                                            : 'text-muted-foreground/45'
                                    "
                                >
                                    {{ t(stage.label) }}
                                </span>
                            </li>
                        </ol>
                    </div>
                </div>
            </div>

            <p
                v-if="error"
                class="text-sm text-destructive"
                role="alert"
            >
                {{ error }}
            </p>
        </div>

        <!-- Composer -->
        <section
            v-if="view === 'chat'"
            class="mt-3 shrink-0 overflow-hidden rounded-2xl border bg-card transition-[border-color] duration-200"
            :class="
                promptFocused
                    ? 'border-[var(--school-navy)]/35'
                    : 'border-border/80'
            "
        >
            <label class="sr-only" for="ai-analytics-prompt">{{
                t('Analytics prompt')
            }}</label>
            <textarea
                id="ai-analytics-prompt"
                v-model="prompt"
                rows="2"
                :disabled="loading"
                class="max-h-36 min-h-[3.25rem] w-full resize-none bg-transparent px-4 pt-3.5 pb-1 text-[14px] leading-relaxed outline-none placeholder:text-muted-foreground/45 disabled:opacity-70"
                :placeholder="
                    t('Ask a follow-up or a new question…')
                "
                @keydown="onKeydown"
                @focus="promptFocused = true"
                @blur="promptFocused = false"
            />
            <div
                class="flex flex-wrap items-center justify-between gap-2 border-t border-border/40 px-3 py-2.5"
            >
                <div
                    class="flex items-center rounded-lg border border-border/60 p-0.5"
                >
                    <button
                        type="button"
                        class="inline-flex h-7 items-center gap-1 rounded-md px-2 text-[11px] font-medium transition"
                        :class="
                            mode === 'efficient'
                                ? 'bg-[var(--school-navy)] text-white'
                                : 'text-muted-foreground hover:text-foreground'
                        "
                        :disabled="loading"
                        @click="mode = 'efficient'"
                    >
                        <Zap class="size-3" />
                        {{ t('Efficient') }}
                    </button>
                    <button
                        type="button"
                        class="inline-flex h-7 items-center gap-1 rounded-md px-2 text-[11px] font-medium transition"
                        :class="
                            mode === 'detailed'
                                ? 'bg-[var(--school-navy)] text-white'
                                : 'text-muted-foreground hover:text-foreground'
                        "
                        :disabled="loading"
                        @click="mode = 'detailed'"
                    >
                        <BookOpen class="size-3" />
                        {{ t('Detailed') }}
                    </button>
                </div>
                <button
                    type="button"
                    class="inline-flex h-9 items-center gap-2 rounded-xl bg-[var(--school-navy)] px-3.5 text-sm font-semibold text-white transition hover:bg-[var(--school-navy)]/90 disabled:cursor-not-allowed disabled:opacity-40"
                    :disabled="loading || setupNeeded || !prompt.trim()"
                    @click="send"
                >
                    <Loader2 v-if="loading" class="size-4 animate-spin" />
                    <Send v-else class="size-3.5" />
                    {{ loading ? t('Working…') : t('Send') }}
                </button>
            </div>
        </section>
    </div>
</template>

<style scoped>
.ai-analytics-scroll {
    scrollbar-width: none;
    -ms-overflow-style: none;
}

.ai-analytics-scroll::-webkit-scrollbar {
    display: none;
}

.ai-analytics-orb::before {
    content: '';
    position: absolute;
    inset: -3px;
    border-radius: 9999px;
    background: color-mix(in oklab, var(--school-navy) 28%, transparent);
    animation: ai-analytics-orb 1.8s ease-out infinite;
}

.ai-analytics-fill {
    box-shadow: 0 0 14px color-mix(in oklab, var(--school-navy) 40%, transparent);
}

.ai-analytics-sheen {
    background: linear-gradient(
        90deg,
        transparent,
        color-mix(in oklab, white 60%, transparent),
        transparent
    );
    animation: ai-analytics-sheen 1.5s ease-in-out infinite;
}

.ai-analytics-node-active {
    box-shadow: 0 0 0 3px color-mix(in oklab, var(--school-navy) 14%, transparent);
}

.ai-analytics-fade {
    animation: ai-analytics-fade 0.35s ease-out;
}

.ai-analytics-dots {
    display: inline-flex;
    gap: 4px;
    align-items: center;
}

.ai-analytics-dots i {
    display: block;
    width: 4px;
    height: 4px;
    border-radius: 9999px;
    background: currentColor;
    opacity: 0.35;
    animation: ai-analytics-dot 1.1s ease-in-out infinite;
}

.ai-analytics-dots i:nth-child(2) {
    animation-delay: 0.15s;
}

.ai-analytics-dots i:nth-child(3) {
    animation-delay: 0.3s;
}

@keyframes ai-analytics-sheen {
    0% {
        transform: translateX(-120%);
    }
    100% {
        transform: translateX(220%);
    }
}

@keyframes ai-analytics-orb {
    0% {
        transform: scale(0.85);
        opacity: 0.55;
    }
    100% {
        transform: scale(1.55);
        opacity: 0;
    }
}

@keyframes ai-analytics-fade {
    from {
        opacity: 0.3;
        transform: translateY(3px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes ai-analytics-dot {
    0%,
    80%,
    100% {
        transform: translateY(0);
        opacity: 0.3;
    }
    40% {
        transform: translateY(-3px);
        opacity: 1;
    }
}
</style>
