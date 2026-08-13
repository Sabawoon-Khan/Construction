<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Eye, Plus, Printer, Trash2 } from '@lucide/vue';
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
    totals: { amount: number };
}>();
const search = ref(props.filters.search ?? '');

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Payments', href: '/payments' },
        ],
    },
});

const apply = () => router.get('/payments', { search: search.value || undefined }, { preserveState: true, replace: true });

const paymentStatuses = ['posted', 'pending', 'cancelled'];

function rowActions(item: any): RowActionItem[] {
    return [
        { label: 'View', icon: Eye, href: `/payments/${item.id}` },
        { label: 'Print', icon: Printer, href: `/payments/${item.id}` },
        { separator: true, isLabel: true, label: 'Change status' },
        ...paymentStatuses.map((status) => ({
            label: status.charAt(0).toUpperCase() + status.slice(1),
            method: 'patch' as const,
            href: `/payments/${item.id}/status`,
            data: { status },
            disabled: item.status === status,
        })),
        { separator: true, isLabel: true, label: 'Danger' },
        {
            label: 'Delete',
            icon: Trash2,
            method: 'delete',
            href: `/payments/${item.id}`,
            variant: 'destructive' as const,
            confirm: {
                title: 'Delete payment',
                description: 'Are you sure you want to delete this payment? This action cannot be undone.',
                confirmLabel: 'Delete',
            },
        },
    ];
}
</script>

<template>
    <Head title="Payments" />
    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <Heading title="Payments" description="Receipts against sales and installments." />
            <div class="flex flex-wrap gap-2">
                <Input v-model="search" placeholder="Search..." @keyup.enter="apply" />
                <Button variant="secondary" @click="apply">Search</Button>
                <Button as-child><Link href="/payments/create"><Plus class="size-4" /> Record payment</Link></Button>
            </div>
        </div>

        <div class="rounded-xl border border-sidebar-border/70 bg-card px-4 py-3 text-sm shadow-sm">
            Total receipts:
            <span class="font-semibold tabular-nums text-emerald-700">{{ money(totals.amount) }}</span>
        </div>

        <div class="overflow-hidden rounded-xl border border-sidebar-border/70 bg-card shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="border-b bg-muted/40 text-left text-muted-foreground">
                        <tr>
                            <th class="px-4 py-3 font-medium">No</th>
                            <th class="px-4 py-3 font-medium">Date</th>
                            <th class="px-4 py-3 font-medium">Customer</th>
                            <th class="px-4 py-3 font-medium">Sale</th>
                            <th class="px-4 py-3 font-medium">Amount</th>
                            <th class="px-4 py-3 font-medium">Method</th>
                            <th class="px-4 py-3 font-medium">Status</th>
                            <th class="px-4 py-3 font-medium text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="item in items.data" :key="item.id" class="border-b last:border-0 transition-colors hover:bg-muted/30">
                            <td class="px-4 py-3 font-medium">
                                <Link :href="`/payments/${item.id}`" class="hover:text-primary hover:underline">{{ item.payment_no }}</Link>
                            </td>
                            <td class="px-4 py-3">{{ item.paid_at }}</td>
                            <td class="px-4 py-3">{{ item.customer?.name || '—' }}</td>
                            <td class="px-4 py-3">{{ item.sale?.sale_no || '—' }}</td>
                            <td class="px-4 py-3 tabular-nums">{{ money(item.amount) }}</td>
                            <td class="px-4 py-3 capitalize">{{ item.payment_method }}</td>
                            <td class="px-4 py-3"><StatusBadge :status="item.status" /></td>
                            <td class="px-4 py-3">
                                <div class="flex justify-end">
                                    <RowActionsMenu :actions="rowActions(item)" />
                                </div>
                            </td>
                        </tr>
                        <EmptyState v-if="!items.data.length" :colspan="8" title="No payments yet" />
                    </tbody>
                </table>
            </div>
            <Pagination :paginator="items" />
        </div>
    </div>
</template>
