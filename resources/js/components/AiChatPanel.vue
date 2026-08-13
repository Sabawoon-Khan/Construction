<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    ArrowLeft,
    Bot,
    CheckCircle2,
    CircleAlert,
    ClipboardPlus,
    FileText,
    History,
    List,
    LoaderCircle,
    MessageSquarePlus,
    Paperclip,
    Plus,
    Send,
    Sparkles,
    Trash2,
    X,
    XCircle,
} from '@lucide/vue';
import { computed, nextTick, ref, watch } from 'vue';
import AiChatMessageContent from '@/components/AiChatMessageContent.vue';
import { Button } from '@/components/ui/button';
import {
    Sheet,
    SheetContent,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import {
    useAiChat,
    type AiChatPendingApproval,
} from '@/composables/useAiChat';
import { useTranslations } from '@/composables/useTranslations';

const page = usePage();
const { t } = useTranslations();
const {
    open,
    view,
    sending,
    loadingHistory,
    messages,
    conversations,
    draft,
    pendingFiles,
    available,
    closeChat,
    showHistory,
    showChat,
    resetConversation,
    openConversation,
    deleteConversation,
    addFiles,
    removePendingFile,
    sendMessage,
    approveAll,
    rejectAll,
    conversationId,
    CHAT_FILE_ACCEPT,
} = useAiChat();

const listEl = ref<HTMLElement | null>(null);
const inputEl = ref<HTMLTextAreaElement | null>(null);
const fileInputEl = ref<HTMLInputElement | null>(null);

const sheetSide = computed<'left' | 'right'>(() =>
    page.props.dir === 'rtl' ? 'left' : 'right',
);

const quickActions = computed(() => [
    {
        id: 'create-report',
        label: t('Create report'),
        prompt: t(
            'Create a field report titled Site visit in Kabul with location Kabul and status draft',
        ),
        icon: ClipboardPlus,
    },
    {
        id: 'list-resources',
        label: t('What can you do?'),
        prompt: t(
            'List the resources you can work on and what I am allowed to create, update, or delete',
        ),
        icon: List,
    },
    {
        id: 'analytics',
        label: t('Ask a report'),
        prompt: t(
            'How many field reports do I have, grouped by status?',
        ),
        icon: Sparkles,
    },
    {
        id: 'from-file',
        label: t('From a file'),
        prompt: t(
            'Summarize the attached file and extract any people or records I could save',
        ),
        icon: FileText,
    },
]);

const headerTitle = computed(() =>
    view.value === 'history' ? t('Chat history') : t('General Chat'),
);

watch(
    () => [messages.value.length, sending.value, view.value] as const,
    async () => {
        await nextTick();
        if (view.value === 'chat' && listEl.value) {
            listEl.value.scrollTop = listEl.value.scrollHeight;
        }
    },
);

watch(open, async (isOpen) => {
    if (isOpen) {
        await nextTick();
        if (view.value === 'chat') {
            inputEl.value?.focus();
        }
    }
});

function onKeydown(event: KeyboardEvent): void {
    if (event.key === 'Enter' && !event.shiftKey) {
        event.preventDefault();
        void sendMessage();
    }
}

function openFilePicker(): void {
    fileInputEl.value?.click();
}

function onFilesSelected(event: Event): void {
    const input = event.target as HTMLInputElement | null;
    addFiles(input?.files ?? null);
    if (input) {
        input.value = '';
    }
}

function canSend(): boolean {
    return Boolean(draft.value.trim() || pendingFiles.value.length > 0);
}

function toolFriendlyName(tool: string): string {
    const map: Record<string, string> = {
        DeleteRecord: t('Delete record'),
        CreateRecord: t('Create record'),
        UpdateRecord: t('Update record'),
        ListRecords: t('List records'),
        GetRecord: t('Get record'),
        ListResources: t('List sections'),
        ListPermissions: t('List permissions'),
        RunAnalyticsQuery: t('Analytics'),
    };

    return map[tool] ?? tool;
}

function approvalHeadline(approval: AiChatPendingApproval): string {
    const resource = String(approval.arguments.resource ?? 'record');
    const id = approval.arguments.id;

    if (approval.tool === 'DeleteRecord') {
        return id != null
            ? t('Delete :resource #:id?', {
                  resource,
                  id: String(id),
              })
            : t('Delete this record?');
    }

    return approval.reason || toolFriendlyName(approval.tool);
}

function approvalSubtitle(approval: AiChatPendingApproval): string {
    if (approval.tool === 'DeleteRecord') {
        return t(
            'This needs your approval. It cannot be undone from chat.',
        );
    }

    return approval.reason || '';
}

function stepStatusIcon(status: string) {
    if (status === 'done') {
        return CheckCircle2;
    }
    if (status === 'failed' || status === 'denied') {
        return XCircle;
    }
    if (status === 'awaiting_approval') {
        return CircleAlert;
    }

    return LoaderCircle;
}

function formatWhen(value: string | null): string {
    if (!value) {
        return '';
    }

    const date = new Date(value);
    if (Number.isNaN(date.getTime())) {
        return '';
    }

    const diffMs = Date.now() - date.getTime();
    const minute = 60_000;
    const hour = 60 * minute;
    const day = 24 * hour;

    if (diffMs < minute) {
        return t('Just now');
    }
    if (diffMs < hour) {
        return t(':count m ago', {
            count: String(Math.max(1, Math.floor(diffMs / minute))),
        });
    }
    if (diffMs < day) {
        return t(':count h ago', {
            count: String(Math.max(1, Math.floor(diffMs / hour))),
        });
    }
    if (diffMs < 7 * day) {
        return t(':count d ago', {
            count: String(Math.max(1, Math.floor(diffMs / day))),
        });
    }

    return date.toLocaleDateString(undefined, {
        month: 'short',
        day: 'numeric',
    });
}

async function confirmDelete(id: string): Promise<void> {
    if (!window.confirm(t('Delete this chat from history?'))) {
        return;
    }
    await deleteConversation(id);
}
</script>

<template>
    <Sheet
        :open="available && open"
        @update:open="(value) => (!value ? closeChat() : undefined)"
    >
        <SheetContent
            :side="sheetSide"
            class="flex w-full max-w-[24rem] flex-col gap-0 border-border/60 bg-[linear-gradient(180deg,color-mix(in_oklab,var(--school-navy)_4%,var(--background))_0%,var(--background)_7.5rem)] p-0 shadow-2xl sm:max-w-[24rem]"
        >
            <SheetHeader class="relative border-b border-border/60 px-3.5 py-3 text-start">
                <div
                    class="pointer-events-none absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-[var(--school-navy)]/35 to-transparent"
                />
                <div class="flex items-center justify-between gap-2 pe-8">
                    <div class="flex min-w-0 items-center gap-2.5">
                        <Button
                            v-if="view === 'history'"
                            type="button"
                            variant="ghost"
                            size="icon"
                            class="size-8 shrink-0 rounded-full"
                            :aria-label="t('Back')"
                            @click="showChat"
                        >
                            <ArrowLeft class="size-4 rtl:rotate-180" />
                        </Button>
                        <div
                            v-else
                            class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-[var(--school-navy)] text-white shadow-[0_10px_24px_-12px_rgba(15,23,42,0.55)]"
                        >
                            <Sparkles class="size-4" :stroke-width="1.75" />
                        </div>
                        <SheetTitle class="truncate text-[15px] font-semibold tracking-tight">
                            {{ headerTitle }}
                        </SheetTitle>
                    </div>

                    <div class="flex shrink-0 items-center gap-0.5">
                        <Button
                            v-if="view === 'chat'"
                            type="button"
                            variant="ghost"
                            size="icon"
                            class="size-8 rounded-full text-muted-foreground hover:text-foreground"
                            :title="t('History')"
                            :aria-label="t('History')"
                            @click="showHistory"
                        >
                            <History class="size-3.5" />
                        </Button>
                        <Button
                            type="button"
                            variant="ghost"
                            size="icon"
                            class="size-8 rounded-full text-muted-foreground hover:text-foreground"
                            :title="t('New chat')"
                            :aria-label="t('New chat')"
                            @click="resetConversation"
                        >
                            <Plus class="size-3.5" />
                        </Button>
                    </div>
                </div>
            </SheetHeader>

            <!-- History -->
            <div
                v-if="view === 'history'"
                class="flex min-h-0 flex-1 flex-col overflow-y-auto px-3 py-3"
            >
                <div
                    v-if="loadingHistory && conversations.length === 0"
                    class="flex flex-1 items-center justify-center gap-2 text-sm text-muted-foreground"
                >
                    <LoaderCircle class="size-4 animate-spin" />
                    {{ t('Loading…') }}
                </div>

                <div
                    v-else-if="conversations.length === 0"
                    class="flex flex-1 flex-col items-center justify-center gap-3 px-4 text-center"
                >
                    <div
                        class="flex size-12 items-center justify-center rounded-2xl border border-border/70 bg-background text-[var(--school-navy)]"
                    >
                        <History class="size-5" />
                    </div>
                    <div class="space-y-1">
                        <p class="text-sm font-semibold">{{ t('No chats yet') }}</p>
                        <p class="text-[12px] text-muted-foreground">
                            {{ t('Start a task and it will show up here.') }}
                        </p>
                    </div>
                    <Button
                        type="button"
                        size="sm"
                        class="mt-1 rounded-full"
                        @click="resetConversation"
                    >
                        <MessageSquarePlus class="size-3.5" />
                        {{ t('New chat') }}
                    </Button>
                </div>

                <div v-else class="space-y-1.5">
                    <div
                        v-for="item in conversations"
                        :key="item.id"
                        class="group flex w-full items-start gap-2 rounded-2xl border px-3 py-2.5 transition hover:border-border/70 hover:bg-background/80"
                        :class="
                            item.id === conversationId
                                ? 'border-border/70 bg-background/80'
                                : 'border-transparent'
                        "
                    >
                        <button
                            type="button"
                            class="flex min-w-0 flex-1 items-start gap-2 text-start"
                            @click="openConversation(item.id)"
                        >
                            <span
                                class="mt-0.5 flex size-8 shrink-0 items-center justify-center rounded-xl bg-[var(--school-navy)]/8 text-[var(--school-navy)]"
                            >
                                <Bot class="size-3.5" />
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="flex items-center justify-between gap-2">
                                    <span class="truncate text-[13px] font-semibold text-foreground">
                                        {{ item.title }}
                                    </span>
                                    <span class="shrink-0 text-[10px] text-muted-foreground">
                                        {{ formatWhen(item.updated_at) }}
                                    </span>
                                </span>
                                <span
                                    v-if="item.preview"
                                    class="mt-0.5 line-clamp-2 text-[12px] leading-snug text-muted-foreground"
                                >
                                    {{ item.preview }}
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

            <!-- Chat -->
            <template v-else>
                <div
                    v-if="messages.length === 0"
                    class="border-b border-border/50 px-3.5 py-3"
                >
                    <p
                        class="mb-2 text-[11px] font-medium uppercase tracking-[0.08em] text-muted-foreground"
                    >
                        {{ t('Quick actions') }}
                    </p>
                    <div class="flex flex-wrap gap-1.5">
                        <button
                            v-for="action in quickActions"
                            :key="action.id"
                            type="button"
                            class="inline-flex items-center gap-1.5 rounded-full border border-border/70 bg-background/80 px-3 py-1.5 text-[12px] font-medium text-foreground/90 shadow-sm transition hover:border-[var(--school-navy)]/25 hover:bg-[var(--school-navy)]/[0.04]"
                            :disabled="sending"
                            @click="sendMessage(action.prompt)"
                        >
                            <component
                                :is="action.icon"
                                class="size-3.5 text-[var(--school-navy)]"
                            />
                            {{ action.label }}
                        </button>
                    </div>
                </div>

                <div
                    ref="listEl"
                    class="flex min-h-0 flex-1 flex-col gap-3.5 overflow-y-auto px-4 py-4"
                >
                    <div
                        v-if="messages.length === 0"
                        class="flex flex-1 flex-col items-center justify-center gap-3 py-6 text-center"
                    >
                        <div
                            class="flex size-12 items-center justify-center rounded-2xl border border-border/70 bg-background/80 text-[var(--school-navy)] shadow-sm"
                        >
                            <Sparkles class="size-5" :stroke-width="1.75" />
                        </div>
                        <p class="text-sm font-semibold text-foreground">
                            {{ t('What should we do?') }}
                        </p>
                        <p
                            class="max-w-xs text-[12px] leading-relaxed text-muted-foreground"
                        >
                            {{
                                t(
                                    'Ask across any registered section — attach a PDF or image to summarize or extract data, create users or roles, or run read-only reports when analytics is on.',
                                )
                            }}
                        </p>
                    </div>

                    <template v-else>
                        <div
                            v-for="message in messages"
                            :key="message.id"
                            class="flex flex-col gap-2"
                            :class="
                                message.role === 'user'
                                    ? 'items-end'
                                    : 'items-start'
                            "
                        >
                            <div
                                v-if="message.role === 'assistant'"
                                class="mb-0.5 flex items-center gap-1.5 text-[11px] font-medium text-muted-foreground"
                            >
                                <span
                                    class="flex size-5 items-center justify-center rounded-full bg-[var(--school-navy)]/10 text-[var(--school-navy)]"
                                >
                                    <Bot class="size-3" />
                                </span>
                                {{ t('Agent') }}
                            </div>

                            <div
                                class="max-w-[92%] px-3.5 py-2.5 text-[13px] leading-relaxed"
                                :class="{
                                    'rounded-2xl rounded-ee-md bg-[var(--school-navy)] text-white shadow-[0_12px_28px_-18px_rgba(15,23,42,0.65)]':
                                        message.role === 'user',
                                    'rounded-2xl rounded-es-md border border-border/70 bg-background/90 text-foreground shadow-sm':
                                        message.role === 'assistant',
                                    'rounded-2xl border border-destructive/25 bg-destructive/[0.06] text-destructive':
                                        message.role === 'system',
                                }"
                            >
                                <div
                                    v-if="message.attachments?.length"
                                    class="mb-2 flex flex-wrap gap-1.5"
                                >
                                    <span
                                        v-for="(file, fileIndex) in message.attachments"
                                        :key="`${message.id}-file-${fileIndex}`"
                                        class="inline-flex max-w-full items-center gap-1 truncate rounded-full px-2 py-0.5 text-[10px] font-medium"
                                        :class="
                                            message.role === 'user'
                                                ? 'bg-white/15 text-white'
                                                : 'bg-muted text-muted-foreground'
                                        "
                                    >
                                        <Paperclip class="size-2.5 shrink-0" />
                                        <span class="truncate">{{
                                            file.name
                                        }}</span>
                                    </span>
                                </div>
                                <AiChatMessageContent
                                    v-if="message.content"
                                    :content="message.content"
                                    :tone="message.role"
                                    @navigate="closeChat"
                                />
                                <template v-else>
                                    {{ t('Waiting for approval…') }}
                                </template>
                            </div>

                            <div
                                v-if="message.links?.length"
                                class="flex max-w-[92%] flex-wrap gap-1.5"
                            >
                                <Link
                                    v-for="(link, linkIndex) in message.links"
                                    :key="`${message.id}-link-${linkIndex}`"
                                    :href="link.href"
                                    class="inline-flex items-center rounded-full border border-[var(--school-navy)]/20 bg-[var(--school-navy)]/5 px-3 py-1 text-[11px] font-semibold text-[var(--school-navy)] transition hover:bg-[var(--school-navy)]/10"
                                    @click="closeChat"
                                >
                                    {{ link.label }}
                                </Link>
                            </div>

                            <ol
                                v-if="message.steps?.length"
                                class="w-full max-w-[92%] space-y-1.5 rounded-2xl border border-border/60 bg-muted/20 px-3 py-2.5"
                            >
                                <li
                                    class="text-[10px] font-semibold tracking-wide text-muted-foreground uppercase"
                                >
                                    {{ t('Steps') }}
                                </li>
                                <li
                                    v-for="(step, stepIndex) in message.steps"
                                    :key="`${message.id}-step-${stepIndex}`"
                                    class="flex items-start gap-2 text-[12px] leading-snug"
                                >
                                    <component
                                        :is="stepStatusIcon(step.status)"
                                        class="mt-0.5 size-3.5 shrink-0"
                                        :class="{
                                            'text-emerald-600 dark:text-emerald-400':
                                                step.status === 'done',
                                            'text-destructive':
                                                step.status === 'failed' ||
                                                step.status === 'denied',
                                            'text-amber-600 dark:text-amber-400':
                                                step.status ===
                                                'awaiting_approval',
                                            'animate-spin text-muted-foreground':
                                                step.status === 'awaiting',
                                            'text-muted-foreground': ![
                                                'done',
                                                'failed',
                                                'denied',
                                                'awaiting_approval',
                                                'awaiting',
                                            ].includes(step.status),
                                        }"
                                    />
                                    <div class="min-w-0">
                                        <p class="font-medium text-foreground">
                                            {{ step.summary || step.label }}
                                        </p>
                                        <p
                                            v-if="
                                                step.detail &&
                                                step.status !== 'done'
                                            "
                                            class="mt-0.5 truncate text-[11px] text-muted-foreground"
                                        >
                                            {{ step.detail }}
                                        </p>
                                    </div>
                                </li>
                            </ol>

                            <div
                                v-if="message.pendingApprovals?.length"
                                class="w-full max-w-[92%] overflow-hidden rounded-2xl border border-destructive/20 bg-destructive/[0.04] shadow-sm"
                            >
                                <div
                                    class="flex items-center gap-2 border-b border-destructive/10 px-3.5 py-2.5"
                                >
                                    <span
                                        class="flex size-7 items-center justify-center rounded-full bg-destructive/10 text-destructive"
                                    >
                                        <Trash2 class="size-3.5" />
                                    </span>
                                    <div class="min-w-0">
                                        <p
                                            class="text-[12px] font-semibold text-foreground"
                                        >
                                            {{ t('Confirm delete') }}
                                        </p>
                                        <p
                                            class="text-[11px] text-muted-foreground"
                                        >
                                            {{
                                                t(
                                                    'Review carefully before approving.',
                                                )
                                            }}
                                        </p>
                                    </div>
                                </div>
                                <div class="space-y-3 p-3.5">
                                    <div
                                        v-for="approval in message.pendingApprovals"
                                        :key="approval.id"
                                        class="rounded-xl border border-border/70 bg-background px-3.5 py-3"
                                    >
                                        <p
                                            class="text-[13px] font-semibold text-foreground"
                                        >
                                            {{ approvalHeadline(approval) }}
                                        </p>
                                        <p
                                            class="mt-1 text-[12px] leading-snug text-muted-foreground"
                                        >
                                            {{ approvalSubtitle(approval) }}
                                        </p>
                                        <dl
                                            v-if="
                                                approval.arguments.resource ||
                                                approval.arguments.id
                                            "
                                            class="mt-2.5 grid grid-cols-2 gap-2 text-[11px]"
                                        >
                                            <div
                                                v-if="
                                                    approval.arguments.resource
                                                "
                                            >
                                                <dt
                                                    class="text-muted-foreground"
                                                >
                                                    {{ t('Section') }}
                                                </dt>
                                                <dd
                                                    class="font-medium text-foreground"
                                                >
                                                    {{
                                                        approval.arguments
                                                            .resource
                                                    }}
                                                </dd>
                                            </div>
                                            <div
                                                v-if="approval.arguments.id"
                                            >
                                                <dt
                                                    class="text-muted-foreground"
                                                >
                                                    {{ t('ID') }}
                                                </dt>
                                                <dd
                                                    class="font-medium text-foreground"
                                                >
                                                    #{{
                                                        approval.arguments.id
                                                    }}
                                                </dd>
                                            </div>
                                        </dl>
                                    </div>
                                    <div class="flex flex-wrap gap-2">
                                        <Button
                                            type="button"
                                            size="sm"
                                            variant="destructive"
                                            class="h-9 rounded-xl px-4"
                                            :disabled="sending"
                                            @click="
                                                approveAll(
                                                    message.pendingApprovals ||
                                                        [],
                                                )
                                            "
                                        >
                                            <Trash2 class="size-3.5" />
                                            {{ t('Delete') }}
                                        </Button>
                                        <Button
                                            type="button"
                                            size="sm"
                                            variant="outline"
                                            class="h-9 rounded-xl px-4"
                                            :disabled="sending"
                                            @click="
                                                rejectAll(
                                                    message.pendingApprovals ||
                                                        [],
                                                )
                                            "
                                        >
                                            {{ t('Keep it') }}
                                        </Button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>

                    <div
                        v-if="sending"
                        class="flex items-center gap-2 text-[12px] text-muted-foreground"
                    >
                        <span
                            class="flex size-7 items-center justify-center rounded-full bg-muted"
                        >
                            <LoaderCircle
                                class="size-3.5 animate-spin text-[var(--school-navy)]"
                            />
                        </span>
                        {{ t('Thinking…') }}
                    </div>
                </div>

                <form
                    class="border-t border-border/60 bg-background/80 p-3 backdrop-blur-sm"
                    @submit.prevent="sendMessage()"
                >
                    <input
                        ref="fileInputEl"
                        type="file"
                        class="hidden"
                        multiple
                        :accept="CHAT_FILE_ACCEPT"
                        @change="onFilesSelected"
                    />

                    <div
                        v-if="pendingFiles.length"
                        class="mb-2 flex flex-wrap gap-1.5"
                    >
                        <span
                            v-for="(file, index) in pendingFiles"
                            :key="`${file.name}-${index}`"
                            class="inline-flex max-w-full items-center gap-1 rounded-full border border-border/70 bg-muted/40 py-1 ps-2.5 pe-1 text-[11px]"
                        >
                            <FileText class="size-3 shrink-0 text-muted-foreground" />
                            <span class="truncate font-medium">{{
                                file.name
                            }}</span>
                            <button
                                type="button"
                                class="rounded-full p-0.5 text-muted-foreground hover:bg-background hover:text-foreground"
                                :aria-label="t('Remove')"
                                @click="removePendingFile(index)"
                            >
                                <X class="size-3" />
                            </button>
                        </span>
                    </div>

                    <div
                        class="flex items-end gap-2 rounded-2xl border border-border/80 bg-background p-2 shadow-[0_10px_30px_-24px_rgba(15,23,42,0.45)] transition focus-within:border-[var(--school-navy)]/30 focus-within:ring-2 focus-within:ring-[var(--school-navy)]/10"
                    >
                        <Button
                            type="button"
                            size="icon"
                            variant="ghost"
                            class="size-9 shrink-0 rounded-xl text-muted-foreground hover:text-foreground"
                            :disabled="sending"
                            :aria-label="t('Attach file')"
                            @click="openFilePicker"
                        >
                            <Paperclip class="size-3.5" />
                        </Button>
                        <textarea
                            ref="inputEl"
                            v-model="draft"
                            rows="2"
                            class="max-h-32 min-h-[2.75rem] flex-1 resize-none bg-transparent px-1 py-1.5 text-[13px] leading-relaxed outline-none placeholder:text-muted-foreground/80"
                            :placeholder="
                                t('Message or attach a PDF / image…')
                            "
                            :disabled="sending"
                            @keydown="onKeydown"
                        />
                        <Button
                            type="submit"
                            size="icon"
                            class="size-9 shrink-0 rounded-xl bg-[var(--school-navy)] text-white hover:bg-[var(--school-navy)]/90"
                            :disabled="sending || !canSend()"
                            :aria-label="t('Send')"
                        >
                            <Send class="size-3.5 rtl:-scale-x-100" />
                        </Button>
                    </div>
                    <p class="mt-1.5 px-1 text-[10px] text-muted-foreground">
                        {{
                            t(
                                'PDF, images, TXT, CSV — up to 5 files. Ask to summarize, extract, or save into the system.',
                            )
                        }}
                    </p>
                </form>
            </template>
        </SheetContent>
    </Sheet>
</template>
