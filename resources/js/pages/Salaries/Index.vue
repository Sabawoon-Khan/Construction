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
    V2SelectFilter,
    V2StatCard,
    V2StatGrid,
    V2TablePanel,
} from '@/components/v2';
import { indexTableColumn } from '@/composables/useTableColumns';
import { useCalendar } from '@/composables/useCalendar';
import { useCurrency } from '@/composables/useCurrency';
import { useMisFilters } from '@/composables/useMisFilters';
import { useMisPage } from '@/composables/useMisPage';
import { provideTableSort } from '@/composables/useTableSort';
import { useTranslations } from '@/composables/useTranslations';
import type { RowActionItem } from '@/lib/row-actions';
import { buildMoneyBars, compactMoneyByCurrency } from '@/lib/v2Money';
import { money, moneyByCurrency } from '@/lib/utils';
import { Banknote, CircleDollarSign, Printer, Trash2, Wallet } from '@lucide/vue';
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';

type Item = Record<string, any>;

const props = defineProps<{
    items: { data: Item[]; links: any[]; meta?: any };
    filters: {
        search?: string;
        employee_id?: number | null;
        month?: number | null;
        year?: number | null;
    };
    totals: {
        count: number;
        paid_count?: number;
        pending_count?: number;
        basic_total: number;
        basic_total_base: number;
        bonus_total: number;
        bonus_total_base: number;
        deduction_total: number;
        deduction_total_base: number;
        net_total: number;
        net_total_base: number;
        basic_by_currency?: Record<string, number>;
        bonus_by_currency?: Record<string, number>;
        deduction_by_currency?: Record<string, number>;
        net_by_currency?: Record<string, number>;
        paid_net_total?: number;
        pending_net_total?: number;
        paid_net_by_currency?: Record<string, number>;
        pending_net_by_currency?: Record<string, number>;
    };
    moneyChart?: Array<{
        key: string;
        label: string;
        by_currency: { AFN: number; USD: number };
    }>;
    employees: {
        id: number;
        name: string;
        employee_no: string;
        basic_salary: number | null;
        currency?: string;
    }[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Salaries', href: '/salaries' },
        ],
    },
});

const currentYear = new Date().getFullYear();
const onlyKeys = ['items', 'filters', 'totals', 'moneyChart', 'employees'];

const { filters, pending, apply } = useMisFilters(
    '/salaries',
    {
        search: props.filters.search ?? '',
        employee_id: props.filters.employee_id
            ? String(props.filters.employee_id)
            : '',
        month: props.filters.month ? String(props.filters.month) : '',
        year: props.filters.year ?? currentYear,
    },
    { search: '', employee_id: '', month: '', year: currentYear },
    { only: onlyKeys },
);
const { sortedRows } = provideTableSort(() => props.items.data);
const { t } = useTranslations();
const tableColumns = computed(() => [
    indexTableColumn(),
    { key: 'employee.name', label: t('Employee') },
    { key: 'year', label: t('Period') },
    { key: 'basic_salary', label: t('Basic Salary') },
    { key: 'bonus', label: t('Bonus') },
    { key: 'deduction', label: t('Deduction') },
    { key: 'net_salary', label: t('Net Salary') },
    { key: 'status', label: t('Status') },
    { key: 'creator.name', label: t('Added by') },
    { key: 'created_at', label: t('Created at') },
    { key: 'actions', label: t('Actions'), locked: true },
]);
const { baseCurrency } = useCurrency();
const { months, digits } = useCalendar('gregorian');
const { editAction, viewAction, can, formatDate } = useMisPage();

const monthOptions = computed(() =>
    months().map((label, index) => ({
        value: String(index + 1),
        label,
    })),
);

const yearOptions = computed(() => {
    const years: number[] = [];
    for (let y = currentYear + 1; y >= currentYear - 5; y--) {
        years.push(y);
    }
    return years;
});

