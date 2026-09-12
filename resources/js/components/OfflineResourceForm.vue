<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowLeft } from '@lucide/vue';
import { computed, onMounted, onUnmounted, ref, useSlots, watch } from 'vue';
import ConfirmActionDialog from '@/components/ConfirmActionDialog.vue';
import FileUploadField from '@/components/FileUploadField.vue';
import { Button } from '@/components/ui/button';
import { useTranslations } from '@/composables/useTranslations';
import { pendingCount } from '@/offline/outbox';
import { getOfflineSettings } from '@/offline/settings';

const props = withDefaults(
    defineProps<{
        title: string;
        offlineEnabled?: boolean;
        online?: boolean;
        processing?: boolean;
        clientUuid: string;
        resource: string;
        backHref?: string;
        showAttachments?: boolean;
        submitLabel?: string;
        fullWidth?: boolean;
        /** Hide page title/back — use when wrapped in V2FormPage. */
        embedded?: boolean;
    }>(),
    {
        offlineEnabled: false,
        online: true,
        processing: false,
        backHref: undefined,
        showAttachments: true,
        submitLabel: undefined,
        fullWidth: false,
        embedded: false,
    },
);

const emit = defineEmits<{
    submit: [];
    'attachment-stored': [id: string, name: string];
}>();

const { t } = useTranslations();
const slots = useSlots();
const settings = computed(() => getOfflineSettings());
const pending = ref(0);
const confirmPendingOpen = ref(false);
let pendingTimer: ReturnType<typeof setInterval> | null = null;

const attachmentsVisible = computed(
    () =>
        props.showAttachments
        && props.offlineEnabled
        && settings.value.attachments_enabled
        && ! props.online,
);

const showPendingRisk = computed(
    () => props.offlineEnabled && pending.value > 0,
);

async function refreshPending(): Promise<void> {
    if (! props.offlineEnabled) {
        pending.value = 0;

        return;
    }

    try {
        pending.value = await pendingCount();
    } catch {
        pending.value = 0;
    }
}

function onOfflineComplete(payload: { offlineBlobId?: string | null; item: { file: File } }): void {
    if (payload.offlineBlobId) {
        emit('attachment-stored', payload.offlineBlobId, payload.item.file.name);
    }
}

function requestSubmit(): void {
    if (showPendingRisk.value) {
        confirmPendingOpen.value = true;

        return;
    }

    emit('submit');
}

function confirmPendingSubmit(): void {
    confirmPendingOpen.value = false;
    emit('submit');
}

watch(
    () => props.offlineEnabled,
    () => {
        void refreshPending();
    },
);

onMounted(() => {
    void refreshPending();
    pendingTimer = setInterval(() => {
        void refreshPending();
    }, 4000);
});

onUnmounted(() => {
    if (pendingTimer) {
        clearInterval(pendingTimer);
    }
});
</script>

<template>
    <form
        class="flex w-full min-w-0 flex-1 flex-col gap-5"
        @submit.prevent="requestSubmit"
    >
        <header
            v-if="!embedded"
            class="flex min-w-0 items-center gap-2"
        >
            <Button
                v-if="backHref"
                as-child
                variant="ghost"
                size="sm"
                class="-ml-2 shrink-0 text-muted-foreground hover:text-foreground"
            >
                <Link :href="backHref">
                    <ArrowLeft class="size-4 rtl:rotate-180" />
                    {{ t('Back') }}
                </Link>
            </Button>
            <h1 class="truncate text-lg font-semibold tracking-tight">{{ title }}</h1>
        </header>

        <div
            v-if="showPendingRisk"
            class="rounded-md border border-amber-500/30 bg-amber-500/10 px-3 py-2 text-sm text-amber-950 dark:text-amber-100"
            role="status"
        >
            {{
                t(
                    ':count change(s) still waiting to sync. Saving again queues another change — sync when online to avoid conflicts.',
                    { count: String(pending) },
                )
            }}
        </div>

        <div class="mis-form-card">
            <slot />

            <FileUploadField
                v-if="attachmentsVisible"
                mode="offline"
                :client-uuid="clientUuid"
                :resource="resource"
                :max-bytes="settings.attachment_max_bytes"
                :accept="settings.allowed_mimes.join(',')"
                :label="t('Attachment')"
                :multiple="false"
                @complete="onOfflineComplete"
            />

            <div class="mis-form-actions">
                <slot name="actions">
                    <Button type="submit" :disabled="processing">
                        {{ submitLabel || t('Save') }}
                    </Button>
                </slot>
            </div>
        </div>

        <slot v-if="slots.footer" name="footer" />
    </form>

    <ConfirmActionDialog
        v-model:open="confirmPendingOpen"
        :title="t('Pending offline changes')"
        :description="
            t(
                'You have :count change(s) waiting to sync. Save anyway and queue this change too?',
                { count: String(pending) },
            )
        "
        :confirm-label="t('Save anyway')"
        :cancel-label="t('Cancel')"
        variant="default"
        @confirm="confirmPendingSubmit"
    />
</template>
