<script setup lang="ts">
import { Paperclip } from '@lucide/vue';

defineProps<{
    files?: Array<{
        id?: number | string;
        original_filename?: string;
        title?: string;
        name?: string;
        download_url?: string;
        url?: string;
    }> | null;
}>();

function href(file: {
    id?: number | string;
    download_url?: string;
    url?: string;
}): string | null {
    return file.download_url || file.url || (file.id != null ? `/attachments/${file.id}/download` : null);
}

function label(file: {
    original_filename?: string;
    title?: string;
    name?: string;
}): string {
    return file.original_filename || file.title || file.name || '';
}
</script>

<template>
    <div v-if="files?.length" class="flex flex-col gap-0.5">
        <a
            v-for="(file, index) in files"
            :key="String(file.id ?? href(file) ?? index)"
            :href="href(file) ?? undefined"
            target="_blank"
            rel="noopener noreferrer"
            class="inline-flex max-w-[12rem] items-center gap-1 truncate text-sm text-primary hover:underline"
            @click.stop
        >
            <Paperclip class="size-3.5 shrink-0" />
            <span class="truncate">{{ label(file) }}</span>
        </a>
    </div>
    <span v-else class="text-muted-foreground">—</span>
</template>
