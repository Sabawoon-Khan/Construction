<script setup lang="ts">
import EmptyState from '@/components/EmptyState.vue';
import { MisTableActions } from '@/components/mis';
import MisSearchInput from '@/components/mis/MisSearchInput.vue';
import RowActionsMenu from '@/components/RowActionsMenu.vue';
import SortableTh from '@/components/SortableTh.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import TableIndexTd from '@/components/TableIndexTd.vue';
import TableIndexTh from '@/components/TableIndexTh.vue';
import TableToolbar from '@/components/TableToolbar.vue';
import { CreateButton } from '@/components/ui/create-button';
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
import { Banknote, Receipt, Wallet } from '@lucide/vue';
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps<{
    items: { data: any[]; links: any[]; meta?: any };
    filters: { search?: string };
    totals: {
        count: number; total_amount: number; total_amount_base: number; paid: number; paid_base: number;
        remaining: number; remaining_base: number;
        total_by_currency?: Record<string, number>; paid_by_currency?: Record<string, number>; remaining_by_currency?: Record<string, number>;
        unpaid_count?: number; partial_count?: number; paid_count?: number;
    };
    moneyChart?: Array<{ key: string; label: string; by_currency: { AFN: number; USD: number } }>;
}>();

defineOptions({ layout: { breadcrumbs: [{ title: 'Dashboard', href: '/dashboard' }, { title: 'Purchases', href: '/purchases' }] } });
const onlyKeys = ['items', 'filters', 'totals', 'moneyChart'];
const { filters, pending } = useMisFilters('/purchases', { search: props.filters.search ?? '' }, { search: '' }, { only: onlyKeys, liveKeys: ['search'] });
const { sortedRows } = provideTableSort(() => props.items.data);
const { t } = useTranslations();
const tableColumns = computed(() => [
    indexTableColumn(),
    { key: 'purchase_no', label: t('Purchase No') },
    { key: 'purchase_date', label: t('Date') },
    { key: 'project.name', label: t('Project') },
    { key: 'supplier.name', label: t('Supplier') },
    { key: 'total_amount', label: t('Total') },
    { key: 'paid_amount', label: t('Paid') },
    { key: 'remaining_amount', label: t('Remaining') },
    { key: 'status', label: t('Status') },
    { key: 'creator.name', label: t('Added by') },
    { key: 'created_at', label: t('Created at') },
    { key: 'actions', label: t('Actions'), locked: true },
]);
const { baseCurrency } = useCurrency();
const { viewAction, editAction, deleteAction, can, formatDate } = useMisPage();
const collection = computed(() => {
    const paid = Number(props.totals.paid ?? 0);
    const remaining = Number(props.totals.remaining ?? 0);
    const base = buildShareSegments([
        { key: 'paid', label: t('Paid'), value: paid, color: 'var(--brand-accent)' },
        { key: 'remaining', label: t('Remaining'), value: remaining, color: 'var(--school-gold)' },
    ]);
    return { ...base, paidShare: base.total > 0 ? Math.round((paid / base.total) * 100) : 0 };
});
const moneyBars = computed(() => buildMoneyBars(props.moneyChart));
function rowActions(item: any): RowActionItem[] {
    return [
        viewAction(`/purchases/${item.id}`),
        { label: t('Record payment'), icon: Banknote, href: `/purchases/${item.id}#pay`, hidden: !can('purchases.edit') || Number(item.remaining_amount ?? 0) <= 0 },
        editAction(`/purchases/${item.id}/edit`, 'purchases.edit'),
        deleteAction({ href: `/purchases/${item.id}`, title: t('Delete purchase'), description: t('This also removes the linked project expense and stock in, if the stock is still unused.'), trashType: 'purchases' }, 'purchases.delete'),
    ];
}
</script>
<template>
    <Head :title="t('Purchases')" />
    <V2ListPage>
        <V2Hero image="/images/dashboard-v2-building.png">
            <template #eyebrow>{{ t('Procurement') }}</template>
            <template #title>{{ t('Purchases') }}</template>
            <template #description>{{ t('Supplier bills with paid vs remaining and monthly purchase volume.') }}</template>
            <template #side>
                <div class="hero-cards">
                    <V2IndicatorCard card-class="inventory-card">
                        <template #head>{{ t('Collection') }}</template>
                        <template #meta>{{ collection.paidShare }}% {{ t('Paid') }}</template>
                        <div class="inventory-hero"><div class="inventory-hero-copy"><strong>{{ compactMoneyByCurrency(totals.paid_by_currency, totals.paid) }}</strong><small>{{ t('Paid') }}</small></div></div>
                        <div class="inventory-bar" aria-hidden="true"><i v-for="seg in collection.segments" :key="seg.key" :style="{ width: `${seg.width}%`, background: seg.color }" /></div>
                        <ul class="indicator-list compact"><li v-for="seg in collection.segments" :key="seg.key"><i :style="{ background: seg.color }" /><span>{{ seg.label }}</span><b>{{ compactMoneyByCurrency(seg.key === 'paid' ? totals.paid_by_currency : totals.remaining_by_currency, seg.value) }}</b></li></ul>
                    </V2IndicatorCard>
                    <V2IndicatorCard card-class="money-card">
                        <template #head>{{ t('Purchases by month') }}</template>
                        <template #meta><span :title="moneyByCurrency(totals.total_by_currency, totals.total_amount)">{{ compactMoneyByCurrency(totals.total_by_currency, totals.total_amount) }}</span></template>
                        <div class="money-legend" aria-hidden="true"><span><i class="afn" /> AFN</span><span><i class="usd" /> USD</span></div>
                        <div class="money-chart"><div v-for="bar in moneyBars" :key="bar.key" class="money-col" :class="{ peak: bar.peak }" :title="`${bar.label}: ${compactMoneyByCurrency(bar.by_currency)}`"><div class="money-pair"><i class="afn" :style="{ height: `${bar.afnHeight}px` }" /><i class="usd" :style="{ height: `${bar.usdHeight}px` }" /></div><span>{{ bar.label }}</span></div></div>
                    </V2IndicatorCard>
                </div>
            </template>
            <template #stats>
                <V2StatGrid>
                    <V2StatCard :delay="0" :title="t('Bills')" :value="totals.count"><template #icon><Receipt /></template></V2StatCard>
                    <V2StatCard :delay="1" icon-tone="warm" :title="t('Purchase total')"><template #icon><Wallet /></template><span>{{ t('Purchase total') }}</span><strong>{{ compactMoneyByCurrency(totals.total_by_currency, totals.total_amount) }}</strong></V2StatCard>
                    <V2StatCard :delay="2" icon-tone="teal" :title="t('Paid')"><template #icon><Banknote /></template><span>{{ t('Paid') }}</span><strong>{{ compactMoneyByCurrency(totals.paid_by_currency, totals.paid) }}</strong></V2StatCard>
                    <V2StatCard :delay="3" accent icon-tone="orange" :title="t('Remaining owed')"><template #icon><Banknote /></template><span>{{ t('Remaining owed') }}</span><strong>{{ compactMoneyByCurrency(totals.remaining_by_currency, totals.remaining) }}</strong></V2StatCard>
                </V2StatGrid>
            </template>
        </V2Hero>
        <V2TablePanel table-id="purchases" :columns="tableColumns" :pending="pending && items.data.length > 0" :print-title="t('Purchases')">
            <template #filters>
                <V2FilterBar>
                    <div class="filter-search">
                        <MisSearchInput v-model="filters.search" :placeholder="t('Search...')" />
                    </div>
                    <template #columns>
                        <MisTableActions export-url="/purchases/export" :filters="filters">
                            <CreateButton href="/purchases/create" permission="purchases.create">{{ t('Create') }}</CreateButton>
                        </MisTableActions>
                        <TableToolbar />
                    </template>
                </V2FilterBar>
            </template>
            <template #default="{ visibleColCount }">
                <table>
                    <thead><tr>
                        <TableIndexTh /><SortableTh column="purchase_no">{{ t('Purchase No') }}</SortableTh><SortableTh column="purchase_date">{{ t('Date') }}</SortableTh><SortableTh column="project.name">{{ t('Project') }}</SortableTh><SortableTh column="supplier.name">{{ t('Supplier') }}</SortableTh><SortableTh column="total_amount">{{ t('Total') }}</SortableTh><SortableTh column="paid_amount">{{ t('Paid') }}</SortableTh><SortableTh column="remaining_amount">{{ t('Remaining') }}</SortableTh><SortableTh column="status">{{ t('Status') }}</SortableTh><SortableTh column="creator.name">{{ t('Added by') }}</SortableTh><SortableTh column="created_at">{{ t('Created at') }}</SortableTh><th class="end">{{ t('Actions') }}</th>
                    </tr></thead>
                    <tbody>
                        <tr v-for="(item, index) in sortedRows" :key="item.id" :style="{ '--i': index }">
                            <TableIndexTd :index="index" :from="items.meta?.from" />
                            <td><Link :href="`/purchases/${item.id}`" class="code-chip">{{ item.purchase_no || '—' }}</Link></td>
                            <td class="muted nowrap">{{ formatDate(item.purchase_date) }}</td>
                            <td>{{ item.project?.name || '—' }}</td><td>{{ item.supplier?.name || '—' }}</td>
                            <td class="nums">{{ money(item.total_amount, item.currency || baseCurrency) }}</td>
                            <td class="nums">{{ money(item.paid_amount, item.currency || baseCurrency) }}</td>
                            <td class="nums">{{ money(item.remaining_amount, item.currency || baseCurrency) }}</td>
                            <td><StatusBadge :status="item.status" /></td>
                            <td class="muted">{{ item.creator?.name || '—' }}</td><td class="muted nowrap">{{ formatDate(item.created_at) }}</td>
                            <td class="end"><RowActionsMenu :actions="rowActions(item)" /></td>
                        </tr>
                        <EmptyState v-if="!items.data.length" :colspan="visibleColCount" :title="t('No purchases yet')">
                        </EmptyState>
                    </tbody>
                </table>
            </template>
            <template #pager><V2Pager :items="items" :only="onlyKeys" /></template>
        </V2TablePanel>
    </V2ListPage>
</template>
