<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    ArrowUpRight,
    ChevronDown,
    ExternalLink,
    MoreHorizontal,
} from '@lucide/vue';
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import { Button } from '@/components/ui/button';
import { DatePicker } from '@/components/ui/date-picker';
import { MisChartCard, MisMoneyStack } from '@/components/mis';
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

type Stats = Record<string, any>;
type PeriodPreset = 'today' | 'month' | 'year' | 'all' | 'custom';

const props = defineProps<{
    mode: 'admin' | 'shareholder';
    preset?: PeriodPreset;
    stats: Stats;
}>();

const { t } = useTranslations();
const calendar = useCalendar();
const formatDate = (value: string | null | undefined) =>
    calendar.format(value) || '—';
const periodOpen = ref(false);
const from = ref(props.stats.period?.from ?? '');
const to = ref(props.stats.period?.to ?? '');

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

watch(
    () => [props.stats.period?.from, props.stats.period?.to],
    ([nextFrom, nextTo]) => {
        from.value = nextFrom ?? '';
        to.value = nextTo ?? '';
    },
);

const kpis = computed(() => props.stats.kpis ?? {});
const isAdmin = computed(() => props.mode !== 'shareholder');
const projects = computed<Array<Record<string, any>>>(
    () => props.stats.projects ?? [],
);
const featured = computed(() => {
    if (!projects.value.length) return null;
    return [...projects.value].sort(
        (a, b) =>
            Number(b.progress_percent ?? 0) - Number(a.progress_percent ?? 0),
    )[0];
});

const periodLabel = computed(() => {
    if (props.preset === 'all') return t('All time');
    if (props.preset === 'today') return t('Today');
    if (props.preset === 'month') return t('This month');
    if (props.preset === 'year') return t('This year');
    const start = props.stats.period?.from;
    const end = props.stats.period?.to;
    if (!start || !end) return t('Overall');
    return `${calendar.format(start) || start} – ${calendar.format(end) || end}`;
});

const presetLabel = computed(() => {
    const map: Record<string, string> = {
        today: t('Today'),
        month: t('This month'),
        year: t('This year'),
        all: t('All time'),
        custom: t('Custom'),
    };
    return map[props.preset ?? 'month'] ?? t('Overall');
});

function applyPeriod(nextPreset: PeriodPreset) {
    periodOpen.value = false;
    router.get(
        '/dashboard',
        {
            preset: nextPreset,
            from: nextPreset === 'custom' ? from.value || undefined : undefined,
            to: nextPreset === 'custom' ? to.value || undefined : undefined,
        },
        { preserveState: true, replace: true },
    );
}

function pctChange(current: number, previous: number): {
    text: string;
    negative: boolean;
} {
    if (!previous && !current) return { text: '0%', negative: false };
    if (!previous) return { text: '+100%', negative: false };
    const change = ((current - previous) / Math.abs(previous)) * 100;
    const rounded = Math.round(change * 10) / 10;
    return {
        text: `${rounded > 0 ? '+' : ''}${rounded}%`,
        negative: rounded < 0,
    };
}

const monthly = computed(() => {
    const raw = props.stats.charts?.monthly ?? {};
    const labels = (raw.labels ?? []) as string[];
    const sales = ((raw.sales ?? []) as number[]).map(Number);
    const expenses = ((raw.expenses ?? []) as number[]).map(Number);
    return { labels, sales, expenses };
});

const currencyChart = computed(() => {
    const byCurrency =
        (props.stats.charts?.expenses_by_currency as Record<string, number>) ??
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
        datasets: [{ label: t('Expenses'), data }],
        totalLabel: moneyByCurrency(byCurrency, kpis.value.period_expenses),
    };
});

