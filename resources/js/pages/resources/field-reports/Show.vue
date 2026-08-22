<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, Pencil } from '@lucide/vue';
import Can from '@/components/Can.vue';
import DetailGrid from '@/components/DetailGrid.vue';
import Heading from '@/components/Heading.vue';
import RecordAttachments from '@/components/RecordAttachments.vue';
import { MisStatusBadge } from '@/components/mis';
import { Button } from '@/components/ui/button';
import { useCalendar } from '@/composables/useCalendar';
import { useTranslations } from '@/composables/useTranslations';
import { translateStatus } from '@/lib/translateStatus';

defineProps<{
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
const { formatDateTime } = useCalendar();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Field reports', href: '/field-reports' },
            { title: 'View', href: '#' },
        ],
    },
});
</script>

<template>
    <Head :title="item.title || t('Field report details')" />

    <div class="flex h-full flex-1 flex-col gap-4">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div class="space-y-3">
                <Button as-child variant="ghost" size="sm" class="-ml-2 w-fit">
                    <Link href="/field-reports">
                        <ArrowLeft class="size-4" /> {{ t('Back') }}
                    </Link>
                </Button>
                <div class="space-y-2">
                    <div class="flex flex-wrap items-center gap-2">
                        <Heading
                            :title="item.title || t('Field report')"
                            :description="item.location || t('Field report details')"
                        />
                        <MisStatusBadge :status="item.status" />
                    </div>
                </div>
            </div>
            <Can permission="field_reports.edit">
                <Button as-child>
                    <Link :href="`/field-reports/${item.id}/edit`">
                        <Pencil class="size-4" /> {{ t('Edit') }}
                    </Link>
                </Button>
            </Can>
        </div>

        <DetailGrid
            :title="t('Overview')"
            :items="[
                { label: t('Location'), value: item.location || t('No location') },
                { label: t('Status'), value: translateStatus(item.status, t) },
                {
                    label: t('Created'),
                    value: item.created_at ? formatDateTime(item.created_at) : '—',
                },
                {
                    label: t('Attachments'),
                    value: item.attachments?.length ? String(item.attachments.length) : '0',
                },
            ]"
        />

        <div class="rounded-xl border border-sidebar-border/70 bg-card p-5 shadow-sm">
            <h3 class="text-sm font-semibold tracking-tight">{{ t('Notes') }}</h3>
            <div
                v-if="item.body"
                class="prose prose-sm dark:prose-invert mt-4 max-w-none text-foreground"
                v-html="item.body"
            />
            <p v-else class="mt-3 text-sm text-muted-foreground">
                {{ t('No notes added.') }}
            </p>
        </div>

        <RecordAttachments
            attachable="field_reports"
            :attachable-id="item.id"
            :attachments="item.attachments"
            boxed
            readonly
            accept="image/jpeg,image/png,image/webp,application/pdf"
        />
    </div>
</template>
