<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import FileUploadField, { type ExistingUploadFile } from '@/components/FileUploadField.vue';
import OfflineResourceForm from '@/components/OfflineResourceForm.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useTranslations } from '@/composables/useTranslations';
import { useOfflineResourceForm } from '@/offline/useOfflineResourceForm';

const props = defineProps<{
    item: {
        id: number;
        client_uuid: string;
        title: string;
        body: string | null;
        location: string | null;
        status: string;
        attachments?: Array<{
            id: number;
            original_filename: string;
            mime_type: string | null;
            file_size: number | null;
            download_url: string;
        }>;
    };
    offlineEnabled: boolean;
}>();

const { t } = useTranslations();

const form = useForm({
    title: props.item.title,
    body: props.item.body ?? '',
    location: props.item.location ?? '',
    status: props.item.status,
});

const existingAttachments = ref<ExistingUploadFile[]>(
    (props.item.attachments ?? []).map((attachment) => ({
        id: attachment.id,
        name: attachment.original_filename,
        size: attachment.file_size,
        mime: attachment.mime_type,
        url: attachment.download_url,
    })),
);

const {
    local,
    clientUuid,
    offlineEnabled,
    online,
    processing,
    attachmentsEnabled,
    onSubmit,
    onAttachmentStored,
} = useOfflineResourceForm({
    resource: 'field_reports',
    offlineEnabled: props.offlineEnabled,
    initialClientUuid: props.item.client_uuid,
    initialValues: {
        title: props.item.title,
        body: props.item.body ?? '',
        location: props.item.location ?? '',
        status: props.item.status,
    },
    resetLocalOnQueue: false,
    extraPayload: () => ({
        attachments_meta: existingAttachments.value
            .filter((attachment) => attachment.id != null)
            .map((attachment) => ({
                id: attachment.id,
            })),
    }),
    onlineSubmit: async (payload) => {
        form.title = String(payload.title ?? '');
        form.body = String(payload.body ?? '');
        form.location = String(payload.location ?? '');
        form.status = String(payload.status ?? 'draft');
        form.put(`/field-reports/${props.item.id}`);
    },
});

const showOnlineUploads = computed(() => online.value);

function onAttachmentComplete(payload: { attachmentId?: number | null; downloadUrl?: string | null; item: { file: File; totalBytes: number } }): void {
    if (!payload.attachmentId) {
        return;
    }

    existingAttachments.value.push({
        id: payload.attachmentId,
        name: payload.item.file.name,
        size: payload.item.totalBytes,
        url: payload.downloadUrl,
    });
}

function onAttachmentRemoved(file: ExistingUploadFile): void {
    existingAttachments.value = existingAttachments.value.filter((entry) => entry.id !== file.id);
}
</script>

<template>
    <Head :title="t('Edit field report')" />

    <OfflineResourceForm
        :title="t('Edit field report')"
        :offline-enabled="offlineEnabled"
        :online="online"
        :processing="processing || form.processing"
        :client-uuid="clientUuid"
        resource="field_reports"
        :show-attachments="attachmentsEnabled"
        @submit="onSubmit"
        @attachment-stored="onAttachmentStored"
    >
        <div class="space-y-2">
            <Label for="title">{{ t('Title') }}</Label>
            <Input id="title" v-model="local.title" required />
        </div>

        <div class="space-y-2">
            <Label for="location">{{ t('Location') }}</Label>
            <Input id="location" v-model="local.location" />
        </div>

        <div class="space-y-2">
            <Label for="body">{{ t('Notes') }}</Label>
            <textarea
                id="body"
                v-model="local.body"
                rows="5"
                class="min-h-28 w-full rounded-md border bg-transparent px-3 py-2 text-sm outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
            />
        </div>

        <FileUploadField
            v-if="showOnlineUploads"
            mode="chunked"
            destination="attachment"
            attachable="field_reports"
            :attachable-id="item.id"
            multiple
            :max-files="5"
            accept="image/jpeg,image/png,image/webp,application/pdf"
            :label="t('Attachments')"
            :existing="existingAttachments"
            @complete="onAttachmentComplete"
            @remove="onAttachmentRemoved"
        />
    </OfflineResourceForm>
</template>
