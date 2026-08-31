<script setup lang="ts">
import { Upload } from '@lucide/vue';
import { computed, ref } from 'vue';
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

const fileInputRef = ref<HTMLInputElement | null>(null);
const selectedNames = ref<string[]>([]);
const isDragging = ref(false);

function applyFiles(files: File[]): void {
    selectedNames.value = files.map((file) => file.name);
    emit('update:files', files);

    if (!fileInputRef.value) {
        return;
    }

    const transfer = new DataTransfer();
    for (const file of files) {
        transfer.items.add(file);
    }
    fileInputRef.value.files = transfer.files;
}

function onFileChange(event: Event): void {
    const input = event.target as HTMLInputElement;
    const files = input.files ? Array.from(input.files) : [];
    selectedNames.value = files.map((file) => file.name);
    emit('update:files', files);
}

function onDrop(event: DragEvent): void {
    event.preventDefault();
    isDragging.value = false;
    const files = event.dataTransfer?.files ? Array.from(event.dataTransfer.files) : [];
    applyFiles(files);
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
            <div
                role="button"
                tabindex="0"
                class="group flex w-full flex-col items-center justify-center gap-2 rounded-xl border border-dashed px-4 py-6 text-center transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                :class="
                    isDragging
                        ? 'border-primary bg-primary/5'
                        : 'cursor-pointer border-border/80 bg-muted/20 hover:border-border hover:bg-muted/35'
                "
                @click="fileInputRef?.click()"
                @keydown.enter.prevent="fileInputRef?.click()"
                @keydown.space.prevent="fileInputRef?.click()"
                @dragenter.prevent="isDragging = true"
                @dragover.prevent="isDragging = true"
                @dragleave.prevent="isDragging = false"
                @drop="onDrop"
            >
                <div
                    class="flex size-10 items-center justify-center rounded-full border border-border/70 bg-background text-muted-foreground shadow-sm"
                >
                    <Upload class="size-4" />
                </div>
                <p class="text-sm font-medium">
                    <span class="text-primary">{{ t('Choose files') }}</span>
                    <span class="text-muted-foreground"> {{ t('or drag and drop') }}</span>
                </p>
                <p v-if="selectedNames.length" class="max-w-full truncate text-xs text-muted-foreground">
                    {{ selectedNames.join(', ') }}
                </p>
            </div>
            <input
                ref="fileInputRef"
                type="file"
                multiple
                :name="files === undefined ? 'documents[]' : undefined"
                :accept="accept"
                class="hidden"
                @change="onFileChange"
            />
            <InputError :message="error" />
        </template>
    </div>
</template>