const projectsChart = computed(() => {
    const projectCharts = props.stats.charts?.projects ?? {};
    let labels = (projectCharts.labels as string[]) ?? [];
    let revenue = (projectCharts.revenue as number[]) ?? [];
    let profit = (projectCharts.profit as number[]) ?? [];

    if (!labels.length) {
        const rows = [...projects.value]
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

const salesTrend = computed(() => {
    const sales = monthly.value.sales;
    if (sales.length < 2) return { text: '—', negative: false };
    return pctChange(sales[sales.length - 1] ?? 0, sales[sales.length - 2] ?? 0);
});

const expenseTrend = computed(() => {
    const expenses = monthly.value.expenses;
    if (expenses.length < 2) return { text: '—', negative: false };
    return pctChange(
        expenses[expenses.length - 1] ?? 0,
        expenses[expenses.length - 2] ?? 0,
    );
});

const profitTrend = computed(() => {
    const { sales, expenses } = monthly.value;
    if (sales.length < 2) return { text: '—', negative: false };
    const last =
        (sales[sales.length - 1] ?? 0) - (expenses[expenses.length - 1] ?? 0);
    const prev =
        (sales[sales.length - 2] ?? 0) - (expenses[expenses.length - 2] ?? 0);
    return pctChange(last, prev);
});

function compactAmount(value: number): string {
    const amount = Number(value ?? 0);
    if (!Number.isFinite(amount)) return '0';
    return new Intl.NumberFormat('en-US', {
        notation: Math.abs(amount) >= 1000 ? 'compact' : 'standard',
        maximumFractionDigits: Math.abs(amount) >= 1000 ? 1 : 0,
    }).format(amount);
}

const chartMax = computed(() => {
    const { sales, expenses } = monthly.value;
    return Math.max(...sales, ...expenses, 1);
});

const yTicks = computed(() => {
    const max = chartMax.value;
    return [
        { label: compactAmount(max), value: max },
        { label: compactAmount(max / 2), value: max / 2 },
        { label: '0', value: 0 },
    ];
});

const bars = computed(() => {
    const { labels, sales, expenses } = monthly.value;
    if (!labels.length) return [];
    const max = chartMax.value;

    return labels.map((label, index) => {
        const sale = Number(sales[index] ?? 0);
        const expense = Number(expenses[index] ?? 0);
        return {
            label: String(label).slice(0, 3),
            fullLabel: String(label),
            salesHeight: Math.max(4, (sale / max) * 100),
            expenseHeight: Math.max(4, (expense / max) * 100),
            sales: sale,
            expenses: expense,
            salesLabel: money(sale),
            expensesLabel: money(expense),
            salesCompact: compactAmount(sale),
            expensesCompact: compactAmount(expense),
            index,
        };
    });
});

const rangeStart = ref(0);
const rangeEnd = ref(100);
const rangeDrag = ref<'start' | 'end' | 'fill' | null>(null);
const rangeDragOrigin = ref({ x: 0, start: 0, end: 100 });
const activeRangeTrack = ref<HTMLElement | null>(null);
const hoverBarIndex = ref<number | null>(null);

const rangeStyle = computed(() => {
    const left = Math.min(rangeStart.value, rangeEnd.value);
    const width = Math.abs(rangeEnd.value - rangeStart.value);
    return {
        left: `${left}%`,
        width: `${Math.max(width, 4)}%`,
    };
});

const visibleBars = computed(() => {
    const start = Math.min(rangeStart.value, rangeEnd.value);
    const end = Math.max(rangeStart.value, rangeEnd.value);
    const total = Math.max(bars.value.length - 1, 1);

    return bars.value.map((bar) => {
        const pct =
            bars.value.length === 1 ? 50 : (bar.index / total) * 100;
        return {
            ...bar,
            active: pct >= start && pct <= end,
        };
    });
});

const rangeTotals = computed(() => {
    const active = visibleBars.value.filter((bar) => bar.active);
    const source = active.length ? active : visibleBars.value;
    const sales = source.reduce((sum, bar) => sum + bar.sales, 0);
    const expenses = source.reduce((sum, bar) => sum + bar.expenses, 0);
    const total = Math.abs(sales) + Math.abs(expenses);
    return {
        sales,
        expenses,
        share: total
            ? Math.round((Math.abs(sales) / total) * 1000) / 10
            : 0,
        salesLabel: money(sales),
        expensesLabel: money(expenses),
    };
});

watch(
    () => bars.value.length,
    (len) => {
        if (!len) return;
        rangeStart.value = 0;
        rangeEnd.value = 100;
    },
    { immediate: true },
);

function pointerPct(event: PointerEvent, el: HTMLElement): number {
    const rect = el.getBoundingClientRect();
    if (!rect.width) return 0;
    return Math.min(
        100,
        Math.max(0, ((event.clientX - rect.left) / rect.width) * 100),
    );
}

function startRangeDrag(
    event: PointerEvent,
    mode: 'start' | 'end' | 'fill',
    track: HTMLElement | null,
) {
    if (!track) return;
    event.preventDefault();
    event.stopPropagation();
    activeRangeTrack.value = track;
    rangeDrag.value = mode;
    rangeDragOrigin.value = {
        x: event.clientX,
        start: rangeStart.value,
        end: rangeEnd.value,
    };
    (event.currentTarget as HTMLElement | null)?.setPointerCapture?.(
        event.pointerId,
    );

    if (mode !== 'fill') {
        const pct = pointerPct(event, track);
        if (mode === 'start') {
            rangeStart.value = Math.min(pct, rangeEnd.value - 4);
        } else {
            rangeEnd.value = Math.max(pct, rangeStart.value + 4);
        }
    }
}

function onRangePointerMove(event: PointerEvent) {
    if (!rangeDrag.value || !activeRangeTrack.value) return;
    const track = activeRangeTrack.value;
    const pct = pointerPct(event, track);
    const minSpan = 6;

    if (rangeDrag.value === 'start') {
        rangeStart.value = Math.min(pct, rangeEnd.value - minSpan);
        return;
    }
    if (rangeDrag.value === 'end') {
        rangeEnd.value = Math.max(pct, rangeStart.value + minSpan);
        return;
    }

    const rect = track.getBoundingClientRect();
    const delta = ((event.clientX - rangeDragOrigin.value.x) / rect.width) * 100;
    let nextStart = rangeDragOrigin.value.start + delta;
    let nextEnd = rangeDragOrigin.value.end + delta;
    const span = nextEnd - nextStart;
    if (nextStart < 0) {
        nextStart = 0;
        nextEnd = span;
    }
    if (nextEnd > 100) {
        nextEnd = 100;
        nextStart = 100 - span;
    }
    rangeStart.value = nextStart;
    rangeEnd.value = nextEnd;
}

function stopRangeDrag() {
    rangeDrag.value = null;
    activeRangeTrack.value = null;
}

function jumpRange(event: PointerEvent, track: HTMLElement) {
    if (rangeDrag.value) return;
    const pct = pointerPct(event, track);
    const span = Math.max(Math.abs(rangeEnd.value - rangeStart.value), 12);
    let start = pct - span / 2;
    let end = pct + span / 2;
    if (start < 0) {
        start = 0;
        end = span;
    }
    if (end > 100) {
        end = 100;
        start = 100 - span;
    }
    rangeStart.value = start;
    rangeEnd.value = end;
}

function onTrackJump(event: PointerEvent) {
    const track = event.currentTarget as HTMLElement | null;
    if (track) jumpRange(event, track);
}

function onHandleStart(event: PointerEvent) {
    const track =
        (event.currentTarget as HTMLElement | null)?.parentElement ?? null;
    startRangeDrag(event, 'start', track);
}

function onHandleEnd(event: PointerEvent) {
    const track =
        (event.currentTarget as HTMLElement | null)?.parentElement ?? null;
    startRangeDrag(event, 'end', track);
}

function onFillDrag(event: PointerEvent) {
    const track =
        (event.currentTarget as HTMLElement | null)?.parentElement ?? null;
    startRangeDrag(event, 'fill', track);
}

onBeforeUnmount(() => {
    if (typeof window === 'undefined') return;
    window.removeEventListener('pointermove', onRangePointerMove);
    window.removeEventListener('pointerup', stopRangeDrag);
    window.removeEventListener('pointercancel', stopRangeDrag);
});

if (typeof window !== 'undefined') {
    window.addEventListener('pointermove', onRangePointerMove);
    window.addEventListener('pointerup', stopRangeDrag);
    window.addEventListener('pointercancel', stopRangeDrag);
}

function gaugePercent(part: number, whole: number): number {
    if (!whole) return part ? 100 : 0;
    return Math.min(100, Math.round((Math.abs(part) / Math.abs(whole)) * 1000) / 10);
}

const metrics = computed(() => {
    if (!isAdmin.value) {
        const ownership = Number(props.stats.partner?.ownership_percentage ?? 0);
        const myProfit = Number(props.stats.my_profit ?? 0);
        const myBalance = Number(props.stats.my_balance ?? 0);
        const companyProfit = Number(props.stats.company_profit ?? 0);
        return [
            {
                label: t('Your ownership'),
                value: `${ownership.toFixed(1)}%`,
                amounts: undefined,
                fallback: undefined,
                hint: money(props.stats.company_profit),
                trend: { text: `${ownership.toFixed(1)}%`, negative: false },
                percent: ownership,
                color: '#4c7cf3',
                href: '/projects',
            },
            {
                label: t('Your profit share'),
                value: '',
                amounts: null,
                fallback: myProfit,
                hint: t('Company profit') + ': ' + money(companyProfit),
                trend: profitTrend.value,
                percent: gaugePercent(myProfit, companyProfit || myProfit || 1),
                color: '#42bfa7',
                href: '/projects',
            },
            {
                label: t('Still owed'),
                value: '',
                amounts: null,
                fallback: myBalance,
                hint: t('Outstanding balance'),
                trend: {
                    text: myBalance > 0 ? t('Due') : t('Clear'),
                    negative: myBalance > 0,
                },
                percent: gaugePercent(myBalance, Math.max(myProfit, myBalance, 1)),
                color: '#e51f56',
                href: '/payments',
            },
        ];
    }

    const sales = Number(kpis.value.period_sales ?? 0);
    const expenses = Number(kpis.value.period_expenses ?? 0);
    const profit = Number(kpis.value.net_profit_period ?? 0);
    const revenue = Math.max(Number(kpis.value.period_revenue ?? sales), sales, 1);

    return [
        {
            label: t('Sales'),
            value: '',
            amounts: kpis.value.sales_by_currency,
            fallback: sales,
            hint: t('Contracts in this period'),
            trend: salesTrend.value,
            percent: gaugePercent(sales, revenue),
            color: '#4c7cf3',
            href: '/sales',
        },
        {
            label: t('Expenses'),
            value: '',
            amounts: kpis.value.expenses_by_currency,
            fallback: expenses,
            hint: t('Approved spending'),
            trend: expenseTrend.value,
            percent: gaugePercent(expenses, revenue),
            color: '#e51f56',
            href: '/expenses',
        },
        {
            label: t('Profit'),
            value: '',
            amounts: kpis.value.profit_by_currency,
            fallback: profit,
            hint: t('Sales − expenses'),
            trend: profitTrend.value,
            percent: gaugePercent(profit, revenue),
            color: '#42bfa7',
            href: '/reports',
        },
    ];
});

const projectRows = computed(() => {
    const colors = ['#c45a4a', '#d9256e', '#f07146', '#e6b83f', '#d7cd4c', '#e5cb3e', '#b7c85f', '#87c587', '#62bea4', '#42bfa7'];
    return projects.value.slice(0, 8).map((project, index) => ({
        id: project.id,
        name: String(project.name ?? '—'),
        progress: Number(project.progress_percent ?? 0),
        profit: money(project.profit),
        color: colors[index % colors.length],
    }));
});

const shareRows = computed(() => {
    const colors = ['#4c7cf3', '#42bfa7', '#f07146', '#e51f56', '#e6b83f', '#87c587'];
    return ((props.stats.project_shares ?? []) as Array<Record<string, any>>)
        .slice(0, 8)
        .map((share, index) => ({
            id: share.project_id,
            name: String(share.project_name ?? '—'),
            progress: Number(share.ownership_percentage ?? 0),
            profit: money(share.entitled_share),
            color: colors[index % colors.length],
        }));
});

const listRows = computed(() => (isAdmin.value ? projectRows.value : shareRows.value));

function arcDash(percent: number): string {
    const length = 119.4;
    return `${Math.max(0, Math.min(100, percent)) * 1.194} ${length}`;
}

function openProject(id?: number | string) {
    if (id) router.visit(`/projects/${id}`);
}
</script>

<template>
    <Head :title="t('Dashboard')" />

    <div class="dashboard-page">
        <div class="dashboard-v2" @click="periodOpen = false">
            <main class="visual-area">
                <section class="hero-copy">
                    <h1>{{ t('Smart Construction') }}</h1>
                    <p>
                        {{
                            t(
                                'Track projects, sales, and spending in one live construction overview.',
                            )
                        }}
                    </p>
                </section>

                <div class="building-wrap">
                    <img
                        class="building"
                        src="/images/dashboard-v2-building.png"
                        alt="Modern construction project"
                    />
                </div>

                <article
                    v-if="featured"
                    class="project-float"
                    role="button"
                    tabindex="0"
                    @click="openProject(featured.id)"
                    @keydown.enter="openProject(featured.id)"
                >
                    <div class="project-head">
                        <div>
                            <strong>{{ featured.name }}</strong>
                            <small>
                                {{ featured.code || featured.status || '—' }}
                                ·
                                {{ featured.units_count ?? 0 }}
                                {{ t('units') }}
                            </small>
                        </div>
                        <button type="button" @click.stop="openProject(featured.id)">
                            <ExternalLink />
                        </button>
                    </div>
                    <div class="project-bottom">
                        <b>{{ Number(featured.progress_percent ?? 0).toFixed(0) }}%</b>
                        <div class="workers">
                            <span class="w1" /><span class="w2" /><span class="w3" />
                            <em>+{{ featured.sales_count ?? projects.length }}</em>
                        </div>
                    </div>
                    <div class="project-meta">
                        <span>{{ money(featured.revenue) }}</span>
                        <span>{{ money(featured.profit) }}</span>
                    </div>
                </article>

                <section class="chart-panel">
                    <div class="chart-header">
                        <div>
                            <span>{{ t('Sales vs expenses') }}</span>
                            <div class="chart-number">
                                {{ rangeTotals.share }}%
                                <small
                                    class="up"
                                    :class="{ down: salesTrend.negative }"
                                >
                                    {{ salesTrend.text }}
                                    <b>{{ salesTrend.negative ? '▼' : '▲' }}</b>
                                </small>
                            </div>
                        </div>
                        <div class="chart-actions">
                            <button type="button" @click="router.visit('/sales')">
                                <ExternalLink />
                            </button>
                            <div class="legend top dual">
                                <span><i class="sales" /> {{ t('Sales') }}</span>
                                <span><i class="expense" /> {{ t('Expenses') }}</span>
                            </div>
                        </div>
                    </div>

                    <div v-if="visibleBars.length" class="chart-plot">
                        <div class="y-axis" aria-hidden="true">
                            <span
                                v-for="tick in yTicks"
                                :key="tick.label"
                                >{{ tick.label }}</span
                            >
                        </div>
                        <div
                            class="bar-chart structo"
                            :style="{ '--bar-count': visibleBars.length }"
                        >
                            <div
                                v-for="(bar, index) in visibleBars"
                                :key="`${bar.fullLabel}-${index}`"
                                class="bar-column"
                                :class="{
                                    active: bar.active,
                                    hovering: hoverBarIndex === index,
                                }"
                                tabindex="0"
                                @mouseenter="hoverBarIndex = index"
                                @mouseleave="hoverBarIndex = null"
                                @focusin="hoverBarIndex = index"
                                @focusout="hoverBarIndex = null"
                                @click.stop="
                                    hoverBarIndex =
                                        hoverBarIndex === index ? null : index
                                "
                            >
                                <div
                                    v-if="hoverBarIndex === index"
                                    class="bar-tooltip"
                                >
                                    <strong>{{ bar.fullLabel }}</strong>
                                    <span
                                        >{{ t('Sales') }}:
                                        {{ bar.salesLabel }}</span
                                    >
                                    <span
                                        >{{ t('Expenses') }}:
                                        {{ bar.expensesLabel }}</span
                                    >
                                </div>
                                <div class="bar-pair">
                                    <i
                                        class="sales"
                                        :class="{ active: bar.active }"
                                        :style="{
                                            height: `${bar.salesHeight}%`,
                                        }"
                                    />
                                    <i
                                        class="expense"
                                        :class="{ active: bar.active }"
                                        :style="{
                                            height: `${bar.expenseHeight}%`,
                                        }"
                                    />
                                </div>
                                <em class="bar-amount">{{
                                    bar.salesCompact
                                }}</em>
                                <small>{{ bar.label }}</small>
                            </div>
                        </div>
                    </div>
                    <div v-else class="chart-empty">
                        {{ t('No chart data for this period.') }}
                    </div>

                    <div
                        class="range-track"
                        @pointerdown="onTrackJump"
                    >
                        <span
                            class="range-fill"
                            :style="rangeStyle"
                            @pointerdown="onFillDrag"
                        />
                        <button
                            type="button"
                            class="range-handle start"
                            :style="{ left: `${Math.min(rangeStart, rangeEnd)}%` }"
                            aria-label="Range start"
                            @pointerdown="onHandleStart"
                        />
                        <button
                            type="button"
                            class="range-handle end"
                            :style="{ left: `${Math.max(rangeStart, rangeEnd)}%` }"
                            aria-label="Range end"
                            @pointerdown="onHandleEnd"
                        />
                        <i
                            class="range-mark first"
                            :style="{ left: `${Math.min(rangeStart, rangeEnd)}%` }"
                        />
                        <i
                            class="range-mark second"
                            :style="{ left: `${Math.max(rangeStart, rangeEnd)}%` }"
                        />
                    </div>
                </section>
            </main>

            <aside class="right-panel" @click.stop>
                <div class="right-glass">
                    <div class="right-tools">
                        <div class="period-menu">
                            <button
                                type="button"
                                class="period-trigger"
                                @click="periodOpen = !periodOpen"
                            >
                                {{ presetLabel }}
                                <ChevronDown />
                            </button>
                            <div v-if="periodOpen" class="period-dropdown">
                                <button type="button" :class="{ active: preset === 'today' }" @click="applyPeriod('today')">{{ t('Today') }}</button>
                                <button type="button" :class="{ active: preset === 'month' }" @click="applyPeriod('month')">{{ t('This month') }}</button>
                                <button type="button" :class="{ active: preset === 'year' }" @click="applyPeriod('year')">{{ t('This year') }}</button>
                                <button type="button" :class="{ active: preset === 'all' }" @click="applyPeriod('all')">{{ t('All time') }}</button>
                                <div class="period-custom">
                                    <DatePicker v-model="from" class="period-date" />
                                    <DatePicker v-model="to" class="period-date" />
                                    <button type="button" class="apply-custom" @click="applyPeriod('custom')">{{ t('Apply') }}</button>
                                </div>
                            </div>
                        </div>
                        <button type="button" @click="router.visit(isAdmin ? '/reports' : '/projects')">
                            <MoreHorizontal />
                        </button>
                    </div>

                    <div class="metric-stack">
                        <article
                            v-for="(metric, index) in metrics"
                            :key="metric.label"
                            class="metric-card"
                            :class="{ featured: index === 0 }"
                            role="button"
                            tabindex="0"
                            @click="router.visit(metric.href)"
                            @keydown.enter="router.visit(metric.href)"
                        >
                            <div class="metric-title">
                                <span>{{ metric.label }}</span>
                                <button type="button" @click.stop="router.visit(metric.href)">
                                    <ExternalLink />
                                </button>
                            </div>
                            <div class="trend" :class="{ down: metric.trend.negative }">
                                {{ metric.trend.text }}
                                <b>{{ metric.trend.negative ? '▼' : '▲' }}</b>
                            </div>
                            <div class="metric-value">
                                <MisMoneyStack
                                    v-if="metric.amounts !== undefined"
                                    class="metric-money"
                                    :amounts="metric.amounts"
                                    :fallback="metric.fallback"
                                />
                                <template v-else>{{ metric.value }}</template>
                            </div>
                            <div class="metric-hint">{{ metric.hint }}</div>
                            <svg viewBox="0 0 112 72" aria-hidden="true">
                                <path d="M13 62 A43 43 0 0 1 99 62" class="gauge-bg" />
                                <path
                                    d="M13 62 A43 43 0 0 1 99 62"
                                    class="gauge-value"
                                    :style="{ stroke: metric.color, strokeDasharray: arcDash(metric.percent) }"
                                />
                                <path d="M56 62 82 39 72 62Z" :fill="metric.color" opacity=".16" />
                            </svg>
                        </article>
                    </div>

                    <article class="cut-card">
                        <div class="cut-title">
                            <div>
                                <strong>{{ isAdmin ? t('Projects') : t('Your project shares') }}</strong>
                                <small>{{ periodLabel }} · {{ listRows.length }}</small>
                            </div>
                            <button type="button" @click="router.visit('/projects')">
                                <ChevronDown />
                            </button>
                        </div>
                        <div v-if="listRows.length" class="project-list">
                            <button
                                v-for="item in listRows"
                                :key="item.id"
                                type="button"
                                class="project-row"
                                @click="openProject(item.id)"
                            >
                                <span class="project-name">{{ item.name }}</span>
                                <span class="project-profit">{{ item.profit }}</span>
                                <i
                                    class="project-bar"
                                    :style="{ width: `${Math.min(100, item.progress)}%`, background: item.color }"
                                />
                                <em>{{ item.progress.toFixed(0) }}%</em>
                            </button>
                        </div>
                        <p v-else class="list-empty">
                            {{ isAdmin ? t('No projects yet.') : t('No project shares assigned yet.') }}
                        </p>
                    </article>
                </div>
            </aside>
        </div>

        <section v-if="isAdmin" class="dashboard-below">
            <div class="below-grid charts">
                <section class="structo-sales-chart span-3">
                    <div class="chart-header">
                        <div>
                            <span>{{ t('Sales vs expenses') }}</span>
                            <div class="chart-number">
                                {{ rangeTotals.share }}%
                                <small
                                    class="up"
                                    :class="{ down: salesTrend.negative }"
                                >
                                    {{ salesTrend.text }}
                                    <b>{{ salesTrend.negative ? '▼' : '▲' }}</b>
                                </small>
                            </div>
                            <p class="chart-sub">
                                {{ t('Sales') }}: {{ rangeTotals.salesLabel }}
                                · {{ t('Expenses') }}:
                                {{ rangeTotals.expensesLabel }}
                            </p>
                        </div>
                        <div class="chart-actions">
                            <button type="button" @click="router.visit('/sales')">
                                <ExternalLink />
                            </button>
                            <div class="legend top dual">
                                <span><i class="sales" /> {{ t('Sales') }}</span>
                                <span
                                    ><i class="expense" />
                                    {{ t('Expenses') }}</span
                                >
                            </div>
                        </div>
                    </div>

                    <div v-if="visibleBars.length" class="chart-plot">
                        <div class="y-axis" aria-hidden="true">
                            <span
                                v-for="tick in yTicks"
                                :key="`below-${tick.label}`"
                                >{{ tick.label }}</span
                            >
                        </div>
                        <div
                            class="bar-chart structo"
                            :style="{ '--bar-count': visibleBars.length }"
                        >
                            <div
                                v-for="(bar, index) in visibleBars"
                                :key="`below-${bar.fullLabel}-${index}`"
                                class="bar-column"
                                :class="{
                                    active: bar.active,
                                    hovering: hoverBarIndex === index,
                                }"
                                tabindex="0"
                                @mouseenter="hoverBarIndex = index"
                                @mouseleave="hoverBarIndex = null"
                                @focusin="hoverBarIndex = index"
                                @focusout="hoverBarIndex = null"
                                @click.stop="
                                    hoverBarIndex =
                                        hoverBarIndex === index ? null : index
                                "
                            >
                                <div
                                    v-if="hoverBarIndex === index"
                                    class="bar-tooltip"
                                >
                                    <strong>{{ bar.fullLabel }}</strong>
                                    <span
                                        >{{ t('Sales') }}:
                                        {{ bar.salesLabel }}</span
                                    >
                                    <span
                                        >{{ t('Expenses') }}:
                                        {{ bar.expensesLabel }}</span
                                    >
                                </div>
                                <div class="bar-pair">
                                    <i
                                        class="sales"
                                        :class="{ active: bar.active }"
                                        :style="{
                                            height: `${bar.salesHeight}%`,
                                        }"
                                    />
                                    <i
                                        class="expense"
                                        :class="{ active: bar.active }"
                                        :style="{
                                            height: `${bar.expenseHeight}%`,
                                        }"
                                    />
                                </div>
                                <em class="bar-amount">{{
                                    bar.salesCompact
                                }}</em>
                                <small>{{ bar.label }}</small>
                            </div>
                        </div>
                    </div>
                    <div v-else class="chart-empty">
                        {{ t('No chart data for this period.') }}
                    </div>

                    <div class="range-track" @pointerdown="onTrackJump">
                        <span
                            class="range-fill"
                            :style="rangeStyle"
                            @pointerdown="onFillDrag"
                        />
                        <button
                            type="button"
                            class="range-handle start"
                            :style="{
                                left: `${Math.min(rangeStart, rangeEnd)}%`,
                            }"
                            aria-label="Range start"
                            @pointerdown="onHandleStart"
                        />
                        <button
                            type="button"
                            class="range-handle end"
                            :style="{
                                left: `${Math.max(rangeStart, rangeEnd)}%`,
                            }"
                            aria-label="Range end"
                            @pointerdown="onHandleEnd"
                        />
                        <i
                            class="range-mark first"
                            :style="{
                                left: `${Math.min(rangeStart, rangeEnd)}%`,
                            }"
                        />
                        <i
                            class="range-mark second"
                            :style="{
                                left: `${Math.max(rangeStart, rangeEnd)}%`,
                            }"
                        />
                    </div>
                </section>
                <MisChartCard
                    class="span-2"
                    :title="t('Expenses by currency')"
                    :description="currencyChart.totalLabel"
                    type="pie"
                    :labels="currencyChart.labels"
                    :datasets="currencyChart.datasets"
                />
            </div>

            <div class="below-grid projects">
                <MisChartCard
                    class="span-2"
                    :title="t('Top projects')"
                    :description="t('Revenue and profit')"
                    type="bar"
                    :labels="projectsChart.labels"
                    :datasets="projectsChart.datasets"
                />
                <div class="glass-panel span-3">
                    <div class="panel-head">
                        <h2>{{ t('Projects') }}</h2>
                        <Button as-child variant="ghost" size="sm">
                            <Link href="/projects">
                                {{ t('View all') }}
                                <ArrowUpRight class="size-4" />
                            </Link>
                        </Button>
                    </div>
                    <TableFrame table-id="dashboard-v2-projects" :columns="tableColumns">
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
                                    @click="openProject(p.id)"
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
                                                    :style="{ width: `${Math.min(100, Number(p.progress_percent || 0))}%` }"
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

            <div class="below-grid alerts">
                <div class="glass-panel">
                    <div class="panel-head">
                        <h2>{{ t('Overdue installments') }}</h2>
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

                <div class="glass-panel">
                    <div class="panel-head">
                        <h2>{{ t('Unpaid purchases') }}</h2>
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

                <div class="glass-panel">
                    <div class="panel-head">
                        <h2>{{ t('Low stock') }}</h2>
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
        </section>

        <section v-else class="dashboard-below">
            <div class="glass-panel">
                <div class="panel-head">
                    <h2>{{ t('Your project shares') }}</h2>
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
        </section>
    </div>
