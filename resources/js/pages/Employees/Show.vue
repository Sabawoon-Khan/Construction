<script setup lang="ts">
import { Form, Head, Link, router } from '@inertiajs/vue3';
import {
    Briefcase,
    CalendarDays,
    FolderKanban,
    KeyRound,
    Pencil,
    Printer,
    Trash2,
    Wallet,
} from '@lucide/vue';
import { ref } from 'vue';
import Can from '@/components/Can.vue';
import ConfirmActionDialog from '@/components/ConfirmActionDialog.vue';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import RecordAttachments from '@/components/RecordAttachments.vue';
import TableIndexTd from '@/components/TableIndexTd.vue';
import TableIndexTh from '@/components/TableIndexTh.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { MisDetailGrid } from '@/components/mis';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import {
    V2DetailHero,
    V2ListPage,
    V2Panel,
    V2StatCard,
    V2StatGrid,
} from '@/components/v2';
import { useCalendar } from '@/composables/useCalendar';
import { useCurrency } from '@/composables/useCurrency';
import { useTranslations } from '@/composables/useTranslations';
import { translateStatus } from '@/lib/translateStatus';
import { money } from '@/lib/utils';
import {
    consumePendingTrashUndo,
    expectTrashUndo,
} from '@/lib/trashUndoToast';

const { baseCurrency } = useCurrency();

const calendar = useCalendar();
const formatDate = (value: string | null | undefined) => calendar.format(value) || '—';

function paymentMethodLabel(method: string | null | undefined): string {
    if (method === 'cash') {
        return t('Cash');
    }

    if (method === 'bank') {
        return t('Bank');
    }

    return '—';
}

const props = defineProps<{
    item: Record<string, any>;
    stats: { attendance_count: number; salaries_count: number; projects_count: number };
    passwordRules: string;
}>();

const { t } = useTranslations();
const deleteOpen = ref(false);
const deleting = ref(false);

function confirmDelete() {
    deleting.value = true;
    expectTrashUndo({ type: 'employees', id: props.item.id });
    router.delete(`/employees/${props.item.id}`, {
        onError: () => consumePendingTrashUndo(),
        onCancel: () => consumePendingTrashUndo(),
        onFinish: () => {
            deleting.value = false;
            deleteOpen.value = false;
        },
    });
}

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Employees', href: '/employees' },
            { title: 'Details', href: '#' },
        ],
    },
});
</script>

