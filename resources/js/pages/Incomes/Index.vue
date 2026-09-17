<script setup lang="ts">
import EmptyState from '@/components/EmptyState.vue';
import IncomeRecordDialog from '@/components/IncomeRecordDialog.vue';
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
import { useCurrency } from '@/composables/useCurrency';
import { useMisFilters } from '@/composables/useMisFilters';
import { useMisPage } from '@/composables/useMisPage';
import { provideTableSort } from '@/composables/useTableSort';
import { useTranslations } from '@/composables/useTranslations';
import type { RowActionItem } from '@/lib/row-actions';
import { buildMoneyBars, compactMoneyByCurrency } from '@/lib/v2Money';
import { money, moneyByCurrency } from '@/lib/utils';
import { CalendarDays, CircleDollarSign, Eye, Pencil, Printer, TrendingUp, Wallet } from '@lucide/vue';
import { computed, ref } from 'vue';
import { Head } from '@inertiajs/vue3';

const props = defineProps<{
    items: { data: any[]; links: any[]; meta?: any };
    filters: { search?: string };
    totals: {
        count: number;
        amount: number;
        amount_base: number;
        this_month: number;
        this_month_base: number;
        average: number;
        average_base: number;
        amount_by_currency?: Record<string, number>;
        this_month_by_currency?: Record<string, number>;
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
            { title: 'Incomes', href: '/incomes' },
        ],
    },
});

const onlyKeys = ['items', 'filters', 'totals', 'moneyChart'];

const { filters, pending, apply, clear } = useMisFilters(
    '/incomes',
    { search: props.filters.search ?? '' },
    { search: '' },
    { only: onlyKeys, liveKeys: ['search'] },
);
const { sortedRows } = provideTableSort(() => props.items.data);
const { t } = useTranslations();
const tableColumns = computed(() => [
    indexTableColumn(),
    { key: 'income_date', label: t('Date') },
    { key: 'description', label: t('Description') },
    { key: 'amount', label: t('Total amount') },
    { key: 'creator.name', label: t('Added by') },
    { key: 'created_at', label: t('Created at') },
    { key: 'actions', label: t('Actions'), locked: true },
]);
const { baseCurrency } = useCurrency();
const { can, deleteAction, formatDate } = useMisPage();
const dialogOpen = ref(false);
const selected = ref<Record<string, any> | null>(null);
const startEditing = ref(false);

const monthShare = computed(() => {
    const total = Number(props.totals.amount ?? 0);
    const month = Number(props.totals.this_month ?? 0);
    const share = total > 0 ? Math.round((month / total) * 100) : 0;

    return {
        share,
        segments: [
            {
                key: 'this_month',
                label: t('This month'),
                value: compactMoneyByCurrency(
                    props.totals.this_month_by_currency,
                    month,
                ),
                color: 'var(--brand-accent)',
                width:
                    total > 0
                        ? Math.max(month > 0 ? 6 : 0, (month / total) * 100)
                        : 0,
            },
            {
                key: 'prior',
                label: t('Earlier'),
                value: compactMoneyByCurrency(
                    props.totals.amount_by_currency,
                    Math.max(0, total - month),
                ),
                color: 'var(--muted-foreground)',
                width:
                    total > 0
                        ? Math.max(
                              total - month > 0 ? 6 : 0,
                              ((total - month) / total) * 100,
                          )
                        : 0,
            },
        ],
    };
});

const moneyBars = computed(() => buildMoneyBars(props.moneyChart));

function openRecord(item: Record<string, any> | null, edit = false) {
    selected.value = item;
    startEditing.value = edit && !!item;
    dialogOpen.value = true;
}

function rowActions(item: any): RowActionItem[] {
    return [
        { label: t('View'), icon: Eye, onClick: () => openRecord(item, false) },
        { label: t('Print'), icon: Printer, href: `/incomes/${item.id}` },
        {
            label: t('Edit'),
            icon: Pencil,
            onClick: () => openRecord(item, true),
            hidden: !can('incomes.edit'),
        },
        deleteAction(
            {
                href: `/incomes/${item.id}`,
                title: t('Delete income'),
                description: t(
                    'Are you sure you want to delete this income? This action cannot be undone.',
                ),
                trashType: 'incomes',
            },
            'incomes.delete',
        ),
    ];
}
</script>

