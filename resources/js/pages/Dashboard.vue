<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    AlertTriangle,
    ArrowUpRight,
    Boxes,
    Building2,
    CircleDollarSign,
    ClipboardList,
    Home,
    Inbox,
    Package,
    TrendingUp,
    Users,
    Wallet,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import Can from '@/components/Can.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import SortableTh from '@/components/SortableTh.vue';
import TableFrame from '@/components/TableFrame.vue';
import TableIndexTd from '@/components/TableIndexTd.vue';
import TableIndexTh from '@/components/TableIndexTh.vue';
import { indexTableColumn } from '@/composables/useTableColumns';
import { useCalendar } from '@/composables/useCalendar';
import { useCurrency } from '@/composables/useCurrency';
import { provideTableSort } from '@/composables/useTableSort';
import { useTranslations } from '@/composables/useTranslations';
import { translateStatus } from '@/lib/translateStatus';
import { moneyByCurrency, resolveCurrency } from '@/lib/utils';
import { dashboard } from '@/routes';

type Stats = Record<string, any>;

const props = defineProps<{
    mode: 'admin' | 'shareholder';
    preset?: 'today' | 'month' | 'year' | 'custom';
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
const { sortedRows } = provideTableSort(() => props.stats.projects || []);
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
const kpis = computed(() => props.stats.kpis ?? {});
const isAdmin = computed(() => props.mode !== 'shareholder');

const money = (v: unknown, currency = baseCurrency.value) =>
    new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: resolveCurrency(currency),
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

    <div class="mx-auto flex h-full w-full max-w-[1600px] flex-1 flex-col gap-6 p-4 md:p-6">
        <div class="rounded-2xl border border-border/70 bg-card p-3">
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
                <div class="grid min-w-[150px] flex-1 gap-1">
                    <label class="text-xs text-muted-foreground">{{ t('From') }}</label>
                    <Input v-model="from" type="date" class="bg-background/80" />
                </div>
                <div class="grid min-w-[150px] flex-1 gap-1">
                    <label class="text-xs text-muted-foreground">{{ t('To') }}</label>
                    <Input v-model="to" type="date" class="bg-background/80" />
                </div>
                <Button @click="applyPeriod('custom')">{{ t('Apply') }}</Button>
            </div>
        </div>

        <!-- Shareholder KPIs -->
        <div
            v-if="!isAdmin"
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
                <div v-if="stats.all_time" class="mt-1 text-xs text-muted-foreground">
                    {{ t('All-time :amount', { amount: money(stats.all_time.paid_total) }) }}
                </div>
            </div>
            <div class="rounded-2xl border bg-card p-5">
                <div class="text-sm text-muted-foreground">{{ t('Still owed') }}</div>
                <div class="mt-2 text-3xl font-semibold tabular-nums text-amber-700">
                    {{ money(stats.my_balance) }}
                </div>
                <div v-if="stats.all_time" class="mt-1 text-xs text-muted-foreground">
                    {{ t('All-time :amount', { amount: money(stats.all_time.balance_owed) }) }}
                </div>
            </div>
        </div>

        <!-- Admin finance KPIs -->
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
                    <span class="text-sm text-muted-foreground">{{ t('Period receipts') }}</span>
                    <CircleDollarSign class="size-4 text-muted-foreground" />
                </div>
                <div class="mt-3 text-3xl font-semibold tabular-nums text-emerald-700">
                    {{ money(kpis.period_receipts) }}
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
                    {{ moneyByCurrency(kpis.expenses_by_currency, kpis.period_expenses) }}
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

        <!-- Unit pipeline (both modes) -->
        <div class="grid gap-4 sm:grid-cols-3">
            <div class="rounded-2xl border bg-card p-5">
                <div class="text-sm text-muted-foreground">{{ t('Units available') }}</div>
                <div class="mt-2 text-3xl font-semibold tabular-nums">{{ kpis.units_available ?? 0 }}</div>
            </div>
            <div class="rounded-2xl border bg-card p-5">
                <div class="text-sm text-muted-foreground">{{ t('Units reserved') }}</div>
                <div class="mt-2 text-3xl font-semibold tabular-nums text-amber-700">{{ kpis.units_reserved ?? 0 }}</div>
            </div>
            <div class="rounded-2xl border bg-card p-5">
                <div class="text-sm text-muted-foreground">{{ t('Units sold') }}</div>
                <div class="mt-2 text-3xl font-semibold tabular-nums text-emerald-700">{{ kpis.units_sold ?? 0 }}</div>
            </div>
        </div>

        <!-- Admin alert strip -->
        <div v-if="isAdmin" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <Link
                href="/sales"
                class="rounded-2xl border bg-card p-4 transition-colors hover:bg-muted/30"
            >
                <div class="flex items-center justify-between gap-2">
                    <span class="text-sm text-muted-foreground">{{ t('Overdue installments') }}</span>
                    <AlertTriangle class="size-4 text-amber-600" />
                </div>
                <div class="mt-2 text-2xl font-semibold tabular-nums">{{ kpis.overdue_installments_count ?? 0 }}</div>
                <div class="mt-1 text-xs text-muted-foreground">{{ money(kpis.overdue_installments_amount) }}</div>
            </Link>
            <Link
                href="/purchases"
                class="rounded-2xl border bg-card p-4 transition-colors hover:bg-muted/30"
            >
                <div class="flex items-center justify-between gap-2">
                    <span class="text-sm text-muted-foreground">{{ t('Supplier balances') }}</span>
                    <Package class="size-4 text-rose-600" />
                </div>
                <div class="mt-2 text-2xl font-semibold tabular-nums">{{ kpis.purchases_owed_count ?? 0 }}</div>
                <div class="mt-1 text-xs text-muted-foreground">{{ money(kpis.purchases_owed_amount) }}</div>
            </Link>
            <Link
                href="/inventory"
                class="rounded-2xl border bg-card p-4 transition-colors hover:bg-muted/30"
            >
                <div class="flex items-center justify-between gap-2">
                    <span class="text-sm text-muted-foreground">{{ t('Low stock items') }}</span>
                    <Boxes class="size-4 text-orange-600" />
                </div>
                <div class="mt-2 text-2xl font-semibold tabular-nums">{{ kpis.low_stock_count ?? 0 }}</div>
            </Link>
            <Link
                href="/contact-messages"
                class="rounded-2xl border bg-card p-4 transition-colors hover:bg-muted/30"
            >
                <div class="flex items-center justify-between gap-2">
                    <span class="text-sm text-muted-foreground">{{ t('Unread messages') }}</span>
                    <Inbox class="size-4 text-sky-600" />
                </div>
                <div class="mt-2 text-2xl font-semibold tabular-nums">{{ kpis.unread_contacts ?? 0 }}</div>
            </Link>
        </div>

        <!-- Admin ops: HR + field reports -->
        <div v-if="isAdmin" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            <div class="rounded-2xl border bg-card p-5">
                <div class="flex items-center justify-between">
                    <span class="text-sm text-muted-foreground">{{ t('Attendance today') }}</span>
                    <Users class="size-4 text-muted-foreground" />
                </div>
                <div class="mt-3 text-3xl font-semibold tabular-nums">
                    {{ kpis.attendance_today ?? 0 }}
                    <span class="text-base font-normal text-muted-foreground">/ {{ kpis.employees_count ?? 0 }}</span>
                </div>
                <Button as-child variant="ghost" size="sm" class="mt-2 -ms-2">
                    <Link href="/attendance">{{ t('Open attendance') }} <ArrowUpRight class="size-4" /></Link>
                </Button>
            </div>
            <div class="rounded-2xl border bg-card p-5">
                <div class="flex items-center justify-between">
                    <span class="text-sm text-muted-foreground">{{ t('Pending salaries') }}</span>
                    <Wallet class="size-4 text-muted-foreground" />
                </div>
                <div class="mt-3 text-3xl font-semibold tabular-nums">{{ kpis.pending_salaries_count ?? 0 }}</div>
                <div class="mt-1 text-xs text-muted-foreground">{{ money(kpis.pending_salaries_amount) }}</div>
                <Button as-child variant="ghost" size="sm" class="mt-2 -ms-2">
                    <Link href="/salaries">{{ t('Open salaries') }} <ArrowUpRight class="size-4" /></Link>
                </Button>
            </div>
            <div class="rounded-2xl border bg-card p-5">
                <div class="flex items-center justify-between">
                    <span class="text-sm text-muted-foreground">{{ t('Draft field reports') }}</span>
                    <ClipboardList class="size-4 text-muted-foreground" />
                </div>
                <div class="mt-3 text-3xl font-semibold tabular-nums">{{ kpis.field_reports_draft ?? 0 }}</div>
                <Button as-child variant="ghost" size="sm" class="mt-2 -ms-2">
                    <Link href="/field-reports">{{ t('Open field reports') }} <ArrowUpRight class="size-4" /></Link>
                </Button>
            </div>
        </div>

        <!-- Currency cards (admin only) -->
        <div v-if="isAdmin" class="grid gap-4 sm:grid-cols-2">
            <div class="rounded-2xl border bg-card p-5">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium text-muted-foreground">{{ t('AFN account') }}</span>
                    <span class="rounded-full bg-muted px-2 py-1 text-xs font-semibold">AFN</span>
                </div>
                <div class="mt-3 grid grid-cols-2 gap-4">
                    <div>
                        <div class="text-xs text-muted-foreground">{{ t('Expenses') }}</div>
                        <div class="text-lg font-semibold tabular-nums text-rose-700">{{ money(kpis.expenses_by_currency?.AFN ?? 0, 'AFN') }}</div>
                    </div>
                    <div>
                        <div class="text-xs text-muted-foreground">{{ t('Incomes') }}</div>
                        <div class="text-lg font-semibold tabular-nums text-emerald-700">{{ money(kpis.incomes_by_currency?.AFN ?? 0, 'AFN') }}</div>
                    </div>
                </div>
            </div>
            <div class="rounded-2xl border bg-card p-5">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium text-muted-foreground">{{ t('USD account') }}</span>
                    <span class="rounded-full bg-muted px-2 py-1 text-xs font-semibold">USD</span>
                </div>
                <div class="mt-3 grid grid-cols-2 gap-4">
                    <div>
                        <div class="text-xs text-muted-foreground">{{ t('Expenses') }}</div>
                        <div class="text-lg font-semibold tabular-nums text-rose-700">{{ money(kpis.expenses_by_currency?.USD ?? 0, 'USD') }}</div>
                    </div>
                    <div>
                        <div class="text-xs text-muted-foreground">{{ t('Incomes') }}</div>
                        <div class="text-lg font-semibold tabular-nums text-emerald-700">{{ money(kpis.incomes_by_currency?.USD ?? 0, 'USD') }}</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Projects + partners / project shares -->
        <div class="grid gap-4 xl:grid-cols-3">
            <div class="rounded-2xl border bg-card xl:col-span-2">
                <div class="flex items-center justify-between border-b px-5 py-4">
                    <h2 class="font-medium">{{ t('Projects performance') }}</h2>
                    <Button as-child variant="ghost" size="sm">
                        <Link href="/projects">{{ t('View all') }} <ArrowUpRight class="size-4" /></Link>
                    </Button>
                </div>
                <TableFrame table-id="dashboard-projects" :columns="tableColumns">
                    <table class="min-w-full text-sm">
                        <thead class="bg-muted/30 text-start text-muted-foreground">
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
                                class="border-t"
                            >
                                <TableIndexTd :index="index" />
                                <td class="px-5 py-3">
                                    <div class="font-medium">{{ p.name }}</div>
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
                                <td class="px-5 py-3 font-medium tabular-nums">
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

            <!-- Shareholder: project shares -->
            <div v-if="!isAdmin" class="rounded-2xl border bg-card">
                <div class="flex items-center justify-between border-b px-5 py-4">
                    <h2 class="font-medium">{{ t('Your project shares') }}</h2>
                    <CircleDollarSign class="size-4 text-muted-foreground" />
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
                                    {{ t(':value% ownership', { value: Number(share.ownership_percentage ?? 0).toFixed(2) }) }}
                                </div>
                            </div>
                            <div class="text-right text-sm tabular-nums">
                                <div class="font-semibold">{{ money(share.entitled_share) }}</div>
                                <div class="text-xs text-muted-foreground">
                                    {{ t('Paid :paid · Owed :owed', { paid: money(share.paid_total ?? 0), owed: money(share.balance_owed ?? 0) }) }}
                                </div>
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

            <!-- Admin: shareholder split -->
            <div v-else class="rounded-2xl border bg-card">
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

        <!-- Admin attention lists -->
        <div v-if="isAdmin" class="grid gap-4 lg:grid-cols-3">
            <div class="rounded-2xl border bg-card">
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
                        <div>
                            <div class="font-medium">{{ row.customer || row.sale_no }}</div>
                            <div class="text-xs text-muted-foreground">
                                {{ row.project || '—' }} · {{ formatDate(row.due_date) }}
                            </div>
                        </div>
                        <div class="font-medium tabular-nums text-amber-700">{{ money(row.remaining) }}</div>
                    </div>
                    <div
                        v-if="!(stats.overdue_installments || []).length"
                        class="px-5 py-6 text-sm text-muted-foreground"
                    >
                        {{ t('No overdue installments.') }}
                    </div>
                </div>
            </div>

            <div class="rounded-2xl border bg-card">
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
                        <div>
                            <div class="font-medium">{{ row.name }}</div>
                            <div class="text-xs text-muted-foreground">{{ row.code }}</div>
                        </div>
                        <div class="text-right text-xs tabular-nums">
                            <div class="font-medium text-orange-700">{{ row.on_hand }}</div>
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

            <div class="rounded-2xl border bg-card">
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
                        <div>
                            <div class="font-medium">{{ row.supplier || row.purchase_no }}</div>
                            <div class="text-xs text-muted-foreground">{{ row.project || '—' }}</div>
                        </div>
                        <div class="font-medium tabular-nums text-rose-700">{{ money(row.remaining) }}</div>
                    </div>
                    <div
                        v-if="!(stats.unpaid_purchases || []).length"
                        class="px-5 py-6 text-sm text-muted-foreground"
                    >
                        {{ t('No unpaid purchases.') }}
                    </div>
                </div>
            </div>
        </div>

        <div v-if="isAdmin" class="grid gap-4 lg:grid-cols-2">
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
                    <div
                        v-if="!(stats.recent_sales || []).length"
                        class="px-5 py-6 text-sm text-muted-foreground"
                    >
                        {{ t('No recent sales.') }}
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
                        <div class="font-medium tabular-nums">{{ money(expense.amount, expense.currency) }}</div>
                    </div>
                    <div
                        v-if="!(stats.recent_expenses || []).length"
                        class="px-5 py-6 text-sm text-muted-foreground"
                    >
                        {{ t('No recent expenses.') }}
                    </div>
                </div>
            </div>
        </div>

        <div
            v-if="isAdmin && (stats.recent_field_reports || []).length"
            class="rounded-2xl border bg-card"
        >
            <div class="flex items-center justify-between border-b px-5 py-4">
                <h2 class="font-medium">{{ t('Recent field reports') }}</h2>
                <Button as-child variant="ghost" size="sm">
                    <Link href="/field-reports">{{ t('Open') }} <ArrowUpRight class="size-4" /></Link>
                </Button>
            </div>
            <div class="divide-y">
                <div
                    v-for="report in stats.recent_field_reports || []"
                    :key="report.id"
                    class="flex items-center justify-between gap-3 px-5 py-3 text-sm"
                >
                    <div>
                        <div class="font-medium">{{ report.title }}</div>
                        <div class="text-xs text-muted-foreground">
                            {{ report.location || '—' }} · {{ translateStatus(report.status, t) }}
                        </div>
                    </div>
                    <Button as-child variant="ghost" size="sm">
                        <Link :href="`/field-reports/${report.id}`">{{ t('View') }}</Link>
                    </Button>
                </div>
            </div>
        </div>

        <div
            v-if="isAdmin"
            class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4 2xl:grid-cols-7"
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
                <Link href="/inventory">{{ t('Inventory') }} <ArrowUpRight class="size-4" /></Link>
            </Button>
            <Button as-child variant="outline" class="justify-between">
                <Link href="/balance-sheet">{{ t('Balance sheet') }} <ArrowUpRight class="size-4" /></Link>
            </Button>
            <Button as-child variant="outline" class="justify-between">
                <Link href="/contact-messages">{{ t('Contact messages') }} <ArrowUpRight class="size-4" /></Link>
            </Button>
            <Button as-child variant="outline" class="justify-between">
                <Link href="/reports">{{ t('Open reports') }} <ArrowUpRight class="size-4" /></Link>
            </Button>
        </div>
    </div>
</template>
