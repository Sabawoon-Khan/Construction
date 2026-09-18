<script setup lang="ts">
import EmptyState from '@/components/EmptyState.vue';
import { MisTableActions } from '@/components/mis';
import MisSearchInput from '@/components/mis/MisSearchInput.vue';
import RowActionsMenu from '@/components/RowActionsMenu.vue';
import SortableTh from '@/components/SortableTh.vue';
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
import { useMisFilters } from '@/composables/useMisFilters';
import { useMisPage } from '@/composables/useMisPage';
import { provideTableSort } from '@/composables/useTableSort';
import { useTranslations } from '@/composables/useTranslations';
import type { RowActionItem } from '@/lib/row-actions';
import { buildMoneyBars, buildShareSegments, compactMoneyByCurrency } from '@/lib/v2Money';
import { moneyByCurrency } from '@/lib/utils';
import { Briefcase, Handshake, Wallet } from '@lucide/vue';
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';

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
    moneyChart?: Array<{ key: string; label: string; by_currency: { AFN: number; USD: number } }>;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Settings', href: '/settings/appearance' },
            { title: 'Shareholders', href: '/partners' },
        ],
    },
});

const onlyKeys = ['items', 'filters', 'totals', 'moneyChart'];
const { filters, pending } = useMisFilters(
    '/partners',
    { search: props.filters.search ?? '' },
    { search: '' },
    { only: onlyKeys, liveKeys: ['search'] },
);
const { sortedRows } = provideTableSort(() => props.items.data);
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
const { viewAction, editAction, deleteAction, can, formatDate } = useMisPage();

const coverage = computed(() => {
    const withProjects = Number(props.totals.with_projects ?? 0);
    const without = Math.max(0, Number(props.totals.count ?? 0) - withProjects);
    const base = buildShareSegments([
        { key: 'with', label: t('On projects'), value: withProjects, color: 'var(--brand-accent)' },
        { key: 'without', label: t('No projects'), value: without, color: 'var(--muted-foreground)' },
    ]);
    const share = base.total > 0 ? Math.round((withProjects / base.total) * 100) : 0;
    return { ...base, share, withProjects };
});
const moneyBars = computed(() => buildMoneyBars(props.moneyChart));

function rowActions(item: Item): RowActionItem[] {
    return [
        viewAction(`/partners/${item.id}`),
        editAction(`/partners/${item.id}/edit`, 'partners.edit'),
        deleteAction(
            {
                href: `/partners/${item.id}`,
                title: t('Delete shareholder'),
                description: t('Are you sure you want to delete this shareholder? This action cannot be undone.'),
                trashType: 'partners',
            },
            'partners.delete',
        ),
    ];
}
</script>

