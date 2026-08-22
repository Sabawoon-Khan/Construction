<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Eye, Printer, Trash2 } from '@lucide/vue';
import { computed, ref } from 'vue';
import EmptyState from '@/components/EmptyState.vue';
import SortableTh from '@/components/SortableTh.vue';
import TableFrame from '@/components/TableFrame.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { MisFilterBar, MisSearchInput, MisStatCard, MisStatGrid } from '@/components/mis';
import Pagination from '@/components/Pagination.vue';
import RowActionsMenu from '@/components/RowActionsMenu.vue';
import { useCalendar } from '@/composables/useCalendar';
import { useCurrency } from '@/composables/useCurrency';
import { useMisPage } from '@/composables/useMisPage';
import { provideTableSort } from '@/composables/useTableSort';
import { useTranslations } from '@/composables/useTranslations';
import type { RowActionItem } from '@/lib/row-actions';
import { money, moneyByCurrency } from '@/lib/utils';

type Item = Record<string, any>;

const props = defineProps<{
    items: { data: Item[]; links: any[]; meta?: any };
    filters: { search?: string; employee_id?: number | null; month?: number | null; year?: number | null };
    totals: { count: number; basic_total: number; basic_total_base: number; bonus_total: number; bonus_total_base: number; deduction_total: number; deduction_total_base: number; net_total: number; net_total_base: number; basic_by_currency?: Record<string, number>; bonus_by_currency?: Record<string, number>; deduction_by_currency?: Record<string, number>; net_by_currency?: Record<string, number> };
    employees: { id: number; name: string; employee_no: string; basic_salary: number | null; currency?: string }[];
}>();

const search = ref(props.filters.search ?? '');
const employeeId = ref(props.filters.employee_id ?? '');
const month = ref(props.filters.month ?? '');
const year = ref(props.filters.year ?? new Date().getFullYear());
const { sortedRows } = provideTableSort(() => props.items.data);
const { t } = useTranslations();
const tableColumns = computed(() => [
    { key: 'employee.name', label: t('Employee') },
    { key: 'year', label: t('Period') },
    { key: 'basic_salary', label: t('Basic Salary') },
    { key: 'bonus', label: t('Bonus') },
    { key: 'deduction', label: t('Deduction') },
    { key: 'net_salary', label: t('Net Salary') },
    { key: 'status', label: t('Status') },
    { key: 'creator.name', label: t('Added by') },
    { key: 'actions', label: t('Actions'), locked: true },
]);
const { baseCurrency } = useCurrency();
const { months, digits } = useCalendar('gregorian');
const { editAction, viewAction, can } = useMisPage();

const monthOptions = computed(() =>
    months().map((label, index) => ({
        value: String(index + 1),
        label,
    })),
);

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Salaries', href: '/salaries' },
        ],
    },
});

function applyFilters() {
    router.get('/salaries', {
        search: search.value || undefined,
        employee_id: employeeId.value || undefined,
        month: month.value || undefined,
        year: year.value || undefined,
    }, { preserveState: true, replace: true });
}

function clearFilters() {
    search.value = '';
    employeeId.value = '';
    month.value = '';
    year.value = new Date().getFullYear();
    applyFilters();
}

function formatPeriod(item: Item): string {
    const monthIndex = Number(item.month) - 1;
    const monthLabel = months()[monthIndex] ?? String(item.month);
    return `${monthLabel} ${digits(item.year)}`;
}

function formatMoney(value: unknown, currency?: string | null): string {
    return money(value, currency || baseCurrency);
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
                description: t('Are you sure you want to delete this salary record? This action cannot be undone.'),
                confirmLabel: t('Delete'),
            },
        },
    ];
}
</script>

