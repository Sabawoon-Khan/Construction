<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import {
    CircleDollarSign,
    Download,
    FileSpreadsheet,
    Printer,
    Receipt,
    TrendingUp,
    Wallet,
} from '@lucide/vue';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { useCalendar } from '@/composables/useCalendar';
import { useCurrency } from '@/composables/useCurrency';
import { useTranslations } from '@/composables/useTranslations';
import { resolveCurrency } from '@/lib/utils';

const props = defineProps<{
    filters: { from: string; to: string; project_id?: number | null; preset?: string; currency?: string };
    restricted?: boolean;
    projects: { id: number; name: string }[];
    summary: Record<string, number>;
    expenses_by_currency: Record<string, number>;
    incomes_by_currency: Record<string, number>;
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
const from = ref(props.filters.from);
const to = ref(props.filters.to);
const projectId = ref(String(props.filters.project_id ?? ''));
const calendar = useCalendar();
const { baseCurrency } = useCurrency();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Reports', href: '/reports' },
        ],
    },
});

const money = (v: unknown, currency = baseCurrency.value) =>
    new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: resolveCurrency(currency),
        maximumFractionDigits: 0,
    }).format(Number(v ?? 0));

const dateOnly = (v: unknown) => calendar.format(v == null ? null : String(v)) || '—';

function apply(preset?: string) {
    router.get(
        '/reports',
        {
            preset: preset ?? 'custom',
            from: preset && preset !== 'custom' ? undefined : from.value || undefined,
            to: preset && preset !== 'custom' ? undefined : to.value || undefined,
            project_id: projectId.value || undefined,
        },
        { preserveState: true, replace: true },
    );
}

function exportUrl(format: string) {
    const params = new URLSearchParams({
        format,
        preset: props.filters.preset || 'custom',
        from: from.value,
        to: to.value,
    });

    if (projectId.value) {
        params.set('project_id', projectId.value);
    }

    return `/reports/export?${params.toString()}`;
}
</script>