</template>


<style scoped>
.dashboard-page {
    display: flex;
    flex-direction: column;
    gap: 16px;
    width: 100%;
    padding-bottom: 24px;
}

.dashboard-v2 {
    position: relative;
    display: grid;
    grid-template-columns: minmax(0, 1fr) minmax(260px, 300px);
    grid-template-rows: minmax(0, 1fr);
    gap: 8px;
    width: 100%;
    height: min(720px, calc(100dvh - var(--app-v2-topbar-height, 72px) - 48px));
    min-height: 640px;
    overflow: hidden;
    color: #1a1a1a;
    font-family:
        var(--font-sans), 'Plus Jakarta Sans', 'Helvetica Neue', sans-serif;
    border-radius: 28px;
    border: 1px solid rgba(255, 255, 255, 0.55);
    background:
        radial-gradient(
            ellipse 52% 46% at 10% 6%,
            rgba(255, 255, 255, 0.95) 0%,
            transparent 72%
        ),
        radial-gradient(
            ellipse 40% 36% at 94% 8%,
            rgba(232, 244, 252, 0.7) 0%,
            transparent 70%
        ),
        linear-gradient(145deg, #f4f5f3 0%, #eef0ee 52%, #eef2f4 100%);
    box-shadow:
        0 18px 44px rgba(55, 65, 70, 0.06),
        inset 0 1px rgba(255, 255, 255, 0.85);
}

.visual-area {
    position: relative;
    grid-column: 1;
    grid-row: 1;
    min-width: 0;
    min-height: 0;
    overflow: hidden;
}

.hero-copy {
    position: absolute;
    z-index: 6;
    top: 22px;
    inset-inline-start: 22px;
    max-width: min(42%, 22rem);
}

.hero-copy h1 {
    margin: 0;
    max-width: 9ch;
    color: #0d0d0d;
    font-size: clamp(36px, 3.4vw, 56px);
    font-weight: 650;
    line-height: 0.95;
    letter-spacing: -0.06em;
    overflow-wrap: anywhere;
}

.hero-copy p {
    margin: 12px 0 0;
    max-width: 28ch;
    color: #5c6568;
    font-size: 11px;
    font-weight: 500;
    line-height: 1.45;
}

.building-wrap {
    position: absolute;
    z-index: 2;
    top: 2%;
    inset-inline-end: -4%;
    width: 98%;
    height: 78%;
    overflow: hidden;
    pointer-events: none;
    -webkit-mask-image: radial-gradient(
        ellipse 68% 62% at 58% 42%,
        #000 40%,
        rgb(0 0 0 / 78%) 68%,
        transparent 100%
    );
    mask-image: radial-gradient(
        ellipse 68% 62% at 58% 42%,
        #000 40%,
        rgb(0 0 0 / 78%) 68%,
        transparent 100%
    );
}

.building {
    display: block;
    width: 100%;
    height: 100%;
    object-fit: contain;
    object-position: right center;
    opacity: 0.92;
    border: 0;
    outline: 0;
    box-shadow: none;
}

.project-float {
    position: absolute;
    z-index: 8;
    top: 148px;
    inset-inline-start: 44%;
    width: 228px;
    min-height: 118px;
    padding: 15px 16px;
    border: 1px solid rgb(255 255 255 / 55%);
    border-radius: 20px;
    background: linear-gradient(
        145deg,
        rgb(255 255 255 / 28%),
        rgb(120 118 112 / 28%)
    );
    color: white;
    box-shadow:
        0 20px 44px rgb(25 28 30 / 12%),
        inset 0 1px rgb(255 255 255 / 45%);
    backdrop-filter: blur(22px) saturate(1.4);
    -webkit-backdrop-filter: blur(22px) saturate(1.4);
}

.project-head,
.project-bottom {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
}

.project-head strong {
    display: block;
    max-width: 150px;
    overflow: hidden;
    font-size: 13px;
    font-weight: 500;
    line-height: 1.25;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.project-head small {
    display: block;
    margin-top: 3px;
    color: rgb(255 255 255 / 63%);
    font-size: 8px;
}

.project-head button {
    width: 28px;
    height: 28px;
    border: 0;
    border-radius: 50%;
    color: white;
    background: rgb(255 255 255 / 12%);
}

.project-head svg,
.chart-header svg,
.metric-title svg,
.cut-title svg {
    width: 12px;
}

.project-bottom {
    align-items: center;
    margin-top: 16px;
}

.project-bottom > b {
    font-size: 30px;
    font-weight: 400;
    letter-spacing: -1px;
}

.workers {
    display: flex;
    align-items: center;
}

.workers span,
.workers em {
    display: grid;
    place-items: center;
    width: 24px;
    height: 24px;
    margin-inline-start: -6px;
    border: 0;
    border-radius: 50%;
    font-style: normal;
    box-shadow: 0 0 0 2px rgb(255 255 255 / 45%);
}

.workers .w1 {
    background: linear-gradient(145deg, #f6d28a, #e8a84a);
}

.workers .w2 {
    background: linear-gradient(145deg, #f0b8a0, #d47a5a);
}

.workers .w3 {
    background: linear-gradient(145deg, #c8d6e4, #8aa0b8);
}

.workers em {
    background: rgb(255 255 255 / 28%);
    color: white;
    font-size: 8px;
}

.chart-panel {
    position: absolute;
    z-index: 7;
    right: 10px;
    bottom: 12px;
    left: 10px;
    height: 46%;
    padding: 16px 18px 14px;
    border: 1px solid rgb(255 255 255 / 62%);
    border-radius: 26px;
    background: linear-gradient(
        180deg,
        rgb(255 255 255 / 48%),
        rgb(255 255 255 / 28%)
    );
    box-shadow:
        0 16px 40px rgb(50 55 60 / 6%),
        inset 0 1px rgb(255 255 255 / 85%);
    backdrop-filter: blur(28px) saturate(1.3);
    -webkit-backdrop-filter: blur(28px) saturate(1.3);
}

.chart-header span {
    color: #2a2d2e;
    font-size: 13px;
    font-weight: 550;
}

.chart-number {
    margin-top: 6px;
    color: #111;
    font-size: 36px;
    font-weight: 600;
    letter-spacing: -1.4px;
}

.chart-number small,
.chart-number .up {
    margin-inline-start: 6px;
    color: #1f9d57;
    font-size: 10px;
    font-weight: 600;
    letter-spacing: 0;
}

.chart-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
}

.chart-header button {
    width: 28px;
    height: 28px;
    color: #595e60;
}

.chart-actions {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 10px;
}

.bar-chart {
    display: flex;
    align-items: end;
    gap: clamp(4px, 0.8vw, 12px);
    height: 108px;
    margin-top: 14px;
}

.bar-chart.structo {
    flex: 1;
    gap: clamp(6px, 1vw, 14px);
    height: 100%;
    min-height: 120px;
    padding: 22px 4px 18px;
}

.chart-plot {
    display: flex;
    gap: 8px;
    align-items: stretch;
    margin-top: 10px;
    min-height: 140px;
}

.y-axis {
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    align-items: flex-end;
    width: 42px;
    padding: 22px 0 18px;
    color: #8b9192;
    font-size: 9px;
    font-weight: 550;
    font-variant-numeric: tabular-nums;
}

.bar-column {
    position: relative;
    display: flex;
    flex: 1;
    justify-content: center;
    align-items: end;
    height: 100%;
    min-width: 18px;
}

.bar-column i {
    display: block;
    width: min(100%, 7px);
    border-radius: 8px 8px 0 0;
    background: rgb(205 210 209 / 42%);
}

.bar-column i.active {
    background: linear-gradient(
        180deg,
        #ff9a4f 0%,
        rgb(255 138 58 / 72%) 48%,
        rgb(255 138 58 / 8%) 100%
    );
    box-shadow: 0 0 16px rgb(255 138 58 / 28%);
}

.bar-column small {
    position: absolute;
    bottom: -16px;
    color: #6d7375;
    font-size: 8px;
    white-space: nowrap;
}

.bar-amount {
    position: absolute;
    top: -2px;
    left: 50%;
    z-index: 2;
    color: #667085;
    font-size: 8px;
    font-style: normal;
    font-weight: 650;
    font-variant-numeric: tabular-nums;
    white-space: nowrap;
    transform: translate(-50%, -100%);
}

.bar-column.active .bar-amount,
.bar-column.hovering .bar-amount {
    color: #1a1a1a;
}

.bar-tooltip {
    position: absolute;
    bottom: calc(100% + 8px);
    left: 50%;
    z-index: 8;
    display: flex;
    flex-direction: column;
    gap: 2px;
    min-width: 132px;
    padding: 8px 10px;
    border: 1px solid rgb(255 255 255 / 70%);
    border-radius: 12px;
    background: rgb(255 255 255 / 94%);
    box-shadow: 0 12px 28px rgb(40 50 55 / 14%);
    color: #1a1a1a;
    font-size: 10px;
    white-space: nowrap;
    transform: translateX(-50%);
    pointer-events: none;
}

.bar-tooltip strong {
    font-size: 11px;
    font-weight: 650;
}

.bar-tooltip span {
    color: #667085;
}

.range-track {
    position: relative;
    height: 36px;
    margin-top: 22px;
    overflow: visible;
    border-radius: 22px;
    background: rgb(214 218 216 / 55%);
    touch-action: none;
    cursor: pointer;
    user-select: none;
}

.range-fill {
    position: absolute;
    top: 0;
    bottom: 0;
    border-radius: inherit;
    background: linear-gradient(90deg, #ff9a4a, #ff7d2e);
    box-shadow: 0 0 22px rgb(255 125 46 / 45%);
    cursor: grab;
    touch-action: none;
}

.range-fill:active {
    cursor: grabbing;
}

.range-handle {
    position: absolute;
    top: 50%;
    z-index: 3;
    width: 18px;
    height: 18px;
    padding: 0;
    border: 2px solid #fff;
    border-radius: 50%;
    background: #ff8a3a;
    box-shadow: 0 4px 12px rgb(255 125 46 / 45%);
    transform: translate(-50%, -50%);
    cursor: ew-resize;
    touch-action: none;
}

.range-mark {
    position: absolute;
    top: -16px;
    bottom: -1px;
    z-index: 1;
    width: 1px;
    pointer-events: none;
    background: #d74b4c;
}

.legend {
    display: flex;
    justify-content: flex-end;
    align-items: center;
    gap: 8px;
    font-size: 10px;
    color: #667085;
}

.legend.top {
    margin: 0;
}

.legend.dual {
    flex-direction: column;
    align-items: flex-end;
    gap: 4px;
}

.legend.dual span {
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

.legend i,
.legend .sales,
.legend .expense {
    display: inline-block;
    width: 34px;
    height: 6px;
    border-radius: 6px;
    background: #ff8a3a;
}

.legend .expense {
    background: rgb(160 168 170 / 85%);
}

.bar-pair {
    display: flex;
    align-items: flex-end;
    gap: 2px;
    height: 100%;
}

.bar-column i.sales,
.bar-column i.expense {
    display: block;
    width: 5px;
    min-width: 4px;
    border-radius: 8px 8px 0 0;
    background: rgb(205 210 209 / 42%);
}

.bar-column i.sales.active {
    background: linear-gradient(
        180deg,
        #ff9a4f 0%,
        rgb(255 138 58 / 72%) 48%,
        rgb(255 138 58 / 8%) 100%
    );
    box-shadow: 0 0 16px rgb(255 138 58 / 28%);
}

.bar-column i.expense.active {
    background: linear-gradient(
        180deg,
        rgb(120 130 135 / 75%) 0%,
        rgb(160 168 170 / 45%) 55%,
        rgb(180 186 190 / 10%) 100%
    );
}

.structo-sales-chart {
    display: flex;
    flex-direction: column;
    min-height: 320px;
    padding: 22px 24px 18px;
    border: 1px solid rgb(255 255 255 / 62%);
    border-radius: 26px;
    background: linear-gradient(
        180deg,
        rgb(255 255 255 / 52%),
        rgb(255 255 255 / 28%)
    );
    box-shadow:
        0 16px 40px rgb(50 55 60 / 6%),
        inset 0 1px rgb(255 255 255 / 85%);
    backdrop-filter: blur(28px) saturate(1.3);
    -webkit-backdrop-filter: blur(28px) saturate(1.3);
}

.structo-sales-chart .chart-plot {
    flex: 1;
    min-height: 180px;
    margin-top: 14px;
}

.structo-sales-chart .bar-chart.structo {
    flex: 1;
    height: auto;
    min-height: 160px;
    margin-top: 0;
}

.structo-sales-chart .chart-number {
    font-size: clamp(28px, 3vw, 42px);
}

.structo-sales-chart .range-track {
    margin-top: 18px;
    height: 40px;
}

.right-panel {
    z-index: 10;
    grid-column: 2;
    grid-row: 1;
    display: flex;
    flex-direction: column;
    width: 100%;
    min-width: 0;
    min-height: 0;
    padding-block: 10px;
    padding-inline: 0 10px;
    background: transparent;
}

.right-glass {
    display: flex;
    flex: 1;
    flex-direction: column;
    min-height: 0;
    padding: 12px 11px 13px;
    border: 1px solid rgb(255 255 255 / 58%);
    border-radius: 28px;
    background: linear-gradient(
        165deg,
        rgb(255 255 255 / 42%),
        rgb(255 255 255 / 18%)
    );
    box-shadow:
        0 20px 50px rgb(40 50 55 / 6%),
        inset 0 1px rgb(255 255 255 / 85%);
    backdrop-filter: blur(32px) saturate(1.35);
    -webkit-backdrop-filter: blur(32px) saturate(1.35);
}

.right-tools {
    display: flex;
    justify-content: flex-end;
    align-items: center;
    gap: 6px;
    height: 36px;
    margin-bottom: 10px;
}

.right-tools button {
    display: flex;
    align-items: center;
    gap: 7px;
    min-width: 34px;
    max-width: 100%;
    height: 32px;
    padding: 0 11px;
    border: 0;
    border-radius: 18px;
    background: rgb(255 255 255 / 48%);
    box-shadow: 0 8px 18px rgb(40 45 50 / 4%);
    backdrop-filter: blur(14px);
    font-size: 11px;
    font-weight: 500;
}

.right-tools svg {
    width: 12px;
}

.metric-stack {
    display: flex;
    flex-direction: column;
    gap: 7px;
}

.metric-card {
    position: relative;
    min-height: 124px;
    height: auto;
    padding: 11px 11px 12px;
    overflow: hidden;
    border: 1px solid rgb(255 255 255 / 55%);
    border-radius: 18px;
    background: rgb(255 255 255 / 36%);
    box-shadow:
        0 12px 28px rgb(55 65 70 / 5%),
        inset 0 1px rgb(255 255 255 / 85%);
    backdrop-filter: blur(20px) saturate(1.25);
    -webkit-backdrop-filter: blur(20px) saturate(1.25);
    cursor: pointer;
}

.metric-card + .metric-card {
    margin-top: 0;
}

.metric-card.featured {
    border: 1px solid rgb(255 180 120 / 55%);
    box-shadow:
        0 14px 32px rgb(255 138 58 / 14%),
        inset 0 1px rgb(255 255 255 / 90%),
        inset 2px 0 0 rgb(255 138 58 / 55%);
}

.metric-title {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.metric-title span {
    display: flex;
    align-items: center;
    gap: 6px;
    min-width: 0;
    color: #2d3133;
    font-size: 12px;
    font-weight: 550;
    line-height: 1.3;
}

.metric-title span svg {
    box-sizing: content-box;
    width: 11px;
    padding: 5px;
    border-radius: 50%;
    background: rgb(236 239 238 / 55%);
}

.metric-title button {
    width: 24px;
    height: 24px;
    border-radius: 50%;
    background: rgb(255 255 255 / 45%);
}

.trend {
    margin-top: 8px;
    color: #1f9d57;
    font-size: 9px;
    font-weight: 600;
}

.trend.down {
    color: #df315b;
}

.trend b {
    font-size: 7px;
}

.metric-value {
    position: relative;
    z-index: 2;
    max-width: calc(100% - 72px);
    margin-top: 4px;
    overflow: visible;
    color: #111;
    font-size: 15px;
    font-weight: 600;
    letter-spacing: -0.4px;
    line-height: 1.2;
    white-space: normal;
    overflow-wrap: anywhere;
}

.metric-money {
    gap: 4px !important;
}

.metric-money :deep(.flex) {
    gap: 6px;
}

.metric-money :deep(.text-base),
.metric-money :deep(.sm\:text-lg) {
    font-size: 13px;
    font-weight: 650;
    color: #111;
}

.metric-money :deep(.min-w-11) {
    min-width: 2.1rem;
    padding: 1px 5px;
    font-size: 9px;
}

.metric-hint {
    position: relative;
    z-index: 2;
    max-width: calc(100% - 72px);
    margin-top: 3px;
    display: -webkit-box;
    overflow: hidden;
    color: #667074;
    font-size: 10px;
    line-height: 1.35;
    white-space: normal;
    -webkit-box-orient: vertical;
    -webkit-line-clamp: 2;
}

.metric-card > svg {
    position: absolute;
    inset-inline-end: 2px;
    bottom: 0;
    z-index: 1;
    width: 72px;
    height: 52px;
    pointer-events: none;
    opacity: 0.9;
}

.gauge-bg,
.gauge-value {
    fill: none;
    stroke-width: 9px;
    stroke-linecap: round;
}

.gauge-bg {
    stroke: rgb(214 218 217 / 45%);
}

.cut-card {
    flex: 1;
    min-height: 112px;
    margin-top: 8px;
    padding: 11px;
    border: 1px solid rgb(255 255 255 / 55%);
    border-radius: 18px;
    background: rgb(255 255 255 / 36%);
    box-shadow:
        0 12px 28px rgb(55 65 70 / 5%),
        inset 0 1px rgb(255 255 255 / 85%);
    backdrop-filter: blur(20px) saturate(1.25);
    -webkit-backdrop-filter: blur(20px) saturate(1.25);
}

.cut-title {
    display: flex;
    justify-content: space-between;
}

.cut-title strong {
    display: block;
    max-width: 12rem;
    font-size: 12px;
    font-weight: 500;
    line-height: 1.3;
}

.cut-title small {
    display: block;
    margin-top: 2px;
    color: #868b8c;
    font-size: 7px;
}

.cut-title button {
    width: 26px;
    height: 26px;
    border-radius: 50%;
    background: rgb(255 255 255 / 32%);
}

.cut-grid {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: 16px 8px;
    align-content: space-evenly;
    height: calc(100% - 36px);
    min-height: 84px;
    margin-top: 8px;
}

.cut-grid div {
    min-width: 0;
}

.cut-grid span {
    display: block;
    overflow: hidden;
    font-size: 7px;
    text-align: center;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.cut-grid i {
    display: block;
    height: 2px;
    margin-top: 8px;
}


.hero-copy p {
    max-width: 34ch;
}

.project-float {
    cursor: pointer;
}

.project-meta {
    display: flex;
    justify-content: space-between;
    gap: 8px;
    margin-top: 10px;
    color: rgb(255 255 255 / 72%);
    font-size: 8px;
}

.chart-number {
    font-size: clamp(18px, 2.2vw, 28px);
}

.chart-number .down {
    color: #df315b;
}

.chart-sub {
    margin: 4px 0 0;
    max-width: 420px;
    overflow: hidden;
    color: #8b9192;
    font-size: 9px;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.chart-empty {
    display: grid;
    place-items: center;
    height: 108px;
    margin-top: 14px;
    color: #8b9192;
    font-size: 12px;
}

.alert-strip {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-top: 22px;
}

.alert-chip {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    gap: 1px;
    min-width: 88px;
    padding: 8px 10px;
    border: 1px solid rgb(255 255 255 / 55%);
    border-radius: 14px;
    background: rgb(255 255 255 / 45%);
    cursor: pointer;
    text-align: left;
}

.alert-chip strong {
    font-size: 13px;
    font-weight: 650;
}

.alert-chip span {
    color: #667085;
    font-size: 8px;
}

.alert-chip small {
    color: #98a2b3;
    font-size: 7px;
}

.alert-chip.danger strong { color: #df315b; }
.alert-chip.warn strong { color: #d97706; }
.alert-chip.ok strong { color: #1f9d57; }

.legend em {
    display: inline-block;
    width: 28px;
    height: 6px;
    margin: 0 4px 0 12px;
    border-radius: 6px;
    background: rgb(180 186 190 / 75%);
}

.period-menu {
    position: relative;
}

.period-trigger {
    display: flex;
    align-items: center;
    gap: 7px;
    min-width: 34px;
    max-width: 11.5rem;
    height: 32px;
    padding: 0 11px;
    overflow: hidden;
    border: 0;
    border-radius: 18px;
    background: rgb(255 255 255 / 48%);
    box-shadow: 0 8px 18px rgb(40 45 50 / 4%);
    backdrop-filter: blur(14px);
    font-size: 11px;
    font-weight: 500;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.period-dropdown {
    position: absolute;
    z-index: 40;
    top: calc(100% + 6px);
    right: 0;
    display: flex;
    flex-direction: column;
    gap: 4px;
    width: 210px;
    padding: 8px;
    border: 1px solid rgb(255 255 255 / 65%);
    border-radius: 16px;
    background: rgb(255 255 255 / 92%);
    box-shadow: 0 18px 40px rgb(40 50 55 / 12%);
    backdrop-filter: blur(20px);
}

.period-dropdown > button {
    justify-content: flex-start;
    width: 100%;
    min-width: 0;
    height: 30px;
    border-radius: 10px;
    background: transparent;
    box-shadow: none;
}

.period-dropdown > button.active {
    background: #1a1a1a;
    color: white;
}

.period-custom {
    display: grid;
    gap: 6px;
    margin-top: 4px;
    padding-top: 8px;
    border-top: 1px solid rgb(0 0 0 / 6%);
}

.period-date {
    width: 100%;
}

.apply-custom {
    justify-content: center !important;
    width: 100%;
    background: #ff8a3a !important;
    color: white;
}

.metric-card {
    cursor: pointer;
}

.project-list {
    display: flex;
    flex-direction: column;
    gap: 6px;
    height: calc(100% - 36px);
    min-height: 84px;
    margin-top: 8px;
    overflow: auto;
}

.project-row {
    position: relative;
    display: grid;
    grid-template-columns: 1fr auto;
    gap: 2px 8px;
    width: 100%;
    padding: 7px 8px 10px;
    border: 0;
    border-radius: 10px;
    background: rgb(255 255 255 / 35%);
    text-align: left;
    cursor: pointer;
}

.project-name {
    overflow: hidden;
    color: #1a1a1a;
    font-size: 10px;
    font-weight: 550;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.project-profit {
    color: #667085;
    font-size: 8px;
    white-space: nowrap;
}

.project-bar {
    grid-column: 1 / -1;
    display: block;
    height: 3px;
    margin-top: 2px;
    border-radius: 4px;
}

.project-row em {
    position: absolute;
    inset-inline-end: 8px;
    bottom: 2px;
    color: #98a2b3;
    font-size: 7px;
    font-style: normal;
}

.list-empty {
    margin-top: 18px;
    color: #8b9192;
    font-size: 11px;
    text-align: center;
}

.dashboard-below {
    display: flex;
    flex-direction: column;
    gap: 14px;
    width: 100%;
}

.below-grid {
    display: grid;
    gap: 14px;
}

.below-grid.charts,
.below-grid.projects {
    grid-template-columns: repeat(5, minmax(0, 1fr));
}

.below-grid.alerts {
    grid-template-columns: repeat(3, minmax(0, 1fr));
}

.span-2 {
    grid-column: span 2;
}

.span-3 {
    grid-column: span 3;
}

.glass-panel {
    overflow: hidden;
    border: 1px solid rgb(255 255 255 / 55%);
    border-radius: 22px;
    background: rgb(255 255 255 / 42%);
    box-shadow:
        0 14px 36px rgb(55 65 70 / 6%),
        inset 0 1px rgb(255 255 255 / 85%);
    backdrop-filter: blur(18px) saturate(1.2);
}

.panel-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 14px 18px;
    border-bottom: 1px solid rgb(255 255 255 / 45%);
}

.panel-head h2 {
    margin: 0;
    color: #1a1a1a;
    font-size: 14px;
    font-weight: 600;
}

@media (max-width: 1100px) {
    .dashboard-v2 {
        grid-template-columns: minmax(0, 1fr) 250px;
        min-height: 640px;
    }

    .project-float {
        inset-inline-start: 40%;
    }

    .below-grid.charts,
    .below-grid.projects {
        grid-template-columns: 1fr;
    }

    .span-2,
    .span-3 {
        grid-column: auto;
    }
}

@media (max-width: 820px) {
    .dashboard-page {
        gap: 12px;
        padding-bottom: 16px;
    }

    .dashboard-v2 {
        display: flex;
        flex-direction: column;
        height: auto;
        min-height: 0;
        max-height: none;
        overflow: visible;
        border-radius: 20px;
    }

    .visual-area {
        display: flex;
        flex-direction: column;
        height: auto;
        min-height: 0;
        overflow: visible;
    }

    .hero-copy {
        position: relative;
        top: auto;
        inset-inline-start: auto;
        z-index: 6;
        max-width: none;
        padding: 18px 16px 0;
    }

    .hero-copy h1 {
        max-width: none;
        font-size: clamp(28px, 8vw, 40px);
        letter-spacing: -0.04em;
        line-height: 1.05;
    }

    .hero-copy p {
        max-width: 42ch;
        font-size: 12px;
    }

    .building-wrap {
        position: relative;
        top: auto;
        inset-inline-end: auto;
        width: 100%;
        height: 220px;
        margin: 4px 0 8px;
        -webkit-mask-image: linear-gradient(
            90deg,
            transparent 0%,
            #000 18%,
            #000 82%,
            transparent 100%
        );
        mask-image: linear-gradient(
            90deg,
            transparent 0%,
            #000 18%,
            #000 82%,
            transparent 100%
        );
    }

    .building {
        object-position: center bottom;
    }

    .project-float {
        position: relative;
        top: auto;
        inset-inline: auto;
        z-index: 8;
        width: calc(100% - 24px);
        max-width: 320px;
        margin: 0 12px 12px;
    }

    .chart-panel {
        position: relative;
        right: auto;
        bottom: auto;
        left: auto;
        height: auto;
        margin: 0 10px 12px;
        padding: 14px 14px 12px;
        border-radius: 20px;
    }

    .chart-header {
        gap: 10px;
        flex-wrap: wrap;
    }

    .chart-number {
        font-size: 24px;
        letter-spacing: -0.8px;
    }

    .chart-plot {
        gap: 6px;
        min-height: 150px;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }

    .y-axis {
        width: 34px;
        flex-shrink: 0;
        font-size: 8px;
        padding: 18px 0 16px;
    }

    .bar-chart.structo {
        gap: 8px;
        min-width: max(100%, calc(var(--bar-count, 12) * 28px));
        min-height: 130px;
        padding: 18px 2px 20px;
    }

    .bar-amount {
        display: none;
    }

    .bar-column.active .bar-amount,
    .bar-column.hovering .bar-amount {
        display: block;
        font-size: 7px;
    }

    .bar-tooltip {
        min-width: 118px;
        font-size: 9px;
    }

    .range-track {
        height: 40px;
        margin-top: 18px;
    }

    .range-handle {
        width: 22px;
        height: 22px;
    }

    .right-panel {
        padding: 0 10px 12px;
    }

    .right-glass {
        border-radius: 20px;
        padding: 12px 10px 12px;
    }

    .metric-stack {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 10px;
    }

    .metric-card {
        min-height: 112px;
    }

    .metric-card.featured {
        grid-column: 1 / -1;
    }

    .metric-value {
        font-size: 13px;
        max-width: calc(100% - 64px);
    }

    .metric-hint {
        max-width: calc(100% - 64px);
    }

    .cut-card {
        margin-top: 10px;
    }

    .project-list {
        height: auto;
        max-height: 240px;
    }

    .structo-sales-chart {
        min-height: 0;
        padding: 16px 14px 14px;
        border-radius: 20px;
    }

    .structo-sales-chart .chart-plot {
        min-height: 160px;
    }

    .below-grid.alerts {
        grid-template-columns: 1fr;
    }

    .glass-panel {
        border-radius: 18px;
    }

    .panel-head {
        padding: 12px 14px;
    }

    .alert-strip {
        margin-top: 14px;
    }
}

@media (max-width: 480px) {
    .dashboard-v2 {
        border-radius: 16px;
    }

    .hero-copy {
        padding: 14px 12px 0;
    }

    .hero-copy h1 {
        font-size: 26px;
        letter-spacing: -0.03em;
    }

    .building-wrap {
        height: 180px;
    }

    .project-float {
        width: calc(100% - 20px);
        margin: 0 10px 10px;
        padding: 12px 12px;
        border-radius: 16px;
    }

    .project-bottom > b {
        font-size: 24px;
    }

    .chart-panel {
        margin: 0 8px 10px;
        padding: 12px 10px 10px;
        border-radius: 16px;
    }

    .legend.dual {
        flex-direction: row;
        flex-wrap: wrap;
        gap: 8px;
    }

    .legend i,
    .legend .sales,
    .legend .expense {
        width: 18px;
    }

    .metric-stack {
        grid-template-columns: 1fr;
    }

    .metric-card.featured {
        grid-column: auto;
    }

    .right-panel {
        padding: 0 8px 10px;
    }

    .y-axis {
        width: 30px;
    }

    .chart-sub {
        white-space: normal;
    }
}

:global(html[dir='rtl']) .hero-copy h1 {
    max-width: 14ch;
    letter-spacing: 0;
    line-height: 1.15;
    font-size: clamp(28px, 3vw, 44px);
}

:global(html[dir='rtl']) .hero-copy p {
    max-width: 36ch;
}

:global(html[dir='rtl']) .building {
    object-position: left center;
}

:global(html[dir='rtl']) .building-wrap {
    -webkit-mask-image: radial-gradient(
        ellipse 68% 62% at 42% 42%,
        #000 40%,
        rgb(0 0 0 / 78%) 68%,
        transparent 100%
    );
    mask-image: radial-gradient(
        ellipse 68% 62% at 42% 42%,
        #000 40%,
        rgb(0 0 0 / 78%) 68%,
        transparent 100%
    );
}

:global(html[dir='rtl']) .project-row {
    text-align: right;
}

:global(html[dir='rtl']) .alert-chip {
    align-items: flex-end;
    text-align: right;
}

:global(html[dir='rtl']) .legend.dual,
:global(html[dir='rtl']) .chart-actions {
    align-items: flex-start;
}

:global(html.dark) .dashboard-page {
    color: #e8eef7;
}

:global(html.dark) .dashboard-v2 {
    color: #e8eef7;
    border-color: rgb(255 255 255 / 10%);
    background:
        radial-gradient(
            ellipse 52% 46% at 10% 6%,
            rgb(36 48 68 / 55%) 0%,
            transparent 72%
        ),
        radial-gradient(
            ellipse 40% 36% at 94% 8%,
            rgb(45 212 191 / 8%) 0%,
            transparent 70%
        ),
        linear-gradient(145deg, #101826 0%, #0c1320 52%, #0a1018 100%);
    box-shadow:
        0 18px 44px rgb(0 0 0 / 28%),
        inset 0 1px rgb(255 255 255 / 6%);
}

:global(html.dark) .hero-copy h1 {
    color: #f4f7fb;
}

:global(html.dark) .hero-copy p {
    color: #9aa8bc;
}

:global(html.dark) .building {
    opacity: 0.78;
    filter: saturate(0.92) brightness(0.88);
}

:global(html.dark) .project-float {
    border-color: rgb(255 255 255 / 14%);
    background: linear-gradient(
        145deg,
        rgb(18 26 39 / 62%),
        rgb(32 40 52 / 48%)
    );
}

:global(html.dark) .chart-panel,
:global(html.dark) .structo-sales-chart,
:global(html.dark) .right-glass,
:global(html.dark) .metric-card,
:global(html.dark) .cut-card,
:global(html.dark) .glass-panel {
    border-color: rgb(255 255 255 / 12%);
    background: linear-gradient(
        180deg,
        rgb(18 26 39 / 72%),
        rgb(16 24 36 / 52%)
    );
    box-shadow:
        0 16px 40px rgb(0 0 0 / 22%),
        inset 0 1px rgb(255 255 255 / 8%);
}

:global(html.dark) .metric-card.featured {
    border-color: rgb(255 180 120 / 35%);
    box-shadow:
        0 14px 32px rgb(255 138 58 / 12%),
        inset 0 1px rgb(255 255 255 / 8%),
        inset 2px 0 0 rgb(255 138 58 / 55%);
}

:global(html.dark) .chart-header span,
:global(html.dark) .metric-title span,
:global(html.dark) .cut-title strong,
:global(html.dark) .panel-head h2,
:global(html.dark) .project-name {
    color: #e8eef7;
}

:global(html.dark) .chart-number,
:global(html.dark) .metric-value,
:global(html.dark) .metric-money :deep(.text-base),
:global(html.dark) .metric-money :deep(.sm\:text-lg) {
    color: #f4f7fb;
}

:global(html.dark) .cut-title small,
:global(html.dark) .metric-hint,
:global(html.dark) .chart-sub,
:global(html.dark) .chart-empty,
:global(html.dark) .list-empty,
:global(html.dark) .legend,
:global(html.dark) .y-axis,
:global(html.dark) .bar-column small,
:global(html.dark) .bar-amount,
:global(html.dark) .project-profit {
    color: #9aa8bc;
}

:global(html.dark) .bar-column.active .bar-amount,
:global(html.dark) .bar-column.hovering .bar-amount {
    color: #f4f7fb;
}

:global(html.dark) .range-track {
    background: rgb(255 255 255 / 10%);
}

:global(html.dark) .right-tools button,
:global(html.dark) .period-trigger,
:global(html.dark) .metric-title button,
:global(html.dark) .cut-title button,
:global(html.dark) .chart-header button {
    color: #c5d0de;
    background: rgb(255 255 255 / 8%);
}

:global(html.dark) .period-dropdown {
    border-color: rgb(255 255 255 / 12%);
    background: rgb(16 24 36 / 96%);
    color: #e8eef7;
}

:global(html.dark) .period-dropdown > button.active {
    background: rgb(255 255 255 / 12%);
    color: #fff;
}

:global(html.dark) .period-custom {
    border-top-color: rgb(255 255 255 / 10%);
}

:global(html.dark) .project-row {
    background: rgb(255 255 255 / 6%);
}

:global(html.dark) .bar-tooltip {
    border-color: rgb(255 255 255 / 12%);
    background: rgb(16 24 36 / 96%);
    color: #e8eef7;
}

:global(html.dark) .bar-tooltip span {
    color: #9aa8bc;
}

:global(html.dark) .gauge-bg {
    stroke: rgb(255 255 255 / 12%);
}

:global(html.dark) .dashboard-below {
    color: #e8eef7;
}

:global(html.dark) .dashboard-below :deep(.text-muted-foreground) {
    color: #9aa8bc;
}
</style>
