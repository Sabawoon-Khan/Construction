<script setup lang="ts">
import { computed } from 'vue';
import {
    MisChartCard,
    MisDetailGrid,
} from '@/components/mis';
import StatusBadge from '@/components/StatusBadge.vue';
import { V2Panel } from '@/components/v2';
import { useCalendar } from '@/composables/useCalendar';
import { useCurrency } from '@/composables/useCurrency';
import { useTranslations } from '@/composables/useTranslations';
import { translateStatus } from '@/lib/translateStatus';
import { formatNumber, money, moneyByCurrency } from '@/lib/utils';

const props = defineProps<{
    item: Record<string, any>;
    stats: Record<string, any>;
    shareholders?: {
        id: number;
        name: string;
        ownership_percentage: number;
    }[];
}>();

const { t } = useTranslations();
const { baseCurrency } = useCurrency();
const calendar = useCalendar();
const formatDate = (value: string | null | undefined) =>
    calendar.format(value) || '—';

const progress = computed(() =>
    Math.max(0, Math.min(100, Number(props.item.progress_percent || 0))),
);

const charts = computed(() => props.stats.charts ?? {});

const salesBase = computed(
    () =>
        Number(props.stats.sales_total_base ?? props.stats.sales_total ?? 0) ||
        0,
);
const expensesBase = computed(
    () =>
        Number(
            props.stats.expenses_total_base ?? props.stats.expenses_total ?? 0,
        ) || 0,
);
const incomeBase = computed(
    () =>
        Number(
            props.stats.incomes_total_base ?? props.stats.incomes_total ?? 0,
        ) || 0,
);
const estimatedBudget = computed(
    () => Number(props.item.estimated_budget ?? 0) || 0,
);
const budgetUsedPercent = computed(() => {
    if (props.stats.budget_used_percent != null) {
        return Number(props.stats.budget_used_percent);
    }
    if (estimatedBudget.value <= 0) {
        return null;
    }
    return Math.round(
        Math.min(999, (expensesBase.value / estimatedBudget.value) * 100) * 10,
    ) / 10;
});
const budgetBarWidth = computed(() =>
    Math.max(0, Math.min(100, Number(budgetUsedPercent.value ?? 0))),
);
const marginPercent = computed(() => {
    if (props.stats.margin_percent != null) {
        return Number(props.stats.margin_percent);
    }
    const revenue = salesBase.value + incomeBase.value;
    if (revenue <= 0) {
        return null;
    }
    return Math.round(
        ((Number(props.stats.net_profit ?? 0) || 0) / revenue) * 1000,
    ) / 10;
});

const unitsAvailable = computed(
    () => Number(props.stats.units_available ?? 0) || 0,
);
const unitsReserved = computed(
    () => Number(props.stats.units_reserved ?? 0) || 0,
);
const unitsSold = computed(() => Number(props.stats.units_sold ?? 0) || 0);
const unitsTotal = computed(() => {
    const mix = unitsAvailable.value + unitsReserved.value + unitsSold.value;
    return Math.max(mix, Number(props.stats.units_count ?? 0) || 0);
});
const unitsSoldShare = computed(() =>
    unitsTotal.value > 0
        ? Math.round((unitsSold.value / unitsTotal.value) * 100)
        : 0,
);

const outstanding = computed(() => {
    const suppliers =
        Number(
            props.stats.purchases_remaining_base ??
                props.stats.purchases_remaining ??
                0,
        ) || 0;
    const loans =
        Number(
            props.stats.loans_remaining_base ??
                props.stats.loans_remaining ??
                0,
        ) || 0;
    return {
        suppliers,
        loans,
        total: suppliers + loans,
    };
});

const shortMonthLabels = (rawLabels: string[]) => {
    const years = new Set(
        rawLabels
            .map((label) => String(label).match(/\d{4}/)?.[0])
            .filter(Boolean),
    );

    return rawLabels.map((label) => {
        const text = String(label);
        if (/^\d{4}$/.test(text)) {
            return text;
        }
        const month = text.slice(0, 3);
        const year = text.match(/\d{4}/)?.[0];
        if (!year) {
            return text;
        }
        return years.size <= 1 ? month : `${month} '${year.slice(2)}`;
    });
};

