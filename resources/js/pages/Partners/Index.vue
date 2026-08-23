<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Trash2 } from '@lucide/vue';
import { computed, ref } from 'vue';
import EmptyState from '@/components/EmptyState.vue';
import SortableTh from '@/components/SortableTh.vue';
import TableFrame from '@/components/TableFrame.vue';
import TableIndexTd from '@/components/TableIndexTd.vue';
import TableIndexTh from '@/components/TableIndexTh.vue';
import { indexTableColumn } from '@/composables/useTableColumns';
import { MisFilterBar, MisSearchInput, MisStatCard, MisStatGrid } from '@/components/mis';
import Pagination from '@/components/Pagination.vue';
import RowActionsMenu from '@/components/RowActionsMenu.vue';
import { useMisPage } from '@/composables/useMisPage';
import { provideTableSort } from '@/composables/useTableSort';
import { useTranslations } from '@/composables/useTranslations';
import type { RowActionItem } from '@/lib/row-actions';
import { moneyByCurrency } from '@/lib/utils';

type Item = Record<string, any>;
const props = defineProps<{
    items: { data: Item[]; links: any[]; meta?: any };
    filters: { search?: string };
    totals: {
        count: number;
        with_projects: number;
        contributions: number;
        payouts: number;
        contributions_by_currency?: Record<string, number>;
        payouts_by_currency?: Record<string, number>;
    };
}>();
const { t } = useTranslations();
const tableColumns = computed(() => [
    indexTableColumn(),
    { key: 'partner_no', label: t('Partner No') },
    { key: 'name', label: t('Name') },
    { key: 'phone', label: t('Phone') },
    { key: 'projects_count', label: t('Projects') },
    { key: 'creator.name', label: t('Added by') },
    { key: 'created_at', label: t('Created at') },
    { key: 'actions', label: t('Actions'), locked: true },
]);
const { viewAction, editAction, can, formatDate } = useMisPage();
const search = ref(props.filters.search ?? '');
const { sortedRows } = provideTableSort(() => props.items.data);

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Settings', href: '/settings/appearance' },
            { title: 'Shareholders', href: '/partners' },
        ],
    },
});

const applySearch = () =>
    router.get('/partners', { search: search.value || undefined }, { preserveState: true, replace: true });
const clearFilters = () => {
    search.value = '';
    applySearch();
};

function rowActions(item: Item): RowActionItem[] {
    return [
        viewAction(`/partners/${item.id}`),
        editAction(`/partners/${item.id}/edit`, 'partners.edit'),
        { separator: true, isLabel: true, label: t('Danger') },
        {
            label: t('Delete'),
            icon: Trash2,
            method: 'delete',
            href: `/partners/${item.id}`,
            variant: 'destructive' as const,
            hidden: !can('partners.delete'),
            confirm: {
                title: t('Delete shareholder'),
                description: t('Are you sure you want to delete this shareholder? This action cannot be undone.'),
                confirmLabel: t('Delete'),
            },
        },
    ];
}
</script>

<template>
    <Head :title="t('Shareholders')" />
    <div class="flex h-full flex-1 flex-col gap-4">
        <MisFilterBar :apply-label="t('Apply')" :clear-label="t('All')" @apply="applySearch" @clear="clearFilters">
            <MisSearchInput v-model="search" class="min-w-[220px] flex-1" :placeholder="t('Search...')" />
        </MisFilterBar>
        <MisStatGrid :columns="4">
            <MisStatCard :label="t('Shareholders')" :value="totals.count" />
            <MisStatCard :label="t('On projects')" :value="totals.with_projects" />
            <MisStatCard :label="t('Contributed')" :value="moneyByCurrency(totals.contributions_by_currency, totals.contributions)" />
            <MisStatCard :label="t('Paid out')" :value="moneyByCurrency(totals.payouts_by_currency, totals.payouts)" tone="success" />
        </MisStatGrid>

        <div class="overflow-hidden rounded-2xl border border-border/80 bg-card shadow-soft">
            <TableFrame table-id="partners" :columns="tableColumns">
                <table class="min-w-full text-sm">
                    <thead class="bg-muted/30 text-start text-muted-foreground">
                        <tr>
                            <TableIndexTh />
                            <SortableTh column="partner_no">{{ t('Partner No') }}</SortableTh>
                            <SortableTh column="name">{{ t('Name') }}</SortableTh>
                            <SortableTh column="phone">{{ t('Phone') }}</SortableTh>
                            <SortableTh column="projects_count">{{ t('Projects') }}</SortableTh>
                            <SortableTh column="creator.name">{{ t('Added by') }}</SortableTh>
                            <SortableTh column="created_at">{{ t('Created at') }}</SortableTh>
                            <th class="px-4 py-3 font-medium text-end">{{ t('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(item, index) in sortedRows" :key="item.id" class="border-b last:border-0 transition-colors hover:bg-muted/30">
                            <TableIndexTd :index="index" :from="items.meta?.from" />
                            <td class="px-4 py-3 font-medium">
                                <Link :href="`/partners/${item.id}`" class="hover:text-primary hover:underline">{{ item.partner_no || '—' }}</Link>
                            </td>
                            <td class="px-4 py-3">{{ item.name || '—' }}</td>
                            <td class="px-4 py-3">{{ item.phone || '—' }}</td>
                            <td class="px-4 py-3 tabular-nums">{{ item.projects_count ?? 0 }}</td>
                            <td class="px-4 py-3 text-muted-foreground">{{ item.creator?.name || '—' }}</td>
                            <td class="px-4 py-3 whitespace-nowrap text-muted-foreground">{{ formatDate(item.created_at) }}</td>
                            <td class="px-4 py-3">
                                <div class="flex justify-end">
                                    <RowActionsMenu :actions="rowActions(item)" />
                                </div>
                            </td>
                        </tr>
                        <EmptyState v-if="!items.data.length" :colspan="8" :title="t('No shareholders yet')" />
                    </tbody>
                </table>
            </TableFrame>
            <Pagination :paginator="items" />
        </div>
    </div>
</template>
