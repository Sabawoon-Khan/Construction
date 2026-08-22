<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Eye, Printer } from '@lucide/vue';
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
    totals: { count: number; amount: number; amount_base: number; paid_out?: number; paid_out_base?: number; this_month: number; this_month_base: number; cash: number; cash_base: number; amount_by_currency?: Record<string, number>; paid_out_by_currency?: Record<string, number>; this_month_by_currency?: Record<string, number> };
}>();
const search = ref(props.filters.search ?? '');
const { sortedRows } = provideTableSort(() => props.items.data);
const { t } = useTranslations();
const tableColumns = computed(() => [
    { key: 'payment_no', label: t('No') },
    { key: 'paid_at', label: t('Date') },
    { key: 'type', label: t('Type') },
    { key: 'customer.name', label: t('Party') },
    { key: 'sale.sale_no', label: t('Linked') },
    { key: 'amount', label: t('Amount') },
    { key: 'payment_method', label: t('Method') },
    { key: 'status', label: t('Status') },
    { key: 'creator.name', label: t('Added by') },
    { key: 'created_at', label: t('Created at') },
    { key: 'actions', label: t('Actions'), locked: true },
]);
const { baseCurrency } = useCurrency();
const { formatDate } = useMisPage();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Payments', href: '/payments' },
        ],
    },
});

const apply = () => router.get('/payments', { search: search.value || undefined }, { preserveState: true, replace: true });
const clearFilters = () => {
    search.value = '';
    apply();
};

function rowActions(item: any): RowActionItem[] {
    return [
        { label: t('View'), icon: Eye, href: `/payments/${item.id}` },
        { label: t('Print'), icon: Printer, href: `/payments/${item.id}` },
    ];
}
</script>

<template>
    <Head :title="t('Payments')" />
    <div class="flex h-full flex-1 flex-col gap-4">
        <MisFilterBar :apply-label="t('Apply')" :clear-label="t('All')" @apply="apply" @clear="clearFilters">
            <MisSearchInput v-model="search" class="min-w-[220px] flex-1" :placeholder="t('Search...')" />
        </MisFilterBar>

        <MisStatGrid :columns="4">
            <MisStatCard :label="t('Entries')" :value="totals.count" />
            <MisStatCard :label="t('Collected')" :value="moneyByCurrency(totals.amount_by_currency, totals.amount)" tone="success" />
            <MisStatCard :label="t('Paid to suppliers')" :value="moneyByCurrency(totals.paid_out_by_currency, totals.paid_out ?? 0)" tone="danger" />
            <MisStatCard :label="t('This month')" :value="moneyByCurrency(totals.this_month_by_currency, totals.this_month)" />
        </MisStatGrid>

        <div class="overflow-hidden rounded-2xl border border-border/80 bg-card shadow-soft">
            <TableFrame table-id="payments" :columns="tableColumns">
                <table class="min-w-full text-sm">
                    <thead class="bg-muted/30 text-start text-muted-foreground">
                        <tr>
                            <SortableTh column="payment_no">{{ t('No') }}</SortableTh>
                            <SortableTh column="paid_at">{{ t('Date') }}</SortableTh>
                            <SortableTh column="type">{{ t('Type') }}</SortableTh>
                            <SortableTh column="customer.name">{{ t('Party') }}</SortableTh>
                            <SortableTh column="sale.sale_no">{{ t('Linked') }}</SortableTh>
                            <SortableTh column="amount">{{ t('Amount') }}</SortableTh>
                            <SortableTh column="payment_method">{{ t('Method') }}</SortableTh>
                            <SortableTh column="status">{{ t('Status') }}</SortableTh>
                            <SortableTh column="creator.name">{{ t('Added by') }}</SortableTh>
                            <SortableTh column="created_at">{{ t('Created at') }}</SortableTh>
                            <th class="px-4 py-3 font-medium text-end">{{ t('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="item in sortedRows" :key="item.id" class="border-b last:border-0 transition-colors hover:bg-muted/30">
                            <td class="px-4 py-3 font-medium">
                                <Link :href="`/payments/${item.id}`" class="hover:text-primary hover:underline">{{ item.payment_no }}</Link>
                            </td>
                            <td class="px-4 py-3">{{ formatDate(item.paid_at) }}</td>
                            <td class="px-4 py-3 capitalize">{{ item.type === 'disbursement' ? t('Paid out') : t('Receipt') }}</td>
                            <td class="px-4 py-3">{{ item.supplier?.name || item.customer?.name || '—' }}</td>
                            <td class="px-4 py-3">{{ item.purchase?.purchase_no || item.sale?.sale_no || '—' }}</td>
                            <td class="px-4 py-3 tabular-nums">{{ money(item.amount, item.currency || baseCurrency) }}</td>
                            <td class="px-4 py-3 capitalize">{{ item.payment_method }}</td>
                            <td class="px-4 py-3"><StatusBadge :status="item.status" /></td>
                            <td class="px-4 py-3 text-muted-foreground">{{ item.creator?.name || '—' }}</td>
                            <td class="px-4 py-3 whitespace-nowrap text-muted-foreground">{{ formatDate(item.created_at) }}</td>
                            <td class="px-4 py-3">
                                <div class="flex justify-end">
                                    <RowActionsMenu :actions="rowActions(item)" />
                                </div>
                            </td>
                        </tr>
                        <EmptyState v-if="!items.data.length" :colspan="11" :title="t('No payments yet')" />
                    </tbody>
                </table>
            </TableFrame>
            <Pagination :paginator="items" />
        </div>
    </div>
</template>
