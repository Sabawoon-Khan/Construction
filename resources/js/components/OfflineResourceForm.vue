<script setup lang="ts">
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
        showAttachments?: boolean;
        submitLabel?: string;
    }>(),
    {
        offlineEnabled: false,
        online: true,
        processing: false,
        showAttachments: true,
        submitLabel: undefined,
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
    <form class="mx-auto flex max-w-lg flex-col gap-4 p-4" @submit.prevent="emit('submit')">
        <div class="space-y-1">
            <h1 class="text-xl font-semibold">{{ title }}</h1>
            <p v-if="offlineEnabled" class="text-sm text-muted-foreground">
                {{
                    online
                        ? t('Online — submits immediately.')
                        : t('Offline — queues to outbox.')
                }}
            </p>
            <slot name="description" />
        </div>

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

        <slot name="actions">
            <Button type="submit" :disabled="processing">
                {{ submitLabel || t('Save') }}
            </Button>
        </slot>

        <slot v-if="slots.footer" name="footer" />
    </form>
</template>
