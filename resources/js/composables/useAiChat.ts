import { usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import type { AiClientSettings } from '@/composables/useAiAssist';
import { usePermissions } from '@/composables/usePermissions';

export type AiChatPendingApproval = {
    id: string;
    tool: string;
    arguments: Record<string, unknown>;
    reason: string | null;
};

export type AiChatStep = {
    type: string;
    tool: string;
    label: string;
    summary: string;
    status: string;
    detail: string | null;
};

export type AiChatLink = {
    label: string;
    href: string;
};

export type AiChatMessage = {
    id: string;
    role: 'user' | 'assistant' | 'system';
    content: string;
    pendingApprovals?: AiChatPendingApproval[];
    steps?: AiChatStep[];
    links?: AiChatLink[];
    attachments?: Array<{ name: string; mime?: string; size?: number }>;
};

export type AiChatConversationSummary = {
    id: string;
    title: string;
    updated_at: string | null;
    preview: string | null;
};

type ChatResponse = {
    conversation_id: string | null;
    text: string;
    pending_approvals: AiChatPendingApproval[];
    steps?: AiChatStep[];
    links?: AiChatLink[];
    usage?: {
        prompt_tokens: number;
        completion_tokens: number;
        total_tokens: number;
    };
    model?: string | null;
    message?: string;
};

type ConversationDetailResponse = {
    conversation_id: string;
    title: string;
    messages: Array<{
        id: string;
        role: string;
        content: string;
        pending_approvals?: AiChatPendingApproval[];
        steps?: AiChatStep[];
        links?: AiChatLink[];
    }>;
    message?: string;
};

const open = ref(false);
const view = ref<'chat' | 'history'>('chat');
const sending = ref(false);
const loadingHistory = ref(false);
const conversationId = ref<string | null>(null);
const messages = ref<AiChatMessage[]>([]);
const conversations = ref<AiChatConversationSummary[]>([]);
const draft = ref('');
const pendingFiles = ref<File[]>([]);
const error = ref<string | null>(null);

const MAX_CHAT_FILES = 5;
const MAX_CHAT_FILE_KB = 10_240;
const CHAT_FILE_ACCEPT =
    '.pdf,.png,.jpg,.jpeg,.webp,.gif,.txt,.csv,application/pdf,image/*,text/plain,text/csv';

let messageSeq = 0;

function nextId(prefix: string): string {
    messageSeq += 1;

    return `${prefix}-${messageSeq}-${Date.now()}`;
}

function csrfToken(): string {
    return (
        document
            .querySelector('meta[name="csrf-token"]')
            ?.getAttribute('content') ?? ''
    );
}

async function requestJson<T extends { message?: string }>(
    url: string,
    options: RequestInit = {},
): Promise<T> {
    const isFormData =
        typeof FormData !== 'undefined' && options.body instanceof FormData;

    const response = await fetch(url, {
        ...options,
        headers: {
            Accept: 'application/json',
            'X-CSRF-TOKEN': csrfToken(),
            'X-Requested-With': 'XMLHttpRequest',
            ...(options.body && !isFormData
                ? { 'Content-Type': 'application/json' }
                : {}),
            ...(options.headers ?? {}),
        },
        credentials: 'same-origin',
    });

    const data = (await response.json().catch(() => ({}))) as T;

    if (!response.ok) {
        throw new Error(
            data.message || `Request failed (${response.status})`,
        );
    }

    return data;
}

export function useAiChat() {
    const page = usePage();
    const { can } = usePermissions();

    const aiSettings = computed(
        () => page.props.aiSettings as AiClientSettings | undefined,
    );

    const available = computed(
        () =>
            Boolean(aiSettings.value?.enabled) &&
            Boolean(aiSettings.value?.chat_enabled) &&
            can('ai.chat'),
    );

    function openChat(): void {
        if (!available.value) {
            return;
        }
        open.value = true;
        view.value = 'chat';
        error.value = null;
    }

    function closeChat(): void {
        open.value = false;
    }

    function toggleChat(): void {
        if (open.value) {
            closeChat();
        } else {
            openChat();
        }
    }

    function showHistory(): void {
        view.value = 'history';
        void loadConversations();
    }

    function showChat(): void {
        view.value = 'chat';
    }

    function resetConversation(): void {
        conversationId.value = null;
        messages.value = [];
        draft.value = '';
        pendingFiles.value = [];
        error.value = null;
        view.value = 'chat';
    }

    function addFiles(fileList: FileList | File[] | null): void {
        if (!fileList) {
            return;
        }

        const incoming = Array.from(fileList);
        const next = [...pendingFiles.value];

        for (const file of incoming) {
            if (next.length >= MAX_CHAT_FILES) {
                error.value = `You can attach at most ${MAX_CHAT_FILES} files.`;
                break;
            }
            if (file.size > MAX_CHAT_FILE_KB * 1024) {
                error.value = `${file.name} is too large (max ${MAX_CHAT_FILE_KB} KB).`;
                continue;
            }
            next.push(file);
        }

        pendingFiles.value = next;
    }

    function removePendingFile(index: number): void {
        pendingFiles.value = pendingFiles.value.filter((_, i) => i !== index);
    }

    function clearPendingFiles(): void {
        pendingFiles.value = [];
    }

    async function loadConversations(): Promise<void> {
        if (!available.value) {
            return;
        }

        loadingHistory.value = true;
        error.value = null;

        try {
            const data = await requestJson<{
                conversations: AiChatConversationSummary[];
            }>('/ai/chat/conversations');
            conversations.value = data.conversations ?? [];
        } catch (e) {
            error.value =
                e instanceof Error
                    ? e.message
                    : 'Could not load chat history.';
        } finally {
            loadingHistory.value = false;
        }
    }

    async function openConversation(id: string): Promise<void> {
        if (!available.value || sending.value) {
            return;
        }

        loadingHistory.value = true;
        error.value = null;

        try {
            const data = await requestJson<ConversationDetailResponse>(
                `/ai/chat/conversations/${encodeURIComponent(id)}`,
            );

            conversationId.value = data.conversation_id;
            messages.value = (data.messages ?? [])
                .filter(
                    (message) =>
                        message.role === 'user' || message.role === 'assistant',
                )
                .map((message) => ({
                    id: message.id,
                    role: message.role as 'user' | 'assistant',
                    content: message.content || '',
                    pendingApprovals: message.pending_approvals?.length
                        ? message.pending_approvals
                        : undefined,
                    steps: message.steps?.length ? message.steps : undefined,
                    links: message.links?.length ? message.links : undefined,
                }));
            draft.value = '';
            view.value = 'chat';
        } catch (e) {
            error.value =
                e instanceof Error
                    ? e.message
                    : 'Could not open that conversation.';
        } finally {
            loadingHistory.value = false;
        }
    }

    async function deleteConversation(id: string): Promise<void> {
        if (!available.value) {
            return;
        }

        try {
            await requestJson<{ ok: boolean }>(
                `/ai/chat/conversations/${encodeURIComponent(id)}`,
                { method: 'DELETE' },
            );

            conversations.value = conversations.value.filter(
                (item) => item.id !== id,
            );

            if (conversationId.value === id) {
                resetConversation();
                view.value = 'history';
            }
        } catch (e) {
            error.value =
                e instanceof Error
                    ? e.message
                    : 'Could not delete that conversation.';
        }
    }

    function appendAssistant(data: ChatResponse): void {
        if (data.conversation_id) {
            conversationId.value = data.conversation_id;
        }

        messages.value.push({
            id: nextId('assistant'),
            role: 'assistant',
            content: data.text || '',
            pendingApprovals:
                data.pending_approvals?.length > 0
                    ? data.pending_approvals
                    : undefined,
            steps: data.steps?.length ? data.steps : undefined,
            links: data.links?.length ? data.links : undefined,
        });
    }

    async function sendMessage(text?: string): Promise<void> {
        const content = (text ?? draft.value).trim();
        const files = [...pendingFiles.value];
        if ((!content && files.length === 0) || sending.value || !available.value) {
            return;
        }

        error.value = null;
        draft.value = '';
        pendingFiles.value = [];
        view.value = 'chat';

        const displayContent =
            content ||
            (files.length === 1
                ? `Attached: ${files[0]?.name ?? 'file'}`
                : `Attached ${files.length} files`);

        messages.value.push({
            id: nextId('user'),
            role: 'user',
            content: displayContent,
            attachments: files.map((file) => ({
                name: file.name,
                mime: file.type,
                size: file.size,
            })),
        });
        sending.value = true;

        try {
            const form = new FormData();
            form.append('message', content);
            if (conversationId.value) {
                form.append('conversation_id', conversationId.value);
            }
            for (const file of files) {
                form.append('files[]', file);
            }

            const data = await requestJson<ChatResponse>('/ai/chat', {
                method: 'POST',
                body: form,
                headers: {
                    // Let the browser set multipart boundary; do not force JSON.
                },
            });
            appendAssistant(data);
            void loadConversations();
        } catch (e) {
            error.value =
                e instanceof Error ? e.message : 'AI chat failed unexpectedly.';
            messages.value.push({
                id: nextId('system'),
                role: 'system',
                content: error.value,
            });
        } finally {
            sending.value = false;
        }
    }

    async function decideApprovals(
        decisions: Record<string, boolean>,
    ): Promise<void> {
        if (!conversationId.value || sending.value) {
            return;
        }

        error.value = null;
        sending.value = true;

        const last = [...messages.value]
            .reverse()
            .find((m) => m.role === 'assistant' && m.pendingApprovals?.length);
        if (last) {
            last.pendingApprovals = undefined;
        }

        try {
            const data = await requestJson<ChatResponse>('/ai/chat/approve', {
                method: 'POST',
                body: JSON.stringify({
                    conversation_id: conversationId.value,
                    decisions,
                }),
            });
            appendAssistant(data);
            void loadConversations();
        } catch (e) {
            error.value =
                e instanceof Error
                    ? e.message
                    : 'AI chat approval failed unexpectedly.';
            messages.value.push({
                id: nextId('system'),
                role: 'system',
                content: error.value,
            });
        } finally {
            sending.value = false;
        }
    }

    async function approveAll(
        approvals: AiChatPendingApproval[],
    ): Promise<void> {
        const decisions: Record<string, boolean> = {};
        for (const approval of approvals) {
            decisions[approval.id] = true;
        }
        await decideApprovals(decisions);
    }

    async function rejectAll(
        approvals: AiChatPendingApproval[],
    ): Promise<void> {
        const decisions: Record<string, boolean> = {};
        for (const approval of approvals) {
            decisions[approval.id] = false;
        }
        await decideApprovals(decisions);
    }

    return {
        open,
        view,
        sending,
        loadingHistory,
        conversationId,
        messages,
        conversations,
        draft,
        pendingFiles,
        error,
        available,
        openChat,
        closeChat,
        toggleChat,
        showHistory,
        showChat,
        resetConversation,
        loadConversations,
        openConversation,
        deleteConversation,
        addFiles,
        removePendingFile,
        clearPendingFiles,
        sendMessage,
        approveAll,
        rejectAll,
        MAX_CHAT_FILES,
        CHAT_FILE_ACCEPT,
    };
}
