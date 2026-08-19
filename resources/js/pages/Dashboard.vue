<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    ArrowUpRight,
    Building2,
    CircleDollarSign,
    Home,
    TrendingUp,
    Wallet,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import Can from '@/components/Can.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { useCalendar } from '@/composables/useCalendar';
import { useCurrency } from '@/composables/useCurrency';
import { useTranslations } from '@/composables/useTranslations';
import { dashboard } from '@/routes';

type Stats = Record<string, any>;

const props = defineProps<{
    mode: 'admin' | 'shareholder';
    preset?: 'today' | 'month' | 'year' | 'custom';
    stats: Stats;
}>();
const { t } = useTranslations();
const calendar = useCalendar();
const { baseCurrency } = useCurrency();
const formatDate = (value: string | null | undefined) => calendar.format(value) || '—';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Dashboard', href: dashboard() }],
    },
});

const from = ref(props.stats.period?.from ?? props.stats.filters?.from ?? '');
const to = ref(props.stats.period?.to ?? props.stats.filters?.to ?? '');

const kpis = computed(() =>
    props.mode === 'shareholder' ? props.stats.kpis : props.stats.kpis,
);

const money = (v: unknown, currency = baseCurrency.value) =>
    new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency,
        maximumFractionDigits: 0,
    }).format(Number(v ?? 0));

