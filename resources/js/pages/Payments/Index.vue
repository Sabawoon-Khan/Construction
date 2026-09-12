<script setup lang="ts">
import EmptyState from '@/components/EmptyState.vue';
import MisSearchInput from '@/components/mis/MisSearchInput.vue';
import RowActionsMenu from '@/components/RowActionsMenu.vue';
import SortableTh from '@/components/SortableTh.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import TableIndexTd from '@/components/TableIndexTd.vue';
import TableIndexTh from '@/components/TableIndexTh.vue';
import TableToolbar from '@/components/TableToolbar.vue';
import {
    V2FilterBar,
    V2Hero,
    V2IndicatorCard,
    V2ListPage,
    V2Pager,
    V2StatCard,
    V2StatGrid,
    V2TablePanel,
} from '@/components/v2';
import { indexTableColumn } from '@/composables/useTableColumns';
import { useCurrency } from '@/composables/useCurrency';
import { useMisFilters } from '@/composables/useMisFilters';
import { useMisPage } from '@/composables/useMisPage';
import { provideTableSort } from '@/composables/useTableSort';
import { useTranslations } from '@/composables/useTranslations';
import type { RowActionItem } from '@/lib/row-actions';
import { buildMoneyBars, buildShareSegments, compactMoneyByCurrency } from '@/lib/v2Money';
import { money, moneyByCurrency } from '@/lib/utils';
import { Banknote, CalendarDays, Eye, ListOrdered, Printer, Wallet } from '@lucide/vue';
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps<{
    items: { data: any[]; links: any[]; meta?: any };
    filters: { search?: string };
    totals: {
        count: number; amount: number; amount_base: number; paid_out?: number; paid_out_base?: number;
        this_month: number; this_month_base: number; cash?: number; cash_base?: number;
        amount_by_currency?: Record<string, number>; paid_out_by_currency?: Record<string, number>; this_month_by_currency?: Record<string, number>;
        receipts_count?: number; disbursements_count?: number;
    };
    moneyChart?: Array<{ key: string; label: string; by_currency: { AFN: number; USD: number } }>;
}>();

