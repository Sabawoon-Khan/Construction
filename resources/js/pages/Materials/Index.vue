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
import { MisFilterBar, MisLoadingState, MisSearchInput, MisStatCard, MisStatGrid } from '@/components/mis';
import Pagination from '@/components/Pagination.vue';
import RowActionsMenu from '@/components/RowActionsMenu.vue';
import { useInertiaLoading } from '@/composables/useInertiaLoading';
import { useMisPage } from '@/composables/useMisPage';
import { provideTableSort } from '@/composables/useTableSort';
import { useTranslations } from '@/composables/useTranslations';
import type { RowActionItem } from '@/lib/row-actions';
import { money } from '@/lib/utils';

type Item = Record<string, any>;

const props = defineProps<{
    items: { data: Item[]; links: any[]; meta?: any };
    filters: { search?: string };
    totals: { count: number; active: number };
}>();

const search = ref(props.filters.search ?? '');
const { sortedRows } = provideTableSort(() => props.items.data);
const { t } = useTranslations();
const tableColumns = computed(() => [
    indexTableColumn(),
    { key: 'code', label: t('Code') },
    { key: 'name', label: t('Name') },
    { key: 'unit.unit_name', label: t('Unit') },
    { key: 'unit_cost', label: t('Unit cost') },
    { key: 'minimum_stock', label: t('Min stock') },
    { key: 'creator.name', label: t('Added by') },
    { key: 'created_at', label: t('Created at') },
    { key: 'actions', label: t('Actions'), locked: true },
]);
const { editAction, can, formatDate } = useMisPage();
const { loading } = useInertiaLoading();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Materials', href: '/materials' },
        ],
    },
});

function applySearch() {
    router.get('/materials', { search: search.value || undefined }, { preserveState: true, replace: true });
}

function clearFilters() {
    search.value = '';
    applySearch();
}

function rowActions(item: Item): RowActionItem[] {
    return [
        editAction(`/materials/${item.id}/edit`, 'materials.edit'),
        { separator: true, isLabel: true, label: t('Danger') },
        {
            label: t('Delete'),
            icon: Trash2,
            method: 'delete',
            href: `/materials/${item.id}`,
            variant: 'destructive' as const,
            hidden: !can('materials.delete'),
            confirm: {
                title: t('Delete material'),
                description: t('Are you sure you want to delete this material?'),
                confirmLabel: t('Delete'),
            },
        },
    ];
}
</script>

<template>
    <Head :title="t('Materials')" />

    <div class="flex h-full flex-1 flex-col gap-4">
        <MisFilterBar :apply-label="t('Apply')" :clear-label="t('All')" @apply="applySearch" @clear="clearFilters">
            <MisSearchInput v-model="search" class="min-w-[220px] flex-1" :placeholder="t('Search materials...')" />
        </MisFilterBar>

        <MisStatGrid :columns="2">
            <MisStatCard :label="t('Materials')" :value="totals.count" />
            <MisStatCard :label="t('Active')" :value="totals.active" />
        </MisStatGrid>

        <MisLoadingState v-if="loading" />

        <div
            v-else
            class="overflow-hidden rounded-2xl border border-border/80 bg-card shadow-soft"
        >
            <TableFrame table-id="materials" :columns="tableColumns">
                <table class="min-w-full text-sm">
                    <thead class="bg-muted/30 text-start text-muted-foreground">
                        <tr>
                            <TableIndexTh />
                            <SortableTh column="code">{{ t('Code') }}</SortableTh>
                            <SortableTh column="name">{{ t('Name') }}</SortableTh>
                            <SortableTh column="unit.unit_name">{{ t('Unit') }}</SortableTh>
                            <SortableTh column="unit_cost">{{ t('Unit cost') }}</SortableTh>
                            <SortableTh column="minimum_stock">{{ t('Min stock') }}</SortableTh>
                            <SortableTh column="creator.name">{{ t('Added by') }}</SortableTh>
                            <SortableTh column="created_at">{{ t('Created at') }}</SortableTh>
                            <th class="px-4 py-3 font-medium text-end">{{ t('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(item, index) in sortedRows" :key="item.id" class="border-b last:border-0 transition-colors hover:bg-muted/30">
                            <TableIndexTd :index="index" :from="items.meta?.from" />
                            <td class="px-4 py-3 font-medium">{{ item.code }}</td>
                            <td class="px-4 py-3">{{ item.name }}</td>
                            <td class="px-4 py-3">{{ item.unit?.unit_name || '—' }}</td>
                            <td class="px-4 py-3 tabular-nums">{{ money(item.unit_cost, item.currency) }}</td>
                            <td class="px-4 py-3 tabular-nums">{{ item.minimum_stock }}</td>
                            <td class="px-4 py-3 text-muted-foreground">{{ item.creator?.name || '—' }}</td>
                            <td class="px-4 py-3 whitespace-nowrap text-muted-foreground">{{ formatDate(item.created_at) }}</td>
                            <td class="px-4 py-3">
                                <div class="flex justify-end">
                                    <RowActionsMenu :actions="rowActions(item)" />
                                </div>
                            </td>
                        </tr>
                        <EmptyState v-if="!items.data.length" :colspan="9" :title="t('No materials yet')" />
                    </tbody>
                </table>
            </TableFrame>
            <Pagination :paginator="items" />
        </div>
    </div>
</template>