<template>
    <Head :title="item.name" />

    <V2ListPage>
        <V2DetailHero image="/images/dashboard-v2-building.png">
            <template #eyebrow>{{ t('Employees') }}</template>
            <template #title>{{ item.name }}</template>
            <template #description>
                {{ item.employee_no || '—' }} · {{ item.designation || '—' }} · {{ item.department || '—' }}
            </template>
            <template #actions>
                <Can permission="employees.edit">
                    <Button as-child>
                        <Link :href="`/employees/${item.id}/edit`"><Pencil class="size-4" /> {{ t('Edit') }}</Link>
                    </Button>
                </Can>
                <Can permission="employees.delete">
                    <Button type="button" variant="destructive" @click="deleteOpen = true">
                        <Trash2 class="size-4" /> {{ t('Delete') }}
                    </Button>
                </Can>
            </template>
            <template #stats>
                <V2StatGrid class="detail-stat-grid">
                    <V2StatCard
                        :delay="0"
                        :title="t('Projects')"
                        :value="stats.projects_count"
                    >
                        <template #icon><FolderKanban /></template>
                    </V2StatCard>
                    <V2StatCard
                        :delay="1"
                        icon-tone="warm"
                        :title="t('Attendance records')"
                        :value="stats.attendance_count"
                    >
                        <template #icon><CalendarDays /></template>
                    </V2StatCard>
                    <V2StatCard
                        :delay="2"
                        icon-tone="teal"
                        :title="t('Salary records')"
                        :value="stats.salaries_count"
                    >
                        <template #icon><Briefcase /></template>
                    </V2StatCard>
                    <V2StatCard
                        :delay="3"
                        accent
                        icon-tone="orange"
                        :title="t('Basic salary')"
                        :value="item.basic_salary != null ? money(item.basic_salary, item.currency) : '—'"
                    >
                        <template #icon><Wallet /></template>
                    </V2StatCard>
                </V2StatGrid>
            </template>
        </V2DetailHero>

        <div class="grid gap-6 lg:grid-cols-2">
            <V2Panel :title="t('Employee Details')">
                <MisDetailGrid
                    :columns="2"
                    :items="[
                        { label: t('Takhallus'), value: item.takhallus },
                        { label: t('Father name'), value: item.father_name },
                        { label: t('Status'), value: translateStatus(item.status, t) },
                        { label: t('Email'), value: item.email },
                        { label: t('Phone'), value: item.phone },
                        { label: t('Employment Date'), value: formatDate(item.joining_date) },
                        { label: t('Working hours'), value: item.working_hours },
                        { label: t('Basic Salary'), value: item.basic_salary != null ? money(item.basic_salary, item.currency) : '—' },
                        { label: t('Salary payment method'), value: paymentMethodLabel(item.salary_payment_method) },
                        { label: t('Notes'), value: item.notes, span: 2 },
                    ]"
                />
            </V2Panel>

            <div class="space-y-4">
                <Can permission="employees.edit">
                    <V2Panel :title="t('Login password')">
                        <div class="flex items-start gap-3">
                            <div class="rounded-lg bg-muted p-2">
                                <KeyRound class="size-4 text-muted-foreground" />
                            </div>
                            <div class="min-w-0 flex-1 space-y-4">
                                <p class="text-sm text-muted-foreground">
                                    <template v-if="item.login_user">
                                        {{ t('Account') }}: {{ item.login_user.email }}
                                    </template>
                                    <template v-else-if="item.email">
                                        {{ t('Sets a login for') }} {{ item.email }}
                                    </template>
                                    <template v-else>
                                        {{ t('Add an email on the employee profile first.') }}
                                    </template>
                                </p>

                                <Form
                                    v-if="item.email"
                                    :action="`/employees/${item.id}/password`"
                                    method="put"
                                    class="space-y-3"
                                    :reset-on-success="['password', 'password_confirmation']"
                                    v-slot="{ errors, processing }"
                                >
                                    <div class="grid gap-2">
                                        <Label for="password">{{ t('New password') }}</Label>
                                        <PasswordInput
                                            id="password"
                                            name="password"
                                            required
                                            autocomplete="new-password"
                                            :passwordrules="passwordRules"
                                        />
                                        <InputError :message="errors.password" />
                                    </div>
                                    <div class="grid gap-2">
                                        <Label for="password_confirmation">{{ t('Confirm password') }}</Label>
                                        <PasswordInput
                                            id="password_confirmation"
                                            name="password_confirmation"
                                            required
                                            autocomplete="new-password"
                                            :passwordrules="passwordRules"
                                        />
                                        <InputError :message="errors.password_confirmation" />
                                    </div>
                                    <Button type="submit" size="sm" :disabled="processing">
                                        {{ processing ? t('Saving…') : t('Update password') }}
                                    </Button>
                                </Form>
                            </div>
                        </div>
                    </V2Panel>
                </Can>

                <RecordAttachments
                    attachable="employees"
                    :attachable-id="item.id"
                    :attachments="item.attachments"
                    boxed
                />
            </div>
        </div>

        <V2Panel
            :padded="false"
            :title="t('Assigned projects')"
            :description="t('Projects this employee works on')"
        >
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="table-chrome-head text-start text-muted-foreground">
                        <tr>
                            <TableIndexTh />
                            <th class="px-4 py-3 font-medium">{{ t('Code') }}</th>
                            <th class="px-4 py-3 font-medium">{{ t('Project') }}</th>
                            <th class="px-4 py-3 font-medium">{{ t('Role on project') }}</th>
                            <th class="px-4 py-3 font-medium">{{ t('Started on project') }}</th>
                            <th class="px-4 py-3 font-medium">{{ t('Working hours') }}</th>
                            <th class="px-4 py-3 font-medium">{{ t('Status') }}</th>
                            <th class="px-4 py-3 font-medium whitespace-nowrap">{{ t('Created at') }}</th>
                            <th class="px-4 py-3 font-medium text-end">{{ t('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="(project, index) in item.projects"
                            :key="project.id"
                            class="border-t transition-colors hover:bg-muted/30"
                        >
                            <TableIndexTd :index="index" />
                            <td class="px-4 py-3 font-medium">{{ project.code || '—' }}</td>
                            <td class="px-4 py-3">
                                <Link :href="`/projects/${project.id}`" class="inline-flex items-center gap-2 font-medium hover:text-primary hover:underline">
                                    <Briefcase class="size-4 text-muted-foreground" />
                                    {{ project.name }}
                                </Link>
                            </td>
                            <td class="px-4 py-3">{{ project.pivot?.role_on_project || '—' }}</td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                {{ formatDate(project.pivot?.started_at) }}
                            </td>
                            <td class="px-4 py-3">{{ project.pivot?.working_hours || '—' }}</td>
                            <td class="px-4 py-3">
                                <StatusBadge :status="project.status" />
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-muted-foreground">{{ formatDate(project.created_at) }}</td>
                            <td class="px-4 py-3 text-end">
                                <Button as-child size="sm" variant="outline">
                                    <Link :href="`/projects/${project.id}#employees`">{{ t('View on project') }}</Link>
                                </Button>
                            </td>
                        </tr>
                        <tr v-if="!item.projects?.length">
                            <td colspan="9" class="px-4 py-10 text-center text-muted-foreground">
                                {{ t('Not assigned to any project yet. Add from a project page.') }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </V2Panel>

        <V2Panel :padded="false" :title="t('Attendance')">
            <template #actions>
                <Button as-child variant="link" size="sm" class="h-auto p-0">
                    <Link :href="`/attendance?employee_id=${item.id}`">{{ t('View all') }}</Link>
                </Button>
            </template>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="table-chrome-head text-start text-muted-foreground">
                        <tr>
                            <TableIndexTh />
                            <th class="px-4 py-3 font-medium">{{ t('Date') }}</th>
                            <th class="px-4 py-3 font-medium">{{ t('Check in') }}</th>
                            <th class="px-4 py-3 font-medium">{{ t('Check out') }}</th>
                            <th class="px-4 py-3 font-medium">{{ t('Status') }}</th>
                            <th class="px-4 py-3 font-medium">{{ t('Notes') }}</th>
                            <th class="px-4 py-3 font-medium">{{ t('Added by') }}</th>
                            <th class="px-4 py-3 font-medium whitespace-nowrap">{{ t('Created at') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="(record, index) in item.attendance"
                            :key="record.id"
                            class="border-t transition-colors hover:bg-muted/30"
                        >
                            <TableIndexTd :index="index" />
                            <td class="px-4 py-3">{{ formatDate(record.date) }}</td>
                            <td class="px-4 py-3 tabular-nums">{{ record.check_in || '—' }}</td>
                            <td class="px-4 py-3 tabular-nums">{{ record.check_out || '—' }}</td>
                            <td class="px-4 py-3">{{ translateStatus(record.status, t) }}</td>
                            <td class="px-4 py-3">{{ record.notes || '—' }}</td>
                            <td class="px-4 py-3 text-muted-foreground">{{ record.creator?.name || '—' }}</td>
                            <td class="px-4 py-3 whitespace-nowrap text-muted-foreground">{{ formatDate(record.created_at) }}</td>
                        </tr>
                        <tr v-if="!item.attendance?.length">
                            <td colspan="8" class="px-4 py-10 text-center text-muted-foreground">{{ t('No attendance records') }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </V2Panel>

        <V2Panel :padded="false" :title="t('Salaries')">
            <template #actions>
                <Button as-child variant="link" size="sm" class="h-auto p-0">
                    <Link :href="`/salaries?employee_id=${item.id}`">{{ t('View all') }}</Link>
                </Button>
            </template>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="table-chrome-head text-start text-muted-foreground">
                        <tr>
                            <TableIndexTh />
                            <th class="px-4 py-3 font-medium">{{ t('Month') }}</th>
                            <th class="px-4 py-3 font-medium">{{ t('Year') }}</th>
                            <th class="px-4 py-3 font-medium">{{ t('Basic Salary') }}</th>
                            <th class="px-4 py-3 font-medium">{{ t('Bonus') }}</th>
                            <th class="px-4 py-3 font-medium">{{ t('Deduction') }}</th>
                            <th class="px-4 py-3 font-medium">{{ t('Net Salary') }}</th>
                            <th class="px-4 py-3 font-medium">{{ t('Status') }}</th>
                            <th class="px-4 py-3 font-medium">{{ t('Added by') }}</th>
                            <th class="px-4 py-3 font-medium whitespace-nowrap">{{ t('Created at') }}</th>
                            <th class="px-4 py-3 font-medium text-end">{{ t('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="(record, index) in item.salaries"
                            :key="record.id"
                            class="border-t transition-colors hover:bg-muted/30"
                        >
                            <TableIndexTd :index="index" />
                            <td class="px-4 py-3">{{ record.month }}</td>
                            <td class="px-4 py-3">{{ record.year }}</td>
                            <td class="px-4 py-3 tabular-nums">{{ money(record.basic_salary, record.currency || baseCurrency) }}</td>
                            <td class="px-4 py-3 tabular-nums">{{ money(record.bonus, record.currency || baseCurrency) }}</td>
                            <td class="px-4 py-3 tabular-nums">{{ money(record.deduction, record.currency || baseCurrency) }}</td>
                            <td class="px-4 py-3 tabular-nums font-medium">{{ money(record.net_salary, record.currency || baseCurrency) }}</td>
                            <td class="px-4 py-3">{{ translateStatus(record.status, t) }}</td>
                            <td class="px-4 py-3 text-muted-foreground">{{ record.creator?.name || '—' }}</td>
                            <td class="px-4 py-3 whitespace-nowrap text-muted-foreground">{{ formatDate(record.created_at) }}</td>
                            <td class="px-4 py-3 text-end">
                                <Button as-child size="sm" variant="outline">
                                    <Link :href="`/salaries/${record.id}`"><Printer class="size-4" /> {{ t('Print') }}</Link>
                                </Button>
                            </td>
                        </tr>
                        <tr v-if="!item.salaries?.length">
                            <td colspan="11" class="px-4 py-10 text-center text-muted-foreground">{{ t('No salary records') }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </V2Panel>

        <ConfirmActionDialog
            v-model:open="deleteOpen"
            :title="t('Delete employee')"
            :description="t('Are you sure you want to delete this employee? This action cannot be undone.')"
            :confirm-label="t('Delete')"
            :processing="deleting"
            @confirm="confirmDelete"
        />
    </V2ListPage>
</template>
