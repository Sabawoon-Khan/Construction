<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowLeft } from '@lucide/vue';
import { computed, useSlots } from 'vue';
import FileUploadField from '@/components/FileUploadField.vue';
import { Button } from '@/components/ui/button';
import { useTranslations } from '@/composables/useTranslations';
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
    }>(),
    {
        offlineEnabled: false,
        online: true,
        processing: false,
        backHref: undefined,
        showAttachments: true,
        submitLabel: undefined,
        fullWidth: false,
    },
);

const emit = defineEmits<{
    submit: [];
    'attachment-stored': [id: string, name: string];
}>();

const { t } = useTranslations();
const slots = useSlots();
const settings = computed(() => getOfflineSettings());

const attachmentsVisible = computed(
    () =>
        props.showAttachments
        && props.offlineEnabled
        && settings.value.attachments_enabled
        && ! props.online,
);

function onOfflineComplete(payload: { offlineBlobId?: string | null; item: { file: File } }): void {
    if (payload.offlineBlobId) {
        emit('attachment-stored', payload.offlineBlobId, payload.item.file.name);
    }
}
</script>

<template>
    <form
        class="flex w-full min-w-0 flex-1 flex-col gap-5"
        @submit.prevent="emit('submit')"
    >
        <header class="flex min-w-0 items-center gap-2">
            <Button
                v-if="backHref"
                as-child
                variant="ghost"
                size="sm"
                class="-ml-2 shrink-0 text-muted-foreground hover:text-foreground"
            >
                <Link :href="backHref">
                    <ArrowLeft class="size-4" />
                    {{ t('Back') }}
                </Link>
            </Button>
            <h1 class="truncate text-lg font-semibold tracking-tight">{{ title }}</h1>
        </header>

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
</template>
