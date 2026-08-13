<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { Plus, Trash2 } from '@lucide/vue';
import { computed, reactive, ref } from 'vue';
import InputError from '@/components/InputError.vue';
import {
    MisEmptyState,
    MisExpandableRow,
    MisFilterBar,
    MisPagination,
    MisSearchInput,
} from '@/components/mis';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useCalendar } from '@/composables/useCalendar';
import { useTranslations } from '@/composables/useTranslations';
import { formatNumber, type Paginated } from '@/lib/format';
import AiTabs from '@/pages/settings/AiTabs.vue';

type AiModelForm = {
    id?: string | null;
    label: string;
    provider: string;
    model: string;
    base_url: string;
    api_key: string;
    api_key_set?: boolean;
};

type FeatureKey =
    | 'grammar'
    | 'improve'
    | 'shorten'
    | 'expand'
    | 'describe'
    | 'auto'
    | 'custom'
    | 'fill_form';

type AiSettingsPayload = {
    enabled: boolean;
    chat_enabled: boolean;
    chat_allow_create: boolean;
    chat_allow_delete: boolean;
    chat_allow_analytics: boolean;
    langfuse_enabled: boolean;
    langfuse_public_key_set: boolean;
    langfuse_secret_key_set: boolean;
    langfuse_base_url: string;
    field_edit_enabled: boolean;
    default_model_id: string | null;
    models: Array<{
        id: string;
        label: string;
        provider: string;
        model: string;
        base_url: string | null;
        api_key_set: boolean;
    }>;
    features: Record<FeatureKey, boolean>;
    failover_enabled: boolean;
    base_system_prompt: string;
    action_prompts: Record<FeatureKey, string>;
    action_prompt_defaults: Record<FeatureKey, string>;
    max_input_chars: number;
    max_output_tokens: number;
    daily_token_budget: number;
    panel_size: string;
    panel_sizes: Array<{
        id: string;
        label: string;
        width: number;
        prompt_rows: number;
        result_max_h: number;
    }>;
    env_default: boolean;
    limits_ceiling: {
        max_models: number;
        max_input_chars: number;
        max_output_tokens: number;
        daily_token_budget: number;
        max_instruction_chars: number;
        max_base_system_prompt_chars: number;
        max_action_system_prompt_chars: number;
    };
};

type AiLogRecord = {
    id: number;
    status: string;
    action: string;
    provider: string | null;
    model_name: string | null;
    instruction_preview: string | null;
    input_preview: string | null;
    output_preview: string | null;
    prompt_tokens: number;
    completion_tokens: number;
    total_tokens: number;
    estimated_cost_usd: number | string | null;
    duration_ms: number | null;
    error: string | null;
    logged_at: string;
    user?: { id: number; name: string; email: string } | null;
};

type AiSummary = {
    requests: number;
    success: number;
    failed: number;
    tokens: number;
    cost_usd: number;
    avg_duration_ms: number;
    today_tokens: number;
    today_cost_usd: number;
    month_tokens: number;
    month_cost_usd: number;
};

const props = withDefaults(
    defineProps<{
        tab?: 'general' | 'prompts' | 'assist' | 'tasks' | 'logs';
        settings?: AiSettingsPayload | null;
        providers: Array<{
            id: string;
            label: string;
            default_base_url: string | null;
            suggested_model?: string | null;
            requires_base_url?: boolean;
            driver?: string;
        }>;
        actions: Array<{ id: string; label: string }>;
        canManage: boolean;
        canViewLogs: boolean;
        logs?: Paginated<AiLogRecord>;
        summary?: AiSummary | null;
        byAction?: Array<{
            action: string;
            requests: number;
            tokens: number;
            cost_usd: number;
        }>;
        statuses?: string[];
        filters?: {
            search?: string | null;
            status?: string | null;
            action?: string | null;
        };
    }>(),
    {
        tab: 'general',
        settings: null,
        logs: undefined,
        summary: null,
        byAction: () => [],
        statuses: () => [],
        filters: () => ({}),
    },
);

const { t } = useTranslations();
const calendar = useCalendar();

const search = ref(props.filters?.search ?? '');
const statusFilter = ref(props.filters?.status ?? '');
const actionFilter = ref(props.filters?.action ?? '');
const expandedLogId = ref<number | null>(null);

const maxModels = computed(
    () => props.settings?.limits_ceiling.max_models ?? 3,
);

function mapModels(
    models: AiSettingsPayload['models'] | undefined,
): AiModelForm[] {
    return (models ?? []).map((m): AiModelForm => ({
        id: m.id,
        label: m.label,
        provider: m.provider,
        model: m.model,
        base_url: m.base_url ?? '',
        api_key: '',
        api_key_set: m.api_key_set,
    }));
}

function defaultFeatures(
    features?: Partial<Record<FeatureKey, boolean>> | null,
): Record<FeatureKey, boolean> {
    return {
        grammar: Boolean(features?.grammar),
        improve: Boolean(features?.improve),
        shorten: Boolean(features?.shorten),
        expand: Boolean(features?.expand),
        describe: Boolean(features?.describe),
        auto: Boolean(features?.auto ?? true),
        custom: Boolean(features?.custom ?? true),
        fill_form: Boolean(features?.fill_form ?? true),
    };
}

function defaultActionPrompts(
    prompts?: Partial<Record<FeatureKey, string>> | null,
): Record<FeatureKey, string> {
    return {
        grammar: prompts?.grammar ?? '',
        improve: prompts?.improve ?? '',
        shorten: prompts?.shorten ?? '',
        expand: prompts?.expand ?? '',
        describe: prompts?.describe ?? '',
        auto: prompts?.auto ?? '',
        custom: prompts?.custom ?? '',
        fill_form: prompts?.fill_form ?? '',
    };
}

/** Local editable state — avoids Inertia form nested-array input bugs. */
const state = reactive({
    enabled: Boolean(props.settings?.enabled),
    chat_enabled: Boolean(props.settings?.chat_enabled ?? true),
    chat_allow_create: Boolean(props.settings?.chat_allow_create ?? false),
    chat_allow_delete: Boolean(props.settings?.chat_allow_delete ?? false),
    chat_allow_analytics: Boolean(
        props.settings?.chat_allow_analytics ?? false,
    ),
    langfuse_enabled: Boolean(props.settings?.langfuse_enabled ?? false),
    langfuse_public_key: '',
    langfuse_secret_key: '',
    langfuse_base_url:
        props.settings?.langfuse_base_url ?? 'https://cloud.langfuse.com',
    field_edit_enabled: Boolean(props.settings?.field_edit_enabled ?? true),
    default_model_id: (props.settings?.default_model_id ?? null) as
        string | null,
    models: mapModels(props.settings?.models),
    features: defaultFeatures(props.settings?.features),
    failover_enabled: Boolean(props.settings?.failover_enabled ?? true),
    base_system_prompt: props.settings?.base_system_prompt ?? '',
    action_prompts: defaultActionPrompts(props.settings?.action_prompts),
    max_input_chars: props.settings?.max_input_chars ?? 2500,
    max_output_tokens: props.settings?.max_output_tokens ?? 400,
    daily_token_budget: props.settings?.daily_token_budget ?? 80_000,
    panel_size: props.settings?.panel_size ?? 'comfortable',
});

