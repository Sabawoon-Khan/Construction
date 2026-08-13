<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Pencil, Plus, Search, Trash2 } from '@lucide/vue';
import { ref } from 'vue';
import EmptyState from '@/components/EmptyState.vue';
import Heading from '@/components/Heading.vue';
import Pagination from '@/components/Pagination.vue';
import RowActionsMenu from '@/components/RowActionsMenu.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import type { RowActionItem } from '@/lib/row-actions';

type Item = Record<string, any>;
const props = defineProps<{ items: { data: Item[]; links: any[]; meta?: any }; filters: { search?: string } }>();
const search = ref(props.filters.search ?? '');

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Material Units', href: '/material-units' },
        ],
    },
});

const applySearch = () => router.get('/material-units', { search: search.value || undefined }, { preserveState: true, replace: true });

function rowActions(item: Item): RowActionItem[] {
    return [
        { label: 'Edit', icon: Pencil, href: `/material-units/${item.id}/edit` },
        { separator: true, isLabel: true, label: 'Danger' },
        {
            label: 'Delete',
            icon: Trash2,
            method: 'delete',
            href: `/material-units/${item.id}`,
            variant: 'destructive' as const,
            confirm: {
                title: 'Delete unit',
                description: 'Are you sure you want to delete this unit?',
                confirmLabel: 'Delete',
            },
        },
    ];
}
</script>

<template>
    <Head title="Material Units" />
    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <Heading title="Material Units" description="Units used for materials, e.g. bag, kg, ton." />
            <div class="flex flex-wrap gap-2">
                <div class="relative min-w-[220px] flex-1">
                    <Search class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                    <Input v-model="search" class="pl-9" placeholder="Search..." @keyup.enter="applySearch" />
                </div>
                <Button variant="secondary" @click="applySearch">Search</Button>
                <Button as-child><Link href="/material-units/create"><Plus class="size-4" /> Add unit</Link></Button>
            </div>
        </div>
        <div class="overflow-hidden rounded-xl border border-sidebar-border/70 bg-card shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="border-b bg-muted/40 text-left text-muted-foreground">
                        <tr>
                            <th class="px-4 py-3 font-medium">Unit name</th>
                            <th class="px-4 py-3 font-medium">Materials</th>
                            <th class="px-4 py-3 font-medium text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="item in items.data" :key="item.id" class="border-b last:border-0 transition-colors hover:bg-muted/30">
                            <td class="px-4 py-3 font-medium">{{ item.unit_name }}</td>
                            <td class="px-4 py-3">{{ item.materials_count }}</td>
                            <td class="px-4 py-3">
                                <div class="flex justify-end">
                                    <RowActionsMenu :actions="rowActions(item)" />
                                </div>
                            </td>
                        </tr>
                        <EmptyState v-if="!items.data.length" :colspan="3" title="No units yet">
                            <Button as-child size="sm" class="mt-3">
                                <Link href="/material-units/create"><Plus class="size-4" /> Add unit</Link>
                            </Button>
                        </EmptyState>
                    </tbody>
                </table>
            </div>
            <Pagination :paginator="items" />
        </div>
    </div>
</template>
