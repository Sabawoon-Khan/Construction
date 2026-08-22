<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Banknote, Printer, Trash2 } from '@lucide/vue';
import { computed, ref } from 'vue';
import EmptyState from '@/components/EmptyState.vue';
import SortableTh from '@/components/SortableTh.vue';
import TableFrame from '@/components/TableFrame.vue';
import { MisFilterBar, MisSearchInput, MisStatCard, MisStatGrid } from '@/components/mis';
import Pagination from '@/components/Pagination.vue';
import RowActionsMenu from '@/components/RowActionsMenu.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { useCurrency } from '@/composables/useCurrency';
import { useMisPage } from '@/composables/useMisPage';
import { provideTableSort } from '@/composables/useTableSort';
import { useTranslations } from '@/composables/useTranslations';
import type { RowActionItem } from '@/lib/row-actions';
import { money, moneyByCurrency } from '@/lib/utils';

const props = defineProps<{
    items: { data: any[]; links: any[]; meta?: any };
    filters: { search?: string };
    totals: {
        count: number;
        total_price: number;
        total_price_base: number;
        remaining: number;
        remaining_base: number;
        total_by_currency?: Record<string, number>;
        remaining_by_currency?: Record<string, number>;
    };
}>();
const search = ref(props.filters.search ?? '');
const { sortedRows } = provideTableSort(() => props.items.data);
const { t } = useTranslations();
const tableColumns = computed(() => [
    { key: 'sale_no', label: t('Sale No') },
    { key: 'customer.name', label: t('Customer') },
    { key: 'project.name', label: t('Project') },
    { key: 'unit.unit_no', label: t('Unit') },
    { key: 'unit.list_price', label: t('Sale price') },
    { key: 'discount', label: t('Discount') },
    { key: 'total_price', label: t('Total') },
    { key: 'remaining', label: t('Remaining') },
    { key: 'status', label: t('Status') },
    { key: 'creator.name', label: t('Added by') },
    { key: 'created_at', label: t('Created at') },
    { key: 'actions', label: t('Actions'), locked: true },
]);
const { baseCurrency } = useCurrency();
const { viewAction, editAction, can, formatDate } = useMisPage();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Sales', href: '/sales' },
        ],
    },
});

const apply = () => router.get('/sales', { search: search.value || undefined }, { preserveState: true, replace: true });
const clearFilters = () => {
    search.value = '';
    apply();
};
const saleTotal = (item: any) => Math.max(0, Number(item.unit?.list_price ?? 0) - Number(item.discount ?? 0));
const saleRemaining = (item: any) => Math.max(0, saleTotal(item) - Number(item.paid_total ?? 0));

const saleStatuses = ['draft', 'active', 'completed', 'cancelled'];

function rowActions(item: any): RowActionItem[] {
    return [
        viewAction(`/sales/${item.id}`),
        {
            label: t('Receive payment'),
            icon: Banknote,
            href: `/sales/${item.id}#receive-payment`,
            hidden: !can('payments.create'),
        },
        { label: t('Print'), icon: Printer, href: `/sales/${item.id}` },
        editAction(`/sales/${item.id}/edit`, 'sales.edit'),
        ...(can('sales.edit')
            ? [
                  { separator: true, isLabel: true, label: t('Change status') },
                  ...saleStatuses.map((status) => ({
                      label: t(status),
                      method: 'patch' as const,
                      href: `/sales/${item.id}/status`,
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
            href: `/sales/${item.id}`,
            variant: 'destructive' as const,
            hidden: !can('sales.delete'),
            confirm: {
                title: t('Delete sale'),
                description: t('Are you sure you want to delete this sale? This action cannot be undone.'),
                confirmLabel: t('Delete'),
            },
        },
    ];
}
</script>

<template>
    <Head :title="t('Sales')" />
    <div class="flex h-full flex-1 flex-col gap-4">
        <MisFilterBar :apply-label="t('Apply')" :clear-label="t('All')" @apply="apply" @clear="clearFilters">
            <MisSearchInput v-model="search" class="min-w-[220px] flex-1" :placeholder="t('Search...')" />
        </MisFilterBar>

        <MisStatGrid :columns="3">
            <MisStatCard :label="t('Contracts')" :value="totals.count" />
            <MisStatCard :label="t('Sales total')" :value="moneyByCurrency(totals.total_by_currency, totals.total_price)" />
            <MisStatCard :label="t('Remaining')" :value="moneyByCurrency(totals.remaining_by_currency, totals.remaining)" tone="warning" />
        </MisStatGrid>

        <div class="overflow-hidden rounded-2xl border border-border/80 bg-card shadow-soft">
            <TableFrame table-id="sales" :columns="tableColumns">
                <table class="min-w-full text-sm">
                    <thead class="bg-muted/30 text-start text-muted-foreground">
                        <tr>
                            <SortableTh column="sale_no">{{ t('Sale No') }}</SortableTh>
                            <SortableTh column="customer.name">{{ t('Customer') }}</SortableTh>
                            <SortableTh column="project.name">{{ t('Project') }}</SortableTh>
                            <SortableTh column="unit.unit_no">{{ t('Unit') }}</SortableTh>
                            <SortableTh column="unit.list_price">{{ t('Sale price') }}</SortableTh>
                            <SortableTh column="discount">{{ t('Discount') }}</SortableTh>
                            <SortableTh column="total_price">{{ t('Total') }}</SortableTh>
                            <SortableTh column="remaining">{{ t('Remaining') }}</SortableTh>
                            <SortableTh column="status">{{ t('Status') }}</SortableTh>
                            <SortableTh column="creator.name">{{ t('Added by') }}</SortableTh>
                            <SortableTh column="created_at">{{ t('Created at') }}</SortableTh>
                            <th class="px-4 py-3 font-medium text-end">{{ t('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="item in sortedRows" :key="item.id" class="border-b last:border-0 transition-colors hover:bg-muted/30">
                            <td class="px-4 py-3 font-medium">
                                <Link :href="`/sales/${item.id}`" class="hover:text-primary hover:underline">{{ item.sale_no }}</Link>
                            </td>
                            <td class="px-4 py-3">{{ item.customer?.name }}</td>
                            <td class="px-4 py-3">{{ item.project?.name }}</td>
                            <td class="px-4 py-3">{{ item.unit?.unit_no }}</td>
                            <td class="px-4 py-3 tabular-nums">{{ money(item.unit?.list_price, item.currency || baseCurrency) }}</td>
                            <td class="px-4 py-3 tabular-nums">{{ money(item.discount, item.currency || baseCurrency) }}</td>
                            <td class="px-4 py-3 tabular-nums">{{ money(saleTotal(item), item.currency || baseCurrency) }}</td>
                            <td class="px-4 py-3 tabular-nums text-amber-700">{{ money(saleRemaining(item), item.currency || baseCurrency) }}</td>
                            <td class="px-4 py-3"><StatusBadge :status="item.status" /></td>
                            <td class="px-4 py-3 text-muted-foreground">{{ item.creator?.name || '—' }}</td>
                            <td class="px-4 py-3 whitespace-nowrap text-muted-foreground">{{ formatDate(item.created_at) }}</td>
                            <td class="px-4 py-3">
                                <div class="flex justify-end">
                                    <RowActionsMenu :actions="rowActions(item)" />
                                </div>
                            </td>
                        </tr>
                        <EmptyState v-if="!items.data.length" :colspan="12" :title="t('No sales yet')" />
                    </tbody>
                </table>
            </TableFrame>
            <Pagination :paginator="items" />
        </div>
    </div>
</template>
