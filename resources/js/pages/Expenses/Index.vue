<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Eye, Pencil, Plus, Printer, Search, Trash2 } from '@lucide/vue';
import { ref } from 'vue';
import EmptyState from '@/components/EmptyState.vue';
import Heading from '@/components/Heading.vue';
import { MisStatCard, MisStatGrid } from '@/components/mis';
import Pagination from '@/components/Pagination.vue';
import RowActionsMenu from '@/components/RowActionsMenu.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import type { RowActionItem } from '@/lib/row-actions';
import { money } from '@/lib/utils';

const props = defineProps<{
    items: { data: any[]; links: any[]; meta?: any };
    filters: { search?: string };
    totals: { count: number; amount: number; this_month: number; average: number };
}>();

const search = ref(props.filters.search ?? '');

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Expenses', href: '/expenses' },
        ],
    },
});

function applyFilters() {
    router.get('/expenses', { search: search.value || undefined }, { preserveState: true, replace: true });
}

function rowActions(item: any): RowActionItem[] {
    return [
        { label: 'View', icon: Eye, href: `/expenses/${item.id}` },
        { label: 'Print', icon: Printer, href: `/expenses/${item.id}` },
        { label: 'Edit', icon: Pencil, href: `/expenses/${item.id}/edit` },
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
            <Heading title="General Expenses" description="Company-wide costs not tied to a project. Use the project page for project expenses." />
            <div class="flex flex-wrap gap-2">
                <Input v-model="search" class="min-w-[180px]" placeholder="Search..." @keyup.enter="applyFilters" />
                <Button variant="secondary" @click="applyFilters"><Search class="size-4" /> Filter</Button>
                <Button as-child><Link href="/expenses/create"><Plus class="size-4" /> Add expense</Link></Button>
            </div>
        </div>

        <MisStatGrid :columns="4">
            <MisStatCard label="Entries" :value="totals.count" hint="Approved expenses" />
            <MisStatCard label="Total spent" :value="money(totals.amount)" tone="danger" />
            <MisStatCard label="This month" :value="money(totals.this_month)" />
            <MisStatCard label="Average" :value="money(totals.average)" />
        </MisStatGrid>

        <div class="overflow-hidden rounded-xl border border-sidebar-border/70 bg-card shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="border-b bg-muted/40 text-left text-muted-foreground">
                        <tr>
                            <th class="px-4 py-3 font-medium">Date</th>
                            <th class="px-4 py-3 font-medium">Description</th>
                            <th class="px-4 py-3 font-medium">Total cost</th>
                            <th class="px-4 py-3 font-medium text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="item in items.data" :key="item.id" class="border-b last:border-0 transition-colors hover:bg-muted/30">
                            <td class="px-4 py-3">{{ item.expense_date?.slice?.(0, 10) ?? item.expense_date }}</td>
                            <td class="px-4 py-3">{{ item.description || '—' }}</td>
                            <td class="px-4 py-3 tabular-nums">{{ money(item.amount) }}</td>
                            <td class="px-4 py-3">
                                <div class="flex justify-end">
                                    <RowActionsMenu :actions="rowActions(item)" />
                                </div>
                            </td>
                        </tr>
                        <EmptyState v-if="!items.data.length" :colspan="4" title="No expenses yet" />
                    </tbody>
                </table>
            </div>
            <Pagination :paginator="items" />
        </div>
    </div>
</template>
