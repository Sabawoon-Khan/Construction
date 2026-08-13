<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, Pencil } from '@lucide/vue';
import DetailGrid from '@/components/DetailGrid.vue';
import Heading from '@/components/Heading.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import { formatNumber, money } from '@/lib/utils';

defineProps<{
    item: Record<string, any>;
    stats: Record<string, number>;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Projects', href: '/projects' },
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
                    <Link href="/projects"><ArrowLeft class="size-4" /> Back</Link>
                </Button>
                <Heading :title="item.name" :description="`${item.code} · ${item.location || 'No location'}`" />
            </div>
            <div class="flex items-center gap-2">
                <StatusBadge :status="item.status" />
                <Button as-child>
                    <Link :href="`/projects/${item.id}/edit`"><Pencil class="size-4" /> Edit</Link>
                </Button>
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="rounded-xl border bg-card p-4 shadow-sm">
                <div class="text-sm text-muted-foreground">Units</div>
                <div class="mt-1 text-2xl font-semibold tabular-nums">{{ stats.units_count }}</div>
            </div>
            <div class="rounded-xl border bg-card p-4 shadow-sm">
                <div class="text-sm text-muted-foreground">Sales</div>
                <div class="mt-1 text-2xl font-semibold tabular-nums">{{ money(stats.sales_total) }}</div>
            </div>
            <div class="rounded-xl border bg-card p-4 shadow-sm">
                <div class="text-sm text-muted-foreground">Expenses</div>
                <div class="mt-1 text-2xl font-semibold tabular-nums text-rose-700">{{ money(stats.expenses_total) }}</div>
            </div>
            <div class="rounded-xl border bg-card p-4 shadow-sm">
                <div class="text-sm text-muted-foreground">Other income</div>
                <div class="mt-1 text-2xl font-semibold tabular-nums text-emerald-700">{{ money(stats.incomes_total) }}</div>
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <div class="rounded-xl border bg-card p-4 shadow-sm">
                <div class="text-sm text-muted-foreground">Estimated revenue</div>
                <div class="mt-1 text-2xl font-semibold tabular-nums">{{ money(item.estimated_revenue) }}</div>
            </div>
            <div class="rounded-xl border bg-card p-4 shadow-sm">
                <div class="text-sm text-muted-foreground">Remaining revenue</div>
                <div class="mt-1 text-2xl font-semibold tabular-nums">{{ money(Math.max(0, Number(item.estimated_revenue ?? 0) - stats.sales_total)) }}</div>
            </div>
            <div class="rounded-xl border bg-card p-4 shadow-sm">
                <div class="text-sm text-muted-foreground">Estimated benefit</div>
                <div class="mt-1 text-2xl font-semibold tabular-nums text-emerald-700">{{ money(Math.max(0, Number(item.estimated_revenue ?? 0) - stats.expenses_total)) }}</div>
            </div>
        </div>

        <div class="grid gap-4 lg:grid-cols-2">
            <DetailGrid
                title="Project details"
                :items="[
                    { label: 'Code', value: item.code },
                    { label: 'Type', value: item.type },
                    { label: 'Location', value: item.location },
                    { label: 'Progress', value: `${formatNumber(item.progress_percent)}%` },
                    { label: 'Start date', value: item.start_date },
                    { label: 'Expected completion', value: item.expected_completion_date },
                    { label: 'Estimated budget', value: money(item.estimated_budget) },
                    { label: 'Estimated revenue', value: money(item.estimated_revenue) },
                    { label: 'Description', value: item.description, span: true },
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
                            <th class="px-4 py-3 font-medium">Customer</th>
                            <th class="px-4 py-3 font-medium">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="sale in item.sales" :key="sale.id" class="border-t hover:bg-muted/30">
                            <td class="px-4 py-3">
                                <Link :href="`/sales/${sale.id}`" class="font-medium text-primary hover:underline">{{ sale.sale_no }}</Link>
                            </td>
                            <td class="px-4 py-3">{{ sale.customer?.name || '—' }}</td>
                            <td class="px-4 py-3 tabular-nums">{{ money(Math.max(0, Number(sale.unit?.list_price ?? 0) - Number(sale.discount ?? 0)), sale.currency || 'USD') }}</td>
                        </tr>
                        <tr v-if="!item.sales?.length">
                            <td colspan="3" class="px-4 py-10 text-center text-muted-foreground">No sales yet</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>
