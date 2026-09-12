<script setup lang="ts">
import EmptyState from '@/components/EmptyState.vue';
import MisSearchInput from '@/components/mis/MisSearchInput.vue';
import RowActionsMenu from '@/components/RowActionsMenu.vue';
import SortableTh from '@/components/SortableTh.vue';
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
import { useMisFilters } from '@/composables/useMisFilters';
import { useMisPage } from '@/composables/useMisPage';
import { provideTableSort } from '@/composables/useTableSort';
import { useTranslations } from '@/composables/useTranslations';
import type { RowActionItem } from '@/lib/row-actions';
import { buildMoneyBars, buildShareSegments, compactMoneyByCurrency } from '@/lib/v2Money';
import { moneyByCurrency } from '@/lib/utils';
import { Banknote, Plus, Truck, Wallet } from '@lucide/vue';
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';

type Item = Record<string, any>;
const props = defineProps<{
    items: { data: Item[]; links: any[]; meta?: any };
    filters: { search?: string };
    totals: {
        count: number;
        purchases_total: number;
        paid_total: number;
        remaining_total: number;
        purchases_by_currency?: Record<string, number>;
        paid_by_currency?: Record<string, number>;
        remaining_by_currency?: Record<string, number>;
    };
    moneyChart?: Array<{ key: string; label: string; by_currency: { AFN: number; USD: number } }>;
}>();