const cashFlowChart = computed(() => {
    const monthly = charts.value.monthly ?? {};
    const rawLabels = (monthly.labels as string[]) ?? [];
    const sales = ((monthly.sales as number[]) ?? []).map(Number);
    const expenses = ((monthly.expenses as number[]) ?? []).map(Number);
    const income = ((monthly.income as number[]) ?? []).map(Number);
    const hasIncome = income.some((n) => n !== 0);

    const datasets = [
        {
            label: t('Sales'),
            data: sales,
            color: 'var(--chart-1)',
        },
        {
            label: t('Expenses'),
            data: expenses,
            color: 'var(--chart-2)',
        },
    ];

    if (hasIncome) {
        datasets.push({
            label: t('Income'),
            data: income,
            color: 'var(--chart-3)',
        });
    }

    return {
        labels: shortMonthLabels(rawLabels),
        datasets,
    };
});

const moneyMixChart = computed(() => {
    const sales = salesBase.value;
    const expenses = expensesBase.value;
    const income = incomeBase.value;
    if (sales === 0 && expenses === 0 && income === 0) {
        return null;
    }

    const labels = [t('Sales'), t('Expenses')];
    const data = [sales, expenses];
    if (income > 0) {
        labels.push(t('Income'));
        data.push(income);
    }

    return {
        labels,
        datasets: [
            {
                label: t('Amount'),
                data,
                color: 'var(--chart-1)',
            },
        ],
    };
});

const categoryChart = computed(() => {
    const byCategory = charts.value.expenses_by_category ?? {};
    const labels = (byCategory.labels as string[]) ?? [];
    const amounts = ((byCategory.amounts as number[]) ?? []).map(Number);
    if (!labels.length || !amounts.some((n) => n !== 0)) {
        return null;
    }

    const rows = labels
        .map((label, index) => ({ label, amount: amounts[index] ?? 0 }))
        .filter((row) => row.amount > 0)
        .slice(0, 5);

    return {
        labels: rows.map((row) => row.label),
        datasets: [
            {
                label: t('Expenses'),
                data: rows.map((row) => row.amount),
                color: 'var(--chart-2)',
            },
        ],
    };
});

const unitsChart = computed(() => {
    if (unitsTotal.value <= 0) {
        return null;
    }

    const rows = [
        {
            label: t('Available'),
            value: unitsAvailable.value,
            color: 'var(--brand-accent)',
        },
        {
            label: t('Reserved'),
            value: unitsReserved.value,
            color: 'var(--school-gold)',
        },
        {
            label: t('Sold'),
            value: unitsSold.value,
            color: 'var(--school-navy)',
        },
    ].filter((row) => row.value > 0);

    if (!rows.length) {
        return null;
    }

    return {
        labels: rows.map((row) => row.label),
        datasets: [
            {
                label: t('Units'),
                data: rows.map((row) => row.value),
                color: rows[0]?.color,
            },
        ],
        segments: rows.map((row) => ({
            ...row,
            width: Math.max(
                row.value > 0 ? 6 : 0,
                (row.value / unitsTotal.value) * 100,
            ),
        })),
    };
});

const ownershipChart = computed(() => {
    const rows = [...(props.shareholders ?? [])]
        .map((row) => ({
            name: String(row.name || '—'),
            pct: Number(row.ownership_percentage ?? 0) || 0,
        }))
        .filter((row) => row.pct > 0)
        .sort((a, b) => b.pct - a.pct)
        .slice(0, 6);

    if (!rows.length) {
        return null;
    }

    return {
        labels: rows.map((row) => row.name),
        datasets: [
            {
                label: t('Ownership %'),
                data: rows.map((row) => row.pct),
                color: 'var(--chart-5)',
            },
        ],
    };
});