const form = useForm({
    enabled: state.enabled,
    chat_enabled: state.chat_enabled,
    chat_allow_create: state.chat_allow_create,
    chat_allow_delete: state.chat_allow_delete,
    chat_allow_analytics: state.chat_allow_analytics,
    langfuse_enabled: state.langfuse_enabled,
    langfuse_public_key: '',
    langfuse_secret_key: '',
    langfuse_base_url: state.langfuse_base_url,
    field_edit_enabled: state.field_edit_enabled,
    default_model_id: state.default_model_id,
    models: [] as Array<Record<string, unknown>>,
    features: { ...state.features },
    failover_enabled: state.failover_enabled,
    base_system_prompt: state.base_system_prompt,
    action_prompts: { ...state.action_prompts },
    max_input_chars: state.max_input_chars,
    max_output_tokens: state.max_output_tokens,
    daily_token_budget: state.daily_token_budget,
    panel_size: state.panel_size,
});

const panelSizes = computed(() => props.settings?.panel_sizes ?? []);

const promptActions = computed(() =>
    props.actions.filter((action) => action.id in state.action_prompts),
);

const featureActions = computed(() =>
    props.actions.filter(
        (action) =>
            action.id !== 'custom' &&
            action.id !== 'fill_form' &&
            action.id in state.features,
    ),
);

function providerMeta(providerId: string) {
    return props.providers.find((p) => p.id === providerId);
}

function needsBaseUrl(providerId: string): boolean {
    const meta = providerMeta(providerId);
    return Boolean(meta?.requires_base_url);
}

function suggestedModel(providerId: string): string {
    return providerMeta(providerId)?.suggested_model || '';
}

function onProviderChange(model: AiModelForm): void {
    const suggested = suggestedModel(model.provider);
    if (suggested && (!model.model || model.model === '')) {
        model.model = suggested;
    }
    if (!needsBaseUrl(model.provider) && !model.base_url) {
        // keep empty; server uses provider default
    }
}

const hasUsableModel = computed(() =>
    state.models.some((m) => m.api_key_set || m.api_key.trim() !== ''),
);

const hasLogs = computed(() => (props.logs?.data.length ?? 0) > 0);

const hasActiveFilters = computed(() =>
    Boolean(search.value || statusFilter.value || actionFilter.value),
);

const isConfigTab = computed(
    () =>
        props.tab === 'general' ||
        props.tab === 'prompts' ||
        props.tab === 'assist' ||
        props.tab === 'tasks',
);

const pageTitle = computed(() => t('AI'));

const switchClass =
    'inline-flex shrink-0 items-center gap-2.5 rounded-full border px-1.5 py-1 transition';

function switchTone(on: boolean): string {
    return on
        ? 'border-[var(--school-navy)]/30 bg-[var(--school-navy)]/8'
        : 'border-border bg-muted/40';
}

function switchKnob(on: boolean): string {
    return on ? 'bg-[var(--school-navy)]' : 'bg-muted-foreground/35';
}

function toggleEnabled(): void {
    state.enabled = !state.enabled;
}

function toggleChatEnabled(): void {
    state.chat_enabled = !state.chat_enabled;
}

function toggleChatAllowCreate(): void {
    state.chat_allow_create = !state.chat_allow_create;
}

function toggleChatAllowDelete(): void {
    state.chat_allow_delete = !state.chat_allow_delete;
}

function toggleChatAllowAnalytics(): void {
    state.chat_allow_analytics = !state.chat_allow_analytics;
}

function toggleLangfuseEnabled(): void {
    state.langfuse_enabled = !state.langfuse_enabled;
}

const langfuseTestBusy = ref(false);
const langfuseTestMessage = ref<string | null>(null);
const langfuseTestOk = ref<boolean | null>(null);

function csrfToken(): string {
    return (
        document
            .querySelector('meta[name="csrf-token"]')
            ?.getAttribute('content') || ''
    );
}

async function testLangfuseConnection(): Promise<void> {
    langfuseTestBusy.value = true;
    langfuseTestMessage.value = null;
    langfuseTestOk.value = null;

    try {
        const response = await fetch('/settings/ai/langfuse/test', {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken(),
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
            body: '{}',
        });
        const data = (await response.json()) as {
            ok?: boolean;
            message?: string;
        };
        langfuseTestOk.value = Boolean(data.ok);
        langfuseTestMessage.value =
            data.message ||
            (data.ok
                ? t('Connected to Langfuse.')
                : t('Langfuse connection failed.'));
    } catch {
        langfuseTestOk.value = false;
        langfuseTestMessage.value = t(
            'Could not reach the Langfuse test endpoint.',
        );
    } finally {
        langfuseTestBusy.value = false;
    }
}

function toggleFieldEditEnabled(): void {
    state.field_edit_enabled = !state.field_edit_enabled;
}

function addModel(): void {
    if (state.models.length >= maxModels.value) {
        return;
    }

    const firstProvider = props.providers[0]?.id ?? 'openai';
    state.models.push({
        id: null,
        label: '',
        provider: firstProvider,
        model: suggestedModel(firstProvider) || 'gpt-4o-mini',
        base_url: '',
        api_key: '',
        api_key_set: false,
    });
}

function removeModel(index: number): void {
    const removed = state.models[index];
    state.models.splice(index, 1);

    if (removed?.id && state.default_model_id === removed.id) {
        state.default_model_id = state.models.find((m) => m.id)?.id ?? null;
    }
}

function setDefault(id: string): void {
    state.default_model_id = id;
}

function toggleFeature(id: string): void {
    const key = id as FeatureKey;
    if (!(key in state.features)) {
        return;
    }
    state.features[key] = !state.features[key];
}

function toggleFailover(): void {
    state.failover_enabled = !state.failover_enabled;
}

function resetActionPrompt(id: string): void {
    const key = id as FeatureKey;
    if (!(key in state.action_prompts)) {
        return;
    }
    state.action_prompts[key] = '';
}

function actionPromptPlaceholder(id: string): string {
    const key = id as FeatureKey;
    return props.settings?.action_prompt_defaults?.[key] ?? '';
}

function featureDescription(id: string): string {
    if (id === 'fill_form') {
        return t(
            'Fill form uses Ctrl+Shift+K (⌘⇧K): describe a person or record and map it onto labelled fields.',
        );
    }

    if (id === 'auto') {
        return t(
            'One-click Auto mode in Ctrl+K — improve or write the field without a custom prompt.',
        );
    }

    if (id === 'grammar') {
        return t('Fix grammar and spelling in the selected text.');
    }

    if (id === 'improve') {
        return t('Rewrite text to be clearer and more polished.');
    }

    if (id === 'shorten') {
        return t('Condense text while keeping the meaning.');
    }

    if (id === 'expand') {
        return t('Add detail and length to the selected text.');
    }

    if (id === 'describe') {
        return t('Turn notes or keywords into a fuller description.');
    }

    return t('Optional presets. Ctrl+K always uses a free prompt.');
}