<template>
    <Head :title="t('Salaries')" />

    <div class="flex h-full flex-1 flex-col gap-4">
        <MisFilterBar :apply-label="t('Apply')" :clear-label="t('All')" @apply="applyFilters" @clear="clearFilters">
            <MisSearchInput v-model="search" class="min-w-[220px] flex-1" :placeholder="t('Search employees...')" />
            <select v-model="employeeId" class="h-9 min-w-[180px] rounded-md border border-input bg-background px-3 text-sm">
                <option value="">{{ t('All employees') }}</option>
                <option v-for="employee in employees" :key="employee.id" :value="String(employee.id)">{{ employee.name }}</option>
            </select>
            <select v-model="month" class="h-9 min-w-[150px] rounded-md border border-input bg-background px-3 text-sm">
                <option value="">{{ t('All months') }}</option>
                <option v-for="option in monthOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
            </select>
            <input v-model="year" type="number" class="h-9 w-[100px] rounded-md border border-input bg-background px-3 text-sm tabular-nums" :placeholder="t('Year')" />
        </MisFilterBar>

        <MisStatGrid :columns="4">
            <MisStatCard :label="t('Records')" :value="totals.count" tone="muted" />
            <MisStatCard
                :label="t('Basic total')"
                :value="moneyByCurrency(totals.basic_by_currency, totals.basic_total)"
            />
            <MisStatCard
                :label="t('Net total')"
                :value="moneyByCurrency(totals.net_by_currency, totals.net_total)"
                class="border-primary/20 bg-primary/[0.03] dark:bg-primary/[0.06]"
            />
            <MisStatCard :label="t('Adjustments')" :show-value="false">
                <div class="mt-2 space-y-2">
                    <div class="flex items-center justify-between gap-4">
                        <span class="text-xs text-muted-foreground">{{ t('Bonus') }}</span>
                        <span class="text-lg font-semibold tabular-nums text-emerald-600 dark:text-emerald-400">
                            {{ moneyByCurrency(totals.bonus_by_currency, totals.bonus_total) }}
                        </span>
                    </div>
                    <div class="flex items-center justify-between gap-4">
                        <span class="text-xs text-muted-foreground">{{ t('Deduction') }}</span>
                        <span class="text-lg font-semibold tabular-nums text-rose-600 dark:text-rose-400">
                            {{ moneyByCurrency(totals.deduction_by_currency, totals.deduction_total) }}
                        </span>
                    </div>
                </div>
            </MisStatCard>
        </MisStatGrid>

        <div class="overflow-hidden rounded-2xl border border-border/80 bg-card shadow-soft">
            <TableFrame table-id="salaries" :columns="tableColumns">
                <table class="min-w-full text-sm">
                    <thead class="bg-muted/30 text-start text-muted-foreground">
                        <tr>
                            <SortableTh column="employee.name">{{ t('Employee') }}</SortableTh>
                            <SortableTh column="year">{{ t('Period') }}</SortableTh>
                            <SortableTh column="basic_salary" align="end">{{ t('Basic Salary') }}</SortableTh>
                            <SortableTh column="bonus" align="end">{{ t('Bonus') }}</SortableTh>
                            <SortableTh column="deduction" align="end">{{ t('Deduction') }}</SortableTh>
                            <SortableTh column="net_salary" align="end">{{ t('Net Salary') }}</SortableTh>
                            <SortableTh column="status">{{ t('Status') }}</SortableTh>
                            <SortableTh column="creator.name">{{ t('Added by') }}</SortableTh>
                            <th class="px-4 py-3 font-medium text-end">{{ t('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="item in sortedRows"
                            :key="item.id"
                            class="border-b last:border-0 transition-colors hover:bg-muted/30"
                        >
                            <td class="px-4 py-3">
                                <Link v-if="item.employee" :href="`/salaries/${item.id}`" class="font-medium hover:text-primary hover:underline">
                                    {{ item.employee.name }}
                                </Link>
                                <span v-else>—</span>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">{{ formatPeriod(item) }}</td>
                            <td class="px-4 py-3 text-end tabular-nums">{{ formatMoney(item.basic_salary, item.currency) }}</td>
                            <td class="px-4 py-3 text-end tabular-nums text-emerald-700 dark:text-emerald-400">
                                {{ Number(item.bonus) > 0 ? formatMoney(item.bonus, item.currency) : '—' }}
                            </td>
                            <td class="px-4 py-3 text-end tabular-nums text-amber-700 dark:text-amber-400">
                                {{ Number(item.deduction) > 0 ? formatMoney(item.deduction, item.currency) : '—' }}
                            </td>
                            <td class="px-4 py-3 text-end tabular-nums font-semibold">{{ formatMoney(item.net_salary, item.currency) }}</td>
                            <td class="px-4 py-3">
                                <StatusBadge :status="item.status" />
                            </td>
                            <td class="px-4 py-3 text-muted-foreground">{{ item.creator?.name || '—' }}</td>
                            <td class="px-4 py-3">
                                <div class="flex justify-end">
                                    <RowActionsMenu :actions="rowActions(item)" />
                                </div>
                            </td>
                        </tr>
                        <EmptyState
                            v-if="!items.data.length"
                            :colspan="9"
                            :title="t('No salary records')"
                        />
                    </tbody>
                </table>
            </TableFrame>
            <Pagination :paginator="items" />
        </div>
    </div>
</template>