<template>
    <Head :title="t('Incomes')" />

    <V2ListPage>
        <V2Hero image="/images/dashboard-v2-building.png">
            <template #eyebrow>{{ t('Finance') }}</template>
            <template #title>{{ t('Incomes') }}</template>
            <template #description>
                {{
                    t(
                        'Approved general income entries — totals by currency and recent monthly flow.',
                    )
                }}
            </template>

            <template #side>
                <div class="hero-cards">
                    <V2IndicatorCard card-class="inventory-card">
                        <template #head>{{ t('This month') }}</template>
                        <template #meta
                            >{{ monthShare.share }}% {{ t('of total') }}</template
                        >

                        <div class="inventory-hero">
                            <div class="inventory-hero-copy">
                                <strong>{{ monthShare.share }}%</strong>
                                <small>{{ t('This month') }}</small>
                            </div>
                        </div>

                        <div class="inventory-bar" aria-hidden="true">
                            <i
                                v-for="seg in monthShare.segments"
                                :key="seg.key"
                                :style="{
                                    width: `${seg.width}%`,
                                    background: seg.color,
                                }"
                            />
                        </div>

                        <ul class="indicator-list compact">
                            <li
                                v-for="seg in monthShare.segments"
                                :key="seg.key"
                            >
                                <i :style="{ background: seg.color }" />
                                <span>{{ seg.label }}</span>
                                <b :title="seg.value">{{ seg.value }}</b>
                            </li>
                        </ul>
                    </V2IndicatorCard>

                    <V2IndicatorCard card-class="money-card">
                        <template #head>{{ t('Income by month') }}</template>
                        <template #meta>
                            <span
                                :title="
                                    moneyByCurrency(
                                        totals.amount_by_currency,
                                        totals.amount,
                                    )
                                ">{{
                                    compactMoneyByCurrency(
                                        totals.amount_by_currency,
                                        totals.amount,
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
                        :title="t('Entries')"
                        :value="totals.count"
                    >
                        <template #icon><CircleDollarSign /></template>
                    </V2StatCard>
                    <V2StatCard
                        :delay="1"
                        icon-tone="teal"
                        :title="t('Total income')">
                        <template #icon><Wallet /></template>
                        <span>{{ t('Total income') }}</span>
                        <strong
                            :title="
                                moneyByCurrency(
                                    totals.amount_by_currency,
                                    totals.amount,
                                )
                            ">{{
                                compactMoneyByCurrency(
                                    totals.amount_by_currency,
                                    totals.amount,
                                )
                            }}</strong
                        >
                    </V2StatCard>
                    <V2StatCard
                        :delay="2"
                        icon-tone="warm"
                        :title="t('This month')">
                        <template #icon><CalendarDays /></template>
                        <span>{{ t('This month') }}</span>
                        <strong
                            :title="
                                moneyByCurrency(
                                    totals.this_month_by_currency,
                                    totals.this_month,
                                )
                            ">{{
                                compactMoneyByCurrency(
                                    totals.this_month_by_currency,
                                    totals.this_month,
                                )
                            }}</strong
                        >
                    </V2StatCard>
                    <V2StatCard
                        :delay="3"
                        accent
                        icon-tone="orange"
                        :title="t('Average')">
                        <template #icon><TrendingUp /></template>
                        <span>{{ t('Average') }}</span>
                        <strong
                            :title="
                                moneyByCurrency(
                                    totals.amount_by_currency,
                                    totals.average,
                                )
                            ">{{
                                compactMoneyByCurrency(
                                    totals.amount_by_currency,
                                    totals.average,
                                )
                            }}</strong
                        >
                    </V2StatCard>
                </V2StatGrid>
            </template>
        </V2Hero>

        <V2TablePanel
            table-id="incomes"
            :columns="tableColumns"
            :pending="pending && items.data.length > 0"
        >
            <template #filters>
                <V2FilterBar>
                    <div class="filter-search">
                        <MisSearchInput
                            v-model="filters.search"
                            :placeholder="t('Search...')"
                            @submit="apply()"
                            @clear="clear"
                        />
                    </div>
                    <template #columns>
                        <MisTableActions export-url="/incomes/export" :filters="filters">
                            <CreateButton href="/incomes/create" permission="incomes.create">{{ t('Create') }}</CreateButton>
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
                            <SortableTh column="income_date">{{
                                t('Date')
                            }}</SortableTh>
                            <SortableTh column="description">{{
                                t('Description')
                            }}</SortableTh>
                            <SortableTh column="amount">{{
                                t('Total amount')
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
                            @click="openRecord(item)"
                        >
                            <TableIndexTd
                                :index="index"
                                :from="items.meta?.from"
                            />
                            <td class="muted nowrap">
                                {{ formatDate(item.income_date) }}
                            </td>
                            <td>{{ item.description || '—' }}</td>
                            <td class="nums">
                                {{
                                    money(
                                        item.amount,
                                        item.currency || baseCurrency,
                                    )
                                }}
                            </td>
                            <td class="muted">
                                {{ item.creator?.name || '—' }}
                            </td>
                            <td class="muted nowrap">
                                {{ formatDate(item.created_at) }}
                            </td>
                            <td class="end" @click.stop>
                                <RowActionsMenu :actions="rowActions(item)" />
                            </td>
                        </tr>
                        <EmptyState
                            v-if="!items.data.length"
                            :colspan="visibleColCount"
                            :title="t('No income records yet')">
                        </EmptyState>
                    </tbody>
                </table>
            </template>

            <template #pager>
                <V2Pager :items="items" :only="onlyKeys" />
            </template>
        </V2TablePanel>

        <IncomeRecordDialog
            v-model:open="dialogOpen"
            :item="selected"
            :start-editing="startEditing"
        />
    </V2ListPage>
</template>