const insightCards = computed(() => [
    {
        key: 'budget',
        label: t('Budget used'),
        value:
            budgetUsedPercent.value == null
                ? '—'
                : `${formatNumber(budgetUsedPercent.value, 1)}%`,
        meta:
            estimatedBudget.value > 0
                ? `${money(expensesBase.value, baseCurrency.value)} / ${money(estimatedBudget.value, baseCurrency.value)}`
                : t('No budget set'),
    },
    {
        key: 'units',
        label: t('Units sold'),
        value: `${formatNumber(unitsSold.value, 0)} / ${formatNumber(unitsTotal.value, 0)}`,
        meta: `${unitsSoldShare.value}% ${t('Sold')}`,
    },
    {
        key: 'margin',
        label: t('Profit margin'),
        value:
            marginPercent.value == null
                ? '—'
                : `${formatNumber(marginPercent.value, 1)}%`,
        meta: moneyByCurrency(
            props.stats.net_profit_by_currency,
            props.stats.net_profit,
        ),
    },
    {
        key: 'outstanding',
        label: t('Outstanding'),
        value: money(outstanding.value.total, baseCurrency.value),
        meta: [
            outstanding.value.suppliers > 0
                ? `${t('Suppliers')}: ${money(outstanding.value.suppliers, baseCurrency.value)}`
                : null,
            outstanding.value.loans > 0
                ? `${t('Loans')}: ${money(outstanding.value.loans, baseCurrency.value)}`
                : null,
        ]
            .filter(Boolean)
            .join(' · ') || t('Nothing outstanding'),
    },
]);
</script>

