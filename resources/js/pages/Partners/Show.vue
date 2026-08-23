<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, Pencil } from '@lucide/vue';
import Can from '@/components/Can.vue';
import DetailGrid from '@/components/DetailGrid.vue';
import RecordAttachments from '@/components/RecordAttachments.vue';
import Heading from '@/components/Heading.vue';
import { computed } from 'vue';
import SortableTh from '@/components/SortableTh.vue';
import TableFrame from '@/components/TableFrame.vue';
import TableIndexTd from '@/components/TableIndexTd.vue';
import TableIndexTh from '@/components/TableIndexTh.vue';
import { indexTableColumn } from '@/composables/useTableColumns';
import { Button } from '@/components/ui/button';
import { useMisPage } from '@/composables/useMisPage';
import { provideTableSort } from '@/composables/useTableSort';
import { useTranslations } from '@/composables/useTranslations';
import { formatNumber, money, moneyByCurrency } from '@/lib/utils';

const props = defineProps<{
    item: Record<string, any>;
    projects: {
        id: number;
        name: string;
        ownership_percentage: number;
        entitled_share: number;
        paid_total: number;
        balance_owed: number;
    }[];
    stats: {
        projects_count: number;
        contributions_total: number;
        payouts_total: number;
        contributions_by_currency?: Record<string, number>;
        payouts_by_currency?: Record<string, number>;
    };
}>();
const { t } = useTranslations();
const tableColumns = computed(() => [
    indexTableColumn(),
    { key: 'name', label: t('Project') },
    { key: 'ownership_percentage', label: t('Share') },
    { key: 'entitled_share', label: t('Profit share') },
    { key: 'paid_total', label: t('Paid') },
    { key: 'balance_owed', label: t('Owed') },
    { key: 'created_at', label: t('Created at') },
]);
const { formatDate } = useMisPage();
const { sortedRows } = provideTableSort(() => props.projects);

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Settings', href: '/settings/appearance' },
            { title: 'Shareholders', href: '/partners' },
            { title: 'Details', href: '#' },
        ],
    },
});
</script>

<template>
    <Head :title="item.name" />
    <div class="flex h-full flex-1 flex-col gap-4">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div class="space-y-3">
                <Button as-child variant="ghost" size="sm" class="-ml-2 w-fit">
                    <Link href="/partners"><ArrowLeft class="size-4" /> {{ t('Back') }}</Link>
                </Button>
                <Heading :title="item.name" :description="`${t('Shareholder')} ${item.partner_no || ''}`" />
            </div>
            <div class="flex gap-2">
                <Button as-child variant="outline">
                    <a href="/reports/print" target="_blank">{{ t('Print distribution') }}</a>
                </Button>
                <Can permission="partners.edit">
                    <Button as-child>
                        <Link :href="`/partners/${item.id}/edit`"><Pencil class="size-4" /> {{ t('Edit') }}</Link>
                    </Button>
                </Can>
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-3">
            <div class="rounded-xl border bg-card p-4 shadow-sm">
                <div class="text-sm text-muted-foreground">{{ t('Projects') }}</div>
                <div class="mt-1 text-2xl font-semibold tabular-nums">{{ stats.projects_count }}</div>
            </div>
            <div class="rounded-xl border bg-card p-4 shadow-sm">
                <div class="text-sm text-muted-foreground">{{ t('Total contributed') }}</div>
                <div class="mt-1 text-2xl font-semibold tabular-nums">{{ moneyByCurrency(stats.contributions_by_currency, stats.contributions_total) }}</div>
            </div>
            <div class="rounded-xl border bg-card p-4 shadow-sm">
                <div class="text-sm text-muted-foreground">{{ t('Total paid out') }}</div>
                <div class="mt-1 text-2xl font-semibold tabular-nums text-emerald-700">{{ moneyByCurrency(stats.payouts_by_currency, stats.payouts_total) }}</div>
            </div>
        </div>

        <RecordAttachments
            attachable="partners"
            :attachable-id="item.id"
            :attachments="item.attachments"
            boxed
        />

        <div class="overflow-hidden rounded-xl border bg-card shadow-sm">
            <div class="border-b px-5 py-3">
                <h3 class="text-sm font-semibold">{{ t('Project shares') }}</h3>
            </div>
            <TableFrame table-id="partner-shares" :columns="tableColumns">
            <table class="min-w-full text-sm">
                <thead class="bg-muted/40 text-start text-muted-foreground">
                    <tr>
                        <TableIndexTh />
                        <SortableTh column="name">{{ t('Project') }}</SortableTh>
                        <SortableTh column="ownership_percentage">{{ t('Share') }}</SortableTh>
                        <SortableTh column="entitled_share">{{ t('Profit share') }}</SortableTh>
                        <SortableTh column="paid_total">{{ t('Paid') }}</SortableTh>
                        <SortableTh column="balance_owed">{{ t('Owed') }}</SortableTh>
                        <SortableTh column="created_at">{{ t('Created at') }}</SortableTh>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(p, index) in sortedRows" :key="p.id" class="border-t hover:bg-muted/30">
                        <TableIndexTd :index="index" />
                        <td class="px-4 py-3">
                            <Link :href="`/projects/${p.id}`" class="font-medium text-primary hover:underline">{{ p.name }}</Link>
                        </td>
                        <td class="px-4 py-3 tabular-nums">{{ formatNumber(p.ownership_percentage) }}%</td>
                        <td class="px-4 py-3 tabular-nums">{{ money(p.entitled_share) }}</td>
                        <td class="px-4 py-3 tabular-nums">{{ money(p.paid_total) }}</td>
                        <td class="px-4 py-3 tabular-nums">{{ money(p.balance_owed) }}</td>
                        <td class="px-4 py-3 whitespace-nowrap text-muted-foreground">{{ formatDate(p.created_at) }}</td>
                    </tr>
                    <tr v-if="!projects.length">
                        <td colspan="7" class="px-4 py-10 text-center text-muted-foreground">
                            {{ t('Not assigned to any project yet. Add from a project page.') }}
                        </td>
                    </tr>
                </tbody>
            </table>
            </TableFrame>
        </div>

        <DetailGrid
            :title="t('Contact details')"
            :items="[
                { label: t('Partner No'), value: item.partner_no },
                { label: t('Phone'), value: item.phone },
                { label: t('Email'), value: item.email },
                { label: t('National ID'), value: item.national_id },
                { label: t('Address'), value: item.address, span: true },
                { label: t('Notes'), value: item.notes, span: true },
            ]"
        />
    </div>
</template>