defineOptions({ layout: { breadcrumbs: [{ title: 'Dashboard', href: '/dashboard' }, { title: 'Suppliers', href: '/suppliers' }] } });
const onlyKeys = ['items', 'filters', 'totals', 'moneyChart'];
const { filters, pending } = useMisFilters('/suppliers', { search: props.filters.search ?? '' }, { search: '' }, { only: onlyKeys, liveKeys: ['search'] });
const { sortedRows } = provideTableSort(() => props.items.data);
const { t } = useTranslations();
const tableColumns = computed(() => [
    indexTableColumn(),
    { key: 'supplier_no', label: t('Supplier No') },
    { key: 'name', label: t('Name') },
    { key: 'contact_person', label: t('Contact') },
    { key: 'phone', label: t('Phone') },
    { key: 'purchases_count', label: t('Purchases') },
    { key: 'creator.name', label: t('Added by') },
    { key: 'created_at', label: t('Created at') },
    { key: 'actions', label: t('Actions'), locked: true },
]);
const { viewAction, editAction, deleteAction, can, formatDate } = useMisPage();
const collection = computed(() => {
    const paid = Number(props.totals.paid_total ?? 0);
    const remaining = Number(props.totals.remaining_total ?? 0);
    const base = buildShareSegments([
        { key: 'paid', label: t('Paid'), value: paid, color: 'var(--brand-accent)' },
        { key: 'remaining', label: t('Remaining'), value: remaining, color: 'var(--school-gold)' },
    ]);
    return { ...base, paidShare: base.total > 0 ? Math.round((paid / base.total) * 100) : 0 };
});
const moneyBars = computed(() => buildMoneyBars(props.moneyChart));
function rowActions(item: Item): RowActionItem[] {
    return [
        viewAction(`/suppliers/${item.id}`),
        editAction(`/suppliers/${item.id}/edit`, 'suppliers.edit'),
        deleteAction({ href: `/suppliers/${item.id}`, title: t('Delete supplier'), description: t('Are you sure you want to delete this supplier? This action cannot be undone.'), trashType: 'suppliers' }, 'suppliers.delete'),
    ];
}
</script>
<template>
    <Head :title="t('Suppliers')" />
    <V2ListPage>
        <V2Hero image="/images/dashboard-v2-building.png">
            <template #eyebrow>{{ t('Procurement') }}</template>
            <template #title>{{ t('Suppliers') }}</template>
            <template #description>{{ t('Vendor roster with purchase totals, paid balance, and monthly volume.') }}</template>
            <template #side>
                <Link v-if="can('suppliers.create')" href="/suppliers/create" class="create-btn"><Plus />{{ t('Create') }}</Link>
                <div class="hero-cards">
                    <V2IndicatorCard card-class="inventory-card">
                        <template #head>{{ t('Payables') }}</template>
                        <template #meta>{{ collection.paidShare }}% {{ t('Paid') }}</template>
                        <div class="inventory-hero"><div class="inventory-hero-copy"><strong>{{ compactMoneyByCurrency(totals.paid_by_currency, totals.paid_total) }}</strong><small>{{ t('Paid') }}</small></div></div>
                        <div class="inventory-bar" aria-hidden="true"><i v-for="seg in collection.segments" :key="seg.key" :style="{ width: `${seg.width}%`, background: seg.color }" /></div>
                        <ul class="indicator-list compact"><li v-for="seg in collection.segments" :key="seg.key"><i :style="{ background: seg.color }" /><span>{{ seg.label }}</span><b>{{ compactMoneyByCurrency(seg.key === 'paid' ? totals.paid_by_currency : totals.remaining_by_currency, seg.value) }}</b></li></ul>
                    </V2IndicatorCard>
                    <V2IndicatorCard card-class="money-card">
                        <template #head>{{ t('Purchases by month') }}</template>
                        <template #meta><span :title="moneyByCurrency(totals.purchases_by_currency, totals.purchases_total)">{{ compactMoneyByCurrency(totals.purchases_by_currency, totals.purchases_total) }}</span></template>
                        <div class="money-legend" aria-hidden="true"><span><i class="afn" /> AFN</span><span><i class="usd" /> USD</span></div>
                        <div class="money-chart"><div v-for="bar in moneyBars" :key="bar.key" class="money-col" :class="{ peak: bar.peak }" :title="`${bar.label}: ${compactMoneyByCurrency(bar.by_currency)}`"><div class="money-pair"><i class="afn" :style="{ height: `${bar.afnHeight}px` }" /><i class="usd" :style="{ height: `${bar.usdHeight}px` }" /></div><span>{{ bar.label }}</span></div></div>
                    </V2IndicatorCard>
                </div>
            </template>
            <template #stats>
                <V2StatGrid>
                    <V2StatCard :delay="0" :title="t('Suppliers')" :value="totals.count"><template #icon><Truck /></template></V2StatCard>
                    <V2StatCard :delay="1" icon-tone="warm" :title="t('Purchases')"><template #icon><Wallet /></template><span>{{ t('Purchases') }}</span><strong>{{ compactMoneyByCurrency(totals.purchases_by_currency, totals.purchases_total) }}</strong></V2StatCard>
                    <V2StatCard :delay="2" icon-tone="teal" :title="t('Paid')"><template #icon><Banknote /></template><span>{{ t('Paid') }}</span><strong>{{ compactMoneyByCurrency(totals.paid_by_currency, totals.paid_total) }}</strong></V2StatCard>
                    <V2StatCard :delay="3" accent icon-tone="orange" :title="t('Remaining owed')"><template #icon><Banknote /></template><span>{{ t('Remaining owed') }}</span><strong>{{ compactMoneyByCurrency(totals.remaining_by_currency, totals.remaining_total) }}</strong></V2StatCard>
                </V2StatGrid>
            </template>
        </V2Hero>
        <V2TablePanel table-id="suppliers" :columns="tableColumns" :pending="pending && items.data.length > 0">
            <template #filters>
                <V2FilterBar>
                    <div class="filter-search">
                        <MisSearchInput v-model="filters.search" :placeholder="t('Search suppliers...')" />
                    </div>
                    <template #columns><TableToolbar /></template>
                </V2FilterBar>
            </template>
            <template #default="{ visibleColCount }">
                <table>
                    <thead><tr><TableIndexTh /><SortableTh column="supplier_no">{{ t('Supplier No') }}</SortableTh><SortableTh column="name">{{ t('Name') }}</SortableTh><SortableTh column="contact_person">{{ t('Contact') }}</SortableTh><SortableTh column="phone">{{ t('Phone') }}</SortableTh><SortableTh column="purchases_count">{{ t('Purchases') }}</SortableTh><SortableTh column="creator.name">{{ t('Added by') }}</SortableTh><SortableTh column="created_at">{{ t('Created at') }}</SortableTh><th class="end">{{ t('Actions') }}</th></tr></thead>
                    <tbody>
                        <tr v-for="(item, index) in sortedRows" :key="item.id" :style="{ '--i': index }">
                            <TableIndexTd :index="index" :from="items.meta?.from" />
                            <td><Link :href="`/suppliers/${item.id}`" class="code-chip">{{ item.supplier_no || '—' }}</Link></td>
                            <td>{{ item.name || '—' }}</td><td>{{ item.contact_person || '—' }}</td><td class="muted">{{ item.phone || '—' }}</td>
                            <td><span class="pill">{{ item.purchases_count ?? 0 }}</span></td>
                            <td class="muted">{{ item.creator?.name || '—' }}</td><td class="muted nowrap">{{ formatDate(item.created_at) }}</td>
                            <td class="end"><RowActionsMenu :actions="rowActions(item)" /></td>
                        </tr>
                        <EmptyState v-if="!items.data.length" :colspan="visibleColCount" :title="t('No suppliers yet')"><template #actions><Link v-if="can('suppliers.create')" href="/suppliers/create" class="create-btn"><Plus />{{ t('Create') }}</Link></template></EmptyState>
                    </tbody>
                </table>
            </template>
            <template #pager><V2Pager :items="items" :only="onlyKeys" /></template>
        </V2TablePanel>
    </V2ListPage>
</template>
