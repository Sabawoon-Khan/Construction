<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Trash2 } from '@lucide/vue';
import { ref } from 'vue';
import EmptyState from '@/components/EmptyState.vue';
import { MisFilterBar, MisSearchInput, MisStatCard, MisStatGrid } from '@/components/mis';
import Pagination from '@/components/Pagination.vue';
import RowActionsMenu from '@/components/RowActionsMenu.vue';
import { useMisPage } from '@/composables/useMisPage';
import { useTranslations } from '@/composables/useTranslations';
import type { RowActionItem } from '@/lib/row-actions';

type Item = Record<string, any>;

const props = defineProps<{
    items: { data: Item[]; links: any[]; meta?: any };
    filters: { search?: string; employee_id?: number | null; date?: string };
    totals: { count: number; present: number; absent: number; leave: number };
    employees: { id: number; name: string; employee_no: string }[];
}>();

const search = ref(props.filters.search ?? '');
const employeeId = ref(props.filters.employee_id ?? '');
const date = ref(props.filters.date ?? '');
const { t } = useTranslations();
const { editAction, can } = useMisPage();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Attendance', href: '/attendance' },
        ],
    },
});

function applyFilters() {
    router.get('/attendance', {
        search: search.value || undefined,
        employee_id: employeeId.value || undefined,
        date: date.value || undefined,
    }, { preserveState: true, replace: true });
}

function clearFilters() {
    search.value = '';
    employeeId.value = '';
    date.value = '';
    applyFilters();
}

function rowActions(item: Item): RowActionItem[] {
    return [
        editAction(`/attendance/${item.id}/edit`, 'attendance.edit'),
        { separator: true, isLabel: true, label: t('Danger') },
        {
            label: t('Delete'),
            icon: Trash2,
            method: 'delete',
            href: `/attendance/${item.id}`,
            variant: 'destructive' as const,
            hidden: !can('attendance.delete'),
            confirm: {
                title: t('Delete attendance record'),
                description: t('Are you sure you want to delete this attendance record? This action cannot be undone.'),
                confirmLabel: t('Delete'),
            },
        },
    ];
}
</script>

<template>
    <Head :title="t('Attendance')" />

    <div class="flex h-full flex-1 flex-col gap-4">
        <MisFilterBar :apply-label="t('Apply')" :clear-label="t('All')" @apply="applyFilters" @clear="clearFilters">
            <MisSearchInput v-model="search" class="min-w-[180px] flex-1" :placeholder="t('Search...')" />
            <select v-model="employeeId" class="h-9 min-w-[160px] rounded-md border border-input bg-background px-3 text-sm">
                <option value="">{{ t('All employees') }}</option>
                <option v-for="employee in employees" :key="employee.id" :value="String(employee.id)">{{ employee.name }}</option>
            </select>
            <input v-model="date" type="date" class="h-9 w-[150px] rounded-md border border-input bg-background px-3 text-sm" />
        </MisFilterBar>

        <MisStatGrid :columns="4">
            <MisStatCard :label="t('Total records')" :value="totals.count" />
            <MisStatCard :label="t('Present')" :value="totals.present" tone="success" />
            <MisStatCard :label="t('Absent')" :value="totals.absent" tone="destructive" />
            <MisStatCard :label="t('Leave')" :value="totals.leave" tone="warning" />
        </MisStatGrid>

        <div class="overflow-hidden rounded-2xl border border-border/80 bg-card shadow-soft">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-muted/30 text-left text-muted-foreground">
                        <tr>
                            <th class="px-4 py-3 font-medium">{{ t('Date') }}</th>
                            <th class="px-4 py-3 font-medium">{{ t('Employee') }}</th>
                            <th class="px-4 py-3 font-medium">{{ t('Check in') }}</th>
                            <th class="px-4 py-3 font-medium">{{ t('Check out') }}</th>
                            <th class="px-4 py-3 font-medium">{{ t('Status') }}</th>
                            <th class="px-4 py-3 font-medium">{{ t('Notes') }}</th>
                            <th class="px-4 py-3 font-medium text-right">{{ t('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="item in items.data"
                            :key="item.id"
                            class="border-b last:border-0 transition-colors hover:bg-muted/30"
                        >
                            <td class="px-4 py-3">{{ item.date || '—' }}</td>
                            <td class="px-4 py-3">
                                <Link v-if="item.employee" :href="`/employees/${item.employee.id}`" class="hover:text-primary hover:underline">
                                    {{ item.employee.name }}
                                </Link>
                                <span v-else>—</span>
                            </td>
                            <td class="px-4 py-3 tabular-nums">{{ item.check_in || '—' }}</td>
                            <td class="px-4 py-3 tabular-nums">{{ item.check_out || '—' }}</td>
                            <td class="px-4 py-3 capitalize">{{ item.status || '—' }}</td>
                            <td class="px-4 py-3">{{ item.notes || '—' }}</td>
                            <td class="px-4 py-3">
                                <div class="flex justify-end">
                                    <RowActionsMenu :actions="rowActions(item)" />
                                </div>
                            </td>
                        </tr>
                        <EmptyState
                            v-if="!items.data.length"
                            :colspan="7"
                            :title="t('No attendance records')"
                            :description="t('Record attendance to track employee presence.')"
                        />
                    </tbody>
                </table>
            </div>
            <Pagination :paginator="items" />
        </div>
    </div>
</template>
