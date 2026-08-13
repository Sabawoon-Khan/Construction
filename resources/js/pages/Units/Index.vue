<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Eye, Pencil, Plus, Trash2 } from '@lucide/vue';
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
    filters: any;
    projects: any[];
}>();
const search = ref(props.filters.search ?? '');
const projectId = ref(String(props.filters.project_id ?? ''));
const status = ref(props.filters.status ?? '');

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Units', href: '/units' },
        ],
    },
});

const apply = () =>
    router.get(
        '/units',
        {
            search: search.value || undefined,
            project_id: projectId.value || undefined,
            status: status.value || undefined,
        },
        { preserveState: true, replace: true },
    );

const unitStatuses = ['available', 'reserved', 'sold'];

function rowActions(item: any): RowActionItem[] {
    return [
        { label: 'View', icon: Eye, href: `/units/${item.id}` },
        { label: 'Edit', icon: Pencil, href: `/units/${item.id}/edit` },
        { separator: true, isLabel: true, label: 'Change status' },
        ...unitStatuses.map((status) => ({
            label: status.charAt(0).toUpperCase() + status.slice(1),
            method: 'patch' as const,
            href: `/units/${item.id}/status`,
            data: { status },
            disabled: item.status === status,
        })),
        { separator: true, isLabel: true, label: 'Danger' },
        {
            label: 'Delete',
            icon: Trash2,
            method: 'delete',
            href: `/units/${item.id}`,
            variant: 'destructive' as const,
            confirm: {
                title: 'Delete unit',
                description: 'Are you sure you want to delete this unit? This action cannot be undone.',
                confirmLabel: 'Delete',
            },
        },
    ];
}
</script>

<template>
    <Head title="Units & Homes" />
    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <Heading title="Units & Homes" description="List prices, specs, and sale status for every unit." />
            <div class="flex flex-wrap gap-2">
                <Input v-model="search" placeholder="Unit no..." @keyup.enter="apply" />
                <select v-model="projectId" class="h-9 rounded-md border border-input bg-background px-3 text-sm" @change="apply">
                    <option value="">All projects</option>
                    <option v-for="p in projects" :key="p.id" :value="String(p.id)">{{ p.name }}</option>
                </select>
                <select v-model="status" class="h-9 rounded-md border border-input bg-background px-3 text-sm" @change="apply">
                    <option value="">All statuses</option>
                    <option value="available">Available</option>
                    <option value="reserved">Reserved</option>
                    <option value="sold">Sold</option>
                </select>
                <Button variant="secondary" @click="apply">Filter</Button>
                <Button as-child><Link href="/units/create"><Plus class="size-4" /> Add unit</Link></Button>
            </div>
        </div>

        <div class="overflow-hidden rounded-xl border border-sidebar-border/70 bg-card shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="border-b bg-muted/40 text-left text-muted-foreground">
                        <tr>
                            <th class="px-4 py-3 font-medium">Unit</th>
                            <th class="px-4 py-3 font-medium">Project</th>
                            <th class="px-4 py-3 font-medium">Type</th>
                            <th class="px-4 py-3 font-medium">Area</th>
                            <th class="px-4 py-3 font-medium">Beds</th>
                            <th class="px-4 py-3 font-medium">Price</th>
                            <th class="px-4 py-3 font-medium">Status</th>
                            <th class="px-4 py-3 font-medium text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="item in items.data" :key="item.id" class="border-b last:border-0 transition-colors hover:bg-muted/30">
                            <td class="px-4 py-3 font-medium">
                                <Link :href="`/units/${item.id}`" class="hover:text-primary hover:underline">{{ item.unit_no }}</Link>
                            </td>
                            <td class="px-4 py-3">{{ item.project?.name }}</td>
                            <td class="px-4 py-3 capitalize">{{ item.type }}</td>
                            <td class="px-4 py-3">{{ item.area ?? '—' }}</td>
                            <td class="px-4 py-3">{{ item.bedrooms ?? '—' }}</td>
                            <td class="px-4 py-3 tabular-nums">{{ money(item.list_price, item.currency || 'USD') }}</td>
                            <td class="px-4 py-3"><StatusBadge :status="item.status" /></td>
                            <td class="px-4 py-3">
                                <div class="flex justify-end">
                                    <RowActionsMenu :actions="rowActions(item)" />
                                </div>
                            </td>
                        </tr>
                        <EmptyState v-if="!items.data.length" :colspan="8" title="No units yet" />
                    </tbody>
                </table>
            </div>
            <Pagination :paginator="items" />
        </div>
    </div>
</template>
