<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, Banknote, Briefcase, CalendarDays, Clock, Mail, Phone, Printer, User, Wallet } from '@lucide/vue';
import RecordAttachments from '@/components/RecordAttachments.vue';
import Heading from '@/components/Heading.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { MisStatCard, MisStatGrid } from '@/components/mis';
import { Button } from '@/components/ui/button';
import { useCalendar } from '@/composables/useCalendar';
import { useCurrency } from '@/composables/useCurrency';
import { useTranslations } from '@/composables/useTranslations';
import { translateStatus } from '@/lib/translateStatus';
import { money } from '@/lib/utils';

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
}>();

const { t } = useTranslations();

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

    <div class="flex h-full flex-1 flex-col gap-4">
        <div class="space-y-3">
            <Button as-child variant="ghost" size="sm" class="-ml-2 w-fit">
                <Link href="/employees"><ArrowLeft class="size-4" /> {{ t('Back') }}</Link>
            </Button>
            <Heading
                :title="item.name"
                :description="`${item.employee_no || '—'} · ${item.designation || '—'} · ${item.department || '—'}`"
            />
        </div>

        <MisStatGrid :columns="4">
            <MisStatCard :label="t('Projects')" :value="stats.projects_count" />
            <MisStatCard :label="t('Attendance records')" :value="stats.attendance_count" />
            <MisStatCard :label="t('Salary records')" :value="stats.salaries_count" />
            <MisStatCard :label="t('Basic salary')" :value="item.basic_salary != null ? money(item.basic_salary, item.currency) : '—'" />
        </MisStatGrid>

        <div class="grid gap-6 lg:grid-cols-2">
            <div class="space-y-6 rounded-xl border border-sidebar-border/70 bg-card p-6 shadow-sm">
                <h3 class="text-sm font-semibold">{{ t('Employee Details') }}</h3>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="flex items-center gap-3">
                        <User class="size-4 text-muted-foreground" />
                        <div>
                            <p class="text-xs text-muted-foreground">{{ t('Takhallus') }}</p>
                            <p>{{ item.takhallus || '—' }}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <User class="size-4 text-muted-foreground" />
                        <div>
                            <p class="text-xs text-muted-foreground">{{ t('Father name') }}</p>
                            <p>{{ item.father_name || '—' }}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <User class="size-4 text-muted-foreground" />
                        <div>
                            <p class="text-xs text-muted-foreground">{{ t('Status') }}</p>
                            <p>{{ translateStatus(item.status, t) }}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <Mail class="size-4 text-muted-foreground" />
                        <div>
                            <p class="text-xs text-muted-foreground">{{ t('Email') }}</p>
                            <p>{{ item.email || '—' }}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <Phone class="size-4 text-muted-foreground" />
                        <div>
                            <p class="text-xs text-muted-foreground">{{ t('Phone') }}</p>
                            <p>{{ item.phone || '—' }}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <CalendarDays class="size-4 text-muted-foreground" />
                        <div>
                            <p class="text-xs text-muted-foreground">{{ t('Employment Date') }}</p>
                            <p>{{ formatDate(item.joining_date) }}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <Clock class="size-4 text-muted-foreground" />
                        <div>
                            <p class="text-xs text-muted-foreground">{{ t('Working hours') }}</p>
                            <p>{{ item.working_hours || '—' }}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <Banknote class="size-4 text-muted-foreground" />
                        <div>
                            <p class="text-xs text-muted-foreground">{{ t('Basic Salary') }}</p>
                            <p class="tabular-nums">{{ item.basic_salary != null ? money(item.basic_salary, item.currency) : '—' }}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <Wallet class="size-4 text-muted-foreground" />
                        <div>
                            <p class="text-xs text-muted-foreground">{{ t('Salary payment method') }}</p>
                            <p>{{ paymentMethodLabel(item.salary_payment_method) }}</p>
                        </div>
                    </div>
                </div>
                <div v-if="item.notes">
                    <p class="text-xs text-muted-foreground">{{ t('Notes') }}</p>
                    <p class="mt-1 whitespace-pre-line text-sm">{{ item.notes }}</p>
                </div>
            </div>

            <div class="space-y-4">
                <RecordAttachments
                    attachable="employees"
                    :attachable-id="item.id"
                    :attachments="item.attachments"
                    boxed
                />
            </div>
        </div>

        <div class="overflow-hidden rounded-xl border border-sidebar-border/70 bg-card shadow-sm">
            <div class="flex items-center justify-between border-b px-5 py-3">
                <div>
                    <h3 class="text-sm font-semibold">{{ t('Assigned projects') }}</h3>
                    <p class="mt-0.5 text-sm text-muted-foreground">{{ t('Projects this employee works on') }}</p>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-muted/40 text-start text-muted-foreground">
                        <tr>
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
                            v-for="project in item.projects"
                            :key="project.id"
                            class="border-t transition-colors hover:bg-muted/30"
                        >
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
                            <td colspan="8" class="px-4 py-10 text-center text-muted-foreground">
                                {{ t('Not assigned to any project yet. Add from a project page.') }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="overflow-hidden rounded-xl border border-sidebar-border/70 bg-card shadow-sm">
            <div class="flex items-center justify-between border-b px-5 py-3">
                <h3 class="text-sm font-semibold">{{ t('Attendance') }}</h3>
                <Button as-child variant="link" size="sm" class="h-auto p-0">
                    <Link :href="`/attendance?employee_id=${item.id}`">{{ t('View all') }}</Link>
                </Button>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-muted/40 text-start text-muted-foreground">
                        <tr>
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
                            v-for="record in item.attendance"
                            :key="record.id"
                            class="border-t transition-colors hover:bg-muted/30"
                        >
                            <td class="px-4 py-3">{{ formatDate(record.date) }}</td>
                            <td class="px-4 py-3 tabular-nums">{{ record.check_in || '—' }}</td>
                            <td class="px-4 py-3 tabular-nums">{{ record.check_out || '—' }}</td>
                            <td class="px-4 py-3">{{ translateStatus(record.status, t) }}</td>
                            <td class="px-4 py-3">{{ record.notes || '—' }}</td>
                            <td class="px-4 py-3 text-muted-foreground">{{ record.creator?.name || '—' }}</td>
                            <td class="px-4 py-3 whitespace-nowrap text-muted-foreground">{{ formatDate(record.created_at) }}</td>
                        </tr>
                        <tr v-if="!item.attendance?.length">
                            <td colspan="7" class="px-4 py-10 text-center text-muted-foreground">{{ t('No attendance records') }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="overflow-hidden rounded-xl border border-sidebar-border/70 bg-card shadow-sm">
            <div class="flex items-center justify-between border-b px-5 py-3">
                <h3 class="text-sm font-semibold">{{ t('Salaries') }}</h3>
                <Button as-child variant="link" size="sm" class="h-auto p-0">
                    <Link :href="`/salaries?employee_id=${item.id}`">{{ t('View all') }}</Link>
                </Button>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-muted/40 text-start text-muted-foreground">
                        <tr>
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
                            v-for="record in item.salaries"
                            :key="record.id"
                            class="border-t transition-colors hover:bg-muted/30"
                        >
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
                            <td colspan="10" class="px-4 py-10 text-center text-muted-foreground">{{ t('No salary records') }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>
