<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import {
    ChevronLeft,
    ChevronRight,
    CircleDollarSign,
    PieChart,
    Receipt,
    TrendingDown,
    TrendingUp,
    Wallet,
} from '@lucide/vue';
import { computed, watch } from 'vue';
import SortableTh from '@/components/SortableTh.vue';
import TableFrame from '@/components/TableFrame.vue';
import TableIndexTd from '@/components/TableIndexTd.vue';
import TableIndexTh from '@/components/TableIndexTh.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    MisChartCard,
    MisPrintFooter,
    MisPrintLetterhead,
    MisTableActions,
    MisFilterBar,
    MisLoadingState,
    MisPageHeader,
    MisSection,
    MisStatCard,
    MisStatGrid,
} from '@/components/mis';
import { indexTableColumn } from '@/composables/useTableColumns';
import { useCalendar } from '@/composables/useCalendar';
import { useClientPagination } from '@/composables/useClientPagination';
import { useMisFilters } from '@/composables/useMisFilters';
import { useTableSort } from '@/composables/useTableSort';
import { useTranslations } from '@/composables/useTranslations';
import { formatNumber, money } from '@/lib/utils';
const props = defineProps<{
    filters: { from: string; to: string; project_id?: number | null; preset?: string; currency?: string };
    restricted?: boolean;
    projects: { id: number; name: string }[];
    summary: Record<string, number>;
    sales_by_currency?: Record<string, number>;
    expenses_by_currency: Record<string, number>;
    incomes_by_currency: Record<string, number>;
    charts?: {
        monthly?: {
            labels: string[];
            sales: number[];
            expenses: number[];
            income?: number[];
        };
    };
    by_category: { category: string; amount: number; amount_base: number; count: number }[];
    partners: {
        id: number;
        name: string;
        project_name?: string;
        ownership_percentage: number;
        profit_share: number;
        entitled_share: number;
        paid_total: number;
        balance_owed: number;
    }[];
    sales: any[];
    expenses: any[];
    salaries: any[];
    activity: any[];
}>();

const { t } = useTranslations();
const tableColumnsCategory = computed(() => [
    indexTableColumn(),
    { key: 'category', label: t('Category') },
    { key: 'count', label: t('Count') },
    { key: 'amount_base', label: t('Amount') },
]);
const tableColumnsPartners = computed(() => [
    indexTableColumn(),
    { key: 'name', label: t('Shareholder') },
    { key: 'project_name', label: t('Project') },
    { key: 'ownership_percentage', label: t('Share %') },
    { key: 'entitled_share', label: t('Entitled') },
    { key: 'paid_total', label: t('Paid') },
    { key: 'balance_owed', label: t('Balance') },
]);
const tableColumnsSales = computed(() => [
    indexTableColumn(),
    { key: 'contract_date', label: t('Date') },
    { key: 'customer.name', label: t('Buyer') },
    { key: 'discount', label: t('Amount') },
    { key: 'created_at', label: t('Created at') },
]);
const tableColumnsExpenses = computed(() => [
    indexTableColumn(),
    { key: 'expense_date', label: t('Date') },
    { key: 'category.name', label: t('Category') },
    { key: 'description', label: t('Description') },
    { key: 'amount', label: t('Amount') },
    { key: 'created_at', label: t('Created at') },
]);
const tableColumnsActivity = computed(() => [
    indexTableColumn(),
    { key: 'created_at', label: t('Date') },
    { key: 'subject.name', label: t('Project') },
    { key: 'to_status', label: t('Status change') },
    { key: 'created_at', label: t('Created at') },
]);
const categorySort = useTableSort(() => props.by_category, { defaultKey: 'amount_base', defaultDir: 'desc' });
const partnerSort = useTableSort(() => props.partners);
const salesSort = useTableSort(() => props.sales);
const expenseSort = useTableSort(() => props.expenses);
const activitySort = useTableSort(() => props.activity);

const categoryPage = useClientPagination(() => categorySort.sortedRows, 10);
const partnerPage = useClientPagination(() => partnerSort.sortedRows, 10);
const salesPage = useClientPagination(() => salesSort.sortedRows, 10);
const expensePage = useClientPagination(() => expenseSort.sortedRows, 10);
const activityPage = useClientPagination(() => activitySort.sortedRows, 10);

const { filters, pending, apply } = useMisFilters(
    '/reports',
    {
        preset: props.filters.preset ?? 'custom',
        from: props.filters.from ?? '',
        to: props.filters.to ?? '',
        project_id: props.filters.project_id ? String(props.filters.project_id) : '',
    },
    { preset: 'month', from: '', to: '', project_id: '' },
);