<template>
    <div class="space-y-4">
        <V2Panel>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div class="flex min-w-0 flex-wrap items-center gap-2">
                    <h2 class="text-[17px] font-semibold tracking-tight">
                        {{ item.name }}
                    </h2>
                    <StatusBadge :status="item.status" />
                </div>
                <p class="text-sm tabular-nums text-muted-foreground">
                    {{ t('Progress') }}
                    <span class="ms-1 font-semibold text-foreground">
                        {{ formatNumber(progress) }}%
                    </span>
                </p>
            </div>

            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <div>
                    <div
                        class="mb-1.5 flex items-center justify-between text-xs text-muted-foreground"
                    >
                        <span>{{ t('Progress') }}</span>
                        <span class="tabular-nums font-medium text-foreground">
                            {{ formatNumber(progress) }}%
                        </span>
                    </div>
                    <div class="h-1.5 overflow-hidden rounded-full bg-muted">
                        <div
                            class="h-full rounded-full bg-primary transition-all"
                            :style="{ width: `${progress}%` }"
                        />
                    </div>
                </div>
                <div>
                    <div
                        class="mb-1.5 flex items-center justify-between text-xs text-muted-foreground"
                    >
                        <span>{{ t('Budget used') }}</span>
                        <span class="tabular-nums font-medium text-foreground">
                            <template v-if="budgetUsedPercent != null">
                                {{ formatNumber(budgetUsedPercent, 1) }}%
                            </template>
                            <template v-else>—</template>
                        </span>
                    </div>
                    <div class="h-1.5 overflow-hidden rounded-full bg-muted">
                        <div
                            class="h-full rounded-full transition-all"
                            :class="
                                (budgetUsedPercent ?? 0) > 100
                                    ? 'bg-destructive'
                                    : 'bg-[var(--chart-2)]'
                            "
                            :style="{ width: `${budgetBarWidth}%` }"
                        />
                    </div>
                </div>
            </div>
        </V2Panel>

        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <div
                v-for="card in insightCards"
                :key="card.key"
                class="rounded-2xl border border-border/70 bg-card px-4 py-3.5 shadow-soft"
            >
                <p class="text-xs font-medium text-muted-foreground">
                    {{ card.label }}
                </p>
                <p class="mt-1 text-lg font-semibold tracking-tight tabular-nums">
                    {{ card.value }}
                </p>
                <p class="mt-1 truncate text-xs text-muted-foreground">
                    {{ card.meta }}
                </p>
            </div>
        </div>

        <div class="grid gap-4 xl:grid-cols-5">
            <MisChartCard
                class="xl:col-span-3"
                :title="t('Cash flow')"
                :description="t('Sales and spending over time')"
                type="line"
                :labels="cashFlowChart.labels"
                :datasets="cashFlowChart.datasets"
                :show-values="false"
                :page-size="8"
            />
            <MisChartCard
                v-if="moneyMixChart"
                class="xl:col-span-2"
                :title="t('Money mix')"
                :description="t('Where money sits on this project')"
                type="pie"
                :labels="moneyMixChart.labels"
                :datasets="moneyMixChart.datasets"
            />
            <V2Panel
                v-else
                class="xl:col-span-2"
                :title="t('Money mix')"
            >
                <p class="text-sm text-muted-foreground">
                    {{ t('No financial activity yet') }}
                </p>
            </V2Panel>
        </div>

        <div class="grid gap-4 lg:grid-cols-3">
            <V2Panel :title="t('Units')">
                <template v-if="unitsChart">
                    <div class="flex items-end justify-between gap-3">
                        <div>
                            <p
                                class="text-2xl font-semibold tracking-tight tabular-nums"
                            >
                                {{ formatNumber(unitsSoldShare, 0) }}%
                            </p>
                            <p class="text-xs text-muted-foreground">
                                {{ t('Sold') }}
                            </p>
                        </div>
                        <p class="text-sm tabular-nums text-muted-foreground">
                            {{ formatNumber(unitsSold, 0) }} /
                            {{ formatNumber(unitsTotal, 0) }}
                        </p>
                    </div>
                    <div
                        class="mt-4 flex h-2 overflow-hidden rounded-full bg-muted"
                        aria-hidden="true"
                    >
                        <i
                            v-for="seg in unitsChart.segments"
                            :key="seg.label"
                            class="block h-full"
                            :style="{
                                width: `${seg.width}%`,
                                background: seg.color,
                            }"
                        />
                    </div>
                    <ul class="mt-3 space-y-2">
                        <li
                            v-for="seg in unitsChart.segments"
                            :key="seg.label"
                            class="flex items-center gap-2 text-sm"
                        >
                            <span
                                class="size-2.5 shrink-0 rounded-full"
                                :style="{ background: seg.color }"
                            />
                            <span class="min-w-0 flex-1 truncate text-muted-foreground">
                                {{ seg.label }}
                            </span>
                            <span class="font-medium tabular-nums">
                                {{ formatNumber(seg.value, 0) }}
                            </span>
                        </li>
                    </ul>
                </template>
                <p v-else class="text-sm text-muted-foreground">
                    {{ t('No units yet') }}
                </p>
            </V2Panel>

            <MisChartCard
                v-if="categoryChart"
                :title="t('Top spending')"
                :description="t('Top spending categories')"
                type="bar"
                orientation="horizontal"
                :labels="categoryChart.labels"
                :datasets="categoryChart.datasets"
                :page-size="5"
            />
            <V2Panel v-else :title="t('Top spending')">
                <p class="text-sm text-muted-foreground">
                    {{ t('No expenses yet') }}
                </p>
            </V2Panel>

            <MisChartCard
                v-if="ownershipChart"
                :title="t('Ownership')"
                :description="t('Shareholder ownership %')"
                type="pie"
                :labels="ownershipChart.labels"
                :datasets="ownershipChart.datasets"
            />
            <V2Panel v-else :title="t('Ownership')">
                <p class="text-sm text-muted-foreground">
                    {{ t('No shareholders yet') }}
                </p>
            </V2Panel>
        </div>

        <V2Panel :title="t('Project details')">
            <MisDetailGrid
                :columns="3"
                :items="[
                    { label: t('Code'), value: item.code },
                    { label: t('Type'), value: item.type },
                    {
                        label: t('Status'),
                        value: translateStatus(item.status, t),
                    },
                    {
                        label: t('Units'),
                        value: formatNumber(stats.units_count, 0),
                    },
                    {
                        label: t('Start date'),
                        value: formatDate(item.start_date),
                    },
                    {
                        label: t('Expected completion'),
                        value: formatDate(item.expected_completion_date),
                    },
                    {
                        label: t('Location'),
                        value: item.location || t('No location'),
                    },
                    {
                        label: t('Estimated budget'),
                        value: money(item.estimated_budget, baseCurrency),
                    },
                    {
                        label: t('Estimated revenue'),
                        value: money(item.estimated_revenue, baseCurrency),
                    },
                ]"
            />

            <p
                v-if="item.description"
                class="mt-5 border-t border-border/60 pt-4 text-sm leading-6 text-muted-foreground whitespace-pre-wrap"
            >
                {{ item.description }}
            </p>
        </V2Panel>
    </div>
</template>