<template>
    <Head :title="t('Reports')" />

    <div class="print-document mx-auto flex h-full w-full max-w-[1600px] flex-1 flex-col gap-4 print:gap-4 print:p-0">
        <div class="space-y-4 print:hidden">
            <div class="rounded-2xl border border-border/70 bg-card p-3">
                <div class="flex flex-wrap items-end gap-2">
                    <Button
                        variant="outline"
                        :class="filters.preset === 'today' ? 'border-emerald-600' : ''"
                        @click="apply('today')"
                    >
                        {{ t('Today') }}
                    </Button>
                    <Button
                        variant="outline"
                        :class="filters.preset === 'month' ? 'border-emerald-600' : ''"
                        @click="apply('month')"
                    >
                        {{ t('This month') }}
                    </Button>
                    <Button
                        variant="outline"
                        :class="filters.preset === 'year' ? 'border-emerald-600' : ''"
                        @click="apply('year')"
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
                    <div class="grid min-w-[180px] flex-1 gap-1">
                        <label class="text-xs text-muted-foreground">{{ t('Project') }}</label>
                        <select
                            v-model="projectId"
                            class="h-9 w-full rounded-md border border-input bg-background/80 px-3 text-sm"
                        >
                            <option value="">{{ t('All projects') }}</option>
                            <option v-for="p in projects" :key="p.id" :value="String(p.id)">
                                {{ p.name }}
                            </option>
                        </select>
                    </div>
                    <Button @click="apply('custom')">{{ t('Apply') }}</Button>
                    <div class="ms-auto flex flex-wrap items-end gap-2">
                        <Button as-child variant="outline">
                            <a :href="exportUrl('xlsx')" class="inline-flex items-center gap-2">
                                <FileSpreadsheet class="size-4" />
                                {{ t('Excel') }}
                            </a>
                        </Button>
                        <Button as-child>
                            <a
                                :href="exportUrl('print').replace('/reports/export', '/reports/print')"
                                target="_blank"
                                class="inline-flex items-center gap-2"
                            >
                                <Printer class="size-4" />
                                {{ t('Print / PDF') }}
                            </a>
                        </Button>
                    </div>
                </div>
            </div>

            <div
                class="relative overflow-hidden rounded-2xl border border-sidebar-border/70 bg-[radial-gradient(circle_at_top_left,rgba(180_83%_40%/0.14),transparent_40%),linear-gradient(135deg,oklch(0.98_0.01_85),oklch(0.96_0.02_145))] p-6 dark:bg-[radial-gradient(circle_at_top_left,rgba(180_60%_45%/0.18),transparent_45%),linear-gradient(135deg,oklch(0.24_0.02_145),oklch(0.2_0.02_85))]"
            >
                <div class="max-w-2xl space-y-2">
                    <p class="text-sm font-medium tracking-wide text-emerald-800 uppercase dark:text-emerald-300">
                        {{ t('Reports') }}
                    </p>
                    <h1 class="font-serif text-3xl tracking-tight text-foreground md:text-4xl">
                        {{ t('Reports & Analytics') }}
                    </h1>
                    <p class="text-muted-foreground">
                        {{ t('Daily and monthly MIS: sales, expenses, salaries, net profit and shareholder shares.') }}
                    </p>
                </div>
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            <div class="rounded-2xl border bg-card p-5">
                <div class="flex items-center justify-between">
                    <span class="text-sm text-muted-foreground">{{ t('Sales') }}</span>
                    <CircleDollarSign class="size-4 text-muted-foreground" />
                </div>
                <div class="mt-3 text-3xl font-semibold tabular-nums">
                    {{ money(summary.sales_total) }}
                </div>
            </div>
            <div class="rounded-2xl border bg-card p-5">
                <div class="flex items-center justify-between">
                    <span class="text-sm text-muted-foreground">{{ t('Total revenue') }}</span>
                    <Download class="size-4 text-muted-foreground" />
                </div>
                <div class="mt-3 text-3xl font-semibold tabular-nums">
                    {{ money(summary.revenue_total) }}
                </div>
            </div>
            <div class="rounded-2xl border bg-card p-5">
                <div class="flex items-center justify-between">
                    <span class="text-sm text-muted-foreground">{{ t('Operating expenses') }}</span>
                    <Receipt class="size-4 text-muted-foreground" />
                </div>
                <div class="mt-3 text-3xl font-semibold tabular-nums">
                    {{ money(summary.operating_expense_total) }}
                </div>
            </div>
            <div class="rounded-2xl border bg-card p-5">
                <div class="flex items-center justify-between">
                    <span class="text-sm text-muted-foreground">{{ t('Paid salaries') }}</span>
                    <Wallet class="size-4 text-muted-foreground" />
                </div>
                <div class="mt-3 text-3xl font-semibold tabular-nums">
                    {{ money(summary.salary_total) }}
                </div>
            </div>
            <div class="rounded-2xl border bg-card p-5">
                <div class="flex items-center justify-between">
                    <span class="text-sm text-muted-foreground">{{ t('All expenses') }}</span>
                    <Wallet class="size-4 text-muted-foreground" />
                </div>
                <div class="mt-3 text-3xl font-semibold tabular-nums">
                    {{ money(summary.expense_total) }}
                </div>
            </div>
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-5 dark:border-emerald-900 dark:bg-emerald-950/40">
                <div class="flex items-center justify-between">
                    <span class="text-sm text-emerald-800 dark:text-emerald-300">{{ t('Net profit') }}</span>
                    <TrendingUp class="size-4 text-emerald-700 dark:text-emerald-300" />
                </div>
                <div class="mt-3 text-3xl font-semibold tabular-nums text-emerald-900 dark:text-emerald-100">
                    {{ money(summary.net_profit) }}
                </div>
                <div class="mt-1 text-xs text-emerald-800/80 dark:text-emerald-200/80">
                    {{ t('Sales + income − expenses − salaries') }}
                </div>
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div class="rounded-2xl border bg-card p-5">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium text-muted-foreground">{{ t('AFN account') }}</span>
                    <span class="rounded-full bg-muted px-2 py-1 text-xs font-semibold">AFN</span>
                </div>
                <div class="mt-3 grid grid-cols-2 gap-4">
                    <div>
                        <div class="text-xs text-muted-foreground">{{ t('Expenses') }}</div>
                        <div class="text-lg font-semibold tabular-nums text-rose-700">
                            {{ money(props.expenses_by_currency.AFN ?? 0, 'AFN') }}
                        </div>
                    </div>
                    <div>
                        <div class="text-xs text-muted-foreground">{{ t('Incomes') }}</div>
                        <div class="text-lg font-semibold tabular-nums text-emerald-700">
                            {{ money(props.incomes_by_currency.AFN ?? 0, 'AFN') }}
                        </div>
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
                        <div class="text-lg font-semibold tabular-nums text-rose-700">
                            {{ money(props.expenses_by_currency.USD ?? 0, 'USD') }}
                        </div>
                    </div>
                    <div>
                        <div class="text-xs text-muted-foreground">{{ t('Incomes') }}</div>
                        <div class="text-lg font-semibold tabular-nums text-emerald-700">
                            {{ money(props.incomes_by_currency.USD ?? 0, 'USD') }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid gap-4 xl:grid-cols-2">
            <div class="rounded-2xl border bg-card">
                <div class="border-b px-5 py-4">
                    <h2 class="font-medium">{{ t('Expenses by category') }}</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-muted/30 text-start text-muted-foreground">
                            <tr>
                                <th class="whitespace-nowrap px-5 py-3">{{ t('Category') }}</th>
                                <th class="whitespace-nowrap px-5 py-3">{{ t('Count') }}</th>
                                <th class="whitespace-nowrap px-5 py-3">{{ t('Amount') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="row in by_category" :key="row.category" class="border-t">
                                <td class="px-5 py-3">{{ row.category }}</td>
                                <td class="px-5 py-3">{{ row.count }}</td>
                                <td class="px-5 py-3 tabular-nums">
                                    {{ money(row.amount_base ?? row.amount) }}
                                </td>
                            </tr>
                            <tr v-if="!by_category.length">
                                <td colspan="3" class="px-5 py-8 text-center text-muted-foreground">
                                    {{ t('No expenses in this period.') }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="rounded-2xl border bg-card">
                <div class="border-b px-5 py-4">
                    <h2 class="font-medium">{{ t('Shareholder distribution') }}</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-muted/30 text-start text-muted-foreground">
                            <tr>
                                <th class="whitespace-nowrap px-5 py-3">{{ t('Shareholder') }}</th>
                                <th class="whitespace-nowrap px-5 py-3">{{ t('Project') }}</th>
                                <th class="whitespace-nowrap px-5 py-3">{{ t('Share %') }}</th>
                                <th class="whitespace-nowrap px-5 py-3">{{ t('Entitled') }}</th>
                                <th class="whitespace-nowrap px-5 py-3">{{ t('Paid') }}</th>
                                <th class="whitespace-nowrap px-5 py-3">{{ t('Balance') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="p in partners"
                                :key="`${p.id}-${p.project_name}`"
                                class="border-t"
                            >
                                <td class="whitespace-nowrap px-5 py-3">{{ p.name }}</td>
                                <td class="whitespace-nowrap px-5 py-3">{{ p.project_name || '—' }}</td>
                                <td class="whitespace-nowrap px-5 py-3">
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
                                <td colspan="6" class="px-5 py-8 text-center text-muted-foreground">
                                    {{ t('No shareholders in this period.') }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div v-if="!restricted" class="grid gap-4 lg:grid-cols-2">
            <div class="rounded-2xl border bg-card">
                <div class="border-b px-5 py-4">
                    <h2 class="font-medium">{{ t('New sales') }}</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-muted/30 text-start text-muted-foreground">
                            <tr>
                                <th class="whitespace-nowrap px-5 py-3">{{ t('Date') }}</th>
                                <th class="whitespace-nowrap px-5 py-3">{{ t('Buyer') }}</th>
                                <th class="whitespace-nowrap px-5 py-3">{{ t('Amount') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="s in sales" :key="s.id" class="border-t">
                                <td class="whitespace-nowrap px-5 py-3">{{ dateOnly(s.contract_date) }}</td>
                                <td class="px-5 py-3">{{ s.customer?.name }}</td>
                                <td class="whitespace-nowrap px-5 py-3 tabular-nums">
                                    {{
                                        money(
                                            Math.max(
                                                0,
                                                Number(s.unit?.list_price ?? 0) - Number(s.discount ?? 0),
                                            ),
                                            s.currency || baseCurrency.value,
                                        )
                                    }}
                                </td>
                            </tr>
                            <tr v-if="!sales.length">
                                <td colspan="3" class="px-5 py-8 text-center text-muted-foreground">
                                    {{ t('No sales in this period.') }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="rounded-2xl border bg-card">
                <div class="border-b px-5 py-4">
                    <h2 class="font-medium">{{ t('New expenses & salaries') }}</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-muted/30 text-start text-muted-foreground">
                            <tr>
                                <th class="whitespace-nowrap px-5 py-3">{{ t('Date') }}</th>
                                <th class="whitespace-nowrap px-5 py-3">{{ t('Category') }}</th>
                                <th class="px-5 py-3">{{ t('Description') }}</th>
                                <th class="whitespace-nowrap px-5 py-3">{{ t('Amount') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="e in expenses" :key="e.id" class="border-t">
                                <td class="whitespace-nowrap px-5 py-3">{{ dateOnly(e.expense_date) }}</td>
                                <td class="whitespace-nowrap px-5 py-3">
                                    {{ e.category?.name || t('General') }}
                                </td>
                                <td class="px-5 py-3">{{ e.description }}</td>
                                <td class="whitespace-nowrap px-5 py-3 tabular-nums">
                                    {{ money(e.amount, e.currency || baseCurrency.value) }}
                                </td>
                            </tr>
                            <tr v-if="!expenses.length">
                                <td colspan="4" class="px-5 py-8 text-center text-muted-foreground">
                                    {{ t('No expenses in this period.') }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div v-if="!restricted && activity.length" class="rounded-2xl border bg-card">
            <div class="border-b px-5 py-4">
                <h2 class="font-medium">{{ t('Project activity') }}</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-muted/30 text-start text-muted-foreground">
                        <tr>
                            <th class="whitespace-nowrap px-5 py-3">{{ t('Date') }}</th>
                            <th class="whitespace-nowrap px-5 py-3">{{ t('Project') }}</th>
                            <th class="px-5 py-3">{{ t('Status change') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="row in activity" :key="row.id" class="border-t">
                            <td class="whitespace-nowrap px-5 py-3">{{ dateOnly(row.created_at) }}</td>
                            <td class="px-5 py-3">{{ row.subject?.name || t('Project') }}</td>
                            <td class="px-5 py-3">{{ row.from_status }} → {{ row.to_status }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>
