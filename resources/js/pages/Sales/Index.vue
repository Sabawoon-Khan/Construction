<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Banknote, Eye, Pencil, Plus, Printer, Trash2 } from '@lucide/vue';
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
    filters: { search?: string };
    totals: { total_price: number };
}>();
const search = ref(props.filters.search ?? '');

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Sales', href: '/sales' },
        ],
    },
});

const apply = () => router.get('/sales', { search: search.value || undefined }, { preserveState: true, replace: true });
const saleTotal = (item: any) => Math.max(0, Number(item.unit?.list_price ?? 0) - Number(item.discount ?? 0));

const saleStatuses = ['draft', 'active', 'completed', 'cancelled'];

function rowActions(item: any): RowActionItem[] {
    return [
        { label: 'View', icon: Eye, href: `/sales/${item.id}` },
        { label: 'Receive payment', icon: Banknote, href: `/sales/${item.id}#receive-payment` },
        { label: 'Print', icon: Printer, href: `/sales/${item.id}` },
        { label: 'Edit', icon: Pencil, href: `/sales/${item.id}/edit` },
        { separator: true, isLabel: true, label: 'Change status' },
        ...saleStatuses.map((status) => ({
            label: status.charAt(0).toUpperCase() + status.slice(1),
            method: 'patch' as const,
            href: `/sales/${item.id}/status`,
            data: { status },
            disabled: item.status === status,
        })),
        { separator: true, isLabel: true, label: 'Danger' },
        {
            label: 'Delete',
            icon: Trash2,
            method: 'delete',
            href: `/sales/${item.id}`,
            variant: 'destructive' as const,
            confirm: {
                title: 'Delete sale',
                description: 'Are you sure you want to delete this sale? This action cannot be undone.',
                confirmLabel: 'Delete',
            },
        },
    ];
}
</script>

<template>
    <Head title="Sales" />
    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <Heading title="Home Sales" description="Contracts, installments and collection status." />
            <div class="flex flex-wrap gap-2">
                <Input v-model="search" placeholder="Search..." @keyup.enter="apply" />
                <Button variant="secondary" @click="apply">Search</Button>
                <Button as-child><Link href="/sales/create"><Plus class="size-4" /> New sale</Link></Button>
            </div>
        </div>

        <div class="rounded-xl border border-sidebar-border/70 bg-card px-4 py-3 text-sm shadow-sm">
            Sales total: <span class="font-semibold tabular-nums">{{ money(totals.total_price) }}</span>
        </div>

        <div class="overflow-hidden rounded-xl border border-sidebar-border/70 bg-card shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="border-b bg-muted/40 text-left text-muted-foreground">
                        <tr>
                            <th class="px-4 py-3 font-medium">Sale No</th>
                            <th class="px-4 py-3 font-medium">Customer</th>
                            <th class="px-4 py-3 font-medium">Project</th>
                            <th class="px-4 py-3 font-medium">Unit</th>
                            <th class="px-4 py-3 font-medium">Sale Price</th>
                            <th class="px-4 py-3 font-medium">Discount</th>
                            <th class="px-4 py-3 font-medium">Total</th>
                            <th class="px-4 py-3 font-medium">Status</th>
                            <th class="px-4 py-3 font-medium text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="item in items.data" :key="item.id" class="border-b last:border-0 transition-colors hover:bg-muted/30">
                            <td class="px-4 py-3 font-medium">
                                <Link :href="`/sales/${item.id}`" class="hover:text-primary hover:underline">{{ item.sale_no }}</Link>
                            </td>
                            <td class="px-4 py-3">{{ item.customer?.name }}</td>
                            <td class="px-4 py-3">{{ item.project?.name }}</td>
                            <td class="px-4 py-3">{{ item.unit?.unit_no }}</td>
                            <td class="px-4 py-3 tabular-nums">{{ money(item.unit?.list_price, item.currency || 'USD') }}</td>
                            <td class="px-4 py-3 tabular-nums">{{ money(item.discount, item.currency || 'USD') }}</td>
                            <td class="px-4 py-3 tabular-nums">{{ money(saleTotal(item), item.currency || 'USD') }}</td>
                            <td class="px-4 py-3"><StatusBadge :status="item.status" /></td>
                            <td class="px-4 py-3">
                                <div class="flex justify-end">
                                    <RowActionsMenu :actions="rowActions(item)" />
                                </div>
                            </td>
                        </tr>
                        <EmptyState v-if="!items.data.length" :colspan="9" title="No sales yet" />
                    </tbody>
                </table>
            </div>
            <Pagination :paginator="items" />
        </div>
    </div>
</template>
