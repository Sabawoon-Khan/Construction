<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import { Eye, Trash2 } from '@lucide/vue';
import { computed, ref } from 'vue';
import Can from '@/components/Can.vue';
import { MisTableActions } from '@/components/mis';
import type { ExportCell } from '@/composables/useTableExport';
import ConfirmActionDialog from '@/components/ConfirmActionDialog.vue';
import EmptyState from '@/components/EmptyState.vue';
import SortableTh from '@/components/SortableTh.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import TableFrame from '@/components/TableFrame.vue';
import TableIndexTd from '@/components/TableIndexTd.vue';
import TableIndexTh from '@/components/TableIndexTh.vue';
import { Button } from '@/components/ui/button';
import { CreateButton } from '@/components/ui/create-button';
import { V2Panel } from '@/components/v2';
import { indexTableColumn } from '@/composables/useTableColumns';
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
        created_at?: string;
    }[];
}>();

const emit = defineEmits<{
    'add-employee': [];
}>();

const { t } = useTranslations();
const tableColumns = computed(() => [
    indexTableColumn(),
    { key: 'employee_no', label: t('Employee No') },
    { key: 'name', label: t('Name') },
    { key: 'role_on_project', label: t('Role on project') },
    { key: 'started_at', label: t('Started on project') },
    { key: 'working_hours', label: t('Working hours') },
    { key: 'department', label: t('Department') },
    { key: 'basic_salary', label: t('Basic Salary') },
    { key: 'status', label: t('Status') },
    { key: 'notes', label: t('Notes') },
    { key: 'created_at', label: t('Created at') },
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

const confirmRemoveOpen = ref(false);
const removingId = ref<number | null>(null);
const pendingRemoveId = ref<number | null>(null);

function requestRemoveEmployee(employeeId: number) {
    pendingRemoveId.value = employeeId;
    confirmRemoveOpen.value = true;
}

function removeEmployee() {
    const employeeId = pendingRemoveId.value;
    if (!employeeId) {
        return;
    }

    removingId.value = employeeId;
    router.delete(`/projects/${props.item.id}/employees/${employeeId}`, {
        preserveScroll: true,
        onFinish: () => {
            removingId.value = null;
        },
        onSuccess: () => {
            confirmRemoveOpen.value = false;
            pendingRemoveId.value = null;
        },
    });
}

const employeeExportHeaders = computed(() => [
    t('Employee No'),
    t('Name'),
    t('Role on project'),
    t('Started on project'),
    t('Working hours'),
    t('Department'),
    t('Basic Salary'),
    t('Status'),
    t('Notes'),
    t('Created at'),
]);

function mapEmployeeExportRow(
    employee: (typeof props.employees)[number],
): ExportCell[] {
    return [
        employee.employee_no || null,
        employee.name,
        employee.role_on_project || null,
        formatDate(employee.started_at),
        employee.working_hours || null,
        employee.department || null,
        employee.basic_salary ?? null,
        employee.status || null,
        employee.notes || null,
        formatDate(employee.created_at),
    ];
}

const employeeExportRows = computed(() =>
    props.employees.map(mapEmployeeExportRow),
);
const employeeFilteredExportRows = computed(() =>
    sortedRows.value.map(mapEmployeeExportRow),
);
</script>

<template>
    <V2Panel
        :padded="false"
        :title="t('Employees')"
        :description="t('Staff assigned to this project')"
    >
        <template #actions>
            
            <MisTableActions
                :filename="`project-${item.id}-employees`"
                :headers="employeeExportHeaders"
                :rows="employeeExportRows"
                :filtered-rows="employeeFilteredExportRows"
            >
                <CreateButton
                permission="employees.edit"
                @click="emit('add-employee')"
            >
                {{ t('Add employee') }}
            </CreateButton>
            </MisTableActions>
        
        </template>

        <TableFrame table-id="project-employees" :columns="tableColumns">
            <table class="min-w-full text-sm">
                <thead class="table-chrome-head text-start text-muted-foreground">
                    <tr>
                        <TableIndexTh />
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
                        <SortableTh column="created_at">{{
                            t('Created at')
                        }}</SortableTh>
                        <th class="px-4 py-3 text-end font-medium">
                            {{ t('Actions') }}
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="(employee, index) in sortedRows"
                        :key="employee.id"
                        class="border-t hover:bg-muted/30"
                    >
                        <TableIndexTd :index="index" />
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
                            <p
                                v-if="employee.phone"
                                class="mt-0.5 text-xs text-muted-foreground"
                            >
                                {{ employee.phone }}
                            </p>
                        </td>
                        <td class="px-4 py-3">
                            {{
                                employee.role_on_project ||
                                employee.designation ||
                                '—'
                            }}
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            {{ formatDate(employee.started_at) }}
                        </td>
                        <td class="px-4 py-3">
                            {{ employee.working_hours || '—' }}
                        </td>
                        <td class="px-4 py-3">
                            {{ employee.department || '—' }}
                        </td>
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
                        <td
                            class="max-w-[220px] truncate px-4 py-3"
                            :title="employee.notes || undefined"
                        >
                            {{ employee.notes || '—' }}
                        </td>
                        <td
                            class="px-4 py-3 whitespace-nowrap text-muted-foreground"
                        >
                            {{ formatDate(employee.created_at) }}
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
                                        :disabled="removingId === employee.id"
                                        @click="
                                            requestRemoveEmployee(employee.id)
                                        "
                                    >
                                        <Trash2 class="size-4" />
                                        {{ t('Remove') }}
                                    </Button>
                                </Can>
                            </div>
                        </td>
                    </tr>
                    <EmptyState
                        v-if="!employees.length"
                        :colspan="12"
                        :title="t('No employees assigned yet')"
                    >
                        </EmptyState>
                </tbody>
            </table>
        </TableFrame>
    </V2Panel>

    <ConfirmActionDialog
        v-model:open="confirmRemoveOpen"
        :title="t('Remove employee from project?')"
        :description="t('Remove this employee from the project?')"
        :confirm-label="t('Remove')"
        :processing="removingId !== null"
        @confirm="removeEmployee"
    />
</template>
