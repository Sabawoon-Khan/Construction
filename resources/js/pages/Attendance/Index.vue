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

    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <Heading :title="t('Attendance')" :description="t('Daily check-in and check-out records for employees.')" />
            <div class="flex flex-wrap gap-2">
                <div class="relative min-w-[180px] flex-1">
                    <Search class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                    <Input v-model="search" class="pl-9" :placeholder="t('Search...')" @keyup.enter="applyFilters" />
                </div>
                <select v-model="employeeId" class="h-9 rounded-md border border-input bg-transparent px-3 text-sm" @change="applyFilters">
                    <option value="">{{ t('All employees') }}</option>
                    <option v-for="employee in employees" :key="employee.id" :value="String(employee.id)">{{ employee.name }}</option>
                </select>
                <Input v-model="date" type="date" class="w-[150px]" @change="applyFilters" />
                <Button variant="secondary" @click="applyFilters">{{ t('Filter') }}</Button>
                <MisCreateButton href="/attendance/create" permission="attendance.create">{{ t('Record attendance') }}</MisCreateButton>
            </div>
        </div>

        <MisStatGrid :columns="4">
            <MisStatCard :label="t('Total records')" :value="totals.count" />
            <MisStatCard :label="t('Present')" :value="totals.present" tone="success" />
            <MisStatCard :label="t('Absent')" :value="totals.absent" tone="destructive" />
            <MisStatCard :label="t('Leave')" :value="totals.leave" tone="warning" />
        </MisStatGrid>

        <div class="overflow-hidden rounded-xl border border-sidebar-border/70 bg-card shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="border-b bg-muted/40 text-left text-muted-foreground">
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
