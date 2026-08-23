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
import { computed, ref } from 'vue';
import SortableTh from '@/components/SortableTh.vue';
import TableFrame from '@/components/TableFrame.vue';
import TableIndexTd from '@/components/TableIndexTd.vue';
import TableIndexTh from '@/components/TableIndexTh.vue';
import { indexTableColumn } from '@/composables/useTableColumns';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { useCalendar } from '@/composables/useCalendar';
import { useCurrency } from '@/composables/useCurrency';
import { useTableSort } from '@/composables/useTableSort';
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
const formatDate = (value: string | null | undefined) => calendar.format(value) || '—';

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
                <TableFrame table-id="reports-category" :columns="tableColumnsCategory">
                    <table class="min-w-full text-sm">
                        <thead class="bg-muted/30 text-start text-muted-foreground">
                            <tr>
                                <TableIndexTh />
                                <SortableTh class="px-5" column="category" :sort-key="categorySort.sortKey" :sort-dir="categorySort.sortDir" @sort="categorySort.sortBy">{{ t('Category') }}</SortableTh>
                                <SortableTh class="px-5" column="count" :sort-key="categorySort.sortKey" :sort-dir="categorySort.sortDir" @sort="categorySort.sortBy">{{ t('Count') }}</SortableTh>
                                <SortableTh class="px-5" column="amount_base" :sort-key="categorySort.sortKey" :sort-dir="categorySort.sortDir" @sort="categorySort.sortBy">{{ t('Amount') }}</SortableTh>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(row, index) in categorySort.sortedRows" :key="row.category" class="border-t">
                                <TableIndexTd :index="index" />
                                <td class="px-5 py-3">{{ row.category }}</td>
                                <td class="px-5 py-3">{{ row.count }}</td>
                                <td class="px-5 py-3 tabular-nums">
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
            </div>

            <div class="rounded-2xl border bg-card">
                <div class="border-b px-5 py-4">
                    <h2 class="font-medium">{{ t('Shareholder distribution') }}</h2>
                </div>
                <TableFrame table-id="reports-partners" :columns="tableColumnsPartners">
                    <table class="min-w-full text-sm">
                        <thead class="bg-muted/30 text-start text-muted-foreground">
                            <tr>
                                <TableIndexTh />
                                <SortableTh class="px-5" column="name" :sort-key="partnerSort.sortKey" :sort-dir="partnerSort.sortDir" @sort="partnerSort.sortBy">{{ t('Shareholder') }}</SortableTh>
                                <SortableTh class="px-5" column="project_name" :sort-key="partnerSort.sortKey" :sort-dir="partnerSort.sortDir" @sort="partnerSort.sortBy">{{ t('Project') }}</SortableTh>
                                <SortableTh class="px-5" column="ownership_percentage" :sort-key="partnerSort.sortKey" :sort-dir="partnerSort.sortDir" @sort="partnerSort.sortBy">{{ t('Share %') }}</SortableTh>
                                <SortableTh class="px-5" column="entitled_share" :sort-key="partnerSort.sortKey" :sort-dir="partnerSort.sortDir" @sort="partnerSort.sortBy">{{ t('Entitled') }}</SortableTh>
                                <SortableTh class="px-5" column="paid_total" :sort-key="partnerSort.sortKey" :sort-dir="partnerSort.sortDir" @sort="partnerSort.sortBy">{{ t('Paid') }}</SortableTh>
                                <SortableTh class="px-5" column="balance_owed" :sort-key="partnerSort.sortKey" :sort-dir="partnerSort.sortDir" @sort="partnerSort.sortBy">{{ t('Balance') }}</SortableTh>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="(p, index) in partnerSort.sortedRows"
                                :key="`${p.id}-${p.project_name}`"
                                class="border-t"
                            >
                                <TableIndexTd :index="index" />
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
                                <td colspan="7" class="px-5 py-8 text-center text-muted-foreground">
                                    {{ t('No shareholders in this period.') }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </TableFrame>
            </div>
        </div>

        <div v-if="!restricted" class="grid gap-4 lg:grid-cols-2">
            <div class="rounded-2xl border bg-card">
                <div class="border-b px-5 py-4">
                    <h2 class="font-medium">{{ t('New sales') }}</h2>
                </div>
                <TableFrame table-id="reports-sales" :columns="tableColumnsSales">
                    <table class="min-w-full text-sm">
                        <thead class="bg-muted/30 text-start text-muted-foreground">
                            <tr>
                                <TableIndexTh />
                                <SortableTh class="px-5" column="contract_date" :sort-key="salesSort.sortKey" :sort-dir="salesSort.sortDir" @sort="salesSort.sortBy">{{ t('Date') }}</SortableTh>
                                <SortableTh class="px-5" column="customer.name" :sort-key="salesSort.sortKey" :sort-dir="salesSort.sortDir" @sort="salesSort.sortBy">{{ t('Buyer') }}</SortableTh>
                                <SortableTh class="px-5" column="discount" :sort-key="salesSort.sortKey" :sort-dir="salesSort.sortDir" @sort="salesSort.sortBy">{{ t('Amount') }}</SortableTh>
                                <SortableTh class="px-5" column="created_at" :sort-key="salesSort.sortKey" :sort-dir="salesSort.sortDir" @sort="salesSort.sortBy">{{ t('Created at') }}</SortableTh>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(s, index) in salesSort.sortedRows" :key="s.id" class="border-t">
                                <TableIndexTd :index="index" />
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
                                <td class="whitespace-nowrap px-5 py-3 text-muted-foreground">{{ formatDate(s.created_at) }}</td>
                            </tr>
                            <tr v-if="!sales.length">
                                <td colspan="5" class="px-5 py-8 text-center text-muted-foreground">
                                    {{ t('No sales in this period.') }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </TableFrame>
            </div>

            <div class="rounded-2xl border bg-card">
                <div class="border-b px-5 py-4">
                    <h2 class="font-medium">{{ t('New expenses & salaries') }}</h2>
                </div>
                <TableFrame table-id="reports-expenses" :columns="tableColumnsExpenses">
                    <table class="min-w-full text-sm">
                        <thead class="bg-muted/30 text-start text-muted-foreground">
                            <tr>
                                <TableIndexTh />
                                <SortableTh class="px-5" column="expense_date" :sort-key="expenseSort.sortKey" :sort-dir="expenseSort.sortDir" @sort="expenseSort.sortBy">{{ t('Date') }}</SortableTh>
                                <SortableTh class="px-5" column="category.name" :sort-key="expenseSort.sortKey" :sort-dir="expenseSort.sortDir" @sort="expenseSort.sortBy">{{ t('Category') }}</SortableTh>
                                <SortableTh class="px-5" column="description" :sort-key="expenseSort.sortKey" :sort-dir="expenseSort.sortDir" @sort="expenseSort.sortBy">{{ t('Description') }}</SortableTh>
                                <SortableTh class="px-5" column="amount" :sort-key="expenseSort.sortKey" :sort-dir="expenseSort.sortDir" @sort="expenseSort.sortBy">{{ t('Amount') }}</SortableTh>
                                <SortableTh class="px-5" column="created_at" :sort-key="expenseSort.sortKey" :sort-dir="expenseSort.sortDir" @sort="expenseSort.sortBy">{{ t('Created at') }}</SortableTh>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(e, index) in expenseSort.sortedRows" :key="e.id" class="border-t">
                                <TableIndexTd :index="index" />
                                <td class="whitespace-nowrap px-5 py-3">{{ dateOnly(e.expense_date) }}</td>
                                <td class="whitespace-nowrap px-5 py-3">
                                    {{ e.category?.name || t('General') }}
                                </td>
                                <td class="px-5 py-3">{{ e.description }}</td>
                                <td class="whitespace-nowrap px-5 py-3 tabular-nums">
                                    {{ money(e.amount, e.currency || baseCurrency.value) }}
                                </td>
                                <td class="whitespace-nowrap px-5 py-3 text-muted-foreground">{{ formatDate(e.created_at) }}</td>
                            </tr>
                            <tr v-if="!expenses.length">
                                <td colspan="6" class="px-5 py-8 text-center text-muted-foreground">
                                    {{ t('No expenses in this period.') }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </TableFrame>
            </div>
        </div>

        <div v-if="!restricted && activity.length" class="rounded-2xl border bg-card">
            <div class="border-b px-5 py-4">
                <h2 class="font-medium">{{ t('Project activity') }}</h2>
            </div>
            <TableFrame table-id="reports-activity" :columns="tableColumnsActivity">
                <table class="min-w-full text-sm">
                    <thead class="bg-muted/30 text-start text-muted-foreground">
                        <tr>
                            <TableIndexTh />
                            <SortableTh class="px-5" column="created_at" :sort-key="activitySort.sortKey" :sort-dir="activitySort.sortDir" @sort="activitySort.sortBy">{{ t('Date') }}</SortableTh>
                            <SortableTh class="px-5" column="subject.name" :sort-key="activitySort.sortKey" :sort-dir="activitySort.sortDir" @sort="activitySort.sortBy">{{ t('Project') }}</SortableTh>
                            <SortableTh class="px-5" column="to_status" :sort-key="activitySort.sortKey" :sort-dir="activitySort.sortDir" @sort="activitySort.sortBy">{{ t('Status change') }}</SortableTh>
                            <th class="px-5 py-3 font-medium whitespace-nowrap">{{ t('Created at') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(row, index) in activitySort.sortedRows" :key="row.id" class="border-t">
                            <TableIndexTd :index="index" />
                            <td class="whitespace-nowrap px-5 py-3">{{ dateOnly(row.created_at) }}</td>
                            <td class="px-5 py-3">{{ row.subject?.name || t('Project') }}</td>
                            <td class="px-5 py-3">{{ row.from_status }} → {{ row.to_status }}</td>
                            <td class="whitespace-nowrap px-5 py-3 text-muted-foreground">{{ formatDate(row.created_at) }}</td>
                        </tr>
                    </tbody>
                </table>
            </TableFrame>
        </div>
    </div>
</template>
