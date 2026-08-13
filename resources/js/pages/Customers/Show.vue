<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, Pencil } from '@lucide/vue';
import DetailGrid from '@/components/DetailGrid.vue';
import Heading from '@/components/Heading.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import { money } from '@/lib/utils';

const props = defineProps<{
    item: Record<string, any>;
    stats: { sales_count: number; sales_total: number; payments_total: number };
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Customers', href: '/customers' },
            { title: 'Details', href: '#' },
        ],
    },
});
</script>

<template>
    <Head :title="item.name" />
    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div class="space-y-3">
                <Button as-child variant="ghost" size="sm" class="-ml-2 w-fit">
                    <Link href="/customers"><ArrowLeft class="size-4" /> Back</Link>
                </Button>
                <Heading :title="item.name" :description="`Customer ${item.customer_no || ''}`" />
            </div>
            <Button as-child>
                <Link :href="`/customers/${item.id}/edit`"><Pencil class="size-4" /> Edit</Link>
            </Button>
        </div>

        <div class="grid gap-4 sm:grid-cols-3">
            <div class="rounded-xl border bg-card p-4 shadow-sm">
                <div class="text-sm text-muted-foreground">Sales</div>
                <div class="mt-1 text-2xl font-semibold tabular-nums">{{ stats.sales_count }}</div>
            </div>
            <div class="rounded-xl border bg-card p-4 shadow-sm">
                <div class="text-sm text-muted-foreground">Sales total</div>
                <div class="mt-1 text-2xl font-semibold tabular-nums">{{ money(stats.sales_total) }}</div>
            </div>
            <div class="rounded-xl border bg-card p-4 shadow-sm">
                <div class="text-sm text-muted-foreground">Payments received</div>
                <div class="mt-1 text-2xl font-semibold tabular-nums text-emerald-700">{{ money(stats.payments_total) }}</div>
            </div>
        </div>

        <DetailGrid
            title="Customer details"
            :items="[
                { label: 'Customer No', value: item.customer_no },
                { label: 'Father name', value: item.father_name },
                { label: 'Phone', value: item.phone },
                { label: 'Email', value: item.email },
                { label: 'National ID', value: item.national_id },
                { label: 'Address', value: item.address, span: true },
                { label: 'Notes', value: item.notes, span: true },
            ]"
        />

        <div class="overflow-hidden rounded-xl border bg-card shadow-sm">
            <div class="border-b px-5 py-3">
                <h3 class="text-sm font-semibold">Recent sales</h3>
            </div>
            <table class="min-w-full text-sm">
                <thead class="bg-muted/40 text-left text-muted-foreground">
                    <tr>
                        <th class="px-4 py-3 font-medium">Sale</th>
                        <th class="px-4 py-3 font-medium">Project</th>
                        <th class="px-4 py-3 font-medium">Unit</th>
                        <th class="px-4 py-3 font-medium">Net</th>
                        <th class="px-4 py-3 font-medium">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="sale in item.sales" :key="sale.id" class="border-t hover:bg-muted/30">
                        <td class="px-4 py-3">
                            <Link :href="`/sales/${sale.id}`" class="font-medium text-primary hover:underline">{{ sale.sale_no }}</Link>
                        </td>
                        <td class="px-4 py-3">{{ sale.project?.name || '—' }}</td>
                        <td class="px-4 py-3">{{ sale.unit?.unit_no || '—' }}</td>
                        <td class="px-4 py-3 tabular-nums">{{ money(Math.max(0, Number(sale.unit?.list_price ?? 0) - Number(sale.discount ?? 0)), sale.currency || 'USD') }}</td>
                        <td class="px-4 py-3"><StatusBadge :status="sale.status" /></td>
                    </tr>
                    <tr v-if="!item.sales?.length">
                        <td colspan="5" class="px-4 py-10 text-center text-muted-foreground">No sales yet</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