watch(
    () => props.filters,
    (next) => {
        filters.preset = next.preset ?? 'custom';
        filters.from = next.from ?? '';
        filters.to = next.to ?? '';
        filters.project_id = next.project_id ? String(next.project_id) : '';
    },
);

const calendar = useCalendar();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Reports', href: '/reports' },
        ],
    },
});

const dateOnly = (v: unknown) => calendar.format(v == null ? null : String(v)) || '—';
const formatDate = (value: string | null | undefined) => calendar.format(value) || '—';

const periodLabel = computed(() => {
    const start = props.filters.from;
    const end = props.filters.to;
    if (!start || !end) {
        return '';
    }
    return `${formatDate(start)} – ${formatDate(end)}`;
});

const profitPositive = computed(() => Number(props.summary.net_profit ?? 0) >= 0);

const marginPercent = computed(() => {
    const revenue = Number(props.summary.revenue_total ?? 0);
    const profit = Number(props.summary.net_profit ?? 0);
    if (!revenue) {
        return null;
    }
    return (profit / revenue) * 100;
});

const monthlyChart = computed(() => {
    const monthly = props.charts?.monthly ?? {
        labels: [] as string[],
        sales: [] as number[],
        expenses: [] as number[],
        income: [] as number[],
    };
    const rawLabels = monthly.labels ?? [];
    const years = new Set(
        rawLabels
            .map((label) => String(label).match(/\d{4}/)?.[0])
            .filter(Boolean),
    );
    const labels = rawLabels.map((label) => {
        const text = String(label);
        const month = text.slice(0, 3);
        const year = text.match(/\d{4}/)?.[0];
        if (!year || text.length === 4) {
            return text;
        }
        return years.size <= 1 ? month : `${month} '${year.slice(2)}`;
    });

    return {
        labels,
        datasets: [
            {
                label: t('Sales'),
                data: monthly.sales ?? [],
                color: 'var(--chart-1)',
            },
            {
                label: t('Expenses'),
                data: monthly.expenses ?? [],
                color: 'var(--chart-2)',
            },
            {
                label: t('Other income'),
                data: monthly.income ?? [],
                color: 'var(--chart-3)',
            },
        ],
    };
});

const pnlChart = computed(() => ({
    labels: [t('Revenue'), t('Operating expenses'), t('Salaries'), t('Net profit')],
    datasets: [
        {
            label: t('Amount'),
            data: [
                Number(props.summary.revenue_total ?? 0),
                Number(props.summary.operating_expense_total ?? 0),
                Number(props.summary.salary_total ?? 0),
                Number(props.summary.net_profit ?? 0),
            ],
            color: 'var(--chart-1)',
        },
    ],
}));

const categoryChart = computed(() => {
    const rows = [...(props.by_category || [])]
        .sort((a, b) => Number(b.amount_base ?? 0) - Number(a.amount_base ?? 0))
        .slice(0, 8);

    return {
        labels: rows.length ? rows.map((row) => String(row.category || t('General'))) : [t('No data')],
        datasets: [
            {
                label: t('Amount'),
                data: rows.length ? rows.map((row) => Number(row.amount_base ?? row.amount ?? 0)) : [0],
                color: 'var(--chart-2)',
            },
        ],
    };
});

const expenseCurrencyChart = computed(() => {
    const entries = Object.entries(props.expenses_by_currency ?? {})
        .map(([code, amount]) => [code.toUpperCase(), Number(amount) || 0] as const)
        .filter(([, amount]) => amount !== 0)
        .sort(([a], [b]) => a.localeCompare(b));

    return {
        labels: entries.length ? entries.map(([code]) => code) : ['AFN', 'USD'],
        datasets: [
            {
                label: t('Expenses'),
                data: entries.length ? entries.map(([, amount]) => amount) : [0, 0],
            },
        ],
    };
});

const incomeCurrencyChart = computed(() => {
    const entries = Object.entries(props.incomes_by_currency ?? {})
        .map(([code, amount]) => [code.toUpperCase(), Number(amount) || 0] as const)
        .filter(([, amount]) => amount !== 0)
        .sort(([a], [b]) => a.localeCompare(b));

    return {
        labels: entries.length ? entries.map(([code]) => code) : ['AFN', 'USD'],
        datasets: [
            {
                label: t('Incomes'),
                data: entries.length ? entries.map(([, amount]) => amount) : [0, 0],
            },
        ],
    };
});

