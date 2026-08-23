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
import StatusBadge from '@/components/StatusBadge.vue';
import { translateStatus } from '@/lib/translateStatus';
import { useMisPage } from '@/composables/useMisPage';
import { provideTableSort } from '@/composables/useTableSort';
import { useTranslations } from '@/composables/useTranslations';
import type { RowActionItem } from '@/lib/row-actions';
import { formatNumber, moneyByCurrency } from '@/lib/utils';

type Item = Record<string, any>;

const props = defineProps<{
    items: { data: Item[]; links: any[]; meta?: any };
    filters: { search?: string };
    totals: {
        count: number;
        active: number;
        units: number;
        sales_total: number;
        sales_by_currency?: Record<string, number>;
    };
}>();

const search = ref(props.filters.search ?? '');
const { sortedRows } = provideTableSort(() => props.items.data);
const { t } = useTranslations();
const tableColumns = computed(() => [
    indexTableColumn(),
    { key: 'code', label: t('Code') },
    { key: 'name', label: t('Name') },
    { key: 'location', label: t('Location') },
    { key: 'status', label: t('Status') },
    { key: 'progress_percent', label: t('Progress') },
    { key: 'units_count', label: t('Units') },
    { key: 'creator.name', label: t('Added by') },
    { key: 'created_at', label: t('Created at') },
    { key: 'actions', label: t('Actions'), locked: true },
]);
const { viewAction, editAction, can, formatDate } = useMisPage();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Projects', href: '/projects' },
        ],
    },
});

function applySearch() {
    router.get('/projects', { search: search.value || undefined }, { preserveState: true, replace: true });
}

function clearFilters() {
    search.value = '';
    applySearch();
}

const projectStatuses = ['planning', 'under_construction', 'ready_for_sale', 'sold', 'completed', 'on_hold'];

function statusLabel(status: string): string {
    return translateStatus(status, t);
}

function rowActions(item: Item): RowActionItem[] {
    return [
        viewAction(`/projects/${item.id}`),
        editAction(`/projects/${item.id}/edit`, 'projects.edit'),
        ...(can('projects.edit')
            ? [
                  { separator: true, isLabel: true, label: t('Change status') },
                  ...projectStatuses.map((status) => ({
                      label: statusLabel(status),
                      method: 'patch' as const,
                      href: `/projects/${item.id}/status`,
                      data: { status },
                      disabled: item.status === status,
                  })),
              ]
            : []),
        { separator: true, isLabel: true, label: t('Danger') },
        {
            label: t('Delete'),
            icon: Trash2,
            method: 'delete',
            href: `/projects/${item.id}`,
            variant: 'destructive' as const,
            hidden: !can('projects.delete'),
            confirm: {
                title: t('Delete project'),
                description: t('Are you sure you want to delete this project? This action cannot be undone.'),
                confirmLabel: t('Delete'),
            },
        },
    ];
}
</script>

<template>
    <Head :title="t('Projects')" />

    <div class="flex h-full flex-1 flex-col gap-4">
        <MisFilterBar :apply-label="t('Apply')" :clear-label="t('All')" @apply="applySearch" @clear="clearFilters">
            <MisSearchInput v-model="search" class="min-w-[220px] flex-1" :placeholder="t('Search projects...')" />
        </MisFilterBar>

        <MisStatGrid :columns="4">
            <MisStatCard :label="t('Projects')" :value="totals.count" />
            <MisStatCard :label="t('Active')" :value="totals.active" />
            <MisStatCard :label="t('Units')" :value="totals.units" />
            <MisStatCard :label="t('Sales total')" :value="moneyByCurrency(totals.sales_by_currency, totals.sales_total)" tone="success" />
        </MisStatGrid>

        <div class="overflow-hidden rounded-2xl border border-border/80 bg-card shadow-soft">
            <TableFrame table-id="projects" :columns="tableColumns">
                <table class="min-w-full text-sm">
                    <thead class="bg-muted/30 text-start text-muted-foreground">
                        <tr>
                            <TableIndexTh />
                            <SortableTh column="code">{{ t('Code') }}</SortableTh>
                            <SortableTh column="name">{{ t('Name') }}</SortableTh>
                            <SortableTh column="location">{{ t('Location') }}</SortableTh>
                            <SortableTh column="status">{{ t('Status') }}</SortableTh>
                            <SortableTh column="progress_percent">{{ t('Progress') }}</SortableTh>
                            <SortableTh column="units_count">{{ t('Units') }}</SortableTh>
                            <SortableTh column="creator.name">{{ t('Added by') }}</SortableTh>
                            <SortableTh column="created_at">{{ t('Created at') }}</SortableTh>
                            <th class="px-4 py-3 text-end font-medium">{{ t('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="(item, index) in sortedRows"
                            :key="item.id"
                            class="border-b last:border-0 transition-colors hover:bg-muted/30"
                        >
                            <TableIndexTd :index="index" :from="items.meta?.from" />
                            <td class="px-4 py-3 font-medium">
                                <Link :href="`/projects/${item.id}`" class="hover:text-primary hover:underline">
                                    {{ item.code || '—' }}
                                </Link>
                            </td>
                            <td class="px-4 py-3">{{ item.name || '—' }}</td>
                            <td class="px-4 py-3">{{ item.location || '—' }}</td>
                            <td class="px-4 py-3"><StatusBadge :status="item.status" /></td>
                            <td class="px-4 py-3 tabular-nums">{{ formatNumber(item.progress_percent) }}%</td>
                            <td class="px-4 py-3">{{ item.units_count }}</td>
                            <td class="px-4 py-3 text-muted-foreground">{{ item.creator?.name || '—' }}</td>
                            <td class="px-4 py-3 whitespace-nowrap text-muted-foreground">{{ formatDate(item.created_at) }}</td>
                            <td class="px-4 py-3">
                                <div class="flex justify-end">
                                    <RowActionsMenu :actions="rowActions(item)" />
                                </div>
                            </td>
                        </tr>
                        <EmptyState
                            v-if="!items.data.length"
                            :colspan="10"
                            :title="t('No projects yet')"
                        />
                    </tbody>
                </table>
            </TableFrame>
            <Pagination :paginator="items" />
        </div>
    </div>
</template>
