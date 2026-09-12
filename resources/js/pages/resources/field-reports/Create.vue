<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import FileUploadField from '@/components/FileUploadField.vue';
import OfflineResourceForm from '@/components/OfflineResourceForm.vue';
import RichTextEditorLite from '@/components/RichTextEditorLite.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { V2FormPage } from '@/components/v2';
import { useTranslations } from '@/composables/useTranslations';
import { useOfflineResourceForm } from '@/offline/useOfflineResourceForm';

const props = defineProps<{
    offlineEnabled: boolean;
}>();

const { t } = useTranslations();
const deferredAttachments = ref<File[]>([]);

const form = useForm({
    client_uuid: crypto.randomUUID() as string,
    title: '',
    body: '',
    location: '',
    status: 'draft',
    attachments: [] as File[],
});

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
    initialValues: {
        title: '',
        body: '',
        location: '',
        status: 'draft',
    },
    initialClientUuid: form.client_uuid,
    resetLocalOnQueue: true,
    onlineSubmit: async (payload) => {
        form.client_uuid = String(payload.client_uuid);
        form.title = String(payload.title ?? '');
        form.body = String(payload.body ?? '');
        form.location = String(payload.location ?? '');
        form.status = String(payload.status ?? 'draft');
        form.attachments = deferredAttachments.value;
        form.post('/field-reports', {
            forceFormData: true,
        });
    },
});

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Field reports', href: '/field-reports' },
            { title: 'Create', href: '/field-reports/create' },
        ],
    },
});
</script>

<template>
    <Head :title="t('Create field report')" />

    <V2FormPage
        :title="t('Create field report')"
        :eyebrow="t('Field')"
        :description="t('Capture site notes offline — they sync when you reconnect.')"
        back-href="/field-reports"
    >
        <OfflineResourceForm
            embedded
            :title="t('Create field report')"
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
            <div class="space-y-6">
                <div class="mis-form-grid">
                    <div class="grid gap-2 md:col-span-2 lg:col-span-2">
                        <Label for="title">{{ t('Title') }}</Label>
                        <Input id="title" v-model="local.title" required />
                    </div>

                    <div class="grid gap-2">
                        <Label for="location">{{ t('Location') }}</Label>
                        <Input id="location" v-model="local.location" />
                    </div>
                </div>

                <div class="grid gap-2">
                    <Label for="body">{{ t('Notes') }}</Label>
                    <RichTextEditorLite v-model="local.body" />
                </div>

                <div v-if="online" class="grid gap-2">
                    <Label>{{ t('Attachments') }}</Label>
                    <FileUploadField
                        mode="deferred"
                        multiple
                        :max-files="5"
                        accept="image/jpeg,image/png,image/webp,application/pdf"
                        @update:deferred="(files) => (deferredAttachments = files)"
                    />
                </div>
            </div>

            <template #actions>
                <Button type="submit" :disabled="processing || form.processing">
                    {{ t('Save') }}
                </Button>
            </template>
        </OfflineResourceForm>
    </V2FormPage>
</template>