const payrollStatus = computed(() => {
    const paid = Number(props.totals.paid_count ?? 0);
    const pendingCount = Number(props.totals.pending_count ?? 0);
    const total = Math.max(
        paid + pendingCount,
        Number(props.totals.count ?? 0),
        0,
    );
    const paidShare = total > 0 ? Math.round((paid / total) * 100) : 0;

    return {
        paid,
        pending: pendingCount,
        total,
        paidShare,
        segments: [
            {
                key: 'paid',
                label: t('Paid'),
                value: compactMoneyByCurrency(
                    props.totals.paid_net_by_currency,
                    props.totals.paid_net_total,
                ),
                color: 'var(--brand-accent)',
                width:
                    total > 0
                        ? Math.max(paid > 0 ? 6 : 0, (paid / total) * 100)
                        : 0,
            },
            {
                key: 'pending',
                label: t('Pending'),
                value: compactMoneyByCurrency(
                    props.totals.pending_net_by_currency,
                    props.totals.pending_net_total,
                ),
                color: 'var(--school-gold)',
                width:
                    total > 0
                        ? Math.max(
                              pendingCount > 0 ? 6 : 0,
                              (pendingCount / total) * 100,
                          )
                        : 0,
            },
        ],
    };
});

const moneyBars = computed(() => buildMoneyBars(props.moneyChart));

function formatPeriod(item: Item): string {
    const monthIndex = Number(item.month) - 1;
    const monthLabel = months()[monthIndex] ?? String(item.month);
    return `${monthLabel} ${digits(item.year)}`;
}

function formatMoney(value: unknown, currency?: string | null): string {
    return money(value, currency || baseCurrency.value);
}

function onEmployeeChange(value: string) {
    apply({ employee_id: value });
}

function onMonthChange(value: string) {
    apply({ month: value });
}

function onYearChange(value: string) {
    apply({ year: value ? Number(value) : currentYear });
}

function rowActions(item: Item): RowActionItem[] {
    return [
        viewAction(`/salaries/${item.id}`),
        { label: t('Print'), icon: Printer, href: `/salaries/${item.id}` },
        editAction(`/salaries/${item.id}/edit`, 'salaries.edit'),
        { separator: true, isLabel: true, label: t('Danger') },
        {
            label: t('Delete'),
            icon: Trash2,
            method: 'delete',
            href: `/salaries/${item.id}`,
            variant: 'destructive' as const,
            hidden: !can('salaries.delete'),
            confirm: {
                title: t('Delete salary record'),
                description: t(
                    'Are you sure you want to delete this salary record? This action cannot be undone.',
                ),
                confirmLabel: t('Delete'),
            },
        },
    ];
}
</script>

