<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Eye, Pencil, Plus, Printer, Search, Trash2 } from '@lucide/vue';
import { ref } from 'vue';
import EmptyState from '@/components/EmptyState.vue';
import Heading from '@/components/Heading.vue';
import Pagination from '@/components/Pagination.vue';
import RowActionsMenu from '@/components/RowActionsMenu.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import type { RowActionItem } from '@/lib/row-actions';
import { money } from '@/lib/utils';

const props = defineProps<{
    items: { data: any[]; links: any[]; meta?: any };
    filters: { search?: string; project_id?: number | string };
    projects: { id: number; name: string }[];
    totals: { amount: number };
}>();
const search = ref(props.filters.search ?? '');
const projectId = ref(String(props.filters.project_id ?? ''));

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Expenses', href: '/expenses' },
        ],
    },
});

function applyFilters() {
    router.get(
        '/expenses',
        {
            search: search.value || undefined,
            project_id: projectId.value || undefined,
        },
        { preserveState: true, replace: true },
    );
}

const expenseStatuses = ['draft', 'pending', 'approved', 'rejected'];

function rowActions(item: any): RowActionItem[] {
    return [
        { label: 'View', icon: Eye, href: `/expenses/${item.id}` },
        { label: 'Print', icon: Printer, href: `/expenses/${item.id}` },
        { label: 'Edit', icon: Pencil, href: `/expenses/${item.id}/edit` },
        { separator: true, isLabel: true, label: 'Change status' },
        ...expenseStatuses.map((status) => ({
            label: status.charAt(0).toUpperCase() + status.slice(1),
            method: 'patch' as const,
            href: `/expenses/${item.id}/status`,
            data: { status },
            disabled: item.status === status,
        })),
        { separator: true, isLabel: true, label: 'Danger' },
        {
            label: 'Delete',
            icon: Trash2,
            method: 'delete',
            href: `/expenses/${item.id}`,
            variant: 'destructive' as const,
            confirm: {
                title: 'Delete expense',
                description: 'Are you sure you want to delete this expense? This action cannot be undone.',
                confirmLabel: 'Delete',
            },
        },
    ];
}
</script>

<template>
    <Head title="Expenses" />
    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <Heading title="Expenses" description="Labor, transport, permits and admin costs. Use Purchases for materials." />
            <div class="flex flex-wrap gap-2">
                <Input v-model="search" class="min-w-[180px]" placeholder="Search..." @keyup.enter="applyFilters" />
                <select
                    v-model="projectId"
                    class="h-9 rounded-md border border-input bg-background px-3 text-sm"
                    @change="applyFilters"
                >
                    <option value="">All projects</option>
                    <option v-for="p in projects" :key="p.id" :value="String(p.id)">{{ p.name }}</option>
                </select>
                <Button variant="secondary" @click="applyFilters"><Search class="size-4" /> Filter</Button>
                <Button as-child><Link href="/expenses/create"><Plus class="size-4" /> Add expense</Link></Button>
            </div>
        </div>

        <div class="rounded-xl border border-sidebar-border/70 bg-card px-4 py-3 text-sm shadow-sm">
            Filtered total:
            <span class="font-semibold tabular-nums text-rose-700">{{ money(totals.amount) }}</span>
        </div>

        <div class="overflow-hidden rounded-xl border border-sidebar-border/70 bg-card shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="border-b bg-muted/40 text-left text-muted-foreground">
                        <tr>
                            <th class="px-4 py-3 font-medium">Date</th>
                            <th class="px-4 py-3 font-medium">No</th>
                            <th class="px-4 py-3 font-medium">Category</th>
                            <th class="px-4 py-3 font-medium">Project</th>
                            <th class="px-4 py-3 font-medium">Paid To</th>
                            <th class="px-4 py-3 font-medium">Amount</th>
                            <th class="px-4 py-3 font-medium">Status</th>
                            <th class="px-4 py-3 font-medium text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="item in items.data" :key="item.id" class="border-b last:border-0 transition-colors hover:bg-muted/30">
                            <td class="px-4 py-3">{{ item.expense_date }}</td>
                            <td class="px-4 py-3 font-medium">
                                <Link :href="`/expenses/${item.id}`" class="hover:text-primary hover:underline">{{ item.expense_no }}</Link>
                            </td>
                            <td class="px-4 py-3">{{ item.category?.name || '—' }}</td>
                            <td class="px-4 py-3">{{ item.project?.name || '—' }}</td>
                            <td class="px-4 py-3">{{ item.paid_to || '—' }}</td>
                            <td class="px-4 py-3 tabular-nums">{{ money(item.amount) }}</td>
                            <td class="px-4 py-3"><StatusBadge :status="item.status" /></td>
                            <td class="px-4 py-3">
                                <div class="flex justify-end">
                                    <RowActionsMenu :actions="rowActions(item)" />
                                </div>
                            </td>
                        </tr>
                        <EmptyState v-if="!items.data.length" :colspan="8" title="No expenses yet" />
                    </tbody>
                </table>
            </div>
            <Pagination :paginator="items" />
        </div>
    </div>
</template>