const partnersChart = computed(() => {
    const rows = [...(props.partners || [])]
        .sort((a, b) => Number(b.entitled_share ?? b.profit_share ?? 0) - Number(a.entitled_share ?? a.profit_share ?? 0))
        .slice(0, 8);

    return {
        labels: rows.length
            ? rows.map((row) => {
                  const name = String(row.name || '—');
                  const project = row.project_name ? ` · ${row.project_name}` : '';
                  const full = `${name}${project}`;
                  return full.length > 22 ? `${full.slice(0, 21)}…` : full;
              })
            : [t('No data')],
        datasets: [
            {
                label: t('Entitled'),
                data: rows.length
                    ? rows.map((row) => Number(row.entitled_share ?? row.profit_share ?? 0))
                    : [0],
                color: 'var(--chart-3)',
            },
            {
                label: t('Paid'),
                data: rows.length ? rows.map((row) => Number(row.paid_total ?? 0)) : [0],
                color: 'var(--chart-4)',
            },
        ],
    };
});

function applyPreset(preset?: string) {
    if (preset && preset !== 'custom') {
        apply({
            preset,
            from: '',
            to: '',
            project_id: filters.project_id || '',
        });
        return;
    }
    apply({
        preset: 'custom',
        from: filters.from || '',
        to: filters.to || '',
        project_id: filters.project_id || '',
    });
}

const activePreset = computed(() => filters.preset || props.filters.preset || 'custom');
</script>

