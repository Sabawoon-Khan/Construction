<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowUpRight, Banknote, CircleDollarSign, TrendingUp, Wallet } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import { Button } from '@/components/ui/button';
import { DatePicker } from '@/components/ui/date-picker';
import { MisChartCard, MisStatCard, MisStatGrid } from '@/components/mis';
import SortableTh from '@/components/SortableTh.vue';
import TableFrame from '@/components/TableFrame.vue';
import TableIndexTd from '@/components/TableIndexTd.vue';
import TableIndexTh from '@/components/TableIndexTh.vue';
import { indexTableColumn } from '@/composables/useTableColumns';
import { useCalendar } from '@/composables/useCalendar';
import { provideTableSort } from '@/composables/useTableSort';
import { useTranslations } from '@/composables/useTranslations';
import { translateStatus } from '@/lib/translateStatus';
import { money, moneyByCurrency } from '@/lib/utils';
import { dashboard } from '@/routes';

type Stats = Record<string, any>;

type PeriodPreset = 'today' | 'month' | 'year' | 'all' | 'custom';

const props = defineProps<{
    mode: 'admin' | 'shareholder';
    preset?: PeriodPreset;
    stats: Stats;
}>();

const { t } = useTranslations();
const tableColumns = computed(() => [
    indexTableColumn(),
    { key: 'name', label: t('Project') },
    { key: 'progress_percent', label: t('Progress') },
    { key: 'revenue', label: t('Revenue') },
    { key: 'expenses', label: t('Expenses') },
    { key: 'profit', label: t('Profit') },
]);
const { sortedRows } = provideTableSort(
    () => (props.stats.projects || []) as Array<Record<string, any>>,
);
const calendar = useCalendar();
const formatDate = (value: string | null | undefined) => calendar.format(value) || '—';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Dashboard', href: dashboard() }],
    },
});

const from = ref(props.stats.period?.from ?? '');
const to = ref(props.stats.period?.to ?? '');

watch(
    () => [props.stats.period?.from, props.stats.period?.to],
    ([nextFrom, nextTo]) => {
        from.value = nextFrom ?? '';
        to.value = nextTo ?? '';
    },
);

const kpis = computed(() => props.stats.kpis ?? {});
const charts = computed(() => props.stats.charts ?? {});
const isAdmin = computed(() => props.mode !== 'shareholder');

const periodLabel = computed(() => {
    if (props.preset === 'all') {
        return t('All time');
    }
    const start = props.stats.period?.from;
    const end = props.stats.period?.to;
    if (!start || !end) {
        return '';
    }
    return `${formatDate(start)} – ${formatDate(end)}`;
});

/** Short month labels: "Apr 2026" → "Apr" when all same year, else "Apr '26" */
const monthlyChart = computed(() => {
    const monthly = charts.value.monthly ?? {};
    const rawLabels = (monthly.labels as string[]) ?? [];
    const years = new Set(
        rawLabels
            .map((label) => String(label).match(/\d{4}/)?.[0])
            .filter(Boolean),
    );
    const labels = rawLabels.map((label) => {
        const text = String(label);
        const month = text.slice(0, 3);
        const year = text.match(/\d{4}/)?.[0];
        if (!year) {
            return text;
        }
        return years.size <= 1 ? month : `${month} '${year.slice(2)}`;
    });

    return {
        labels,
        datasets: [
            {
                label: t('Sales'),
                data: (monthly.sales as number[]) ?? [],
                color: 'var(--chart-1)',
            },
            {
                label: t('Expenses'),
                data: (monthly.expenses as number[]) ?? [],
                color: 'var(--chart-2)',
            },
        ],
    };
});