defineOptions({ layout: { breadcrumbs: [{ title: 'Dashboard', href: '/dashboard' }, { title: 'Payments', href: '/payments' }] } });
const onlyKeys = ['items', 'filters', 'totals', 'moneyChart'];
const { filters, pending } = useMisFilters('/payments', { search: props.filters.search ?? '' }, { search: '' }, { only: onlyKeys, liveKeys: ['search'] });
const { sortedRows } = provideTableSort(() => props.items.data);
const { t } = useTranslations();
const tableColumns = computed(() => [
    indexTableColumn(),
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
const flow = computed(() => {
    const inAmt = Number(props.totals.amount ?? 0);
    const outAmt = Number(props.totals.paid_out ?? 0);
    const base = buildShareSegments([
        { key: 'in', label: t('Collected'), value: inAmt, color: 'var(--brand-accent)' },
        { key: 'out', label: t('Paid out'), value: outAmt, color: 'var(--school-gold)' },
    ]);
    return { ...base, inShare: base.total > 0 ? Math.round((inAmt / base.total) * 100) : 0 };
});
const moneyBars = computed(() => buildMoneyBars(props.moneyChart));
function rowActions(item: any): RowActionItem[] {
    return [
        { label: t('View'), icon: Eye, href: `/payments/${item.id}` },
        { label: t('Print'), icon: Printer, href: `/payments/${item.id}` },
    ];
}
</script>
<template>
    <Head :title="t('Payments')" />
    <V2ListPage>
        <V2Hero image="/images/dashboard-v2-building.png">
            <template #eyebrow>{{ t('Finance') }}</template>
            <template #title>{{ t('Payments') }}</template>
            <template #description>{{ t('Receipts and disbursements — cash movement by month and currency.') }}</template>
            <template #side>
                <div class="hero-cards">
                    <V2IndicatorCard card-class="inventory-card">
                        <template #head>{{ t('Cash flow') }}</template>
                        <template #meta>{{ flow.inShare }}% {{ t('Collected') }}</template>
                        <div class="inventory-hero"><div class="inventory-hero-copy"><strong>{{ compactMoneyByCurrency(totals.amount_by_currency, totals.amount) }}</strong><small>{{ t('Collected') }}</small></div></div>
                        <div class="inventory-bar" aria-hidden="true"><i v-for="seg in flow.segments" :key="seg.key" :style="{ width: `${seg.width}%`, background: seg.color }" /></div>
                        <ul class="indicator-list compact"><li v-for="seg in flow.segments" :key="seg.key"><i :style="{ background: seg.color }" /><span>{{ seg.label }}</span><b>{{ compactMoneyByCurrency(seg.key === 'in' ? totals.amount_by_currency : totals.paid_out_by_currency, seg.value) }}</b></li></ul>
                    </V2IndicatorCard>
                    <V2IndicatorCard card-class="money-card">
                        <template #head>{{ t('Receipts by month') }}</template>
                        <template #meta><span :title="moneyByCurrency(totals.amount_by_currency, totals.amount)">{{ compactMoneyByCurrency(totals.amount_by_currency, totals.amount) }}</span></template>
                        <div class="money-legend" aria-hidden="true"><span><i class="afn" /> AFN</span><span><i class="usd" /> USD</span></div>
                        <div class="money-chart"><div v-for="bar in moneyBars" :key="bar.key" class="money-col" :class="{ peak: bar.peak }" :title="`${bar.label}: ${compactMoneyByCurrency(bar.by_currency)}`"><div class="money-pair"><i class="afn" :style="{ height: `${bar.afnHeight}px` }" /><i class="usd" :style="{ height: `${bar.usdHeight}px` }" /></div><span>{{ bar.label }}</span></div></div>
                    </V2IndicatorCard>
                </div>
            </template>
            <template #stats>
                <V2StatGrid>
                    <V2StatCard :delay="0" :title="t('Entries')" :value="totals.count"><template #icon><ListOrdered /></template></V2StatCard>
                    <V2StatCard :delay="1" icon-tone="teal" :title="t('Collected')"><template #icon><Wallet /></template><span>{{ t('Collected') }}</span><strong>{{ compactMoneyByCurrency(totals.amount_by_currency, totals.amount) }}</strong></V2StatCard>
                    <V2StatCard :delay="2" icon-tone="orange" :title="t('Paid to suppliers')"><template #icon><Banknote /></template><span>{{ t('Paid to suppliers') }}</span><strong>{{ compactMoneyByCurrency(totals.paid_out_by_currency, totals.paid_out ?? 0) }}</strong></V2StatCard>
                    <V2StatCard :delay="3" accent icon-tone="warm" :title="t('This month')"><template #icon><CalendarDays /></template><span>{{ t('This month') }}</span><strong>{{ compactMoneyByCurrency(totals.this_month_by_currency, totals.this_month) }}</strong></V2StatCard>
                </V2StatGrid>
            </template>
        </V2Hero>
        <V2TablePanel table-id="payments" :columns="tableColumns" :pending="pending && items.data.length > 0">
            <template #filters>
                <V2FilterBar>
                    <div class="filter-search">
                        <MisSearchInput v-model="filters.search" :placeholder="t('Search...')" />
                    </div>
                    <template #columns><TableToolbar /></template>
                </V2FilterBar>
            </template>
            <template #default="{ visibleColCount }">
                <table>
                    <thead><tr>
                        <TableIndexTh /><SortableTh column="payment_no">{{ t('No') }}</SortableTh><SortableTh column="paid_at">{{ t('Date') }}</SortableTh><SortableTh column="type">{{ t('Type') }}</SortableTh><SortableTh column="customer.name">{{ t('Party') }}</SortableTh><SortableTh column="sale.sale_no">{{ t('Linked') }}</SortableTh><SortableTh column="amount">{{ t('Amount') }}</SortableTh><SortableTh column="payment_method">{{ t('Method') }}</SortableTh><SortableTh column="status">{{ t('Status') }}</SortableTh><SortableTh column="creator.name">{{ t('Added by') }}</SortableTh><SortableTh column="created_at">{{ t('Created at') }}</SortableTh><th class="end">{{ t('Actions') }}</th>
                    </tr></thead>
                    <tbody>
                        <tr v-for="(item, index) in sortedRows" :key="item.id" :style="{ '--i': index }">
                            <TableIndexTd :index="index" :from="items.meta?.from" />
                            <td><Link :href="`/payments/${item.id}`" class="code-chip">{{ item.payment_no }}</Link></td>
                            <td class="muted nowrap">{{ formatDate(item.paid_at) }}</td>
                            <td>{{ item.type === 'disbursement' ? t('Paid out') : t('Receipt') }}</td>
                            <td>{{ item.supplier?.name || item.customer?.name || '—' }}</td>
                            <td class="muted">{{ item.purchase?.purchase_no || item.sale?.sale_no || '—' }}</td>
                            <td class="nums">{{ money(item.amount, item.currency || baseCurrency) }}</td>
                            <td class="muted">{{ item.payment_method }}</td>
                            <td><StatusBadge :status="item.status" /></td>
                            <td class="muted">{{ item.creator?.name || '—' }}</td>
                            <td class="muted nowrap">{{ formatDate(item.created_at) }}</td>
                            <td class="end"><RowActionsMenu :actions="rowActions(item)" /></td>
                        </tr>
                        <EmptyState v-if="!items.data.length" :colspan="visibleColCount" :title="t('No payments yet')" />
                    </tbody>
                </table>
            </template>
            <template #pager><V2Pager :items="items" :only="onlyKeys" /></template>
        </V2TablePanel>
    </V2ListPage>
</template>
