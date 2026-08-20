<script setup lang="ts">
import { FileIcon, X } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import type { ChunkedUploadItem } from '@/composables/useChunkedUpload';
import { useTranslations } from '@/composables/useTranslations';

const props = defineProps<{
    items: ChunkedUploadItem[];
}>();

const emit = defineEmits<{
    cancel: [id: string];
    dismiss: [id: string];
    clearFinished: [];
}>();

const { t } = useTranslations();

function formatBytes(bytes: number | null | undefined): string {
    if (bytes == null) {
        return '—';
    }

    if (bytes < 1024) {
        return `${bytes} B`;
    }

    if (bytes < 1024 * 1024) {
        return `${(bytes / 1024).toFixed(1)} KB`;
    }

    if (bytes < 1024 * 1024 * 1024) {
        return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
    }

    return `${(bytes / (1024 * 1024 * 1024)).toFixed(2)} GB`;
}

function formatSpeed(bps: number): string {
    if (!bps || bps < 1) {
        return '';
    }

    return `${formatBytes(Math.round(bps))}/s`;
}

function statusLabel(status: ChunkedUploadItem['status']): string {
    switch (status) {
        case 'queued':
            return t('Queued');
        case 'hashing':
            return t('Preparing…');
        case 'uploading':
            return t('Uploading…');
        case 'assembling':
            return t('Finalizing…');
        case 'completed':
            return t('Completed');
        case 'failed':
            return t('Failed');
        case 'aborted':
            return t('Cancelled');
        case 'paused':
            return t('Paused');
        default:
            return status;
    }
}

const hasFinished = () =>
    props.items.some((item) => ['completed', 'failed', 'aborted'].includes(item.status));
</script>

<template>
    <div v-if="items.length > 0" class="space-y-2.5 rounded-xl border border-border/70 bg-muted/15 p-3">
        <div class="flex items-center justify-between gap-3 px-0.5">
            <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                {{ t('Uploads') }}
            </p>
            <Button
                v-if="hasFinished()"
                type="button"
                variant="ghost"
                size="sm"
                class="h-7 px-2 text-xs"
                @click="emit('clearFinished')"
            >
                {{ t('Clear finished') }}
            </Button>
        </div>

        <div
            v-for="item in items"
            :key="item.id"
            class="rounded-lg border border-border/60 bg-card px-3 py-2.5"
        >
            <div class="flex items-start gap-3">
                <div class="mt-0.5 flex size-8 shrink-0 items-center justify-center rounded-md bg-muted/60 text-muted-foreground">
                    <FileIcon class="size-3.5" />
                </div>

                <div class="min-w-0 flex-1">
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-medium">{{ item.file.name }}</p>
                            <p class="mt-0.5 text-xs text-muted-foreground">
                                <span>{{ statusLabel(item.status) }}</span>
                                <span v-if="item.totalBytes"> · {{ formatBytes(item.loadedBytes) }} / {{ formatBytes(item.totalBytes) }}</span>
                                <span v-if="formatSpeed(item.speedBps)"> · {{ formatSpeed(item.speedBps) }}</span>
                            </p>
                        </div>

                        <div class="flex shrink-0 items-center gap-1">
                            <Button
                                v-if="['queued', 'hashing', 'uploading', 'assembling'].includes(item.status)"
                                type="button"
                                variant="ghost"
                                size="sm"
                                class="h-7 px-2 text-xs"
                                @click="emit('cancel', item.id)"
                            >
                                {{ t('Cancel') }}
                            </Button>
                            <Button
                                v-else
                                type="button"
                                variant="ghost"
                                size="icon"
                                class="size-7"
                                @click="emit('dismiss', item.id)"
                            >
                                <X class="size-3.5" />
                            </Button>
                        </div>
                    </div>

                    <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-muted">
                        <div
                            class="h-full rounded-full transition-all duration-300"
                            :class="
                                item.status === 'failed' || item.status === 'aborted'
                                    ? 'bg-destructive'
                                    : item.status === 'completed'
                                      ? 'bg-emerald-500'
                                      : 'bg-primary'
                            "
                            :style="{ width: `${Math.min(100, Math.max(0, item.progress))}%` }"
                        />
                    </div>

                    <p v-if="item.error" class="mt-2 text-xs text-destructive">
                        {{ item.error }}
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>
