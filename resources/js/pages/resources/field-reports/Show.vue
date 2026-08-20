<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, MapPin, Paperclip } from '@lucide/vue';
import { ref } from 'vue';
import Heading from '@/components/Heading.vue';
import { MisTabs } from '@/components/mis';
import { translateStatus } from '@/lib/translateStatus';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { useCalendar } from '@/composables/useCalendar';
import { useTranslations } from '@/composables/useTranslations';

const props = defineProps<{
    item: {
        id: number;
        title: string;
        body: string | null;
        location: string | null;
        status: string;
        created_at?: string | null;
        attachments?: Array<{
            id: number;
            original_filename: string;
            mime_type: string | null;
            file_size: number | null;
            download_url: string;
        }>;
    };
}>();

const { t } = useTranslations();
const calendar = useCalendar();
const tab = ref('overview');
const tabs = [
    { id: 'overview', label: t('Overview') },
    { id: 'notes', label: t('Notes') },
    { id: 'attachments', label: t('Attachments') },
];

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Field reports', href: '/field-reports' },
            { title: 'View', href: '#' },
        ],
    },
});

function formatBytes(bytes: number | null | undefined): string {
    if (!bytes) {
        return '';
    }
    if (bytes < 1024) {
        return `${bytes} B`;
    }
    if (bytes < 1024 * 1024) {
        return `${(bytes / 1024).toFixed(1)} KB`;
    }
    return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
}

</script>

<template>
    <Head :title="t('Field report details')" />

    <div class="mx-auto flex w-full max-w-5xl flex-col gap-5 p-4 md:p-6">
        <Button as-child variant="ghost" size="sm" class="-ml-2 w-fit">
            <Link href="/field-reports"><ArrowLeft class="size-4" /> {{ t('Back') }}</Link>
        </Button>

        <div class="rounded-2xl border bg-card p-5 md:p-6">
            <Heading :title="item.title || t('Field report')" />

            <div class="mt-4 flex flex-wrap items-center gap-2">
                <Badge variant="secondary">
                    {{ translateStatus(item.status, t) }}
                </Badge>
                <span v-if="item.location" class="inline-flex items-center gap-1 text-sm text-muted-foreground">
                    <MapPin class="size-4" />
                    {{ item.location }}
                </span>
                <span v-if="item.created_at" class="text-sm text-muted-foreground">
                    {{ calendar.format(item.created_at) }}
                </span>
            </div>
        </div>

        <MisTabs v-model="tab" :tabs="tabs" />

        <div v-if="tab === 'overview'" class="rounded-2xl border bg-card p-5 md:p-6">
            <h2 class="mb-3 text-sm font-semibold">{{ t('Overview') }}</h2>
            <p class="text-sm text-muted-foreground">
                {{ item.location || t('No location') }}
            </p>
        </div>

        <div v-else-if="tab === 'notes'" class="rounded-2xl border bg-card p-5 md:p-6">
            <h2 class="mb-3 text-sm font-semibold">{{ t('Notes') }}</h2>
            <div
                v-if="item.body"
                class="prose prose-sm dark:prose-invert max-w-none"
                v-html="item.body"
            />
            <p v-else class="text-sm text-muted-foreground">
                {{ t('No notes added.') }}
            </p>
        </div>

        <div v-else class="rounded-2xl border bg-card p-5 md:p-6">
            <h2 class="mb-3 text-sm font-semibold">{{ t('Attachments') }}</h2>
            <div
                v-if="item.attachments?.length"
                class="space-y-2"
            >
                <a
                    v-for="file in item.attachments"
                    :key="file.id"
                    :href="file.download_url || `/attachments/${file.id}/download`"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="flex items-center justify-between gap-3 rounded-lg border border-border/70 bg-background px-3 py-2 hover:bg-muted/50"
                >
                    <span class="inline-flex min-w-0 items-center gap-2">
                        <Paperclip class="size-4 shrink-0 text-muted-foreground" />
                        <span class="truncate text-sm font-medium">{{ file.original_filename }}</span>
                    </span>
                    <span class="text-xs text-muted-foreground">
                        {{ formatBytes(file.file_size) }}
                    </span>
                </a>
            </div>
            <p v-else class="text-sm text-muted-foreground">
                {{ t('No attachments.') }}
            </p>
        </div>
    </div>
</template>
