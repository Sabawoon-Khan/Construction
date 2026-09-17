<script setup lang="ts">
import EmptyState from '@/components/EmptyState.vue';
import SortableTh from '@/components/SortableTh.vue';
import TableIndexTd from '@/components/TableIndexTd.vue';
import TableIndexTh from '@/components/TableIndexTh.vue';
import TableToolbar from '@/components/TableToolbar.vue';
import RowActionsMenu from '@/components/RowActionsMenu.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { MisTableActions } from '@/components/mis';
import MisSearchInput from '@/components/mis/MisSearchInput.vue';
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
import { buildMoneyBars, compactMoneyByCurrency } from '@/lib/v2Money';
import { money, moneyByCurrency } from '@/lib/utils';
import {
    Banknote,
    FileText,
    Printer,
    Wallet,
} from '@lucide/vue';
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';

type Item = Record<string, any>;

const props = defineProps<{
    items: { data: Item[]; links: any[]; meta?: any };
    filters: { search?: string };
    totals: {
        count: number;
        total_price: number;
        total_price_base: number;
        paid?: number;
        paid_base?: number;
        remaining: number;
        remaining_base: number;
        total_by_currency?: Record<string, number>;
        paid_by_currency?: Record<string, number>;
        remaining_by_currency?: Record<string, number>;
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
            { title: 'Sales', href: '/sales' },
        ],
    },
});

const onlyKeys = ['items', 'filters', 'totals', 'moneyChart'];

