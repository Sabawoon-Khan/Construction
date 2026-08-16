<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Search, Trash2 } from '@lucide/vue';
import { ref } from 'vue';
import EmptyState from '@/components/EmptyState.vue';
import Heading from '@/components/Heading.vue';
import { MisCreateButton, MisStatCard, MisStatGrid } from '@/components/mis';
import Pagination from '@/components/Pagination.vue';
import RowActionsMenu from '@/components/RowActionsMenu.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { useMisPage } from '@/composables/useMisPage';
import { useTranslations } from '@/composables/useTranslations';
import type { RowActionItem } from '@/lib/row-actions';
import { money } from '@/lib/utils';

type Item = Record<string, any>;

const props = defineProps<{
    items: { data: Item[]; links: any[]; meta?: any };
    filters: { employee_id?: number | null; month?: number | null; year?: number | null };
    totals: { count: number; basic_total: number; bonus_total: number; deduction_total: number; net_total: number };
    employees: { id: number; name: string; employee_no: string; basic_salary: number | null }[];
}>();

const employeeId = ref(props.filters.employee_id ?? '');
const month = ref(props.filters.month ?? '');
const year = ref(props.filters.year ?? new Date().getFullYear());
const { t } = useTranslations();
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
        employee_id: employeeId.value || undefined,
        month: month.value || undefined,
        year: year.value || undefined,
    }, { preserveState: true, replace: true });
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

    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <Heading :title="t('Salaries')" :description="t('Fixed monthly salary records for employees.')" />
            <div class="flex flex-wrap gap-2">
                <select v-model="employeeId" class="h-9 rounded-md border border-input bg-transparent px-3 text-sm" @change="applyFilters">
                    <option value="">{{ t('All employees') }}</option>
                    <option v-for="employee in employees" :key="employee.id" :value="String(employee.id)">{{ employee.name }}</option>
                </select>
                <select v-model="month" class="h-9 w-[130px] rounded-md border border-input bg-transparent px-3 text-sm" @change="applyFilters">
                    <option value="">{{ t('All months') }}</option>
                    <option v-for="m in 12" :key="m" :value="String(m)">{{ m }}</option>
                </select>
                <Input v-model="year" type="number" class="w-[100px]" :placeholder="t('Year')" @change="applyFilters" />
                <Button variant="secondary" @click="applyFilters">{{ t('Filter') }}</Button>
                <MisCreateButton href="/salaries/create" permission="salaries.create">{{ t('Add salary') }}</MisCreateButton>
            </div>
        </div>

        <MisStatGrid :columns="5">
            <MisStatCard :label="t('Records')" :value="totals.count" />
            <MisStatCard :label="t('Basic total')" :value="money(totals.basic_total)" />
            <MisStatCard :label="t('Bonus total')" :value="money(totals.bonus_total)" tone="success" />
            <MisStatCard :label="t('Deduction total')" :value="money(totals.deduction_total)" tone="warning" />
            <MisStatCard :label="t('Net total')" :value="money(totals.net_total)" />
        </MisStatGrid>

        <div class="overflow-hidden rounded-xl border border-sidebar-border/70 bg-card shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="border-b bg-muted/40 text-left text-muted-foreground">
                        <tr>
                            <th class="px-4 py-3 font-medium">{{ t('Employee') }}</th>
                            <th class="px-4 py-3 font-medium">{{ t('Month') }}</th>
                            <th class="px-4 py-3 font-medium">{{ t('Year') }}</th>
                            <th class="px-4 py-3 font-medium">{{ t('Basic Salary') }}</th>
                            <th class="px-4 py-3 font-medium">{{ t('Bonus') }}</th>
                            <th class="px-4 py-3 font-medium">{{ t('Deduction') }}</th>
                            <th class="px-4 py-3 font-medium">{{ t('Net Salary') }}</th>
                            <th class="px-4 py-3 font-medium">{{ t('Status') }}</th>
                            <th class="px-4 py-3 font-medium text-right">{{ t('Actions') }}</th>
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
                            <td class="px-4 py-3 tabular-nums">{{ money(item.basic_salary) }}</td>
                            <td class="px-4 py-3 tabular-nums">{{ money(item.bonus) }}</td>
                            <td class="px-4 py-3 tabular-nums">{{ money(item.deduction) }}</td>
                            <td class="px-4 py-3 tabular-nums font-medium">{{ money(item.net_salary) }}</td>
                            <td class="px-4 py-3 capitalize">{{ item.status || '—' }}</td>
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
                            :description="t('Add a salary record for an employee.')"
                        />
                    </tbody>
                </table>
            </div>
            <Pagination :paginator="items" />
        </div>
    </div>
</template>
