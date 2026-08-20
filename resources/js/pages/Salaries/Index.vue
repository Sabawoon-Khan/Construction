<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Trash2 } from '@lucide/vue';
import { ref } from 'vue';
import EmptyState from '@/components/EmptyState.vue';
import { MisFilterBar, MisSearchInput, MisStatCard, MisStatGrid } from '@/components/mis';
import Pagination from '@/components/Pagination.vue';
import RowActionsMenu from '@/components/RowActionsMenu.vue';
import { useCurrency } from '@/composables/useCurrency';
import { useMisPage } from '@/composables/useMisPage';
import { useTranslations } from '@/composables/useTranslations';
import type { RowActionItem } from '@/lib/row-actions';
import { translateStatus } from '@/lib/translateStatus';
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
const { t } = useTranslations();
const { baseCurrency } = useCurrency();
const { editAction, can } = useMisPage();

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

function rowActions(item: Item): RowActionItem[] {
    return [
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
            <MisSearchInput v-model="search" class="min-w-[180px] flex-1" :placeholder="t('Search...')" />
            <select v-model="employeeId" class="h-9 min-w-[160px] rounded-md border border-input bg-background px-3 text-sm">
                <option value="">{{ t('All employees') }}</option>
                <option v-for="employee in employees" :key="employee.id" :value="String(employee.id)">{{ employee.name }}</option>
            </select>
            <select v-model="month" class="h-9 w-[130px] rounded-md border border-input bg-background px-3 text-sm">
                <option value="">{{ t('All months') }}</option>
                <option v-for="m in 12" :key="m" :value="String(m)">{{ m }}</option>
            </select>
            <input v-model="year" type="number" class="h-9 w-[100px] rounded-md border border-input bg-background px-3 text-sm" :placeholder="t('Year')" />
        </MisFilterBar>

        <MisStatGrid :columns="5">
            <MisStatCard :label="t('Records')" :value="totals.count" />
            <MisStatCard :label="t('Basic total')" :value="moneyByCurrency(totals.basic_by_currency, totals.basic_total)" />
            <MisStatCard :label="t('Bonus total')" :value="moneyByCurrency(totals.bonus_by_currency, totals.bonus_total)" tone="success" />
            <MisStatCard :label="t('Deduction total')" :value="moneyByCurrency(totals.deduction_by_currency, totals.deduction_total)" tone="warning" />
            <MisStatCard :label="t('Net total')" :value="moneyByCurrency(totals.net_by_currency, totals.net_total)" />
        </MisStatGrid>

        <div class="overflow-hidden rounded-2xl border border-border/80 bg-card shadow-soft">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-muted/30 text-start text-muted-foreground">
                        <tr>
                            <th class="px-4 py-3 font-medium">{{ t('Employee') }}</th>
                            <th class="px-4 py-3 font-medium">{{ t('Month') }}</th>
                            <th class="px-4 py-3 font-medium">{{ t('Year') }}</th>
                            <th class="px-4 py-3 font-medium">{{ t('Basic Salary') }}</th>
                            <th class="px-4 py-3 font-medium">{{ t('Bonus') }}</th>
                            <th class="px-4 py-3 font-medium">{{ t('Deduction') }}</th>
                            <th class="px-4 py-3 font-medium">{{ t('Net Salary') }}</th>
                            <th class="px-4 py-3 font-medium">{{ t('Status') }}</th>
                            <th class="px-4 py-3 font-medium">{{ t('Added by') }}</th>

                            <th class="px-4 py-3 font-medium text-end">{{ t('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="item in items.data"
                            :key="item.id"
                            class="border-b last:border-0 transition-colors hover:bg-muted/30"
                        >
                            <td class="px-4 py-3">
                                <Link v-if="item.employee" :href="`/employees/${item.employee.id}`" class="hover:text-primary hover:underline">
                                    {{ item.employee.name }}
                                </Link>
                                <span v-else>—</span>
                            </td>
                            <td class="px-4 py-3">{{ item.month }}</td>
                            <td class="px-4 py-3">{{ item.year }}</td>
                            <td class="px-4 py-3 tabular-nums">{{ money(item.basic_salary, item.currency || baseCurrency) }}</td>
                            <td class="px-4 py-3 tabular-nums">{{ money(item.bonus, item.currency || baseCurrency) }}</td>
                            <td class="px-4 py-3 tabular-nums">{{ money(item.deduction, item.currency || baseCurrency) }}</td>
                            <td class="px-4 py-3 tabular-nums font-medium">{{ money(item.net_salary, item.currency || baseCurrency) }}</td>
                            <td class="px-4 py-3">{{ translateStatus(item.status, t) }}</td>
                            <td class="px-4 py-3 text-muted-foreground">{{ item.creator?.name || '—' }}</td>

                            <td class="px-4 py-3">
                                <div class="flex justify-end">
                                    <RowActionsMenu :actions="rowActions(item)" />
                                </div>
                            </td>
                        </tr>
                        <EmptyState
                            v-if="!items.data.length"
                            :colspan="10"
                            :title="t('No salary records')"
                        />
                    </tbody>
                </table>
            </div>
            <Pagination :paginator="items" />
        </div>
    </div>
</template>
