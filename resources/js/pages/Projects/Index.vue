<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Eye, Pencil, Plus, Search, Trash2 } from '@lucide/vue';
import { ref } from 'vue';
import EmptyState from '@/components/EmptyState.vue';
import Heading from '@/components/Heading.vue';
import { MisStatCard, MisStatGrid } from '@/components/mis';
import Pagination from '@/components/Pagination.vue';
import RowActionsMenu from '@/components/RowActionsMenu.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import type { RowActionItem } from '@/lib/row-actions';
import { formatNumber, money } from '@/lib/utils';

type Item = Record<string, any>;

const props = defineProps<{
    items: { data: Item[]; links: any[]; meta?: any };
    filters: { search?: string };
    totals: { count: number; active: number; units: number; sales_total: number };
}>();

const search = ref(props.filters.search ?? '');

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Projects', href: '/projects' },
        ],
    },
});

function applySearch() {
    router.get('/projects', { search: search.value || undefined }, { preserveState: true, replace: true });
}

const projectStatuses = ['planning', 'under_construction', 'ready_for_sale', 'sold', 'completed', 'on_hold'];

function statusLabel(status: string): string {
    return status.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase());
}

function rowActions(item: Item): RowActionItem[] {
    return [
        { label: 'View', icon: Eye, href: `/projects/${item.id}` },
        { label: 'Edit', icon: Pencil, href: `/projects/${item.id}/edit` },
        { separator: true, isLabel: true, label: 'Change status' },
        ...projectStatuses.map((status) => ({
            label: statusLabel(status),
            method: 'patch' as const,
            href: `/projects/${item.id}/status`,
            data: { status },
            disabled: item.status === status,
        })),
        { separator: true, isLabel: true, label: 'Danger' },
        {
            label: 'Delete',
            icon: Trash2,
            method: 'delete',
            href: `/projects/${item.id}`,
            variant: 'destructive' as const,
            confirm: {
                title: 'Delete project',
                description: 'Are you sure you want to delete this project? This action cannot be undone.',
                confirmLabel: 'Delete',
            },
        },
    ];
}
</script>

<template>
    <Head title="Projects" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <Heading title="Projects" description="Construction sites, budgets, and progress in one place." />
            <div class="flex flex-wrap gap-2">
                <div class="relative min-w-[220px] flex-1">
                    <Search class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                    <Input v-model="search" class="pl-9" placeholder="Search projects..." @keyup.enter="applySearch" />
                </div>
                <Button variant="secondary" @click="applySearch">Search</Button>
                <Button as-child>
                    <Link href="/projects/create"><Plus class="size-4" /> Add project</Link>
                </Button>
            </div>
        </div>

        <MisStatGrid :columns="4">
            <MisStatCard label="Projects" :value="totals.count" />
            <MisStatCard label="Active" :value="totals.active" hint="Planning, construction, ready" />
            <MisStatCard label="Units" :value="totals.units" />
            <MisStatCard label="Sales total" :value="money(totals.sales_total)" tone="success" />
        </MisStatGrid>

        <div class="overflow-hidden rounded-xl border border-sidebar-border/70 bg-card shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="border-b bg-muted/40 text-left text-muted-foreground">
                        <tr>
                            <th class="px-4 py-3 font-medium">Code</th>
                            <th class="px-4 py-3 font-medium">Name</th>
                            <th class="px-4 py-3 font-medium">Location</th>
                            <th class="px-4 py-3 font-medium">Status</th>
                            <th class="px-4 py-3 font-medium">Progress</th>
                            <th class="px-4 py-3 font-medium">Units</th>
                            <th class="px-4 py-3 font-medium text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="item in items.data"
                            :key="item.id"
                            class="border-b last:border-0 transition-colors hover:bg-muted/30"
                        >
                            <td class="px-4 py-3 font-medium">
                                <Link :href="`/projects/${item.id}`" class="hover:text-primary hover:underline">
                                    {{ item.code || '—' }}
                                </Link>
                            </td>
                            <td class="px-4 py-3">{{ item.name || '—' }}</td>
                            <td class="px-4 py-3">{{ item.location || '—' }}</td>
                            <td class="px-4 py-3"><StatusBadge :status="item.status" /></td>
                            <td class="px-4 py-3 tabular-nums">{{ formatNumber(item.progress_percent) }}%</td>
                            <td class="px-4 py-3">{{ item.units_count }}</td>
                            <td class="px-4 py-3">
                                <div class="flex justify-end">
                                    <RowActionsMenu :actions="rowActions(item)" />
                                </div>
                            </td>
                        </tr>
                        <EmptyState
                            v-if="!items.data.length"
                            :colspan="7"
                            title="No projects yet"
                            description="Create a project to start adding units and sales."
                        />
                    </tbody>
                </table>
            </div>
            <Pagination :paginator="items" />
        </div>
    </div>
</template>
