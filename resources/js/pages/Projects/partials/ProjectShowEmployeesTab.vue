<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import { Eye, Trash2 } from '@lucide/vue';
import Can from '@/components/Can.vue';
import { computed } from 'vue';
import SortableTh from '@/components/SortableTh.vue';
import TableFrame from '@/components/TableFrame.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import { useCalendar } from '@/composables/useCalendar';
import { useCurrency } from '@/composables/useCurrency';
import { provideTableSort } from '@/composables/useTableSort';
import { useTranslations } from '@/composables/useTranslations';
import { money } from '@/lib/utils';

const props = defineProps<{
    item: Record<string, any>;
    employees: {
        id: number;
        employee_no?: string;
        name: string;
        department?: string;
        designation?: string;
        phone?: string;
        basic_salary?: number | null;
        currency?: string;
        status?: string;
        started_at?: string | null;
        role_on_project?: string | null;
        working_hours?: string | null;
        notes?: string | null;
    }[];
}>();

const emit = defineEmits<{
    'add-employee': [];
}>();

const { t } = useTranslations();
const tableColumns = computed(() => [
    { key: 'employee_no', label: t('Employee No') },
    { key: 'name', label: t('Name') },
    { key: 'role_on_project', label: t('Role on project') },
    { key: 'started_at', label: t('Started on project') },
    { key: 'working_hours', label: t('Working hours') },
    { key: 'department', label: t('Department') },
    { key: 'basic_salary', label: t('Basic Salary') },
    { key: 'status', label: t('Status') },
    { key: 'notes', label: t('Notes') },
    { key: 'actions', label: t('Actions'), locked: true },
]);
const { sortedRows } = provideTableSort(() => props.employees, {
    defaultKey: 'started_at',
    defaultDir: 'desc',
});
const { baseCurrency } = useCurrency();
const calendar = useCalendar();
const formatDate = (value: string | null | undefined) =>
    calendar.format(value) || '—';

function removeEmployee(employeeId: number) {
    if (!window.confirm(t('Remove this employee from the project?'))) {
        return;
    }

    router.delete(`/projects/${props.item.id}/employees/${employeeId}`, {
        preserveScroll: true,
    });
}
</script>

<template>
    <div class="overflow-hidden rounded-xl border bg-card shadow-sm">
        <div
            class="flex flex-wrap items-center justify-between gap-3 border-b px-5 py-4"
        >
            <div>
                <h3 class="text-sm font-semibold">
                    {{ t('Employees') }}
                </h3>
                <p class="mt-0.5 text-sm text-muted-foreground">
                    {{ t('Staff assigned to this project') }}
                </p>
            </div>
            <Can permission="employees.edit">
                <Button size="sm" @click="emit('add-employee')">
                    {{ t('Add employee') }}
                </Button>
            </Can>
        </div>

        <TableFrame table-id="project-employees" :columns="tableColumns">
            <table class="min-w-full text-sm">
                <thead class="bg-muted/40 text-start text-muted-foreground">
                    <tr>
                        <SortableTh column="employee_no">
                            {{ t('Employee No') }}
                        </SortableTh>
                        <SortableTh column="name">
                            {{ t('Name') }}
                        </SortableTh>
                        <SortableTh column="role_on_project">
                            {{ t('Role on project') }}
                        </SortableTh>
                        <SortableTh column="started_at">
                            {{ t('Started on project') }}
                        </SortableTh>
                        <SortableTh column="working_hours">
                            {{ t('Working hours') }}
                        </SortableTh>
                        <SortableTh column="department">
                            {{ t('Department') }}
                        </SortableTh>
                        <SortableTh column="basic_salary" align="end">
                            {{ t('Basic Salary') }}
                        </SortableTh>
                        <SortableTh column="status">
                            {{ t('Status') }}
                        </SortableTh>
                        <th class="px-4 py-3 font-medium">
                            {{ t('Notes') }}
                        </th>
                        <th class="px-4 py-3 text-end font-medium">
                            {{ t('Actions') }}
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="employee in sortedRows"
                        :key="employee.id"
                        class="border-t hover:bg-muted/30"
                    >
                        <td class="px-4 py-3 font-medium">
                            {{ employee.employee_no || '—' }}
                        </td>
                        <td class="px-4 py-3">
                            <Link
                                :href="`/employees/${employee.id}`"
                                class="font-medium hover:text-primary hover:underline"
                            >
                                {{ employee.name }}
                            </Link>
                            <p v-if="employee.phone" class="mt-0.5 text-xs text-muted-foreground">
                                {{ employee.phone }}
                            </p>
                        </td>
                        <td class="px-4 py-3">
                            {{ employee.role_on_project || employee.designation || '—' }}
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            {{ formatDate(employee.started_at) }}
                        </td>
                        <td class="px-4 py-3">{{ employee.working_hours || '—' }}</td>
                        <td class="px-4 py-3">{{ employee.department || '—' }}</td>
                        <td class="px-4 py-3 text-end tabular-nums">
                            {{
                                employee.basic_salary != null
                                    ? money(
                                          employee.basic_salary,
                                          employee.currency || baseCurrency,
                                      )
                                    : '—'
                            }}
                        </td>
                        <td class="px-4 py-3">
                            <StatusBadge :status="employee.status" />
                        </td>
                        <td class="px-4 py-3 max-w-[220px] truncate" :title="employee.notes || undefined">
                            {{ employee.notes || '—' }}
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex justify-end gap-2">
                                <Button as-child size="sm" variant="outline">
                                    <Link :href="`/employees/${employee.id}`">
                                        <Eye class="size-4" />
                                        {{ t('View') }}
                                    </Link>
                                </Button>
                                <Can permission="employees.edit">
                                    <Button
                                        size="sm"
                                        variant="outline"
                                        class="text-destructive hover:text-destructive"
                                        @click="removeEmployee(employee.id)"
                                    >
                                        <Trash2 class="size-4" />
                                        {{ t('Remove') }}
                                    </Button>
                                </Can>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="!employees.length">
                        <td
                            colspan="10"
                            class="px-4 py-10 text-center text-muted-foreground"
                        >
                            {{ t('No employees assigned yet') }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </TableFrame>
    </div>
</template>