<template>
    <Head :title="t('Reports')" />

    <div class="print-document mx-auto flex h-full w-full max-w-[1600px] flex-1 flex-col gap-5 p-4 md:p-6 print:gap-4 print:p-0">
        <MisPrintLetterhead class="hidden print:block" :title="t('Reports')" :subtitle="periodLabel" />
        <MisPageHeader
            :title="t('Reports')"
            :description="periodLabel ? `${t('Period summary')} · ${periodLabel}` : t('Period summary')"
            class="print:hidden"
        >
            <template #actions>
                <MisTableActions export-url="/reports/export" :filters="filters" />
            </template>
        </MisPageHeader>

        <MisFilterBar class="print:hidden"  @apply="applyPreset('custom')">
            <Button
                type="button"
                variant="outline"
                size="sm"
                :class="
                    activePreset === 'today'
                        ? 'border-emerald-600 bg-emerald-50 text-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-100'
                        : ''
                "
                @click="applyPreset('today')"
            >
                {{ t('Today') }}
            </Button>
            <Button
                type="button"
                variant="outline"
                size="sm"
                :class="
                    activePreset === 'month'
                        ? 'border-emerald-600 bg-emerald-50 text-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-100'
                        : ''
                "
                @click="applyPreset('month')"
            >
                {{ t('This month') }}
            </Button>
            <Button
                type="button"
                variant="outline"
                size="sm"
                :class="
                    activePreset === 'year'
                        ? 'border-emerald-600 bg-emerald-50 text-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-100'
                        : ''
                "
                @click="applyPreset('year')"
            >
                {{ t('This year') }}
            </Button>
            <div class="grid min-w-[150px] flex-1 gap-1">
                <label class="text-xs text-muted-foreground">{{ t('From') }}</label>
                <Input v-model="filters.from" type="date" class="bg-background/80" />
            </div>
            <div class="grid min-w-[150px] flex-1 gap-1">
                <label class="text-xs text-muted-foreground">{{ t('To') }}</label>
                <Input v-model="filters.to" type="date" class="bg-background/80" />
            </div>
            <div class="grid min-w-[180px] flex-1 gap-1">
                <label class="text-xs text-muted-foreground">{{ t('Project') }}</label>
                <select
                    v-model="filters.project_id"
                    class="h-9 w-full rounded-md border border-input bg-background/80 px-3 text-sm"
                >
                    <option value="">{{ t('All projects') }}</option>
                    <option v-for="p in projects" :key="p.id" :value="String(p.id)">
                        {{ p.name }}
                    </option>
                </select>
            </div>
            <template #actions>
                <Button type="button" size="sm" @click="applyPreset('custom')">{{ t('Apply') }}</Button>
            </template>
        </MisFilterBar>

        <MisLoadingState v-if="pending" :label="t('Loading report…')" />

        <template v-else>
            <MisStatGrid :columns="4" class="reports-kpi-grid">
                <MisStatCard
                    :label="t('Sales')"
                    :hint="`${t('Base total')}: ${money(summary.sales_total)}`"
                    :amounts="sales_by_currency"
                    :fallback-amount="summary.sales_total"
                    :icon="CircleDollarSign"
                    tone="default"
                />
                <MisStatCard
                    :label="t('Other income')"
                    :hint="`${t('Base total')}: ${money(summary.income_total)}`"
                    :amounts="incomes_by_currency"
                    :fallback-amount="summary.income_total"
                    :icon="Wallet"
                    tone="success"
                />
                <MisStatCard
                    :label="t('All expenses')"
                    :hint="`${t('Operating')} ${money(summary.operating_expense_total)} · ${t('Salaries')} ${money(summary.salary_total)}`"
                    :amounts="expenses_by_currency"
                    :fallback-amount="summary.expense_total"
                    :icon="Receipt"
                    tone="danger"
                />
                <MisStatCard
                    :label="t('Net profit')"
                    :hint="
                        marginPercent != null
                            ? `${t('Sales + income − expenses − salaries')} · ${formatNumber(marginPercent, 1)}% ${t('margin')}`
                            : t('Sales + income − expenses − salaries')
                    "
                    :value="money(summary.net_profit)"
                    :icon="profitPositive ? TrendingUp : TrendingDown"
                    :tone="profitPositive ? 'success' : 'danger'"
                />
            </MisStatGrid>

            <!-- Charts -->
            <div class="reports-chart-grid grid gap-4 xl:grid-cols-5 print:hidden">
                <MisChartCard
                    class="reports-chart-card xl:col-span-3"
                    :title="t('Sales vs expenses')"
                    :description="t('Trend across the selected period')"
                    type="bar"
                    :labels="monthlyChart.labels"
                    :datasets="monthlyChart.datasets"
                    :icon="TrendingUp"
                />
                <MisChartCard
                    class="reports-chart-card xl:col-span-2"
                    :title="t('Profit & loss')"
                    :description="t('How revenue turns into profit')"
                    type="bar"
                    :labels="pnlChart.labels"
                    :datasets="pnlChart.datasets"
                    :icon="PieChart"
                />
            </div>

            <div class="reports-chart-grid grid gap-4 xl:grid-cols-4 print:hidden">
                <MisChartCard
                    class="reports-chart-card xl:col-span-2"
                    :title="t('Expenses by category')"
                    :description="t('Top spending categories')"
                    type="bar"
                    orientation="horizontal"
                    :labels="categoryChart.labels"
                    :datasets="categoryChart.datasets"
                    :icon="Receipt"
                />
                <MisChartCard
                    class="reports-chart-card"
                    :title="t('Expenses by currency')"
                    :description="t('Spent in each currency')"
                    type="pie"
                    :labels="expenseCurrencyChart.labels"
                    :datasets="expenseCurrencyChart.datasets"
                />
                <MisChartCard
                    class="reports-chart-card"
                    :title="t('Income by currency')"
                    :description="t('Received in each currency')"
                    type="pie"
                    :labels="incomeCurrencyChart.labels"
                    :datasets="incomeCurrencyChart.datasets"
                />
            </div>

            <div class="print:hidden">
                <MisChartCard
                    class="reports-chart-card"
                    :title="t('Shareholder distribution')"
                    :description="t('Entitled share vs paid distributions')"
                    type="bar"
                    orientation="horizontal"
                    :labels="partnersChart.labels"
                    :datasets="partnersChart.datasets"
                    :icon="Wallet"
                />
            </div>

            <MisSection :title="t('Details')" class="reports-details">
                <div class="grid gap-4 xl:grid-cols-2">
                    <div class="reports-table-panel rounded-2xl border border-border/80 bg-card shadow-soft">
                        <div class="border-b border-border/60 px-5 py-4">
                            <h2 class="text-sm font-semibold tracking-tight">
                                {{ t('Expenses by category') }}
                            </h2>
                        </div>
                        <TableFrame table-id="reports-category" :columns="tableColumnsCategory">
                            <table class="min-w-full text-sm">
                                <thead class="table-chrome-head text-start text-muted-foreground">
                                    <tr>
                                        <TableIndexTh />
                                        <SortableTh
                                            class="px-5"
                                            column="category"
                                            :sort-key="categorySort.sortKey"
                                            :sort-dir="categorySort.sortDir"
                                            @sort="categorySort.sortBy"
                                        >
                                            {{ t('Category') }}
                                        </SortableTh>
                                        <SortableTh
                                            class="px-5"
                                            column="count"
                                            :sort-key="categorySort.sortKey"
                                            :sort-dir="categorySort.sortDir"
                                            @sort="categorySort.sortBy"
                                        >
                                            {{ t('Count') }}
                                        </SortableTh>
                                        <SortableTh
                                            class="px-5"
                                            column="amount_base"
                                            :sort-key="categorySort.sortKey"
                                            :sort-dir="categorySort.sortDir"
                                            @sort="categorySort.sortBy"
                                        >
                                            {{ t('Amount') }}
                                        </SortableTh>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr
                                        v-for="(row, index) in categoryPage.pageRows"
                                        :key="row.category"
                                        class="border-t"
                                    >
                                        <TableIndexTd :index="index" :from="categoryPage.from" />
                                        <td class="px-5 py-3">{{ row.category }}</td>
                                        <td class="px-5 py-3 tabular-nums">{{ row.count }}</td>
                                        <td class="px-5 py-3 font-medium tabular-nums">
                                            {{ money(row.amount_base ?? row.amount) }}
                                        </td>
                                    </tr>
                                    <tr v-if="!by_category.length">
                                        <td colspan="4" class="px-5 py-8 text-center text-muted-foreground">
                                            {{ t('No expenses in this period.') }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </TableFrame>
                        <div
                            v-if="categoryPage.showControls"
                            class="flex flex-wrap items-center justify-between gap-3 border-t px-5 py-3 print:hidden"
                        >
                            <p class="text-xs text-muted-foreground">
                                {{
                                    t('Showing :from–:to of :total', {
                                        from: formatNumber(categoryPage.from, 0),
                                        to: formatNumber(categoryPage.to, 0),
                                        total: formatNumber(categoryPage.total, 0),
                                    })
                                }}
                            </p>
                            <div class="flex items-center gap-1">
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="icon"
                                    class="size-8"
                                    :disabled="categoryPage.currentPage <= 1"
                                    @click="categoryPage.currentPage -= 1"
                                >
                                    <ChevronLeft class="size-4" />
                                </Button>
                                <span class="min-w-[4rem] text-center text-xs tabular-nums text-muted-foreground">
                                    {{ categoryPage.currentPage }} / {{ categoryPage.totalPages }}
                                </span>
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="icon"
                                    class="size-8"
                                    :disabled="categoryPage.currentPage >= categoryPage.totalPages"
                                    @click="categoryPage.currentPage += 1"
                                >
                                    <ChevronRight class="size-4" />
                                </Button>
                            </div>
                        </div>
                    </div>

                    <div class="reports-table-panel rounded-2xl border border-border/80 bg-card shadow-soft">
                        <div class="border-b border-border/60 px-5 py-4">
                            <h2 class="text-sm font-semibold tracking-tight">
                                {{ t('Shareholder distribution') }}
                            </h2>
                        </div>
                        <TableFrame table-id="reports-partners" :columns="tableColumnsPartners">
                            <table class="min-w-full text-sm">
                                <thead class="table-chrome-head text-start text-muted-foreground">
                                    <tr>
                                        <TableIndexTh />
                                        <SortableTh
                                            class="px-5"
                                            column="name"
                                            :sort-key="partnerSort.sortKey"
                                            :sort-dir="partnerSort.sortDir"
                                            @sort="partnerSort.sortBy"
                                        >
                                            {{ t('Shareholder') }}
                                        </SortableTh>
                                        <SortableTh
                                            class="px-5"
                                            column="project_name"
                                            :sort-key="partnerSort.sortKey"
                                            :sort-dir="partnerSort.sortDir"
                                            @sort="partnerSort.sortBy"
                                        >
                                            {{ t('Project') }}
                                        </SortableTh>
                                        <SortableTh
                                            class="px-5"
                                            column="ownership_percentage"
                                            :sort-key="partnerSort.sortKey"
                                            :sort-dir="partnerSort.sortDir"
                                            @sort="partnerSort.sortBy"
                                        >
                                            {{ t('Share %') }}
                                        </SortableTh>
                                        <SortableTh
                                            class="px-5"
                                            column="entitled_share"
                                            :sort-key="partnerSort.sortKey"
                                            :sort-dir="partnerSort.sortDir"
                                            @sort="partnerSort.sortBy"
                                        >
                                            {{ t('Entitled') }}
                                        </SortableTh>
                                        <SortableTh
                                            class="px-5"
                                            column="paid_total"
                                            :sort-key="partnerSort.sortKey"
                                            :sort-dir="partnerSort.sortDir"
                                            @sort="partnerSort.sortBy"
                                        >
                                            {{ t('Paid') }}
                                        </SortableTh>
                                        <SortableTh
                                            class="px-5"
                                            column="balance_owed"
                                            :sort-key="partnerSort.sortKey"
                                            :sort-dir="partnerSort.sortDir"
                                            @sort="partnerSort.sortBy"
                                        >
                                            {{ t('Balance') }}
                                        </SortableTh>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr
                                        v-for="(p, index) in partnerPage.pageRows"
                                        :key="`${p.id}-${p.project_name}`"
                                        class="border-t"
                                    >
                                        <TableIndexTd :index="index" :from="partnerPage.from" />
                                        <td class="whitespace-nowrap px-5 py-3">{{ p.name }}</td>
                                        <td class="whitespace-nowrap px-5 py-3">{{ p.project_name || '—' }}</td>
                                        <td class="whitespace-nowrap px-5 py-3 tabular-nums">
                                            {{ Number(p.ownership_percentage).toFixed(2) }}%
                                        </td>
                                        <td class="whitespace-nowrap px-5 py-3 tabular-nums">
                                            {{ money(p.entitled_share ?? p.profit_share) }}
                                        </td>
                                        <td class="whitespace-nowrap px-5 py-3 tabular-nums">
                                            {{ money(p.paid_total ?? 0) }}
                                        </td>
                                        <td class="whitespace-nowrap px-5 py-3 font-medium tabular-nums">
                                            {{ money(p.balance_owed ?? 0) }}
                                        </td>
                                    </tr>
                                    <tr v-if="!partners.length">
                                        <td colspan="7" class="px-5 py-8 text-center text-muted-foreground">
                                            {{ t('No shareholders in this period.') }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </TableFrame>
                        <div
                            v-if="partnerPage.showControls"
                            class="flex flex-wrap items-center justify-between gap-3 border-t px-5 py-3 print:hidden"
                        >
                            <p class="text-xs text-muted-foreground">
                                {{
                                    t('Showing :from–:to of :total', {
                                        from: formatNumber(partnerPage.from, 0),
                                        to: formatNumber(partnerPage.to, 0),
                                        total: formatNumber(partnerPage.total, 0),
                                    })
                                }}
                            </p>
                            <div class="flex items-center gap-1">
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="icon"
                                    class="size-8"
                                    :disabled="partnerPage.currentPage <= 1"
                                    @click="partnerPage.currentPage -= 1"
                                >
                                    <ChevronLeft class="size-4" />
                                </Button>
                                <span class="min-w-[4rem] text-center text-xs tabular-nums text-muted-foreground">
                                    {{ partnerPage.currentPage }} / {{ partnerPage.totalPages }}
                                </span>
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="icon"
                                    class="size-8"
                                    :disabled="partnerPage.currentPage >= partnerPage.totalPages"
                                    @click="partnerPage.currentPage += 1"
                                >
                                    <ChevronRight class="size-4" />
                                </Button>
                            </div>
                        </div>
                    </div>
                </div>
            </MisSection>

            <div v-if="!restricted" class="grid gap-4 lg:grid-cols-2">
                <div class="reports-table-panel rounded-2xl border border-border/80 bg-card shadow-soft">
                    <div class="border-b border-border/60 px-5 py-4">
                        <h2 class="text-sm font-semibold tracking-tight">{{ t('New sales') }}</h2>
                    </div>
                    <TableFrame table-id="reports-sales" :columns="tableColumnsSales">
                        <table class="min-w-full text-sm">
                            <thead class="table-chrome-head text-start text-muted-foreground">
                                <tr>
                                    <TableIndexTh />
                                    <SortableTh
                                        class="px-5"
                                        column="contract_date"
                                        :sort-key="salesSort.sortKey"
                                        :sort-dir="salesSort.sortDir"
                                        @sort="salesSort.sortBy"
                                    >
                                        {{ t('Date') }}
                                    </SortableTh>
                                    <SortableTh
                                        class="px-5"
                                        column="customer.name"
                                        :sort-key="salesSort.sortKey"
                                        :sort-dir="salesSort.sortDir"
                                        @sort="salesSort.sortBy"
                                    >
                                        {{ t('Buyer') }}
                                    </SortableTh>
                                    <SortableTh
                                        class="px-5"
                                        column="discount"
                                        :sort-key="salesSort.sortKey"
                                        :sort-dir="salesSort.sortDir"
                                        @sort="salesSort.sortBy"
                                    >
                                        {{ t('Amount') }}
                                    </SortableTh>
                                    <SortableTh
                                        class="px-5"
                                        column="created_at"
                                        :sort-key="salesSort.sortKey"
                                        :sort-dir="salesSort.sortDir"
                                        @sort="salesSort.sortBy"
                                    >
                                        {{ t('Created at') }}
                                    </SortableTh>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(s, index) in salesPage.pageRows" :key="s.id" class="border-t">
                                    <TableIndexTd :index="index" :from="salesPage.from" />
                                    <td class="whitespace-nowrap px-5 py-3">{{ dateOnly(s.contract_date) }}</td>
                                    <td class="px-5 py-3">{{ s.customer?.name }}</td>
                                    <td class="whitespace-nowrap px-5 py-3 font-medium tabular-nums">
                                        {{
                                            money(
                                                Math.max(
                                                    0,
                                                    Number(s.unit?.list_price ?? 0) - Number(s.discount ?? 0),
                                                ),
                                                s.currency,
                                            )
                                        }}
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-3 text-muted-foreground">
                                        {{ formatDate(s.created_at) }}
                                    </td>
                                </tr>
                                <tr v-if="!sales.length">
                                    <td colspan="5" class="px-5 py-8 text-center text-muted-foreground">
                                        {{ t('No sales in this period.') }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </TableFrame>
                    <div
                        v-if="salesPage.showControls"
                        class="flex flex-wrap items-center justify-between gap-3 border-t px-5 py-3 print:hidden"
                    >
                        <p class="text-xs text-muted-foreground">
                            {{
                                t('Showing :from–:to of :total', {
                                    from: formatNumber(salesPage.from, 0),
                                    to: formatNumber(salesPage.to, 0),
                                    total: formatNumber(salesPage.total, 0),
                                })
                            }}
                        </p>
                        <div class="flex items-center gap-1">
                            <Button
                                type="button"
                                variant="outline"
                                size="icon"
                                class="size-8"
                                :disabled="salesPage.currentPage <= 1"
                                @click="salesPage.currentPage -= 1"
                            >
                                <ChevronLeft class="size-4" />
                            </Button>
                            <span class="min-w-[4rem] text-center text-xs tabular-nums text-muted-foreground">
                                {{ salesPage.currentPage }} / {{ salesPage.totalPages }}
                            </span>
                            <Button
                                type="button"
                                variant="outline"
                                size="icon"
                                class="size-8"
                                :disabled="salesPage.currentPage >= salesPage.totalPages"
                                @click="salesPage.currentPage += 1"
                            >
                                <ChevronRight class="size-4" />
                            </Button>
                        </div>
                    </div>
                </div>

                <div class="reports-table-panel rounded-2xl border border-border/80 bg-card shadow-soft">
                    <div class="border-b border-border/60 px-5 py-4">
                        <h2 class="text-sm font-semibold tracking-tight">{{ t('New expenses & salaries') }}</h2>
                    </div>
                    <TableFrame table-id="reports-expenses" :columns="tableColumnsExpenses">
                        <table class="min-w-full text-sm">
                            <thead class="table-chrome-head text-start text-muted-foreground">
                                <tr>
                                    <TableIndexTh />
                                    <SortableTh
                                        class="px-5"
                                        column="expense_date"
                                        :sort-key="expenseSort.sortKey"
                                        :sort-dir="expenseSort.sortDir"
                                        @sort="expenseSort.sortBy"
                                    >
                                        {{ t('Date') }}
                                    </SortableTh>
                                    <SortableTh
                                        class="px-5"
                                        column="category.name"
                                        :sort-key="expenseSort.sortKey"
                                        :sort-dir="expenseSort.sortDir"
                                        @sort="expenseSort.sortBy"
                                    >
                                        {{ t('Category') }}
                                    </SortableTh>
                                    <SortableTh
                                        class="px-5"
                                        column="description"
                                        :sort-key="expenseSort.sortKey"
                                        :sort-dir="expenseSort.sortDir"
                                        @sort="expenseSort.sortBy"
                                    >
                                        {{ t('Description') }}
                                    </SortableTh>
                                    <SortableTh
                                        class="px-5"
                                        column="amount"
                                        :sort-key="expenseSort.sortKey"
                                        :sort-dir="expenseSort.sortDir"
                                        @sort="expenseSort.sortBy"
                                    >
                                        {{ t('Amount') }}
                                    </SortableTh>
                                    <SortableTh
                                        class="px-5"
                                        column="created_at"
                                        :sort-key="expenseSort.sortKey"
                                        :sort-dir="expenseSort.sortDir"
                                        @sort="expenseSort.sortBy"
                                    >
                                        {{ t('Created at') }}
                                    </SortableTh>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(e, index) in expensePage.pageRows" :key="e.id" class="border-t">
                                    <TableIndexTd :index="index" :from="expensePage.from" />
                                    <td class="whitespace-nowrap px-5 py-3">{{ dateOnly(e.expense_date) }}</td>
                                    <td class="whitespace-nowrap px-5 py-3">
                                        {{ e.category?.name || t('General') }}
                                    </td>
                                    <td class="px-5 py-3">{{ e.description }}</td>
                                    <td class="whitespace-nowrap px-5 py-3 font-medium tabular-nums">
                                        {{ money(e.amount, e.currency) }}
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-3 text-muted-foreground">
                                        {{ formatDate(e.created_at) }}
                                    </td>
                                </tr>
                                <tr v-if="!expenses.length">
                                    <td colspan="6" class="px-5 py-8 text-center text-muted-foreground">
                                        {{ t('No expenses in this period.') }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </TableFrame>
                    <div
                        v-if="expensePage.showControls"
                        class="flex flex-wrap items-center justify-between gap-3 border-t px-5 py-3 print:hidden"
                    >
                        <p class="text-xs text-muted-foreground">
                            {{
                                t('Showing :from–:to of :total', {
                                    from: formatNumber(expensePage.from, 0),
                                    to: formatNumber(expensePage.to, 0),
                                    total: formatNumber(expensePage.total, 0),
                                })
                            }}
                        </p>
                        <div class="flex items-center gap-1">
                            <Button
                                type="button"
                                variant="outline"
                                size="icon"
                                class="size-8"
                                :disabled="expensePage.currentPage <= 1"
                                @click="expensePage.currentPage -= 1"
                            >
                                <ChevronLeft class="size-4" />
                            </Button>
                            <span class="min-w-[4rem] text-center text-xs tabular-nums text-muted-foreground">
                                {{ expensePage.currentPage }} / {{ expensePage.totalPages }}
                            </span>
                            <Button
                                type="button"
                                variant="outline"
                                size="icon"
                                class="size-8"
                                :disabled="expensePage.currentPage >= expensePage.totalPages"
                                @click="expensePage.currentPage += 1"
                            >
                                <ChevronRight class="size-4" />
                            </Button>
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="!restricted && activity.length" class="reports-table-panel rounded-2xl border border-border/80 bg-card shadow-soft">
                <div class="border-b border-border/60 px-5 py-4">
                    <h2 class="text-sm font-semibold tracking-tight">{{ t('Project activity') }}</h2>
                </div>
                <TableFrame table-id="reports-activity" :columns="tableColumnsActivity">
                    <table class="min-w-full text-sm">
                        <thead class="table-chrome-head text-start text-muted-foreground">
                            <tr>
                                <TableIndexTh />
                                <SortableTh
                                    class="px-5"
                                    column="created_at"
                                    :sort-key="activitySort.sortKey"
                                    :sort-dir="activitySort.sortDir"
                                    @sort="activitySort.sortBy"
                                >
                                    {{ t('Date') }}
                                </SortableTh>
                                <SortableTh
                                    class="px-5"
                                    column="subject.name"
                                    :sort-key="activitySort.sortKey"
                                    :sort-dir="activitySort.sortDir"
                                    @sort="activitySort.sortBy"
                                >
                                    {{ t('Project') }}
                                </SortableTh>
                                <SortableTh
                                    class="px-5"
                                    column="to_status"
                                    :sort-key="activitySort.sortKey"
                                    :sort-dir="activitySort.sortDir"
                                    @sort="activitySort.sortBy"
                                >
                                    {{ t('Status change') }}
                                </SortableTh>
                                <th class="px-5 py-3 font-medium whitespace-nowrap">{{ t('Created at') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(row, index) in activityPage.pageRows" :key="row.id" class="border-t">
                                <TableIndexTd :index="index" :from="activityPage.from" />
                                <td class="whitespace-nowrap px-5 py-3">{{ dateOnly(row.created_at) }}</td>
                                <td class="px-5 py-3">{{ row.subject?.name || t('Project') }}</td>
                                <td class="px-5 py-3">{{ row.from_status }} → {{ row.to_status }}</td>
                                <td class="whitespace-nowrap px-5 py-3 text-muted-foreground">
                                    {{ formatDate(row.created_at) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </TableFrame>
                <div
                    v-if="activityPage.showControls"
                    class="flex flex-wrap items-center justify-between gap-3 border-t px-5 py-3 print:hidden"
                >
                    <p class="text-xs text-muted-foreground">
                        {{
                            t('Showing :from–:to of :total', {
                                from: formatNumber(activityPage.from, 0),
                                to: formatNumber(activityPage.to, 0),
                                total: formatNumber(activityPage.total, 0),
                            })
                        }}
                    </p>
                    <div class="flex items-center gap-1">
                        <Button
                            type="button"
                            variant="outline"
                            size="icon"
                            class="size-8"
                            :disabled="activityPage.currentPage <= 1"
                            @click="activityPage.currentPage -= 1"
                        >
                            <ChevronLeft class="size-4" />
                        </Button>
                        <span class="min-w-[4rem] text-center text-xs tabular-nums text-muted-foreground">
                            {{ activityPage.currentPage }} / {{ activityPage.totalPages }}
                        </span>
                        <Button
                            type="button"
                            variant="outline"
                            size="icon"
                            class="size-8"
                            :disabled="activityPage.currentPage >= activityPage.totalPages"
                            @click="activityPage.currentPage += 1"
                        >
                            <ChevronRight class="size-4" />
                        </Button>
                    </div>
                </div>
            </div>
        </template>
        <MisPrintFooter class="hidden print:flex" />
    </div>
</template>

<style scoped>
.reports-kpi-grid :deep(.text-2xl) {
    font-size: 1.55rem;
    letter-spacing: -0.04em;
}

.reports-chart-card {
    border-color: rgb(255 255 255 / 78%);
    background: rgb(255 255 255 / 78%);
}

.reports-table-panel {
    overflow: hidden;
    background: rgb(255 255 255 / 82%);
    backdrop-filter: blur(12px);
}

.dark .reports-chart-card,
.dark .reports-table-panel {
    background: rgb(255 255 255 / 5%);
}
</style>
