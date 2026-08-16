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
    filters: { search?: string };
    totals: { count: number; active: number; inactive: number };
}>();

const search = ref(props.filters.search ?? '');
const { t } = useTranslations();
const { viewAction, editAction, can } = useMisPage();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Employees', href: '/employees' },
        ],
    },
});

function applySearch() {
    router.get('/employees', { search: search.value || undefined }, { preserveState: true, replace: true });
}

function rowActions(item: Item): RowActionItem[] {
    return [
        viewAction(`/employees/${item.id}`),
        editAction(`/employees/${item.id}/edit`, 'employees.edit'),
        { separator: true, isLabel: true, label: t('Danger') },
        {
            label: t('Delete'),
            icon: Trash2,
            method: 'delete',
            href: `/employees/${item.id}`,
            variant: 'destructive' as const,
            hidden: !can('employees.delete'),
            confirm: {
                title: t('Delete employee'),
                description: t('Are you sure you want to delete this employee? This action cannot be undone.'),
                confirmLabel: t('Delete'),
            },
        },
    ];
}
</script>

<template>
    <Head :title="t('Employees')" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <Heading :title="t('Employees')" :description="t('Manage staff, their attendance, and salary records.')" />
            <div class="flex flex-wrap gap-2">
                <div class="relative min-w-[220px] flex-1">
                    <Search class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                    <Input v-model="search" class="pl-9" :placeholder="t('Search employees...')" @keyup.enter="applySearch" />
                </div>
                <Button variant="secondary" @click="applySearch">{{ t('Search') }}</Button>
                <MisCreateButton href="/employees/create" permission="employees.create">{{ t('Add employee') }}</MisCreateButton>
            </div>
        </div>

        <MisStatGrid :columns="4">
            <MisStatCard :label="t('Total')" :value="totals.count" />
            <MisStatCard :label="t('Active')" :value="totals.active" tone="success" />
            <MisStatCard :label="t('Inactive')" :value="totals.inactive" tone="warning" />
        </MisStatGrid>

        <div class="overflow-hidden rounded-xl border border-sidebar-border/70 bg-card shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="border-b bg-muted/40 text-left text-muted-foreground">
                        <tr>
                            <th class="px-4 py-3 font-medium">{{ t('Employee No') }}</th>
                            <th class="px-4 py-3 font-medium">{{ t('Name') }}</th>
                            <th class="px-4 py-3 font-medium">{{ t('Department') }}</th>
                            <th class="px-4 py-3 font-medium">{{ t('Designation') }}</th>
                            <th class="px-4 py-3 font-medium">{{ t('Phone') }}</th>
                            <th class="px-4 py-3 font-medium">{{ t('Basic Salary') }}</th>
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
                            <td class="px-4 py-3 font-medium">
                                <Link :href="`/employees/${item.id}`" class="hover:text-primary hover:underline">
                                    {{ item.employee_no || '—' }}
                                </Link>
                            </td>
                            <td class="px-4 py-3">{{ item.name || '—' }}</td>
                            <td class="px-4 py-3">{{ item.department || '—' }}</td>
                            <td class="px-4 py-3">{{ item.designation || '—' }}</td>
                            <td class="px-4 py-3">{{ item.phone || '—' }}</td>
                            <td class="px-4 py-3 tabular-nums">{{ item.basic_salary ? money(item.basic_salary) : '—' }}</td>
                            <td class="px-4 py-3">
                                <span class="capitalize">{{ item.status || '—' }}</span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex justify-end">
                                    <RowActionsMenu :actions="rowActions(item)" />
                                </div>
                            </td>
                        </tr>
                        <EmptyState
                            v-if="!items.data.length"
                            :colspan="8"
                            :title="t('No employees yet')"
                            :description="t('Add an employee to start tracking attendance and salary.')"
                        />
                    </tbody>
                </table>
            </div>
            <Pagination :paginator="items" />
        </div>
    </div>
</template>