function applyPeriod(nextPreset?: 'today' | 'month' | 'year' | 'custom') {
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
</script>

<template>
    <Head :title="t('Dashboard')" />

    <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto p-4 md:p-6">
        <div
            class="relative overflow-hidden rounded-2xl border border-sidebar-border/70 bg-[radial-gradient(circle_at_top_left,rgba(180_83%_40%/0.14),transparent_40%),linear-gradient(135deg,oklch(0.98_0.01_85),oklch(0.96_0.02_145))] p-6 dark:bg-[radial-gradient(circle_at_top_left,rgba(180_60%_45%/0.18),transparent_45%),linear-gradient(135deg,oklch(0.24_0.02_145),oklch(0.2_0.02_85))]"
        >
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div class="max-w-2xl space-y-2">
                    <p class="text-sm font-medium tracking-wide text-emerald-800 uppercase dark:text-emerald-300">
                        {{ t('Construction MIS') }}
                    </p>
                    <h1 class="font-serif text-3xl tracking-tight text-foreground md:text-4xl">
                        {{
                            mode === 'shareholder'
                                ? t('Your shareholder overview')
                                : t('Projects, costs, sales & profit — one clear view')
                        }}
                    </h1>
                    <p class="text-muted-foreground">
                        {{
                            mode === 'shareholder'
                                ? t('See company profit for the selected period and your ownership share.')
                                : t('Track active builds, home sales, expenses and shareholder profit in real time.')
                        }}
                    </p>
                </div>
                <div class="flex flex-wrap items-end gap-2">
                    <Button
                        variant="outline"
                        :class="preset === 'today' ? 'border-emerald-600' : ''"
                        @click="applyPeriod('today')"
                    >
                        {{ t('Today') }}
                    </Button>
                    <Button
                        variant="outline"
                        :class="preset === 'month' ? 'border-emerald-600' : ''"
                        @click="applyPeriod('month')"
                    >
                        {{ t('This month') }}
                    </Button>
                    <Button
                        variant="outline"
                        :class="preset === 'year' ? 'border-emerald-600' : ''"
                        @click="applyPeriod('year')"
                    >
                        {{ t('This year') }}
                    </Button>
                    <div class="grid gap-1">
                        <label class="text-xs text-muted-foreground">{{ t('From') }}</label>
                        <Input v-model="from" type="date" class="bg-background/80" />
                    </div>
                    <div class="grid gap-1">
                        <label class="text-xs text-muted-foreground">{{ t('To') }}</label>
                        <Input v-model="to" type="date" class="bg-background/80" />
                    </div>
                    <Button @click="applyPeriod('custom')">{{ t('Apply') }}</Button>
                </div>
            </div>
        </div>

        <div
            v-if="mode === 'shareholder'"
            class="grid gap-4 md:grid-cols-2 xl:grid-cols-5"
        >
            <div class="rounded-2xl border bg-card p-5">
                <div class="text-sm text-muted-foreground">{{ t('Company profit') }}</div>
                <div class="mt-2 text-3xl font-semibold tabular-nums">
                    {{ money(stats.company_profit) }}
                </div>
            </div>
            <div class="rounded-2xl border bg-card p-5">
                <div class="text-sm text-muted-foreground">{{ t('Your ownership') }}</div>
                <div class="mt-2 text-3xl font-semibold tabular-nums">
                    {{ Number(stats.partner?.ownership_percentage ?? 0).toFixed(2) }}%
                </div>
            </div>
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-5 dark:border-emerald-900 dark:bg-emerald-950/40">
                <div class="text-sm text-emerald-800 dark:text-emerald-300">{{ t('Your profit share') }}</div>
                <div class="mt-2 text-3xl font-semibold tabular-nums text-emerald-900 dark:text-emerald-200">
                    {{ money(stats.my_profit) }}
                </div>
            </div>
            <div class="rounded-2xl border bg-card p-5">
                <div class="text-sm text-muted-foreground">{{ t('Paid to you') }}</div>
                <div class="mt-2 text-3xl font-semibold tabular-nums text-emerald-700">
                    {{ money(stats.my_paid) }}
                </div>
            </div>
            <div class="rounded-2xl border bg-card p-5">
                <div class="text-sm text-muted-foreground">{{ t('Still owed') }}</div>
                <div class="mt-2 text-3xl font-semibold tabular-nums text-amber-700">
                    {{ money(stats.my_balance) }}
                </div>
            </div>
        </div>

        <div v-else class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            <div class="rounded-2xl border bg-card p-5">
                <div class="flex items-center justify-between">
                    <span class="text-sm text-muted-foreground">{{ t('Active projects') }}</span>
                    <Building2 class="size-4 text-muted-foreground" />
                </div>
                <div class="mt-3 text-3xl font-semibold">{{ kpis.active_projects }}</div>
            </div>
            <div class="rounded-2xl border bg-card p-5">
                <div class="flex items-center justify-between">
                    <span class="text-sm text-muted-foreground">{{ t('Period sales') }}</span>
                    <Home class="size-4 text-muted-foreground" />
                </div>
                <div class="mt-3 text-3xl font-semibold tabular-nums">
                    {{ money(kpis.period_sales) }}
                </div>
            </div>
            <div class="rounded-2xl border bg-card p-5">
                <div class="flex items-center justify-between">
                    <span class="text-sm text-muted-foreground">{{ t('Total revenue') }}</span>
                    <CircleDollarSign class="size-4 text-muted-foreground" />
                </div>
                <div class="mt-3 text-3xl font-semibold tabular-nums">
                    {{ money(kpis.period_revenue) }}
                </div>
            </div>
            <div class="rounded-2xl border bg-card p-5">
                <div class="flex items-center justify-between">
                    <span class="text-sm text-muted-foreground">{{ t('Period expenses') }}</span>
                    <Wallet class="size-4 text-muted-foreground" />
                </div>
                <div class="mt-3 text-3xl font-semibold tabular-nums">
                    {{ money(kpis.period_expenses) }}
                </div>
            </div>
            <div class="rounded-2xl border bg-card p-5">
                <div class="flex items-center justify-between">
                    <span class="text-sm text-muted-foreground">{{ t('Paid salaries') }}</span>
                    <Wallet class="size-4 text-muted-foreground" />
                </div>
                <div class="mt-3 text-3xl font-semibold tabular-nums">
                    {{ money(kpis.period_salaries) }}
                </div>
            </div>
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-5 dark:border-emerald-900 dark:bg-emerald-950/40">
                <div class="flex items-center justify-between">
                    <span class="text-sm text-emerald-800 dark:text-emerald-300">{{ t('Net profit') }}</span>
                    <TrendingUp class="size-4 text-emerald-700 dark:text-emerald-300" />
                </div>
                <div class="mt-3 text-3xl font-semibold tabular-nums text-emerald-900 dark:text-emerald-100">
                    {{ money(kpis.net_profit_period) }}
                </div>
                <div class="mt-1 text-xs text-emerald-800/80 dark:text-emerald-200/80">
                    {{ t('All-time :amount', { amount: money(kpis.net_profit_total) }) }}
                </div>
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div class="rounded-2xl border bg-card p-5">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium text-muted-foreground">{{ t('AFN account') }}</span>
                    <span class="text-xs font-semibold rounded-full bg-muted px-2 py-1">AFN</span>
                </div>
                <div class="mt-3 grid grid-cols-2 gap-4">
                    <div>
                        <div class="text-xs text-muted-foreground">{{ t('Expenses') }}</div>
                        <div class="text-lg font-semibold tabular-nums text-rose-700">{{ money(stats.kpis?.expenses_by_currency?.AFN ?? 0, 'AFN') }}</div>
                    </div>
                    <div>
                        <div class="text-xs text-muted-foreground">{{ t('Incomes') }}</div>
                        <div class="text-lg font-semibold tabular-nums text-emerald-700">{{ money(stats.kpis?.incomes_by_currency?.AFN ?? 0, 'AFN') }}</div>
                    </div>
                </div>
            </div>
            <div class="rounded-2xl border bg-card p-5">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium text-muted-foreground">{{ t('USD account') }}</span>
                    <span class="text-xs font-semibold rounded-full bg-muted px-2 py-1">USD</span>
                </div>
                <div class="mt-3 grid grid-cols-2 gap-4">
                    <div>
                        <div class="text-xs text-muted-foreground">{{ t('Expenses') }}</div>
                        <div class="text-lg font-semibold tabular-nums text-rose-700">{{ money(stats.kpis?.expenses_by_currency?.USD ?? 0, 'USD') }}</div>
                    </div>
                    <div>
                        <div class="text-xs text-muted-foreground">{{ t('Incomes') }}</div>
                        <div class="text-lg font-semibold tabular-nums text-emerald-700">{{ money(stats.kpis?.incomes_by_currency?.USD ?? 0, 'USD') }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid gap-4 xl:grid-cols-3">
            <div class="rounded-2xl border bg-card xl:col-span-2">
                <div class="flex items-center justify-between border-b px-5 py-4">
                    <h2 class="font-medium">{{ t('Projects performance') }}</h2>
                    <Button as-child variant="ghost" size="sm">
                        <Link href="/projects">{{ t('View all') }} <ArrowUpRight class="size-4" /></Link>
                    </Button>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-muted/30 text-left text-muted-foreground">
                            <tr>
                                <th class="px-5 py-3">{{ t('Project') }}</th>
                                <th class="px-5 py-3">{{ t('Progress') }}</th>
                                <th class="px-5 py-3">{{ t('Revenue') }}</th>
                                <th class="px-5 py-3">{{ t('Expenses') }}</th>
                                <th class="px-5 py-3">{{ t('Profit') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="p in stats.projects || []"
                                :key="p.id"
                                class="border-t"
                            >
                                <td class="px-5 py-3">
                                    <div class="font-medium">{{ p.name }}</div>
                                    <div class="text-xs text-muted-foreground capitalize">
                                        {{ String(p.status).replaceAll('_', ' ') }}
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
                                <td class="px-5 py-3 font-medium tabular-nums">
                                    {{ money(p.profit) }}
                                </td>
                            </tr>
                            <tr v-if="!(stats.projects || []).length">
                                <td colspan="5" class="px-5 py-8 text-center text-muted-foreground">
                                    {{ t('No projects yet.') }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="rounded-2xl border bg-card">
                <div class="flex items-center justify-between border-b px-5 py-4">
                    <h2 class="font-medium">{{ t('Shareholder split') }}</h2>
                    <CircleDollarSign class="size-4 text-muted-foreground" />
                </div>
                <div class="space-y-3 p-5">
                    <div
                        v-for="partner in stats.partners || []"
                        :key="partner.id"
                        class="rounded-xl bg-muted/40 px-3 py-3"
                    >
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <div class="font-medium">{{ partner.name }}</div>
                                <div class="text-xs text-muted-foreground">
                                    {{ partner.project_name || t('Company') }} ·
                                    {{ t(':value% ownership', { value: Number(partner.ownership_percentage).toFixed(2) }) }}
                                </div>
                            </div>
                            <div class="text-right text-sm tabular-nums">
                                <div class="font-semibold">{{ money(partner.entitled_share ?? partner.period_profit_share) }}</div>
                                <div class="text-xs text-muted-foreground">
                                    {{ t('Paid :paid · Owed :owed', { paid: money(partner.paid_total ?? 0), owed: money(partner.balance_owed ?? 0) }) }}
                                </div>
                            </div>
                        </div>
                    </div>
                    <p
                        v-if="!(stats.partners || []).length"
                        class="text-sm text-muted-foreground"
                    >
                        {{ t('Add shareholders to see profit allocation.') }}
                    </p>
                    <Button as-child variant="outline" class="w-full">
                        <Link href="/partners">{{ t('Manage shareholders') }}</Link>
                    </Button>
                </div>
            </div>
        </div>

        <div v-if="mode !== 'shareholder'" class="grid gap-4 lg:grid-cols-2">
            <div class="rounded-2xl border bg-card">
                <div class="flex items-center justify-between border-b px-5 py-4">
                    <h2 class="font-medium">{{ t('Recent sales') }}</h2>
                    <Button as-child variant="ghost" size="sm">
                        <Link href="/sales">{{ t('Open') }} <ArrowUpRight class="size-4" /></Link>
                    </Button>
                </div>
                <div class="divide-y">
                    <div
                        v-for="sale in stats.recent_sales || []"
                        :key="sale.id"
                        class="flex items-center justify-between gap-3 px-5 py-3 text-sm"
                    >
                        <div>
                            <div class="font-medium">{{ sale.customer?.name }}</div>
                            <div class="text-xs text-muted-foreground">
                                {{ sale.project?.name }} · {{ sale.unit?.unit_no }}
                            </div>
                        </div>
                        <div class="font-medium tabular-nums">{{ money(Math.max(0, Number(sale.unit?.list_price_base ?? 0) - Number(sale.discount_base ?? 0))) }}</div>
                    </div>
                </div>
            </div>
            <div class="rounded-2xl border bg-card">
                <div class="flex items-center justify-between border-b px-5 py-4">
                    <h2 class="font-medium">{{ t('Recent expenses') }}</h2>
                    <Button as-child variant="ghost" size="sm">
                        <Link href="/expenses">{{ t('Open') }} <ArrowUpRight class="size-4" /></Link>
                    </Button>
                </div>
                <div class="divide-y">
                    <div
                        v-for="expense in stats.recent_expenses || []"
                        :key="expense.id"
                        class="flex items-center justify-between gap-3 px-5 py-3 text-sm"
                    >
                        <div>
                            <div class="font-medium">
                                {{ expense.description || t('Expense') }}
                            </div>
                            <div class="text-xs text-muted-foreground">
                                {{ expense.project?.name || t('Company') }} ·
                                {{ formatDate(String(expense.expense_date ?? '')) }}
                            </div>
                        </div>
                        <div class="font-medium tabular-nums">{{ money(expense.amount_base ?? expense.amount) }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div
            v-if="mode !== 'shareholder'"
            class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4"
        >
            <Can permission="expenses.create">
                <Button as-child variant="outline" class="justify-between">
                    <Link href="/expenses/create">{{ t('Record expense') }} <ArrowUpRight class="size-4" /></Link>
                </Button>
            </Can>
            <Can permission="sales.create">
                <Button as-child variant="outline" class="justify-between">
                    <Link href="/sales/create">{{ t('Create sale') }} <ArrowUpRight class="size-4" /></Link>
                </Button>
            </Can>
            <Can permission="projects.create">
                <Button as-child variant="outline" class="justify-between">
                    <Link href="/projects/create">{{ t('Add project') }} <ArrowUpRight class="size-4" /></Link>
                </Button>
            </Can>
            <Button as-child variant="outline" class="justify-between">
                <Link href="/reports">{{ t('Open reports') }} <ArrowUpRight class="size-4" /></Link>
            </Button>
            <Button as-child variant="outline" class="justify-between">
                <Link href="/partners">{{ t('Shareholder accounts') }} <ArrowUpRight class="size-4" /></Link>
            </Button>
        </div>
    </div>
</template>