function syncFromProps(): void {
    if (!props.settings) {
        return;
    }

    state.enabled = Boolean(props.settings.enabled);
    state.chat_enabled = Boolean(props.settings.chat_enabled ?? true);
    state.chat_allow_create = Boolean(
        props.settings.chat_allow_create ?? false,
    );
    state.chat_allow_delete = Boolean(
        props.settings.chat_allow_delete ?? false,
    );
    state.chat_allow_analytics = Boolean(
        props.settings.chat_allow_analytics ?? false,
    );
    state.langfuse_enabled = Boolean(props.settings.langfuse_enabled ?? false);
    state.langfuse_public_key = '';
    state.langfuse_secret_key = '';
    state.langfuse_base_url =
        props.settings.langfuse_base_url ?? 'https://cloud.langfuse.com';
    state.field_edit_enabled = Boolean(
        props.settings.field_edit_enabled ?? true,
    );
    state.default_model_id = props.settings.default_model_id;
    state.models = mapModels(props.settings.models);
    state.features = defaultFeatures(props.settings.features);
    state.failover_enabled = Boolean(props.settings.failover_enabled);
    state.base_system_prompt = props.settings.base_system_prompt ?? '';
    state.action_prompts = defaultActionPrompts(props.settings.action_prompts);
    state.max_input_chars = props.settings.max_input_chars;
    state.max_output_tokens = props.settings.max_output_tokens;
    state.daily_token_budget = props.settings.daily_token_budget;
    state.panel_size = props.settings.panel_size ?? 'comfortable';
}

function submit(): void {
    if (!props.settings) {
        return;
    }

    state.features.custom = true;

    form.enabled = state.enabled;
    form.chat_enabled = state.chat_enabled;
    form.chat_allow_create = state.chat_allow_create;
    form.chat_allow_delete = state.chat_allow_delete;
    form.chat_allow_analytics = state.chat_allow_analytics;
    form.langfuse_enabled = state.langfuse_enabled;
    form.langfuse_public_key =
        state.langfuse_public_key.trim() !== ''
            ? state.langfuse_public_key.trim()
            : '';
    form.langfuse_secret_key =
        state.langfuse_secret_key.trim() !== ''
            ? state.langfuse_secret_key.trim()
            : '';
    form.langfuse_base_url = state.langfuse_base_url.trim();
    form.field_edit_enabled = state.field_edit_enabled;
    form.default_model_id = state.default_model_id;
    form.features = { ...state.features, custom: true };
    form.failover_enabled = state.failover_enabled;
    form.base_system_prompt = state.base_system_prompt;
    form.action_prompts = { ...state.action_prompts };
    form.max_input_chars = state.max_input_chars;
    form.max_output_tokens = state.max_output_tokens;
    form.daily_token_budget = state.daily_token_budget;
    form.panel_size = state.panel_size;
    form.models = state.models.map((m) => ({
        id: m.id || undefined,
        label: m.label,
        provider: m.provider,
        model: m.model,
        base_url: m.base_url || null,
        api_key: m.api_key.trim() !== '' ? m.api_key : undefined,
    }));

    form.put('/settings/ai', {
        preserveScroll: true,
        onSuccess: () => syncFromProps(),
    });
}

function applyFilters(): void {
    router.get(
        '/settings/ai/logs',
        {
            search: search.value || undefined,
            status: statusFilter.value || undefined,
            action: actionFilter.value || undefined,
        },
        { preserveState: true, replace: true },
    );
}

function clearFilters(): void {
    search.value = '';
    statusFilter.value = '';
    actionFilter.value = '';
    applyFilters();
}

function formatDateTime(value: string): string {
    return calendar.formatDateTime(value) || value;
}

function formatCost(value: number | string | null | undefined): string {
    const amount = typeof value === 'string' ? Number(value) : (value ?? 0);

    if (!Number.isFinite(amount)) {
        return '—';
    }

    return `$${formatNumber(amount, {
        minimumFractionDigits: 2,
        maximumFractionDigits: 4,
    })}`;
}

function formatDuration(ms: number | null | undefined): string {
    if (ms == null) {
        return '—';
    }

    return `${formatNumber(ms)} ms`;
}

function statusVariant(value: string): 'default' | 'destructive' | 'outline' {
    if (value === 'success') {
        return 'default';
    }

    if (value === 'failed') {
        return 'destructive';
    }

    return 'outline';
}

function statusLabel(value: string): string {
    if (value === 'success') {
        return t('Success');
    }

    if (value === 'failed') {
        return t('Failed');
    }

    return value;
}

function actionLabel(id: string): string {
    const match = props.actions.find((item) => item.id === id);

    return match ? t(match.label) : id;
}

function previewText(value: string | null | undefined): string {
    if (!value) {
        return '—';
    }

    return value.trim() || '—';
}

function logSnippet(log: AiLogRecord): string | null {
    if (log.error) {
        return log.error;
    }

    return (
        log.instruction_preview?.trim() ||
        log.input_preview?.trim() ||
        log.output_preview?.trim() ||
        null
    );
}

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Settings', href: '/settings/appearance' },
            { title: 'AI', href: '/settings/ai' },
        ],
    },
});
</script>

