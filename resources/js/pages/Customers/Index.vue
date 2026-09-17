<script setup lang="ts">
import EmptyState from '@/components/EmptyState.vue';
import { MisTableActions } from '@/components/mis';
import MisSearchInput from '@/components/mis/MisSearchInput.vue';
import SortableTh from '@/components/SortableTh.vue';
import TableIndexTd from '@/components/TableIndexTd.vue';
import TableIndexTh from '@/components/TableIndexTh.vue';
import TableToolbar from '@/components/TableToolbar.vue';
import RowActionsMenu from '@/components/RowActionsMenu.vue';
import StatusBadge from '@/components/StatusBadge.vue';
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
import { buildMoneyBars, compactMoneyByCurrency } from '@/lib/v2Money';
import { money, moneyByCurrency } from '@/lib/utils';
import { Banknote, ShoppingBag, Users, Wallet } from '@lucide/vue';
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';

type Item = Record<string, any>;

const props = defineProps<{
    items: { data: Item[]; links: any[]; meta?: any };
    filters: { search?: string };
    totals: {
        count: number;
        with_sales: number;
        without_sales?: number;
        sales_total: number;
        payments_total: number;
        sales_by_currency?: Record<string, number>;
        payments_by_currency?: Record<string, number>;
    };
    moneyChart?: Array<{
        key: string;
        label: string;
        by_currency: { AFN: number; USD: number };
    }>;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Customers', href: '/customers' },
        ],
    },
});

const onlyKeys = ['items', 'filters', 'totals', 'moneyChart'];

const { filters, pending, apply } = useMisFilters(
    '/customers',
    { search: props.filters.search ?? '' },
    { search: '' },
    { only: onlyKeys, liveKeys: ['search'] },
);
const { sortedRows } = provideTableSort(() => props.items.data);
const { t } = useTranslations();
const tableColumns = computed(() => [
    indexTableColumn(),
    { key: 'customer_no', label: t('Customer No') },
    { key: 'name', label: t('Name') },
    { key: 'takhallus', label: t('Last name') },
    { key: 'father_name', label: t('Father name') },
    { key: 'phone', label: t('Phone') },
    { key: 'address', label: t('Address') },
    { key: 'registered_at', label: t('Registration date') },
    { key: 'type', label: t('Customer type') },
    { key: 'purchase_amount', label: t('Purchase amount') },
    { key: 'paid_amount', label: t('Amount paid') },
    { key: 'remaining', label: t('Remaining') },
    { key: 'last_purchase_at', label: t('Last purchase') },
    { key: 'status', label: t('Status') },
    { key: 'notes', label: t('Notes') },
    { key: 'created_at', label: t('Created at') },
    { key: 'actions', label: t('Actions'), locked: true },
]);
const { viewAction, editAction, deleteAction, can, formatDate } = useMisPage();

const coverage = computed(() => {
    const withSales = Number(props.totals.with_sales ?? 0);
    const withoutSales = Number(
        props.totals.without_sales ??
            Math.max(0, Number(props.totals.count ?? 0) - withSales),
    );
    const total = Math.max(withSales + withoutSales, Number(props.totals.count ?? 0), 0);
    const withShare = total > 0 ? Math.round((withSales / total) * 100) : 0;

    return {
        withSales,
        withoutSales,
        total,
        withShare,
        segments: [
            {
                key: 'with',
                label: t('With sales'),
                value: withSales,
                color: 'var(--brand-accent)',
                width:
                    total > 0
                        ? Math.max(withSales > 0 ? 6 : 0, (withSales / total) * 100)
                        : 0,
            },
            {
                key: 'without',
                label: t('Without sales'),
                value: withoutSales,
                color: 'var(--muted-foreground)',
                width:
                    total > 0
                        ? Math.max(
                              withoutSales > 0 ? 6 : 0,
                              (withoutSales / total) * 100,
                          )
                        : 0,
            },
        ],
    };
});

const moneyBars = computed(() => buildMoneyBars(props.moneyChart));

function customerType(item: Item): string {
    return item.type === 'wholesale' ? t('Wholesale') : t('Retail');
}

function rowActions(item: Item): RowActionItem[] {
    return [
        viewAction(`/customers/${item.id}`),
        editAction(`/customers/${item.id}/edit`, 'customers.edit'),
        deleteAction(
            {
                href: `/customers/${item.id}`,
                title: t('Delete customer'),
                description: t(
                    'Are you sure you want to delete this customer? This action cannot be undone.',
                ),
                trashType: 'customers',
            },
            'customers.delete',
        ),
    ];
}
</script>