const { filters, pending } = useMisFilters(
    '/sales',
    { search: props.filters.search ?? '' },
    { search: '' },
    { only: onlyKeys, liveKeys: ['search'] },
);
const { sortedRows } = provideTableSort(() => props.items.data);
const { t } = useTranslations();
const tableColumns = computed(() => [
    indexTableColumn(),
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
const { viewAction, editAction, deleteAction, can, formatDate } = useMisPage();

const saleTotal = (item: Item) =>
    Math.max(0, Number(item.unit?.list_price ?? 0) - Number(item.discount ?? 0));
const saleRemaining = (item: Item) =>
    Math.max(0, saleTotal(item) - Number(item.paid_total ?? 0));

const saleStatuses = ['draft', 'active', 'completed', 'cancelled'];

const collection = computed(() => {
    const paid = Number(props.totals.paid ?? 0);
    const remaining = Number(props.totals.remaining ?? 0);
    const total = Math.max(paid + remaining, 0);
    const paidShare = total > 0 ? Math.round((paid / total) * 100) : 0;

    return {
        paid,
        remaining,
        total,
        paidShare,
        segments: [
            {
                key: 'paid',
                label: t('Paid'),
                value: compactMoneyByCurrency(
                    props.totals.paid_by_currency,
                    paid,
                ),
                color: 'var(--brand-accent)',
                width:
                    total > 0
                        ? Math.max(paid > 0 ? 6 : 0, (paid / total) * 100)
                        : 0,
            },
            {
                key: 'remaining',
                label: t('Remaining'),
                value: compactMoneyByCurrency(
                    props.totals.remaining_by_currency,
                    remaining,
                ),
                color: 'var(--school-gold)',
                width:
                    total > 0
                        ? Math.max(
                              remaining > 0 ? 6 : 0,
                              (remaining / total) * 100,
                          )
                        : 0,
            },
        ],
    };
});

const moneyBars = computed(() => buildMoneyBars(props.moneyChart));

function rowActions(item: Item): RowActionItem[] {
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
        deleteAction(
            {
                href: `/sales/${item.id}`,
                title: t('Delete sale'),
                description: t(
                    'Are you sure you want to delete this sale? This action cannot be undone.',
                ),
                trashType: 'sales',
            },
            'sales.delete',
        ),
    ];
}
</script>

<template>
    <Head :title="t('Sales')" />

    <V2ListPage>
        <V2Hero image="/images/projects-v2-hero-luxury.png">
            <template #eyebrow>{{ t('Contracts') }}</template>
            <template #title>{{ t('Sales') }}</template>
            <template #description>
                {{
                    t(
                        'Track every contract from draft to completion — totals, payments, and remaining balance.',
                    )
                }}
            </template>

            <template #side>
                <div class="hero-cards">
                    <V2IndicatorCard card-class="inventory-card">
                        <template #head>{{ t('Collection') }}</template>
                        <template #meta
                            >{{ collection.paidShare }}% {{ t('Paid') }}</template
                        >

                        <div class="inventory-hero">
                            <div class="inventory-hero-copy">
                                <strong>{{
                                    compactMoneyByCurrency(
                                        totals.paid_by_currency,
                                        totals.paid,
                                    )
                                }}</strong>
                                <small>{{ t('Paid') }}</small>
                            </div>
                        </div>

                        <div class="inventory-bar" aria-hidden="true">
                            <i
                                v-for="seg in collection.segments"
                                :key="seg.key"
                                :style="{
                                    width: `${seg.width}%`,
                                    background: seg.color,
                                }"
                            />
                        </div>

                        <ul class="indicator-list compact">
                            <li
                                v-for="seg in collection.segments"
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
                                        totals.total_by_currency,
                                        totals.total_price,
                                    )
                                ">{{
                                    compactMoneyByCurrency(
                                        totals.total_by_currency,
                                        totals.total_price,
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
                        :title="t('Contracts')"
                        :value="totals.count"
                    >
                        <template #icon><FileText /></template>
                    </V2StatCard>
                    <V2StatCard
                        :delay="1"
                        icon-tone="warm"
                        :title="t('Sales total')">
                        <template #icon><Wallet /></template>
                        <span>{{ t('Sales total') }}</span>
                        <strong
                            :title="
                                moneyByCurrency(
                                    totals.total_by_currency,
                                    totals.total_price,
                                )
                            ">{{
                                compactMoneyByCurrency(
                                    totals.total_by_currency,
                                    totals.total_price,
                                )
                            }}</strong
                        >
                    </V2StatCard>
                    <V2StatCard
                        :delay="2"
                        icon-tone="orange"
                        :title="t('Remaining')">
                        <template #icon><Banknote /></template>
                        <span>{{ t('Remaining') }}</span>
                        <strong
                            :title="
                                moneyByCurrency(
                                    totals.remaining_by_currency,
                                    totals.remaining,
                                )
                            ">{{
                                compactMoneyByCurrency(
                                    totals.remaining_by_currency,
                                    totals.remaining,
                                )
                            }}</strong
                        >
                    </V2StatCard>
                    <V2StatCard
                        v-if="totals.paid != null"
                        :delay="3"
                        accent
                        icon-tone="teal"
                        :title="t('Paid')">
                        <template #icon><Banknote /></template>
                        <span>{{ t('Paid') }}</span>
                        <strong
                            :title="
                                moneyByCurrency(
                                    totals.paid_by_currency,
                                    totals.paid,
                                )
                            ">{{
                                compactMoneyByCurrency(
                                    totals.paid_by_currency,
                                    totals.paid,
                                )
                            }}</strong
                        >
                    </V2StatCard>
                </V2StatGrid>
            </template>
        </V2Hero>

        <V2TablePanel
            table-id="sales"
            :columns="tableColumns"
            :pending="pending && items.data.length > 0"
            :print-title="t('Sales')"
        >
            <template #filters>
                <V2FilterBar>
                    <div class="filter-search">
                        <MisSearchInput
                            v-model="filters.search"
                            :placeholder="t('Search...')"
                        />
                    </div>
                    <template #columns>
                        <MisTableActions export-url="/sales/export" :filters="filters">
                            <CreateButton href="/sales/create" permission="sales.create">{{ t('Create') }}</CreateButton>
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
                            <SortableTh column="sale_no">{{
                                t('Sale No')
                            }}</SortableTh>
                            <SortableTh column="customer.name">{{
                                t('Customer')
                            }}</SortableTh>
                            <SortableTh column="project.name">{{
                                t('Project')
                            }}</SortableTh>
                            <SortableTh column="unit.unit_no">{{
                                t('Unit')
                            }}</SortableTh>
                            <SortableTh column="unit.list_price">{{
                                t('Sale price')
                            }}</SortableTh>
                            <SortableTh column="discount">{{
                                t('Discount')
                            }}</SortableTh>
                            <SortableTh column="total_price">{{
                                t('Total')
                            }}</SortableTh>
                            <SortableTh column="remaining">{{
                                t('Remaining')
                            }}</SortableTh>
                            <SortableTh column="status">{{
                                t('Status')
                            }}</SortableTh>
                            <SortableTh column="creator.name">{{
                                t('Added by')
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
                            <td>
                                <Link
                                    :href="`/sales/${item.id}`"
                                    class="code-chip"
                                >
                                    {{ item.sale_no || '—' }}
                                </Link>
                            </td>
                            <td>{{ item.customer?.name || '—' }}</td>
                            <td class="muted">
                                {{ item.project?.name || '—' }}
                            </td>
                            <td>{{ item.unit?.unit_no || '—' }}</td>
                            <td class="nums nowrap end">
                                {{
                                    money(
                                        item.unit?.list_price,
                                        item.currency || baseCurrency,
                                    )
                                }}
                            </td>
                            <td class="nums nowrap end">
                                {{
                                    money(
                                        item.discount,
                                        item.currency || baseCurrency,
                                    )
                                }}
                            </td>
                            <td class="nums nowrap end">
                                {{
                                    money(
                                        saleTotal(item),
                                        item.currency || baseCurrency,
                                    )
                                }}
                            </td>
                            <td class="nums nowrap end">
                                {{
                                    money(
                                        saleRemaining(item),
                                        item.currency || baseCurrency,
                                    )
                                }}
                            </td>
                            <td>
                                <StatusBadge :status="item.status" />
                            </td>
                            <td class="muted">
                                {{ item.creator?.name || '—' }}
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
                            :title="t('No sales yet')">
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