<template>
    <Head :title="pageTitle" />

    <div class="space-y-6">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <AiTabs
                :active="tab"
                :can-manage="canManage"
                :can-view-logs="canViewLogs"
            />

            <button
                v-if="isConfigTab && canManage && settings"
                type="button"
                role="switch"
                :aria-checked="state.enabled"
                :class="[switchClass, switchTone(state.enabled)]"
                @click="toggleEnabled"
            >
                <span
                    class="size-5 rounded-full transition"
                    :class="switchKnob(state.enabled)"
                />
                <span class="pe-2 text-xs font-semibold tracking-tight">
                    {{ state.enabled ? t('On') : t('Off') }}
                </span>
            </button>
        </div>

        <form
            v-if="isConfigTab && canManage && settings"
            class="space-y-8 pb-20"
            @submit.prevent="submit"
        >
            <template v-if="tab === 'general'">
                <p
                    v-if="state.enabled && !hasUsableModel"
                    class="rounded-lg border border-amber-500/30 bg-amber-500/5 px-3 py-2 text-sm text-foreground"
                >
                    {{
                        t(
                            'Add a model and API key, then Save — helpers appear on text fields after that.',
                        )
                    }}
                </p>

                <section class="space-y-4 border-b border-border/70 pb-8">
                    <div class="flex flex-wrap items-end justify-between gap-3">
                        <div>
                            <h2 class="text-sm font-semibold">
                                {{ t('Models') }}
                            </h2>
                            <p class="mt-0.5 text-xs text-muted-foreground">
                                {{
                                    t(':count of :max', {
                                        count: String(state.models.length),
                                        max: String(maxModels),
                                    })
                                }}
                            </p>
                        </div>
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            :disabled="state.models.length >= maxModels"
                            @click="addModel"
                        >
                            <Plus class="size-4" />
                            {{ t('Add model') }}
                        </Button>
                    </div>

                    <div
                        v-if="state.models.length === 0"
                        class="flex flex-col items-center justify-center gap-3 rounded-lg border border-dashed px-4 py-10"
                    >
                        <p class="text-sm text-muted-foreground">
                            {{ t('No models yet') }}
                        </p>
                        <Button type="button" size="sm" @click="addModel">
                            <Plus class="size-4" />
                            {{ t('Add model') }}
                        </Button>
                    </div>

                    <div
                        v-for="(model, index) in state.models"
                        :key="model.id || `new-${index}`"
                        class="space-y-3 rounded-lg border border-border/80 p-4"
                    >
                        <div class="flex items-center justify-between gap-2">
                            <div class="flex items-center gap-2">
                                <button
                                    v-if="model.id"
                                    type="button"
                                    class="rounded-md px-2 py-1 text-[11px] font-semibold"
                                    :class="
                                        state.default_model_id === model.id
                                            ? 'bg-[var(--school-navy)] text-white'
                                            : 'bg-muted text-muted-foreground hover:text-foreground'
                                    "
                                    @click="setDefault(String(model.id))"
                                >
                                    {{
                                        state.default_model_id === model.id
                                            ? t('Default')
                                            : t('Use as default')
                                    }}
                                </button>
                                <span
                                    v-else
                                    class="text-xs font-medium text-muted-foreground"
                                >
                                    {{ t('Unsaved') }}
                                </span>
                                <span
                                    v-if="model.api_key_set"
                                    class="text-[11px] text-muted-foreground"
                                >
                                    {{ t('Key saved') }}
                                </span>
                            </div>
                            <Button
                                type="button"
                                variant="ghost"
                                size="icon"
                                class="size-8 text-muted-foreground hover:text-destructive"
                                :aria-label="t('Remove')"
                                @click="removeModel(index)"
                            >
                                <Trash2 class="size-4" />
                            </Button>
                        </div>

                        <div class="grid gap-3 sm:grid-cols-2">
                            <div class="space-y-1.5">
                                <Label :for="`ai-label-${index}`">{{
                                    t('Name')
                                }}</Label>
                                <input
                                    :id="`ai-label-${index}`"
                                    v-model="model.label"
                                    type="text"
                                    autocomplete="off"
                                    :placeholder="t('Fast')"
                                    class="h-9 w-full rounded-md border border-input bg-transparent px-3 text-sm outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                                />
                            </div>

                            <div class="space-y-1.5">
                                <Label :for="`ai-provider-${index}`">
                                    {{ t('Provider') }}
                                </Label>
                                <select
                                    :id="`ai-provider-${index}`"
                                    v-model="model.provider"
                                    class="h-9 w-full rounded-md border border-input bg-transparent px-3 text-sm outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                                    @change="onProviderChange(model)"
                                >
                                    <option
                                        v-for="provider in providers"
                                        :key="provider.id"
                                        :value="provider.id"
                                    >
                                        {{ provider.label }}
                                    </option>
                                </select>
                            </div>

                            <div class="space-y-1.5">
                                <Label :for="`ai-model-name-${index}`">
                                    {{ t('Model') }}
                                </Label>
                                <input
                                    :id="`ai-model-name-${index}`"
                                    v-model="model.model"
                                    type="text"
                                    autocomplete="off"
                                    :placeholder="
                                        suggestedModel(model.provider) ||
                                        'model-name'
                                    "
                                    class="h-9 w-full rounded-md border border-input bg-transparent px-3 text-sm outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                                />
                            </div>

                            <div class="space-y-1.5">
                                <Label :for="`ai-api-key-${index}`">
                                    {{ t('API key') }}
                                </Label>
                                <input
                                    :id="`ai-api-key-${index}`"
                                    v-model="model.api_key"
                                    type="password"
                                    autocomplete="new-password"
                                    :placeholder="
                                        model.api_key_set ? '••••••••' : 'sk-…'
                                    "
                                    class="h-9 w-full rounded-md border border-input bg-transparent px-3 text-sm outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                                />
                            </div>

                            <div class="space-y-1.5 sm:col-span-2">
                                <Label :for="`ai-base-${index}`">
                                    {{
                                        needsBaseUrl(model.provider)
                                            ? t('Base URL')
                                            : t('Base URL (optional)')
                                    }}
                                </Label>
                                <input
                                    :id="`ai-base-${index}`"
                                    v-model="model.base_url"
                                    type="url"
                                    autocomplete="off"
                                    :placeholder="
                                        providerMeta(model.provider)
                                            ?.default_base_url ||
                                        'https://api.example.com/v1'
                                    "
                                    class="h-9 w-full rounded-md border border-input bg-transparent px-3 text-sm outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                                />
                                <p
                                    v-if="needsBaseUrl(model.provider)"
                                    class="text-[11px] text-muted-foreground"
                                >
                                    {{
                                        t(
                                            'Required for Custom / Other endpoints.',
                                        )
                                    }}
                                </p>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="space-y-3 border-b border-border/70 pb-8">
                    <div>
                        <h2 class="text-sm font-semibold">
                            {{ t('How to use') }}
                        </h2>
                        <ol
                            class="mt-2 list-decimal space-y-1 ps-4 text-sm text-muted-foreground"
                        >
                            <li>{{ t('Click any text field') }}</li>
                            <li>{{ t('Press Ctrl+K (⌘K) or the sparkle') }}</li>
                            <li>
                                {{
                                    t(
                                        'Type a prompt → Accept, Reject, or follow up',
                                    )
                                }}
                            </li>
                        </ol>
                    </div>
                </section>

                <section class="space-y-3 border-b border-border/70 pb-8">
                    <div>
                        <h2 class="text-sm font-semibold">
                            {{ t('Ctrl+K panel size') }}
                        </h2>
                        <p class="mt-0.5 text-xs text-muted-foreground">
                            {{
                                t(
                                    'Default size for the AI edit window. Users can still switch while editing.',
                                )
                            }}
                        </p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <button
                            v-for="size in panelSizes"
                            :key="size.id"
                            type="button"
                            class="rounded-lg border px-3 py-2 text-sm font-medium transition"
                            :class="
                                state.panel_size === size.id
                                    ? 'border-[var(--school-navy)] bg-[var(--school-navy)] text-white'
                                    : 'border-border bg-background text-muted-foreground hover:text-foreground'
                            "
                            @click="state.panel_size = size.id"
                        >
                            {{ t(size.label) }}
                        </button>
                    </div>
                </section>

                <section class="space-y-3">
                    <h2 class="text-sm font-semibold">{{ t('Limits') }}</h2>
                    <div class="grid gap-4 sm:grid-cols-3">
                        <div class="space-y-1.5">
                            <Label for="max_input_chars">{{
                                t('Max characters')
                            }}</Label>
                            <Input
                                id="max_input_chars"
                                v-model.number="state.max_input_chars"
                                type="number"
                                min="100"
                                :max="settings.limits_ceiling.max_input_chars"
                            />
                            <InputError
                                :message="form.errors.max_input_chars"
                            />
                        </div>
                        <div class="space-y-1.5">
                            <Label for="max_output_tokens">{{
                                t('Max tokens')
                            }}</Label>
                            <Input
                                id="max_output_tokens"
                                v-model.number="state.max_output_tokens"
                                type="number"
                                min="50"
                                :max="settings.limits_ceiling.max_output_tokens"
                            />
                            <InputError
                                :message="form.errors.max_output_tokens"
                            />
                        </div>
                        <div class="space-y-1.5">
                            <Label for="daily_token_budget">{{
                                t('Daily budget')
                            }}</Label>
                            <Input
                                id="daily_token_budget"
                                v-model.number="state.daily_token_budget"
                                type="number"
                                min="1000"
                                :max="
                                    settings.limits_ceiling.daily_token_budget
                                "
                            />
                            <InputError
                                :message="form.errors.daily_token_budget"
                            />
                        </div>
                    </div>
                </section>
            </template>

            <template v-else-if="tab === 'prompts'">
                <section class="space-y-6">
                    <div class="space-y-1.5 border-b border-border/70 pb-6">
                        <Label for="ai-base-system">
                            {{ t('Base instructions (optional)') }}
                        </Label>
                        <textarea
                            id="ai-base-system"
                            v-model="state.base_system_prompt"
                            rows="3"
                            :maxlength="
                                settings.limits_ceiling
                                    .max_base_system_prompt_chars
                            "
                            :placeholder="
                                t(
                                    'Applied to every action — e.g. keep a formal school tone, never invent IDs.',
                                )
                            "
                            class="min-h-[4.5rem] w-full resize-y rounded-md border border-input bg-transparent px-3 py-2 text-sm outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                        />
                        <InputError :message="form.errors.base_system_prompt" />
                    </div>

                    <div
                        v-for="action in promptActions"
                        :key="action.id"
                        class="space-y-1.5 border-b border-border/60 pb-5 last:border-b-0 last:pb-0"
                    >
                        <div
                            class="flex flex-wrap items-center justify-between gap-2"
                        >
                            <Label :for="`ai-prompt-${action.id}`">
                                {{ t(action.label) }}
                            </Label>
                            <button
                                type="button"
                                class="text-[11px] font-medium text-muted-foreground hover:text-foreground"
                                @click="resetActionPrompt(action.id)"
                            >
                                {{ t('Use default') }}
                            </button>
                        </div>
                        <textarea
                            :id="`ai-prompt-${action.id}`"
                            v-model="
                                state.action_prompts[action.id as FeatureKey]
                            "
                            rows="3"
                            :maxlength="
                                settings.limits_ceiling
                                    .max_action_system_prompt_chars
                            "
                            :placeholder="actionPromptPlaceholder(action.id)"
                            class="min-h-[4.5rem] w-full resize-y rounded-md border border-input bg-transparent px-3 py-2 text-sm outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                        />
                    </div>
                </section>
            </template>

            <template v-else-if="tab === 'assist'">
                <section class="space-y-0 divide-y divide-border/70">
                    <div
                        class="flex items-start justify-between gap-4 py-4 first:pt-0"
                    >
                        <div class="min-w-0 space-y-0.5">
                            <h2 class="text-sm font-semibold">
                                {{ t('Field editing') }}
                            </h2>
                            <p class="text-xs text-muted-foreground">
                                {{
                                    t(
                                        'Ctrl+K / sparkle on text fields. Turn off to disable field AI entirely.',
                                    )
                                }}
                            </p>
                        </div>
                        <button
                            type="button"
                            role="switch"
                            :aria-checked="state.field_edit_enabled"
                            :class="[
                                switchClass,
                                switchTone(state.field_edit_enabled),
                            ]"
                            :disabled="!state.enabled"
                            @click="toggleFieldEditEnabled"
                        >
                            <span
                                class="size-5 rounded-full transition"
                                :class="switchKnob(state.field_edit_enabled)"
                            />
                            <span
                                class="pe-2 text-xs font-semibold tracking-tight"
                            >
                                {{
                                    state.field_edit_enabled
                                        ? t('On')
                                        : t('Off')
                                }}
                            </span>
                        </button>
                    </div>

                    <div class="flex items-start justify-between gap-4 py-4">
                        <div class="min-w-0 space-y-0.5">
                            <h2 class="text-sm font-semibold">
                                {{ t('Fill form') }}
                            </h2>
                            <p class="text-xs text-muted-foreground">
                                {{ featureDescription('fill_form') }}
                            </p>
                        </div>
                        <button
                            type="button"
                            role="switch"
                            :aria-checked="state.features.fill_form"
                            :class="[
                                switchClass,
                                switchTone(state.features.fill_form),
                            ]"
                            :disabled="!state.enabled"
                            @click="toggleFeature('fill_form')"
                        >
                            <span
                                class="size-5 rounded-full transition"
                                :class="switchKnob(state.features.fill_form)"
                            />
                            <span
                                class="pe-2 text-xs font-semibold tracking-tight"
                            >
                                {{
                                    state.features.fill_form
                                        ? t('On')
                                        : t('Off')
                                }}
                            </span>
                        </button>
                    </div>

                    <div class="flex items-start justify-between gap-4 py-4">
                        <div class="min-w-0 space-y-0.5">
                            <h2 class="text-sm font-semibold">
                                {{ t('Provider failover') }}
                            </h2>
                            <p class="text-xs text-muted-foreground">
                                {{
                                    t(
                                        'If the selected model fails, try the next saved models (with API keys) in list order.',
                                    )
                                }}
                            </p>
                        </div>
                        <button
                            type="button"
                            role="switch"
                            :aria-checked="state.failover_enabled"
                            :class="[
                                switchClass,
                                switchTone(state.failover_enabled),
                            ]"
                            @click="toggleFailover"
                        >
                            <span
                                class="size-5 rounded-full transition"
                                :class="switchKnob(state.failover_enabled)"
                            />
                            <span
                                class="pe-2 text-xs font-semibold tracking-tight"
                            >
                                {{
                                    state.failover_enabled ? t('On') : t('Off')
                                }}
                            </span>
                        </button>
                    </div>

                    <div
                        v-for="action in featureActions"
                        :key="action.id"
                        class="flex items-start justify-between gap-4 py-4 last:pb-0"
                    >
                        <div class="min-w-0 space-y-0.5">
                            <h2 class="text-sm font-semibold">
                                {{ t(action.label) }}
                            </h2>
                            <p class="text-xs text-muted-foreground">
                                {{ featureDescription(action.id) }}
                            </p>
                        </div>
                        <button
                            type="button"
                            role="switch"
                            :aria-checked="
                                state.features[action.id as FeatureKey]
                            "
                            :class="[
                                switchClass,
                                switchTone(
                                    state.features[action.id as FeatureKey],
                                ),
                            ]"
                            :disabled="!state.field_edit_enabled"
                            @click="toggleFeature(action.id)"
                        >
                            <span
                                class="size-5 rounded-full transition"
                                :class="
                                    switchKnob(
                                        state.features[action.id as FeatureKey],
                                    )
                                "
                            />
                            <span
                                class="pe-2 text-xs font-semibold tracking-tight"
                            >
                                {{
                                    state.features[action.id as FeatureKey]
                                        ? t('On')
                                        : t('Off')
                                }}
                            </span>
                        </button>
                    </div>
                </section>
            </template>

            <template v-else-if="tab === 'tasks'">
                <section class="space-y-0 divide-y divide-border/70">
                    <div
                        class="flex items-start justify-between gap-4 py-4 first:pt-0"
                    >
                        <div class="min-w-0 space-y-0.5">
                            <h2 class="text-sm font-semibold">
                                {{ t('Task management AI') }}
                            </h2>
                            <p class="text-xs text-muted-foreground">
                                {{
                                    t(
                                        'General Chat in the header — ask the agent to help with platform tasks.',
                                    )
                                }}
                            </p>
                        </div>
                        <button
                            type="button"
                            role="switch"
                            :aria-checked="state.chat_enabled"
                            :class="[
                                switchClass,
                                switchTone(state.chat_enabled),
                            ]"
                            :disabled="!state.enabled"
                            @click="toggleChatEnabled"
                        >
                            <span
                                class="size-5 rounded-full transition"
                                :class="switchKnob(state.chat_enabled)"
                            />
                            <span
                                class="pe-2 text-xs font-semibold tracking-tight"
                            >
                                {{ state.chat_enabled ? t('On') : t('Off') }}
                            </span>
                        </button>
                    </div>

                    <div class="flex items-start justify-between gap-4 py-4">
                        <div class="min-w-0 space-y-0.5">
                            <h2 class="text-sm font-semibold">
                                {{ t('Allow creating & updating records') }}
                            </h2>
                            <p class="text-xs text-muted-foreground">
                                {{
                                    t(
                                        'Lets the agent create and update any registered catalog resource (reports, users, roles, lookups, …). Runs immediately when On — still requires the user’s own permissions.',
                                    )
                                }}
                            </p>
                        </div>
                        <button
                            type="button"
                            role="switch"
                            :aria-checked="state.chat_allow_create"
                            :class="[
                                switchClass,
                                switchTone(state.chat_allow_create),
                            ]"
                            :disabled="!state.enabled || !state.chat_enabled"
                            @click="toggleChatAllowCreate"
                        >
                            <span
                                class="size-5 rounded-full transition"
                                :class="switchKnob(state.chat_allow_create)"
                            />
                            <span
                                class="pe-2 text-xs font-semibold tracking-tight"
                            >
                                {{
                                    state.chat_allow_create ? t('On') : t('Off')
                                }}
                            </span>
                        </button>
                    </div>

                    <div class="flex items-start justify-between gap-4 py-4">
                        <div class="min-w-0 space-y-0.5">
                            <h2 class="text-sm font-semibold">
                                {{ t('Allow deleting records') }}
                            </h2>
                            <p class="text-xs text-muted-foreground">
                                {{
                                    t(
                                        'High impact. Deletes always need Approve in the chat panel, plus the user’s delete permission.',
                                    )
                                }}
                            </p>
                        </div>
                        <button
                            type="button"
                            role="switch"
                            :aria-checked="state.chat_allow_delete"
                            :class="[
                                switchClass,
                                switchTone(state.chat_allow_delete),
                            ]"
                            :disabled="!state.enabled || !state.chat_enabled"
                            @click="toggleChatAllowDelete"
                        >
                            <span
                                class="size-5 rounded-full transition"
                                :class="switchKnob(state.chat_allow_delete)"
                            />
                            <span
                                class="pe-2 text-xs font-semibold tracking-tight"
                            >
                                {{
                                    state.chat_allow_delete ? t('On') : t('Off')
                                }}
                            </span>
                        </button>
                    </div>

                    <div class="flex items-start justify-between gap-4 py-4">
                        <div class="min-w-0 space-y-0.5">
                            <h2 class="text-sm font-semibold">
                                {{ t('Allow analytics (read-only SQL)') }}
                            </h2>
                            <p class="text-xs text-muted-foreground">
                                {{
                                    t(
                                        'Lets the agent answer reporting questions by running read-only SELECT queries. Never writes to the database.',
                                    )
                                }}
                            </p>
                        </div>
                        <button
                            type="button"
                            role="switch"
                            :aria-checked="state.chat_allow_analytics"
                            :class="[
                                switchClass,
                                switchTone(state.chat_allow_analytics),
                            ]"
                            :disabled="!state.enabled || !state.chat_enabled"
                            @click="toggleChatAllowAnalytics"
                        >
                            <span
                                class="size-5 rounded-full transition"
                                :class="switchKnob(state.chat_allow_analytics)"
                            />
                            <span
                                class="pe-2 text-xs font-semibold tracking-tight"
                            >
                                {{
                                    state.chat_allow_analytics
                                        ? t('On')
                                        : t('Off')
                                }}
                            </span>
                        </button>
                    </div>

                    <p class="py-4 text-xs text-muted-foreground">
                        {{
                            t(
                                'Risk: turn Off any capability you do not want the model to reach. Creates/updates run when On; deletes always pause for Approve. Jailbreak-style prompts are blocked server-side.',
                            )
                        }}
                    </p>
                </section>

                <section class="mt-8 space-y-4 border-t border-border/70 pt-6">
                    <div>
                        <h2 class="text-sm font-semibold">
                            {{ t('Observability (Langfuse)') }}
                        </h2>
                        <p class="mt-0.5 text-xs text-muted-foreground">
                            {{
                                t(
                                    'Trace General Chat, Ctrl+K assist, and agent tools. Open the same Langfuse region as your Base URL (US projects use https://us.cloud.langfuse.com). Leave secret blank to keep the existing key.',
                                )
                            }}
                        </p>
                    </div>

                    <div class="flex items-start justify-between gap-4">
                        <div class="min-w-0 space-y-0.5">
                            <h3 class="text-sm font-medium">
                                {{ t('Enable Langfuse') }}
                            </h3>
                            <p class="text-xs text-muted-foreground">
                                {{
                                    props.settings?.langfuse_public_key_set &&
                                    props.settings?.langfuse_secret_key_set
                                        ? t(
                                              'Keys are saved. Save settings, then use Test connection or send a chat message.',
                                          )
                                        : t(
                                              'Add public and secret keys from your Langfuse project, then Save.',
                                          )
                                }}
                            </p>
                        </div>
                        <button
                            type="button"
                            role="switch"
                            :aria-checked="state.langfuse_enabled"
                            :class="[
                                switchClass,
                                switchTone(state.langfuse_enabled),
                            ]"
                            :disabled="!state.enabled"
                            @click="toggleLangfuseEnabled"
                        >
                            <span
                                class="size-5 rounded-full transition"
                                :class="switchKnob(state.langfuse_enabled)"
                            />
                            <span
                                class="pe-2 text-xs font-semibold tracking-tight"
                            >
                                {{
                                    state.langfuse_enabled ? t('On') : t('Off')
                                }}
                            </span>
                        </button>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="grid gap-1.5">
                            <Label for="langfuse_public_key">{{
                                t('Public key')
                            }}</Label>
                            <Input
                                id="langfuse_public_key"
                                v-model="state.langfuse_public_key"
                                type="text"
                                autocomplete="off"
                                :placeholder="
                                    props.settings?.langfuse_public_key_set
                                        ? t('Saved — enter to replace')
                                        : 'pk-lf-…'
                                "
                                :disabled="!state.langfuse_enabled"
                            />
                            <InputError
                                :message="form.errors.langfuse_public_key"
                            />
                        </div>
                        <div class="grid gap-1.5">
                            <Label for="langfuse_secret_key">{{
                                t('Secret key')
                            }}</Label>
                            <Input
                                id="langfuse_secret_key"
                                v-model="state.langfuse_secret_key"
                                type="password"
                                autocomplete="new-password"
                                :placeholder="
                                    props.settings?.langfuse_secret_key_set
                                        ? t('Saved — enter to replace')
                                        : 'sk-lf-…'
                                "
                                :disabled="!state.langfuse_enabled"
                            />
                            <InputError
                                :message="form.errors.langfuse_secret_key"
                            />
                        </div>
                        <div class="grid gap-1.5 sm:col-span-2">
                            <Label for="langfuse_base_url">{{
                                t('Base URL')
                            }}</Label>
                            <Input
                                id="langfuse_base_url"
                                v-model="state.langfuse_base_url"
                                type="url"
                                autocomplete="off"
                                placeholder="https://us.cloud.langfuse.com"
                                :disabled="!state.langfuse_enabled"
                            />
                            <p class="text-xs text-muted-foreground">
                                {{
                                    t(
                                        'EU cloud: https://cloud.langfuse.com — US cloud: https://us.cloud.langfuse.com — or your self-hosted host.',
                                    )
                                }}
                            </p>
                            <InputError
                                :message="form.errors.langfuse_base_url"
                            />
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <Button
                            type="button"
                            variant="outline"
                            :disabled="
                                !state.enabled ||
                                !state.langfuse_enabled ||
                                langfuseTestBusy ||
                                !(
                                    props.settings?.langfuse_public_key_set &&
                                    props.settings?.langfuse_secret_key_set
                                )
                            "
                            @click="testLangfuseConnection"
                        >
                            {{
                                langfuseTestBusy
                                    ? t('Testing…')
                                    : t('Test connection')
                            }}
                        </Button>
                        <p
                            v-if="langfuseTestMessage"
                            class="text-xs"
                            :class="
                                langfuseTestOk
                                    ? 'text-emerald-700 dark:text-emerald-400'
                                    : 'text-destructive'
                            "
                        >
                            {{ langfuseTestMessage }}
                        </p>
                    </div>
                </section>
            </template>

            <div
                class="sticky bottom-0 z-10 -mx-1 flex justify-end border-t border-border/70 bg-background/95 px-1 py-3 backdrop-blur supports-[backdrop-filter]:bg-background/80"
            >
                <Button type="submit" :disabled="form.processing">
                    {{ t('Save') }}
                </Button>
            </div>
        </form>

        <template v-else-if="tab === 'logs' && canViewLogs && logs">
            <div class="space-y-5">
                <div
                    class="flex flex-wrap gap-x-10 gap-y-4 border-b border-border/70 pb-4"
                >
                    <div>
                        <p class="text-xs text-muted-foreground">
                            {{ t('Today') }}
                        </p>
                        <p
                            class="mt-1 text-lg font-semibold tracking-tight tabular-nums"
                        >
                            {{ formatNumber(summary?.today_tokens ?? 0) }}
                        </p>
                        <p class="text-xs text-muted-foreground">
                            {{ formatCost(summary?.today_cost_usd ?? 0) }}
                        </p>
                    </div>
                    <div>
                        <p class="text-xs text-muted-foreground">
                            {{ t('This month') }}
                        </p>
                        <p
                            class="mt-1 text-lg font-semibold tracking-tight tabular-nums"
                        >
                            {{ formatNumber(summary?.month_tokens ?? 0) }}
                        </p>
                        <p class="text-xs text-muted-foreground">
                            {{ formatCost(summary?.month_cost_usd ?? 0) }}
                        </p>
                    </div>
                    <div>
                        <p class="text-xs text-muted-foreground">
                            {{ t('Matching requests') }}
                        </p>
                        <p
                            class="mt-1 text-lg font-semibold tracking-tight tabular-nums"
                        >
                            {{ formatNumber(summary?.requests ?? 0) }}
                        </p>
                        <p class="text-xs text-muted-foreground">
                            {{ formatNumber(summary?.success ?? 0) }}
                            {{ t('ok') }}
                            ·
                            {{ formatNumber(summary?.failed ?? 0) }}
                            {{ t('failed') }}
                        </p>
                    </div>
                </div>

                <MisFilterBar
                    :apply-label="t('Search')"
                    :clear-label="t('Clear')"
                    @apply="applyFilters"
                    @clear="clearFilters"
                >
                    <template #actions>
                        <Button type="submit">{{ t('Search') }}</Button>
                        <Button
                            v-if="hasActiveFilters"
                            type="button"
                            variant="outline"
                            @click="clearFilters"
                        >
                            {{ t('Clear') }}
                        </Button>
                    </template>
                    <div class="grid min-w-[14rem] flex-1 gap-1.5">
                        <label
                            class="text-sm text-muted-foreground"
                            for="ai-log-search"
                        >
                            {{ t('Search') }}
                        </label>
                        <MisSearchInput
                            id="ai-log-search"
                            v-model="search"
                            :placeholder="t('User, model, prompt…')"
                            :aria-label="t('Search')"
                        />
                    </div>
                    <div class="grid min-w-[8.5rem] gap-1.5">
                        <label
                            class="text-sm text-muted-foreground"
                            for="ai-log-status"
                        >
                            {{ t('Status') }}
                        </label>
                        <select
                            id="ai-log-status"
                            v-model="statusFilter"
                            class="h-9 rounded-md border bg-background px-3 text-sm"
                        >
                            <option value="">{{ t('All') }}</option>
                            <option
                                v-for="item in statuses || []"
                                :key="item"
                                :value="item"
                            >
                                {{ statusLabel(item) }}
                            </option>
                        </select>
                    </div>
                    <div class="grid min-w-[8.5rem] gap-1.5">
                        <label
                            class="text-sm text-muted-foreground"
                            for="ai-log-action"
                        >
                            {{ t('Action') }}
                        </label>
                        <select
                            id="ai-log-action"
                            v-model="actionFilter"
                            class="h-9 rounded-md border bg-background px-3 text-sm"
                        >
                            <option value="">{{ t('All') }}</option>
                            <option
                                v-for="item in actions"
                                :key="item.id"
                                :value="item.id"
                            >
                                {{ t(item.label) }}
                            </option>
                        </select>
                    </div>
                </MisFilterBar>

                <MisEmptyState
                    v-if="!hasLogs"
                    :title="t('No AI usage yet')"
                    :description="
                        hasActiveFilters
                            ? t(
                                  'Try clearing filters or broadening your search.',
                              )
                            : t(
                                  'Requests will appear here once AI assist is used.',
                              )
                    "
                />

                <div v-else class="space-y-2">
                    <MisExpandableRow
                        v-for="log in logs.data"
                        :key="log.id"
                        :open="expandedLogId === log.id"
                        @update:open="
                            (open) => (expandedLogId = open ? log.id : null)
                        "
                    >
                        <div class="min-w-0 space-y-2">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="font-medium">
                                    {{ log.user?.name || t('System') }}
                                </span>
                                <Badge variant="secondary">
                                    {{ actionLabel(log.action) }}
                                </Badge>
                                <Badge :variant="statusVariant(log.status)">
                                    {{ statusLabel(log.status) }}
                                </Badge>
                            </div>

                            <div
                                class="flex flex-wrap gap-x-4 gap-y-1 text-sm text-muted-foreground"
                            >
                                <span>{{ formatDateTime(log.logged_at) }}</span>
                                <span class="tabular-nums">
                                    {{ formatNumber(log.total_tokens) }}
                                    {{ t('tokens') }}
                                </span>
                                <span class="tabular-nums">
                                    {{ formatCost(log.estimated_cost_usd) }}
                                </span>
                                <span v-if="log.model_name">
                                    {{ log.model_name }}
                                </span>
                                <span
                                    v-if="log.duration_ms != null"
                                    class="tabular-nums"
                                >
                                    {{ formatDuration(log.duration_ms) }}
                                </span>
                            </div>

                            <p
                                v-if="logSnippet(log)"
                                class="line-clamp-1 text-sm"
                                :class="
                                    log.error
                                        ? 'text-destructive'
                                        : 'text-muted-foreground'
                                "
                            >
                                {{ logSnippet(log) }}
                            </p>
                        </div>

                        <template #detail>
                            <div class="space-y-4 text-sm">
                                <div
                                    class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4"
                                >
                                    <div class="min-w-0">
                                        <p
                                            class="text-xs font-medium tracking-wide text-muted-foreground uppercase"
                                        >
                                            {{ t('User') }}
                                        </p>
                                        <p class="mt-0.5">
                                            {{ log.user?.name || t('System') }}
                                        </p>
                                        <p
                                            v-if="log.user?.email"
                                            class="truncate text-muted-foreground"
                                        >
                                            {{ log.user.email }}
                                        </p>
                                    </div>
                                    <div class="min-w-0">
                                        <p
                                            class="text-xs font-medium tracking-wide text-muted-foreground uppercase"
                                        >
                                            {{ t('Model') }}
                                        </p>
                                        <p class="mt-0.5">
                                            {{ log.model_name || '—' }}
                                        </p>
                                        <p
                                            v-if="log.provider"
                                            class="text-muted-foreground"
                                        >
                                            {{ log.provider }}
                                        </p>
                                    </div>
                                    <div class="min-w-0">
                                        <p
                                            class="text-xs font-medium tracking-wide text-muted-foreground uppercase"
                                        >
                                            {{ t('Tokens') }}
                                        </p>
                                        <p class="mt-0.5 tabular-nums">
                                            {{ formatNumber(log.total_tokens) }}
                                        </p>
                                        <p
                                            class="text-muted-foreground tabular-nums"
                                        >
                                            {{
                                                formatNumber(log.prompt_tokens)
                                            }}
                                            /
                                            {{
                                                formatNumber(
                                                    log.completion_tokens,
                                                )
                                            }}
                                        </p>
                                    </div>
                                    <div class="min-w-0">
                                        <p
                                            class="text-xs font-medium tracking-wide text-muted-foreground uppercase"
                                        >
                                            {{ t('Cost') }}
                                        </p>
                                        <p class="mt-0.5 tabular-nums">
                                            {{
                                                formatCost(
                                                    log.estimated_cost_usd,
                                                )
                                            }}
                                        </p>
                                        <p
                                            class="text-muted-foreground tabular-nums"
                                        >
                                            {{
                                                formatDuration(log.duration_ms)
                                            }}
                                        </p>
                                    </div>
                                </div>

                                <div
                                    v-if="log.error"
                                    class="rounded-md border border-destructive/20 bg-destructive/5 px-3 py-2 text-destructive"
                                >
                                    <p
                                        class="text-xs font-medium tracking-wide uppercase"
                                    >
                                        {{ t('Error') }}
                                    </p>
                                    <p
                                        class="mt-1 break-words whitespace-pre-wrap"
                                    >
                                        {{ log.error }}
                                    </p>
                                </div>

                                <div class="grid gap-3 lg:grid-cols-3">
                                    <div class="min-w-0">
                                        <p
                                            class="text-xs font-medium tracking-wide text-muted-foreground uppercase"
                                        >
                                            {{ t('Instruction') }}
                                        </p>
                                        <p
                                            class="mt-1 break-words whitespace-pre-wrap text-muted-foreground"
                                        >
                                            {{
                                                previewText(
                                                    log.instruction_preview,
                                                )
                                            }}
                                        </p>
                                    </div>
                                    <div class="min-w-0">
                                        <p
                                            class="text-xs font-medium tracking-wide text-muted-foreground uppercase"
                                        >
                                            {{ t('Input') }}
                                        </p>
                                        <p
                                            class="mt-1 break-words whitespace-pre-wrap text-muted-foreground"
                                        >
                                            {{ previewText(log.input_preview) }}
                                        </p>
                                    </div>
                                    <div class="min-w-0">
                                        <p
                                            class="text-xs font-medium tracking-wide text-muted-foreground uppercase"
                                        >
                                            {{ t('Output') }}
                                        </p>
                                        <p
                                            class="mt-1 break-words whitespace-pre-wrap text-muted-foreground"
                                        >
                                            {{
                                                previewText(log.output_preview)
                                            }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </MisExpandableRow>

                    <MisPagination :pagination="logs" />
                </div>
            </div>
        </template>
    </div>
</template>