<template>
    <Head :title="t('Shareholders')" />
    <V2ListPage>
        <V2Hero image="/images/dashboard-v2-building.png">
            <template #eyebrow>{{ t('Ownership') }}</template>
            <template #title>{{ t('Shareholders') }}</template>
            <template #description>{{ t('Partners, project coverage, contributions and payouts.') }}</template>
            <template #side>
                <div class="hero-cards">
                    <V2IndicatorCard card-class="inventory-card">
                        <template #head>{{ t('Coverage') }}</template>
                        <template #meta>{{ coverage.share }}% {{ t('On projects') }}</template>
                        <div class="inventory-hero"><div class="inventory-hero-copy"><strong>{{ coverage.withProjects }}</strong><small>{{ t('On projects') }}</small></div></div>
                        <div class="inventory-bar" aria-hidden="true"><i v-for="seg in coverage.segments" :key="seg.key" :style="{ width: `${seg.width}%`, background: seg.color }" /></div>
                        <ul class="indicator-list compact"><li v-for="seg in coverage.segments" :key="seg.key"><i :style="{ background: seg.color }" /><span>{{ seg.label }}</span><b>{{ seg.value }}</b></li></ul>
                    </V2IndicatorCard>
                    <V2IndicatorCard card-class="money-card">
                        <template #head>{{ t('Contributions by month') }}</template>
                        <template #meta><span :title="moneyByCurrency(totals.contributions_by_currency, totals.contributions)">{{ compactMoneyByCurrency(totals.contributions_by_currency, totals.contributions) }}</span></template>
                        <div class="money-legend" aria-hidden="true"><span><i class="afn" /> AFN</span><span><i class="usd" /> USD</span></div>
                        <div class="money-chart">
                            <div v-for="bar in moneyBars" :key="bar.key" class="money-col" :class="{ peak: bar.peak }" :title="`${bar.label}: ${compactMoneyByCurrency(bar.by_currency)}`">
                                <div class="money-pair"><i class="afn" :style="{ height: `${bar.afnHeight}px` }" /><i class="usd" :style="{ height: `${bar.usdHeight}px` }" /></div>
                                <span>{{ bar.label }}</span>
                            </div>
                        </div>
                    </V2IndicatorCard>
                </div>
            </template>
            <template #stats>
                <V2StatGrid>
                    <V2StatCard :delay="0" :title="t('Shareholders')" :value="totals.count"><template #icon><Handshake /></template></V2StatCard>
                    <V2StatCard :delay="1" icon-tone="teal" :title="t('On projects')" :value="totals.with_projects"><template #icon><Briefcase /></template></V2StatCard>
                    <V2StatCard :delay="2" icon-tone="warm" :title="t('Contributed')"><template #icon><Wallet /></template><span>{{ t('Contributed') }}</span><strong>{{ compactMoneyByCurrency(totals.contributions_by_currency, totals.contributions) }}</strong></V2StatCard>
                    <V2StatCard :delay="3" accent icon-tone="orange" :title="t('Paid out')"><template #icon><Wallet /></template><span>{{ t('Paid out') }}</span><strong>{{ compactMoneyByCurrency(totals.payouts_by_currency, totals.payouts) }}</strong></V2StatCard>
                </V2StatGrid>
            </template>
        </V2Hero>
        <V2TablePanel table-id="partners" :columns="tableColumns" :pending="pending && items.data.length > 0" :print-title="t('Shareholders')">
            <template #filters>
                <V2FilterBar>
                    <div class="filter-search">
                        <MisSearchInput v-model="filters.search" :placeholder="t('Search...')" />
                    </div>
                    <template #columns>
                        <MisTableActions export-url="/partners/export" :filters="filters">
                            <CreateButton href="/partners/create" permission="partners.create">{{ t('Create') }}</CreateButton>
                        </MisTableActions>
                        <TableToolbar />
                    </template>
                </V2FilterBar>
            </template>
            <template #default="{ visibleColCount }">
                <table>
                    <thead><tr>
                        <TableIndexTh /><SortableTh column="partner_no">{{ t('Partner No') }}</SortableTh><SortableTh column="name">{{ t('Name') }}</SortableTh><SortableTh column="phone">{{ t('Phone') }}</SortableTh><SortableTh column="projects_count">{{ t('Projects') }}</SortableTh><SortableTh column="creator.name">{{ t('Added by') }}</SortableTh><SortableTh column="created_at">{{ t('Created at') }}</SortableTh><th class="end">{{ t('Actions') }}</th>
                    </tr></thead>
                    <tbody>
                        <tr v-for="(item, index) in sortedRows" :key="item.id" :style="{ '--i': index }">
                            <TableIndexTd :index="index" :from="items.meta?.from" />
                            <td><Link :href="`/partners/${item.id}`" class="code-chip">{{ item.partner_no || '—' }}</Link></td>
                            <td>{{ item.name || '—' }}</td>
                            <td class="muted">{{ item.phone || '—' }}</td>
                            <td><span class="pill">{{ item.projects_count ?? 0 }}</span></td>
                            <td class="muted">{{ item.creator?.name || '—' }}</td>
                            <td class="muted nowrap">{{ formatDate(item.created_at) }}</td>
                            <td class="end"><RowActionsMenu :actions="rowActions(item)" /></td>
                        </tr>
                        <EmptyState v-if="!items.data.length" :colspan="visibleColCount" :title="t('No shareholders yet')">
                        </EmptyState>
                    </tbody>
                </table>
            </template>
            <template #pager><V2Pager :items="items" :only="onlyKeys" /></template>
        </V2TablePanel>
    </V2ListPage>
</template>
