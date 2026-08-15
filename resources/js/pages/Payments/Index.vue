<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Eye, Printer } from '@lucide/vue';
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
import { money } from '@/lib/utils';

const props = defineProps<{
    items: { data: any[]; links: any[]; meta?: any };
    filters: { search?: string };
    totals: { count: number; amount: number; this_month: number; cash: number };
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

function rowActions(item: any): RowActionItem[] {
    return [
        { label: 'View', icon: Eye, href: `/payments/${item.id}` },
        { label: 'Print', icon: Printer, href: `/payments/${item.id}` },
    ];
}
</script>

<template>
    <Head title="Payments" />
    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <Heading title="Payments" description="Receipts created when you collect money on a sale. Open a sale to receive a payment." />
            <div class="flex flex-wrap gap-2">
                <Input v-model="search" placeholder="Search..." @keyup.enter="apply" />
                <Button variant="secondary" @click="apply">Search</Button>
                <Button as-child variant="outline"><Link href="/sales">Go to sales</Link></Button>
            </div>
        </div>

        <MisStatGrid :columns="4">
            <MisStatCard label="Receipts" :value="totals.count" hint="Collected payments" />
            <MisStatCard label="Total collected" :value="money(totals.amount)" tone="success" />
            <MisStatCard label="This month" :value="money(totals.this_month)" />
            <MisStatCard label="Cash" :value="money(totals.cash)" />
        </MisStatGrid>

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
                        <EmptyState v-if="!items.data.length" :colspan="8" title="No receipts yet" description="Payments appear here when collected on a sale." />
                    </tbody>
                </table>
            </div>
            <Pagination :paginator="items" />
        </div>
    </div>
</template>
