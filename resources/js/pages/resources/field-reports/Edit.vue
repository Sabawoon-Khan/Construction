<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import FileUploadField, {
    type ExistingUploadFile,
} from '@/components/FileUploadField.vue';
import OfflineResourceForm from '@/components/OfflineResourceForm.vue';
import RichTextEditorLite from '@/components/RichTextEditorLite.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { V2FormPage } from '@/components/v2';
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

function onAttachmentComplete(payload: {
    attachmentId?: number | null;
    downloadUrl?: string | null;
    item: { file: File; totalBytes: number };
}): void {
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
    existingAttachments.value = existingAttachments.value.filter(
        (entry) => entry.id !== file.id,
    );
}

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Field reports', href: '/field-reports' },
            { title: 'Edit', href: '#' },
        ],
    },
});
</script>

<template>
    <Head :title="t('Edit field report')" />

    <V2FormPage
        :title="t('Edit field report')"
        :eyebrow="t('Field')"
        back-href="/field-reports"
    >
        <OfflineResourceForm
            embedded
            :title="t('Edit field report')"
            full-width
            :offline-enabled="offlineEnabled"
            :online="online"
            :processing="processing || form.processing"
            :client-uuid="clientUuid"
            resource="field_reports"
            :show-attachments="attachmentsEnabled"
            @submit="onSubmit"
            @attachment-stored="onAttachmentStored"
        >
            <div class="v2-form-split">
                <div class="v2-form-split-main">
                    <div class="v2-form-group">
                        <div class="mis-form-grid">
                            <div class="v2-field mis-form-span">
                                <Label for="title">{{ t('Title') }}</Label>
                                <Input id="title" v-model="local.title" required />
                            </div>
                            <div class="v2-field">
                                <Label for="location">{{ t('Location') }}</Label>
                                <Input id="location" v-model="local.location" />
                            </div>
                        </div>
                    </div>
                </div>

                <aside class="v2-form-split-aside">
                    <div class="v2-form-split-panel">
                        <div class="v2-form-split-block">
                            <h2 class="v2-form-split-block-title">{{ t('Notes') }}</h2>
                            <div class="v2-field">
                                <Label for="body" class="sr-only">{{ t('Notes') }}</Label>
                                <RichTextEditorLite :key="item.id" v-model="local.body" />
                            </div>
                        </div>
                        <div v-if="showOnlineUploads" class="v2-form-split-block">
                            <h2 class="v2-form-split-block-title">{{ t('Attachments') }}</h2>
                            <FileUploadField
                                mode="chunked"
                                destination="attachment"
                                attachable="field_reports"
                                :attachable-id="item.id"
                                multiple
                                :max-files="5"
                                accept="image/jpeg,image/png,image/webp,application/pdf"
                                :existing="existingAttachments"
                                @complete="onAttachmentComplete"
                                @remove="onAttachmentRemoved"
                            />
                        </div>
                    </div>
                </aside>
            </div>

            <template #actions>
                <Button type="submit" :disabled="processing || form.processing">
                    {{ t('Save') }}
                </Button>
            </template>
        </OfflineResourceForm>
    </V2FormPage>
</template>
