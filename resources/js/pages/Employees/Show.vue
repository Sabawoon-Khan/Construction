<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, CalendarDays, Mail, Phone, User } from '@lucide/vue';
import Heading from '@/components/Heading.vue';
import { MisStatCard, MisStatGrid } from '@/components/mis';
import { Button } from '@/components/ui/button';
import { useTranslations } from '@/composables/useTranslations';
import { money } from '@/lib/utils';

const props = defineProps<{
    item: Record<string, any>;
    stats: { attendance_count: number; salaries_count: number };
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

        <MisStatGrid :columns="3">
            <MisStatCard :label="t('Attendance records')" :value="stats.attendance_count" />
            <MisStatCard :label="t('Salary records')" :value="stats.salaries_count" />
            <MisStatCard :label="t('Basic salary')" :value="item.basic_salary ? money(item.basic_salary) : '—'" />
        </MisStatGrid>

        <div class="grid gap-6 lg:grid-cols-2">
            <div class="space-y-6 rounded-xl border border-sidebar-border/70 bg-card p-6 shadow-sm">
                <h3 class="text-sm font-semibold">{{ t('Employee Details') }}</h3>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="flex items-center gap-3">
                        <User class="size-4 text-muted-foreground" />
                        <div>
                            <p class="text-xs text-muted-foreground">{{ t('Status') }}</p>
                            <p class="capitalize">{{ item.status }}</p>
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
                            <p class="text-xs text-muted-foreground">{{ t('Joining Date') }}</p>
                            <p>{{ item.joining_date || '—' }}</p>
                        </div>
                    </div>
                </div>
                <div v-if="item.notes">
                    <p class="text-xs text-muted-foreground">{{ t('Notes') }}</p>
                    <p class="mt-1 whitespace-pre-line text-sm">{{ item.notes }}</p>
                </div>
            </div>

            <div class="space-y-4 rounded-xl border border-sidebar-border/70 bg-card p-6 shadow-sm">
                <h3 class="text-sm font-semibold">{{ t('Quick Links') }}</h3>
                <div class="flex flex-col gap-2">
                    <Button as-child variant="secondary">
                        <Link :href="`/attendance?employee_id=${item.id}`">{{ t('View attendance') }}</Link>
                    </Button>
                    <Button as-child variant="secondary">
                        <Link :href="`/salaries?employee_id=${item.id}`">{{ t('View salaries') }}</Link>
                    </Button>
                </div>
            </div>
        </div>
    </div>
</template>
