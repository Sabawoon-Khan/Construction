<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Pencil } from '@lucide/vue';
import Can from '@/components/Can.vue';
import RecordAttachments from '@/components/RecordAttachments.vue';
import { MisDetailGrid, MisStatusBadge } from '@/components/mis';
import { Button } from '@/components/ui/button';
import { V2DetailHero, V2ListPage, V2Panel } from '@/components/v2';
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
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Field reports', href: '/field-reports' },
            { title: 'View', href: '#' },
        ],
    },
});
</script>

<template>
    <Head :title="item.title || t('Field report details')" />

    <V2ListPage>
        <div class="flex flex-col gap-4">
            <V2DetailHero image="/images/dashboard-v2-building.png">
                <template #eyebrow>{{ t('Field') }}</template>
                <template #title>{{
                    item.title || t('Field report')
                }}</template>
                <template #description>{{
                    item.location || t('Field report details')
                }}</template>
                <template #actions>
                    <MisStatusBadge :status="item.status" />
                    <Can permission="field_reports.edit">
                        <Button as-child size="sm" class="h-8 gap-1.5">
                            <Link :href="`/field-reports/${item.id}/edit`">
                                <Pencil class="size-3.5" />
                                {{ t('Edit') }}
                            </Link>
                        </Button>
                    </Can>
                </template>
            </V2DetailHero>

            <V2Panel :title="t('Overview')">
                <MisDetailGrid
                    :items="[
                        {
                            label: t('Location'),
                            value: item.location || t('No location'),
                        },
                        {
                            label: t('Status'),
                            value: translateStatus(item.status, t),
                        },
                        {
                            label: t('Created'),
                            value: item.created_at
                                ? formatDateTime(item.created_at)
                                : '—',
                        },
                        {
                            label: t('Attachments'),
                            value: item.attachments?.length
                                ? String(item.attachments.length)
                                : '0',
                        },
                    ]"
                />
            </V2Panel>

            <V2Panel :title="t('Notes')">
                <div
                    v-if="item.body"
                    class="prose prose-sm dark:prose-invert max-w-none text-foreground"
                    v-html="item.body"
                />
                <p v-else class="text-sm text-muted-foreground">
                    {{ t('No notes added.') }}
                </p>
            </V2Panel>

            <V2Panel :title="t('Attachments')">
                <RecordAttachments
                    attachable="field_reports"
                    :attachable-id="item.id"
                    :attachments="item.attachments"
                    readonly
                    accept="image/jpeg,image/png,image/webp,application/pdf"
                />
            </V2Panel>
        </div>
    </V2ListPage>
</template>