<template>
    <Head :title="t('Customers')" />

    <V2ListPage>
        <V2Hero image="/images/dashboard-v2-building.png">
            <template #eyebrow>{{ t('People') }}</template>
            <template #title>{{ t('Customers') }}</template>
            <template #description>
                {{
                    t(
                        'Manage buyers, purchase history, and outstanding balances in one place.',
                    )
                }}
            </template>

            <template #side>
                <div class="hero-cards">
                    <V2IndicatorCard card-class="inventory-card">
                        <template #head>{{ t('Coverage') }}</template>
                        <template #meta
                            >{{ coverage.withShare }}% {{ t('With sales') }}</template
                        >

                        <div class="inventory-hero">
                            <div class="inventory-hero-copy">
                                <strong>{{ coverage.withSales }}</strong>
                                <small>{{ t('With sales') }}</small>
                            </div>
                        </div>

                        <div class="inventory-bar" aria-hidden="true">
                            <i
                                v-for="seg in coverage.segments"
                                :key="seg.key"
                                :style="{
                                    width: `${seg.width}%`,
                                    background: seg.color,
                                }"
                            />
                        </div>

                        <ul class="indicator-list compact">
                            <li
                                v-for="seg in coverage.segments"
                                :key="seg.key"
                            >
                                <i :style="{ background: seg.color }" />
                                <span>{{ seg.label }}</span>
                                <b>{{ seg.value }}</b>
                            </li>
                        </ul>
                    </V2IndicatorCard>

                    <V2IndicatorCard card-class="money-card">
                        <template #head>{{ t('Sales by month') }}</template>
                        <template #meta>
                            <span
                                :title="
                                    moneyByCurrency(
                                        totals.sales_by_currency,
                                        totals.sales_total,
                                    )
                                ">{{
                                    compactMoneyByCurrency(
                                        totals.sales_by_currency,
                                        totals.sales_total,
                                    )
                                }}</span
                            >
                        </template>

                        <div class="money-legend" aria-hidden="true">
                            <span><i class="afn" /> AFN</span>
                            <span><i class="usd" /> USD</span>
                        </div>

                        <div class="money-chart">
                            <div
                                v-for="bar in moneyBars"
                                :key="bar.key"
                                class="money-col"
                                :class="{ peak: bar.peak }"
                                :title="`${bar.label}: ${compactMoneyByCurrency(bar.by_currency)}`">
                                <div class="money-pair">
                                    <i
                                        class="afn"
                                        :style="{
                                            height: `${bar.afnHeight}px`,
                                        }"
                                    />
                                    <i
                                        class="usd"
                                        :style="{
                                            height: `${bar.usdHeight}px`,
                                        }"
                                    />
                                </div>
                                <span>{{ bar.label }}</span>
                            </div>
                        </div>
                    </V2IndicatorCard>
                </div>
            </template>

            <template #stats>
                <V2StatGrid>
                    <V2StatCard
                        :delay="0"
                        :title="t('Customers')"
                        :value="totals.count"
                    >
                        <template #icon><Users /></template>
                    </V2StatCard>
                    <V2StatCard
                        :delay="1"
                        icon-tone="teal"
                        :title="t('With sales')"
                        :value="totals.with_sales"
                    >
                        <template #icon><ShoppingBag /></template>
                    </V2StatCard>
                    <V2StatCard
                        :delay="2"
                        icon-tone="warm"
                        :title="t('Sales total')">
                        <template #icon><Wallet /></template>
                        <span>{{ t('Sales total') }}</span>
                        <strong
                            :title="
                                moneyByCurrency(
                                    totals.sales_by_currency,
                                    totals.sales_total,
                                )
                            ">{{
                                compactMoneyByCurrency(
                                    totals.sales_by_currency,
                                    totals.sales_total,
                                )
                            }}</strong
                        >
                    </V2StatCard>
                    <V2StatCard
                        :delay="3"
                        accent
                        icon-tone="orange"
                        :title="t('Payments received')">
                        <template #icon><Banknote /></template>
                        <span>{{ t('Payments received') }}</span>
                        <strong
                            :title="
                                moneyByCurrency(
                                    totals.payments_by_currency,
                                    totals.payments_total,
                                )
                            ">{{
                                compactMoneyByCurrency(
                                    totals.payments_by_currency,
                                    totals.payments_total,
                                )
                            }}</strong
                        >
                    </V2StatCard>
                </V2StatGrid>
            </template>
        </V2Hero>

        <V2TablePanel
            table-id="customers"
            :columns="tableColumns"
            :pending="pending && items.data.length > 0"
        >
            <template #filters>
                <V2FilterBar>
                    <div class="filter-search">
                        <MisSearchInput
                            v-model="filters.search"
                            :placeholder="t('Search customers...')"
                        />
                    </div>
                    <template #columns>
                        <MisTableActions export-url="/customers/export" :filters="filters">
                            <CreateButton href="/customers/create" permission="customers.create">{{ t('Create') }}</CreateButton>
                        </MisTableActions>
                        <TableToolbar />
                    </template>
                </V2FilterBar>
            </template>

            <template #default="{ visibleColCount }">
                <table>
                    <thead>
                        <tr>
                            <TableIndexTh />
                            <SortableTh column="customer_no">{{
                                t('Customer No')
                            }}</SortableTh>
                            <SortableTh column="name">{{
                                t('Name')
                            }}</SortableTh>
                            <SortableTh column="takhallus">{{
                                t('Last name')
                            }}</SortableTh>
                            <SortableTh column="father_name">{{
                                t('Father name')
                            }}</SortableTh>
                            <SortableTh column="phone">{{
                                t('Phone')
                            }}</SortableTh>
                            <SortableTh column="address">{{
                                t('Address')
                            }}</SortableTh>
                            <SortableTh column="registered_at">{{
                                t('Registration date')
                            }}</SortableTh>
                            <SortableTh column="type">{{
                                t('Customer type')
                            }}</SortableTh>
                            <SortableTh column="purchase_amount">{{
                                t('Purchase amount')
                            }}</SortableTh>
                            <SortableTh column="paid_amount">{{
                                t('Amount paid')
                            }}</SortableTh>
                            <SortableTh column="remaining">{{
                                t('Remaining')
                            }}</SortableTh>
                            <SortableTh column="last_purchase_at">{{
                                t('Last purchase')
                            }}</SortableTh>
                            <SortableTh column="status">{{
                                t('Status')
                            }}</SortableTh>
                            <SortableTh column="notes">{{
                                t('Notes')
                            }}</SortableTh>
                            <SortableTh column="created_at">{{
                                t('Created at')
                            }}</SortableTh>
                            <th class="end">{{ t('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="(item, index) in sortedRows"
                            :key="item.id"
                            :style="{ '--i': index }"
                        >
                            <TableIndexTd
                                :index="index"
                                :from="items.meta?.from"
                            />
                            <td class="nowrap">
                                <Link
                                    :href="`/customers/${item.id}`"
                                    class="code-chip"
                                >
                                    {{ item.customer_no || '—' }}
                                </Link>
                            </td>
                            <td class="nowrap">{{ item.name || '—' }}</td>
                            <td class="nowrap">{{ item.takhallus || '—' }}</td>
                            <td class="nowrap">{{ item.father_name || '—' }}</td>
                            <td class="nowrap">{{ item.phone || '—' }}</td>
                            <td class="muted" :title="item.address || undefined">
                                {{ item.address || '—' }}
                            </td>
                            <td class="muted nowrap">
                                {{ formatDate(item.registered_at) }}
                            </td>
                            <td class="nowrap">{{ customerType(item) }}</td>
                            <td class="nums nowrap">
                                {{ money(item.purchase_amount ?? 0) }}
                            </td>
                            <td class="nums nowrap">
                                {{ money(item.paid_amount ?? 0) }}
                            </td>
                            <td class="nums nowrap">
                                {{ money(item.remaining ?? 0) }}
                            </td>
                            <td class="muted nowrap">
                                {{ formatDate(item.last_purchase_at) }}
                            </td>
                            <td>
                                <StatusBadge :status="item.status || 'active'" />
                            </td>
                            <td class="muted" :title="item.notes || undefined">
                                {{ item.notes || '—' }}
                            </td>
                            <td class="muted nowrap">
                                {{ formatDate(item.created_at) }}
                            </td>
                            <td class="end">
                                <RowActionsMenu :actions="rowActions(item)" />
                            </td>
                        </tr>
                        <EmptyState
                            v-if="!items.data.length"
                            :colspan="visibleColCount"
                            :title="t('No customers yet')">
                        </EmptyState>
                    </tbody>
                </table>
            </template>

            <template #pager>
                <V2Pager :items="items" :only="onlyKeys" />
            </template>
        </V2TablePanel>
    </V2ListPage>
</template>
