<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Eye, Pencil, Plus, Search, ShoppingCart, Trash2 } from '@lucide/vue';
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

type Item = Record<string, any>;
const props = defineProps<{ items: { data: Item[]; links: any[]; meta?: any }; filters: { search?: string } }>();
const search = ref(props.filters.search ?? '');

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Materials', href: '/materials' },
        ],
    },
});

const applySearch = () => router.get('/materials', { search: search.value || undefined }, { preserveState: true, replace: true });

function rowActions(item: Item): RowActionItem[] {
    return [
        { label: 'View', icon: Eye, href: `/materials/${item.id}` },
        { label: 'Purchase', icon: ShoppingCart, href: `/purchases/create?material_id=${item.id}` },
        { label: 'Edit', icon: Pencil, href: `/materials/${item.id}/edit` },
        { separator: true, isLabel: true, label: 'Danger' },
        {
            label: 'Delete',
            icon: Trash2,
            method: 'delete',
            href: `/materials/${item.id}`,
            variant: 'destructive' as const,
            confirm: {
                title: 'Delete material',
                description: 'Are you sure you want to delete this material? This action cannot be undone.',
                confirmLabel: 'Delete',
            },
        },
    ];
}
</script>

<template>
    <Head title="Materials" />
    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <Heading title="Materials" description="Stock items — quantities update when you record purchases." />
            <div class="flex flex-wrap gap-2">
                <div class="relative min-w-[220px] flex-1">
                    <Search class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                    <Input v-model="search" class="pl-9" placeholder="Search..." @keyup.enter="applySearch" />
                </div>
                <Button variant="secondary" @click="applySearch">Search</Button>
                <Button as-child><Link href="/materials/create"><Plus class="size-4" /> Add</Link></Button>
            </div>
        </div>
        <div class="overflow-hidden rounded-xl border border-sidebar-border/70 bg-card shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="border-b bg-muted/40 text-left text-muted-foreground">
                        <tr>
                            <th class="px-4 py-3 font-medium">Code</th>
                            <th class="px-4 py-3 font-medium">Name</th>
                            <th class="px-4 py-3 font-medium">Unit</th>
                            <th class="px-4 py-3 font-medium">Stock</th>
                            <th class="px-4 py-3 font-medium">Avg Cost</th>
                            <th class="px-4 py-3 font-medium">Min Stock</th>
                            <th class="px-4 py-3 font-medium">Status</th>
                            <th class="px-4 py-3 font-medium text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="item in items.data" :key="item.id" class="border-b last:border-0 transition-colors hover:bg-muted/30">
                            <td class="px-4 py-3 font-medium">
                                <Link :href="`/materials/${item.id}`" class="hover:text-primary hover:underline">{{ item.code }}</Link>
                            </td>
                            <td class="px-4 py-3">{{ item.name }}</td>
                            <td class="px-4 py-3">{{ item.unit }}</td>
                            <td class="px-4 py-3 tabular-nums">
                                <span :class="Number(item.current_stock) < Number(item.minimum_stock) ? 'font-medium text-amber-700' : ''">
                                    {{ item.current_stock }} {{ item.unit }}
                                </span>
                            </td>
                            <td class="px-4 py-3 tabular-nums">
                                {{ Number(item.average_unit_cost) > 0 ? money(item.average_unit_cost, item.currency || 'USD') : '—' }}
                            </td>
                            <td class="px-4 py-3">{{ item.minimum_stock }}</td>
                            <td class="px-4 py-3"><StatusBadge :status="item.is_active ? 'active' : 'inactive'" /></td>
                            <td class="px-4 py-3">
                                <div class="flex justify-end">
                                    <RowActionsMenu :actions="rowActions(item)" />
                                </div>
                            </td>
                        </tr>
                        <EmptyState v-if="!items.data.length" :colspan="8" title="No materials yet" />
                    </tbody>
                </table>
            </div>
            <Pagination :paginator="items" />
        </div>
    </div>
</template>
