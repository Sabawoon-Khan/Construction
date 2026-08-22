<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Trash2 } from '@lucide/vue';
import { computed, ref } from 'vue';
import EmptyState from '@/components/EmptyState.vue';
import SortableTh from '@/components/SortableTh.vue';
import TableFrame from '@/components/TableFrame.vue';
import { MisFilterBar, MisSearchInput, MisSelectField, MisStatCard, MisStatGrid } from '@/components/mis';
import Pagination from '@/components/Pagination.vue';
import RowActionsMenu from '@/components/RowActionsMenu.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { useMisPage } from '@/composables/useMisPage';
import { provideTableSort } from '@/composables/useTableSort';
import { useTranslations } from '@/composables/useTranslations';
import type { RowActionItem } from '@/lib/row-actions';
import { money } from '@/lib/utils';

const props = defineProps<{
    items: { data: any[]; links: any[]; meta?: any };
    filters: any;
    projects: any[];
    totals: { count: number; available: number; reserved: number; sold: number };
}>();
const search = ref(props.filters.search ?? '');
const projectId = ref(String(props.filters.project_id ?? ''));
const status = ref(props.filters.status ?? '');
const { sortedRows } = provideTableSort(() => props.items.data);
const { t } = useTranslations();
const tableColumns = computed(() => [
    { key: 'unit_no', label: t('Unit') },
    { key: 'project.name', label: t('Project') },
    { key: 'floor_number', label: t('Floor') },
    { key: 'type', label: t('Type') },
    { key: 'area', label: t('Area') },
    { key: 'bedrooms', label: t('Beds') },
    { key: 'cost_price', label: t('Planned cost') },
    { key: 'list_price', label: t('Sale price') },
    { key: 'sales.0.customer.name', label: t('Buyer') },
    { key: 'status', label: t('Status') },
    { key: 'creator.name', label: t('Added by') },
    { key: 'actions', label: t('Actions'), locked: true },
]);
const { viewAction, editAction, can } = useMisPage();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Units', href: '/units' },
        ],
    },
});

const apply = () =>
    router.get(
        '/units',
        {
            search: search.value || undefined,
            project_id: projectId.value || undefined,
            status: status.value || undefined,
        },
        { preserveState: true, replace: true },
    );

function clearFilters() {
    search.value = '';
    projectId.value = '';
    status.value = '';
    apply();
}

const projectOptions = computed(() => [
    { value: '', label: t('All projects') },
    ...props.projects.map((p: any) => ({ value: String(p.id), label: p.name })),
]);

const unitStatuses = ['available', 'reserved', 'sold'];

function rowActions(item: any): RowActionItem[] {
    return [
        viewAction(`/units/${item.id}`),
        editAction(`/units/${item.id}/edit`, 'units.edit'),
        ...(can('units.edit')
            ? [
                  { separator: true, isLabel: true, label: t('Change status') },
                  ...unitStatuses.map((status) => ({
                      label: t(status.charAt(0).toUpperCase() + status.slice(1)),
                      method: 'patch' as const,
                      href: `/units/${item.id}/status`,
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
            href: `/units/${item.id}`,
            variant: 'destructive' as const,
            hidden: !can('units.delete'),
            confirm: {
                title: t('Delete unit'),
                description: t('Are you sure you want to delete this unit? This action cannot be undone.'),
                confirmLabel: t('Delete'),
            },
        },
    ];
}
</script>

<template>
    <Head :title="t('Units & Homes')" />
    <div class="flex h-full flex-1 flex-col gap-4">
        <MisFilterBar :apply-label="t('Apply')" :clear-label="t('All')" @apply="apply" @clear="clearFilters">
            <MisSearchInput v-model="search" class="min-w-[180px] flex-1" :placeholder="t('Unit no...')" />
            <MisSelectField
                v-model="projectId"
                class="min-w-[160px]"
                :label="t('Project')"
                :options="projectOptions"
            />
            <MisSelectField
                v-model="status"
                class="min-w-[160px]"
                :label="t('Status')"
                :options="[
                    { value: '', label: t('All statuses') },
                    { value: 'available', label: t('Available') },
                    { value: 'reserved', label: t('Reserved') },
                    { value: 'sold', label: t('Sold') },
                ]"
            />
        </MisFilterBar>

        <MisStatGrid :columns="4">
            <MisStatCard :label="t('Units')" :value="totals.count" />
            <MisStatCard :label="t('Available')" :value="totals.available" tone="success" />
            <MisStatCard :label="t('Reserved')" :value="totals.reserved" tone="warning" />
            <MisStatCard :label="t('Sold')" :value="totals.sold" />
        </MisStatGrid>

        <div class="overflow-hidden rounded-2xl border border-border/80 bg-card shadow-soft">
            <TableFrame table-id="units" :columns="tableColumns">
                <table class="min-w-full text-sm">
                    <thead class="bg-muted/30 text-start text-muted-foreground">
                        <tr>
                            <SortableTh column="unit_no">{{ t('Unit') }}</SortableTh>
                            <SortableTh column="project.name">{{ t('Project') }}</SortableTh>
                            <SortableTh column="floor_number">{{ t('Floor') }}</SortableTh>
                            <SortableTh column="type">{{ t('Type') }}</SortableTh>
                            <SortableTh column="area">{{ t('Area') }}</SortableTh>
                            <SortableTh column="bedrooms">{{ t('Beds') }}</SortableTh>
                            <SortableTh column="cost_price">{{ t('Planned cost') }}</SortableTh>
                            <SortableTh column="list_price">{{ t('Sale price') }}</SortableTh>
                            <SortableTh column="sales.0.customer.name">{{ t('Buyer') }}</SortableTh>
                            <SortableTh column="status">{{ t('Status') }}</SortableTh>
                            <SortableTh column="creator.name">{{ t('Added by') }}</SortableTh>

                            <th class="px-4 py-3 font-medium text-end">{{ t('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="item in sortedRows" :key="item.id" class="border-b last:border-0 transition-colors hover:bg-muted/30">
                            <td class="px-4 py-3 font-medium">
                                <Link :href="`/units/${item.id}`" class="hover:text-primary hover:underline">{{ item.unit_no }}</Link>
                            </td>
                            <td class="px-4 py-3">{{ item.project?.name }}</td>
                            <td class="px-4 py-3">{{ item.floor_number ?? '—' }}</td>
                            <td class="px-4 py-3 capitalize">{{ item.type }}</td>
                            <td class="px-4 py-3">{{ item.area ?? '—' }}</td>
                            <td class="px-4 py-3">{{ item.bedrooms ?? '—' }}</td>
                            <td class="px-4 py-3 tabular-nums">{{ money(item.cost_price, item.currency) }}</td>
                            <td class="px-4 py-3 tabular-nums">{{ money(item.list_price, item.currency) }}</td>
                            <td class="px-4 py-3">{{ item.sales?.[0]?.customer?.name || '—' }}</td>
                            <td class="px-4 py-3"><StatusBadge :status="item.status" /></td>
                            <td class="px-4 py-3 text-muted-foreground">{{ item.creator?.name || '—' }}</td>

                            <td class="px-4 py-3">
                                <div class="flex justify-end">
                                    <RowActionsMenu :actions="rowActions(item)" />
                                </div>
                            </td>
                        </tr>
                        <EmptyState v-if="!items.data.length" :colspan="12" :title="t('No units yet')" />
                    </tbody>
                </table>
            </TableFrame>
            <Pagination :paginator="items" />
        </div>
    </div>
</template>