<template>
    <Head :title="t('Salaries')" />

    <V2ListPage>
        <V2Hero image="/images/dashboard-v2-building.png">
            <template #eyebrow>{{ t('Payroll') }}</template>
            <template #title>{{ t('Salaries') }}</template>
            <template #description>
                {{
                    t(
                        'Monthly payroll, bonuses, and deductions — paid vs unpaid at a glance.',
                    )
                }}
            </template>

            <template #side>
                <div class="hero-cards">
                    <V2IndicatorCard card-class="inventory-card">
                        <template #head>{{ t('Payroll status') }}</template>
                        <template #meta
                            >{{ payrollStatus.paidShare }}%
                            {{ t('Paid') }}</template
                        >

                        <div class="inventory-hero">
                            <div class="inventory-hero-copy">
                                <strong>{{ payrollStatus.paid }}</strong>
                                <small>{{ t('Paid') }}</small>
                            </div>
                        </div>

                        <div class="inventory-bar" aria-hidden="true">
                            <i
                                v-for="seg in payrollStatus.segments"
                                :key="seg.key"
                                :style="{
                                    width: `${seg.width}%`,
                                    background: seg.color,
                                }"
                            />
                        </div>

                        <ul class="indicator-list compact">
                            <li
                                v-for="seg in payrollStatus.segments"
                                :key="seg.key"
                            >
                                <i :style="{ background: seg.color }" />
                                <span>{{ seg.label }}</span>
                                <b :title="seg.value">{{ seg.value }}</b>
                            </li>
                        </ul>
                    </V2IndicatorCard>

                    <V2IndicatorCard card-class="money-card">
                        <template #head>{{ t('Payroll by month') }}</template>
                        <template #meta>
                            <span
                                :title="
                                    moneyByCurrency(
                                        totals.net_by_currency,
                                        totals.net_total,
                                    )
                                ">{{
                                    compactMoneyByCurrency(
                                        totals.net_by_currency,
                                        totals.net_total,
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
                        :title="t('Records')"
                        :value="totals.count"
                    >
                        <template #icon><CircleDollarSign /></template>
                    </V2StatCard>
                    <V2StatCard
                        :delay="1"
                        icon-tone="teal"
                        :title="t('Basic total')">
                        <template #icon><Wallet /></template>
                        <span>{{ t('Basic total') }}</span>
                        <strong
                            :title="
                                moneyByCurrency(
                                    totals.basic_by_currency,
                                    totals.basic_total,
                                )
                            ">{{
                                compactMoneyByCurrency(
                                    totals.basic_by_currency,
                                    totals.basic_total,
                                )
                            }}</strong
                        >
                    </V2StatCard>
                    <V2StatCard
                        :delay="2"
                        accent
                        icon-tone="orange"
                        :title="t('Net total')">
                        <template #icon><Banknote /></template>
                        <span>{{ t('Net total') }}</span>
                        <strong
                            :title="
                                moneyByCurrency(
                                    totals.net_by_currency,
                                    totals.net_total,
                                )
                            ">{{
                                compactMoneyByCurrency(
                                    totals.net_by_currency,
                                    totals.net_total,
                                )
                            }}</strong
                        >
                    </V2StatCard>
                    <V2StatCard
                        :delay="3"
                        icon-tone="warm"
                        :title="t('Pending')"
                        :value="totals.pending_count ?? 0"
                    >
                        <template #icon><Wallet /></template>
                    </V2StatCard>
                </V2StatGrid>
            </template>
        </V2Hero>

        <V2TablePanel
            table-id="salaries"
            :columns="tableColumns"
            :pending="pending && items.data.length > 0"
            :print-title="t('Salaries')"
        >
            <template #filters>
                <V2FilterBar>
                    <div class="filter-search">
                        <MisSearchInput
                            v-model="filters.search"
                            :placeholder="t('Search employees...')"
                            @submit="apply()"
                            @clear="apply({ search: '' })"
                        />
                    </div>
                    <V2SelectFilter
                        :label="t('Employee')"
                        :model-value="filters.employee_id || ''"
                        @change="onEmployeeChange"
                    >
                        <option value="">{{ t('All employees') }}</option>
                        <option
                            v-for="employee in employees"
                            :key="employee.id"
                            :value="String(employee.id)"
                        >
                            {{ employee.name }}
                        </option>
                    </V2SelectFilter>
                    <V2SelectFilter
                        :label="t('Month')"
                        :model-value="filters.month || ''"
                        @change="onMonthChange"
                    >
                        <option value="">{{ t('All months') }}</option>
                        <option
                            v-for="option in monthOptions"
                            :key="option.value"
                            :value="option.value"
                        >
                            {{ option.label }}
                        </option>
                    </V2SelectFilter>
                    <V2SelectFilter
                        :label="t('Year')"
                        :model-value="String(filters.year || currentYear)"
                        @change="onYearChange"
                    >
                        <option
                            v-for="year in yearOptions"
                            :key="year"
                            :value="String(year)"
                        >
                            {{ year }}
                        </option>
                    </V2SelectFilter>
                    <template #columns>
                        <MisTableActions export-url="/salaries/export" :filters="filters">
                            <CreateButton href="/salaries/create" permission="salaries.create">{{ t('Create') }}</CreateButton>
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
                            <SortableTh column="employee.name">{{
                                t('Employee')
                            }}</SortableTh>
                            <SortableTh column="year">{{
                                t('Period')
                            }}</SortableTh>
                            <SortableTh column="basic_salary" align="end">{{
                                t('Basic Salary')
                            }}</SortableTh>
                            <SortableTh column="bonus" align="end">{{
                                t('Bonus')
                            }}</SortableTh>
                            <SortableTh column="deduction" align="end">{{
                                t('Deduction')
                            }}</SortableTh>
                            <SortableTh column="net_salary" align="end">{{
                                t('Net Salary')
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
                                    v-if="item.employee"
                                    :href="`/salaries/${item.id}`"
                                    class="code-chip"
                                >
                                    {{ item.employee.name }}
                                </Link>
                                <span v-else>—</span>
                            </td>
                            <td class="nowrap">{{ formatPeriod(item) }}</td>
                            <td class="nums end">
                                {{
                                    formatMoney(
                                        item.basic_salary,
                                        item.currency,
                                    )
                                }}
                            </td>
                            <td class="nums end">
                                {{
                                    Number(item.bonus) > 0
                                        ? formatMoney(item.bonus, item.currency)
                                        : '—'
                                }}
                            </td>
                            <td class="nums end">
                                {{
                                    Number(item.deduction) > 0
                                        ? formatMoney(
                                              item.deduction,
                                              item.currency,
                                          )
                                        : '—'
                                }}
                            </td>
                            <td class="nums end">
                                <b>{{
                                    formatMoney(item.net_salary, item.currency)
                                }}</b>
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
                            :title="t('No salary records')">
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