const currencyChart = computed(() => {
    const byCurrency =
        (charts.value.expenses_by_currency as Record<string, number>) ??
        (kpis.value.expenses_by_currency as Record<string, number>) ??
        {};
    const entries = Object.entries(byCurrency)
        .map(([code, amount]) => [code.toUpperCase(), Number(amount) || 0] as const)
        .filter(([, amount]) => amount !== 0)
        .sort(([a], [b]) => a.localeCompare(b));

    const labels = entries.length ? entries.map(([code]) => code) : ['AFN', 'USD'];
    const data = entries.length ? entries.map(([, amount]) => amount) : [0, 0];

    return {
        labels,
        datasets: [
            {
                label: t('Expenses'),
                data,
            },
        ],
        totalLabel: moneyByCurrency(byCurrency, kpis.value.period_expenses),
    };
});

const projectsChart = computed(() => {
    const projectCharts = charts.value.projects ?? {};
    let labels = (projectCharts.labels as string[]) ?? [];
    let revenue = (projectCharts.revenue as number[]) ?? [];
    let profit = (projectCharts.profit as number[]) ?? [];

    if (!labels.length) {
        const rows = [...(props.stats.projects || [])]
            .sort((a, b) => Number(b.profit ?? 0) - Number(a.profit ?? 0))
            .slice(0, 6);
        labels = rows.map((row) => String(row.name ?? '—'));
        revenue = rows.map((row) => Number(row.revenue ?? 0));
        profit = rows.map((row) => Number(row.profit ?? 0));
    }

    return {
        labels,
        datasets: [
            {
                label: t('Revenue'),
                data: revenue,
                color: 'var(--chart-1)',
            },
            {
                label: t('Profit'),
                data: profit,
                color: 'var(--chart-3)',
            },
        ],
    };
});

function applyPeriod(nextPreset?: PeriodPreset) {
    router.get(
        '/dashboard',
        {
            preset: nextPreset ?? 'custom',
            from: nextPreset && nextPreset !== 'custom' ? undefined : from.value || undefined,
            to: nextPreset && nextPreset !== 'custom' ? undefined : to.value || undefined,
        },
        { preserveState: true, replace: true },
    );
}

function openProject(row: Record<string, any>) {
    if (row?.id) {
        router.visit(`/projects/${row.id}`);
    }
}
</script>

