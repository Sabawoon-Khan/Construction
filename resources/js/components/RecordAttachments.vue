<script setup lang="ts">
import { computed } from 'vue';
import FileUploadField, { type ExistingUploadFile } from '@/components/FileUploadField.vue';
import InputError from '@/components/InputError.vue';
import { Label } from '@/components/ui/label';
import { useTranslations } from '@/composables/useTranslations';

const props = withDefaults(
    defineProps<{
        attachable: string;
        attachableId?: number | string | null;
        attachments?: Array<Record<string, any>> | null;
        label?: string | null;
        error?: string;
        accept?: string;
        maxFiles?: number;
        boxed?: boolean;
        files?: File[];
        readonly?: boolean;
    }>(),
    {
        attachableId: null,
        attachments: null,
        accept: 'application/pdf,image/jpeg,image/png,image/webp,.doc,.docx,.xls,.xlsx',
        maxFiles: 10,
        boxed: false,
        readonly: false,
    },
);

const emit = defineEmits<{
    'update:files': [files: File[]];
}>();

const { t } = useTranslations();

const title = computed(() => (props.label === undefined ? t('Attachments') : props.label));
const saved = computed(() => props.attachableId != null && props.attachableId !== '');
const existing = computed<ExistingUploadFile[]>(() =>
    (props.attachments ?? []).map((file) => ({
        id: file.id,
        name: file.original_filename || file.title || t('Attachment'),
        size: file.file_size,
        mime: file.mime_type,
        url: file.download_url || (file.id != null ? `/attachments/${file.id}/download` : null),
    })),
);

function onFileChange(event: Event): void {
    const input = event.target as HTMLInputElement;
    emit('update:files', input.files ? Array.from(input.files) : []);
}
</script>

<template>
    <div
        :class="
            boxed
                ? 'space-y-3 rounded-xl border bg-card p-5 shadow-sm print:hidden'
                : 'grid gap-2 mis-form-span'
        "
    >
        <h3 v-if="boxed && title" class="text-sm font-semibold">{{ title }}</h3>
        <Label v-else-if="title">{{ title }}</Label>
        <FileUploadField
            v-if="saved"
            mode="chunked"
            destination="attachment"
            :attachable="attachable"
            :attachable-id="attachableId"
            multiple
            :max-files="maxFiles"
            :accept="accept"
            :existing="existing"
            :readonly="readonly"
        />
        <p v-else-if="readonly" class="text-sm text-muted-foreground">{{ t('No attachments.') }}</p>
        <template v-else>
            <input
                type="file"
                multiple
                :name="files === undefined ? 'documents[]' : undefined"
                :accept="accept"
                class="text-sm"
                @change="onFileChange"
            />
            <InputError :message="error" />
        </template>
    </div>
</template>