<template>
    <Head :title="t('Dashboard')" />

    <div class="dashboard-page mx-auto flex h-full w-full max-w-[1600px] flex-1 flex-col gap-6 p-4 md:p-6">
        <div class="relative z-10 overflow-visible rounded-2xl border border-white/55 bg-white/40 p-3 shadow-soft backdrop-blur-xl sm:p-4 dark:border-white/10 dark:bg-white/5">
            <div class="mb-3 flex flex-wrap items-center justify-between gap-2 px-1">
                <div class="text-sm font-medium">{{ t('Period') }}</div>
                <div v-if="periodLabel" class="text-xs text-muted-foreground tabular-nums">
                    {{ periodLabel }}
                </div>
            </div>
            <div class="relative z-20 flex flex-wrap items-end gap-2">
                <Button
                    variant="outline"
                    size="sm"
                    :class="preset === 'today' ? 'border-emerald-600 bg-emerald-50 dark:bg-emerald-950/40' : ''"
                    @click="applyPeriod('today')"
                >
                    {{ t('Today') }}
                </Button>
                <Button
                    variant="outline"
                    size="sm"
                    :class="preset === 'month' ? 'border-emerald-600 bg-emerald-50 dark:bg-emerald-950/40' : ''"
                    @click="applyPeriod('month')"
                >
                    {{ t('This month') }}
                </Button>
                <Button
                    variant="outline"
                    size="sm"
                    :class="preset === 'year' ? 'border-emerald-600 bg-emerald-50 dark:bg-emerald-950/40' : ''"
                    @click="applyPeriod('year')"
                >
                    {{ t('This year') }}
                </Button>
                <Button
                    variant="outline"
                    size="sm"
                    :class="preset === 'all' ? 'border-emerald-600 bg-emerald-50 dark:bg-emerald-950/40' : ''"
                    @click="applyPeriod('all')"
                >
                    {{ t('All time') }}
                </Button>
                <div class="grid min-w-[160px] flex-1 gap-1">
                    <label class="text-xs text-muted-foreground">{{ t('From') }}</label>
                    <DatePicker v-model="from" class="h-9 bg-white/50 backdrop-blur-sm" />
                </div>
                <div class="grid min-w-[160px] flex-1 gap-1">
                    <label class="text-xs text-muted-foreground">{{ t('To') }}</label>
                    <DatePicker v-model="to" class="h-9 bg-white/50 backdrop-blur-sm" />
                </div>
                <Button size="sm" @click="applyPeriod('custom')">{{ t('Apply') }}</Button>
            </div>
        </div>

        <!-- Shareholder -->
        <MisStatGrid v-if="!isAdmin" :columns="4">
            <MisStatCard
                :label="t('Company profit')"
                :value="money(stats.company_profit)"
            />
            <MisStatCard
                :label="t('Your ownership')"
                :value="`${Number(stats.partner?.ownership_percentage ?? 0).toFixed(2)}%`"
            />
            <MisStatCard
                :label="t('Your profit share')"
                :value="money(stats.my_profit)"
                tone="success"
            />
            <MisStatCard
                :label="t('Still owed')"
                :value="money(stats.my_balance)"
                tone="warning"
            />
        </MisStatGrid>

        <template v-if="!isAdmin">
            <div class="rounded-2xl border bg-white/40 backdrop-blur-xl border-white/55 dark:border-white/10 dark:bg-white/5">
                <div class="border-b px-5 py-4">
                    <h2 class="font-medium">{{ t('Your project shares') }}</h2>
                </div>
                <div class="space-y-3 p-5">
                    <div
                        v-for="share in stats.project_shares || []"
                        :key="share.project_id"
                        class="rounded-xl bg-muted/40 px-3 py-3"
                    >
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <div class="font-medium">{{ share.project_name }}</div>
                                <div class="text-xs text-muted-foreground">
                                    {{
                                        t(':value% ownership', {
                                            value: Number(share.ownership_percentage ?? 0).toFixed(2),
                                        })
                                    }}
                                </div>
                            </div>
                            <div class="text-right text-sm font-semibold tabular-nums">
                                {{ money(share.entitled_share) }}
                            </div>
                        </div>
                    </div>
                    <p
                        v-if="!(stats.project_shares || []).length"
                        class="text-sm text-muted-foreground"
                    >
                        {{ t('No project shares assigned yet.') }}
                    </p>
                </div>
            </div>
        </template>

        <!-- Admin KPIs -->
        <MisStatGrid v-else :columns="4">
            <MisStatCard
                :label="t('Sales')"
                :hint="t('Contracts in this period')"
                :icon="CircleDollarSign"
                :amounts="kpis.sales_by_currency"
                :fallback-amount="kpis.period_sales"
            />
            <MisStatCard
                :label="t('Expenses')"
                :hint="t('Approved spending')"
                :icon="Wallet"
                tone="danger"
                :amounts="kpis.expenses_by_currency"
                :fallback-amount="kpis.period_expenses"
            />
            <MisStatCard
                :label="t('Profit')"
                :hint="t('Sales + income − expenses')"
                :icon="TrendingUp"
                tone="success"
                :amounts="kpis.profit_by_currency"
                :fallback-amount="kpis.net_profit_period"
            />
            <MisStatCard
                :label="t('Cash in')"
                :hint="t('Money received')"
                :icon="Banknote"
                tone="success"
                :amounts="kpis.receipts_by_currency"
                :fallback-amount="kpis.period_receipts"
            />
        </MisStatGrid>

        <template v-if="isAdmin">
            <div class="grid gap-4 xl:grid-cols-5">
                <MisChartCard
                    class="xl:col-span-3"
                    :title="t('Sales vs expenses')"
                    :description="t('Grouped totals for the selected period')"
                    type="bar"
                    :labels="monthlyChart.labels"
                    :datasets="monthlyChart.datasets"
                />
                <MisChartCard
                    class="xl:col-span-2"
                    :title="t('Expenses by currency')"
                    :description="currencyChart.totalLabel"
                    type="pie"
                    :labels="currencyChart.labels"
                    :datasets="currencyChart.datasets"
                />
            </div>

            <div class="grid gap-4 xl:grid-cols-5">
                <MisChartCard
                    class="xl:col-span-2"
                    :title="t('Top projects')"
                    :description="t('Revenue and profit')"
                    type="bar"
                    :labels="projectsChart.labels"
                    :datasets="projectsChart.datasets"
                />
                <div class="overflow-hidden rounded-2xl border bg-white/40 backdrop-blur-xl border-white/55 dark:border-white/10 dark:bg-white/5 shadow-soft xl:col-span-3">
                    <div class="flex items-center justify-between border-b px-5 py-4">
                        <h2 class="font-medium">{{ t('Projects') }}</h2>
                        <Button as-child variant="ghost" size="sm">
                            <Link href="/projects">
                                {{ t('View all') }} <ArrowUpRight class="size-4" />
                            </Link>
                        </Button>
                    </div>
                    <TableFrame table-id="dashboard-projects" :columns="tableColumns">
                        <table class="min-w-full text-sm">
                            <thead class="table-chrome-head text-start text-muted-foreground">
                                <tr>
                                    <TableIndexTh />
                                    <SortableTh class="px-5" column="name">{{ t('Project') }}</SortableTh>
                                    <SortableTh class="px-5" column="progress_percent">{{ t('Progress') }}</SortableTh>
                                    <SortableTh class="px-5" column="revenue">{{ t('Revenue') }}</SortableTh>
                                    <SortableTh class="px-5" column="expenses">{{ t('Expenses') }}</SortableTh>
                                    <SortableTh class="px-5" column="profit">{{ t('Profit') }}</SortableTh>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="(p, index) in sortedRows"
                                    :key="p.id"
                                    class="cursor-pointer border-t transition-colors hover:bg-muted/30"
                                    @click="openProject(p)"
                                >
                                    <TableIndexTd :index="index" />
                                    <td class="px-5 py-3">
                                        <Link
                                            :href="`/projects/${p.id}`"
                                            class="font-medium hover:text-primary hover:underline"
                                            @click.stop
                                        >
                                            {{ p.name }}
                                        </Link>
                                        <div class="text-xs text-muted-foreground">
                                            {{ translateStatus(p.status, t) }}
                                        </div>
                                    </td>
                                    <td class="px-5 py-3">
                                        <div class="flex items-center gap-2">
                                            <div class="h-2 w-20 overflow-hidden rounded-full bg-muted">
                                                <div
                                                    class="h-full rounded-full bg-emerald-600"
                                                    :style="{
                                                        width: `${Math.min(100, Number(p.progress_percent || 0))}%`,
                                                    }"
                                                />
                                            </div>
                                            <span class="tabular-nums">{{ p.progress_percent }}%</span>
                                        </div>
                                    </td>
                                    <td class="px-5 py-3 tabular-nums">{{ money(p.revenue) }}</td>
                                    <td class="px-5 py-3 tabular-nums">{{ money(p.expenses) }}</td>
                                    <td
                                        class="px-5 py-3 font-medium tabular-nums"
                                        :class="Number(p.profit) < 0 ? 'text-rose-700 dark:text-rose-400' : 'text-emerald-700 dark:text-emerald-400'"
                                    >
                                        {{ money(p.profit) }}
                                    </td>
                                </tr>
                                <tr v-if="!(stats.projects || []).length">
                                    <td colspan="6" class="px-5 py-8 text-center text-muted-foreground">
                                        {{ t('No projects yet.') }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </TableFrame>
                </div>
            </div>

            <div class="grid gap-4 lg:grid-cols-3">
                <div class="rounded-2xl border bg-white/40 backdrop-blur-xl border-white/55 dark:border-white/10 dark:bg-white/5 shadow-soft">
                    <div class="flex items-center justify-between border-b px-5 py-4">
                        <h2 class="font-medium">{{ t('Overdue installments') }}</h2>
                        <Button as-child variant="ghost" size="sm">
                            <Link href="/sales">{{ t('Open') }} <ArrowUpRight class="size-4" /></Link>
                        </Button>
                    </div>
                    <div class="divide-y">
                        <div
                            v-for="row in stats.overdue_installments || []"
                            :key="row.id"
                            class="flex items-center justify-between gap-3 px-5 py-3 text-sm"
                        >
                            <div class="min-w-0">
                                <div class="truncate font-medium">{{ row.customer || row.sale_no }}</div>
                                <div class="truncate text-xs text-muted-foreground">
                                    {{ row.project || '—' }} · {{ formatDate(row.due_date) }}
                                </div>
                            </div>
                            <div class="shrink-0 font-semibold tabular-nums text-amber-700">
                                {{ money(row.remaining_amount ?? row.remaining, row.currency) }}
                            </div>
                        </div>
                        <div
                            v-if="!(stats.overdue_installments || []).length"
                            class="px-5 py-6 text-sm text-muted-foreground"
                        >
                            {{ t('No overdue installments.') }}
                        </div>
                    </div>
                </div>

                <div class="rounded-2xl border bg-white/40 backdrop-blur-xl border-white/55 dark:border-white/10 dark:bg-white/5 shadow-soft">
                    <div class="flex items-center justify-between border-b px-5 py-4">
                        <h2 class="font-medium">{{ t('Unpaid purchases') }}</h2>
                        <Button as-child variant="ghost" size="sm">
                            <Link href="/purchases">{{ t('Open') }} <ArrowUpRight class="size-4" /></Link>
                        </Button>
                    </div>
                    <div class="divide-y">
                        <div
                            v-for="row in stats.unpaid_purchases || []"
                            :key="row.id"
                            class="flex items-center justify-between gap-3 px-5 py-3 text-sm"
                        >
                            <div class="min-w-0">
                                <div class="truncate font-medium">{{ row.supplier || row.purchase_no }}</div>
                                <div class="truncate text-xs text-muted-foreground">{{ row.project || '—' }}</div>
                            </div>
                            <div class="shrink-0 font-semibold tabular-nums text-rose-700">
                                {{ money(row.remaining_amount ?? row.remaining, row.currency) }}
                            </div>
                        </div>
                        <div
                            v-if="!(stats.unpaid_purchases || []).length"
                            class="px-5 py-6 text-sm text-muted-foreground"
                        >
                            {{ t('No unpaid purchases.') }}
                        </div>
                    </div>
                </div>

                <div class="rounded-2xl border bg-white/40 backdrop-blur-xl border-white/55 dark:border-white/10 dark:bg-white/5 shadow-soft">
                    <div class="flex items-center justify-between border-b px-5 py-4">
                        <h2 class="font-medium">{{ t('Low stock') }}</h2>
                        <Button as-child variant="ghost" size="sm">
                            <Link href="/inventory">{{ t('Open') }} <ArrowUpRight class="size-4" /></Link>
                        </Button>
                    </div>
                    <div class="divide-y">
                        <div
                            v-for="row in stats.low_stock || []"
                            :key="row.id"
                            class="flex items-center justify-between gap-3 px-5 py-3 text-sm"
                        >
                            <div class="min-w-0">
                                <div class="truncate font-medium">{{ row.name }}</div>
                                <div class="truncate text-xs text-muted-foreground">{{ row.code }}</div>
                            </div>
                            <div class="shrink-0 text-right text-xs tabular-nums">
                                <div class="font-semibold text-orange-700">{{ row.on_hand }}</div>
                                <div class="text-muted-foreground">{{ t('Min') }} {{ row.minimum_stock }}</div>
                            </div>
                        </div>
                        <div
                            v-if="!(stats.low_stock || []).length"
                            class="px-5 py-6 text-sm text-muted-foreground"
                        >
                            {{ t('Stock levels look fine.') }}
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </div>
</template>
