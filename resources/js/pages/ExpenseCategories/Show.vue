<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, Pencil } from '@lucide/vue';
import DetailGrid from '@/components/DetailGrid.vue';
import Heading from '@/components/Heading.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import { money } from '@/lib/utils';

defineProps<{
    item: Record<string, any>;
    stats: { expenses_total: number; expenses_count: number };
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Expense Categories', href: '/expense-categories' },
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
                    <Link href="/expense-categories"><ArrowLeft class="size-4" /> Back</Link>
                </Button>
                <Heading :title="item.name" :description="item.code || 'Expense category'" />
            </div>
            <div class="flex items-center gap-2">
                <StatusBadge :status="item.is_active ? 'active' : 'inactive'" />
                <Button as-child>
                    <Link :href="`/expense-categories/${item.id}/edit`"><Pencil class="size-4" /> Edit</Link>
                </Button>
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div class="rounded-xl border bg-card p-4 shadow-sm">
                <div class="text-sm text-muted-foreground">Expenses</div>
                <div class="mt-1 text-2xl font-semibold tabular-nums">{{ stats.expenses_count }}</div>
            </div>
            <div class="rounded-xl border bg-card p-4 shadow-sm">
                <div class="text-sm text-muted-foreground">Total amount</div>
                <div class="mt-1 text-2xl font-semibold tabular-nums text-rose-700">{{ money(stats.expenses_total) }}</div>
            </div>
        </div>

        <DetailGrid
            title="Category details"
            :items="[
                { label: 'Code', value: item.code },
                { label: 'Status', value: item.is_active ? 'Active' : 'Inactive' },
                { label: 'Description', value: item.description, span: true },
            ]"
        />
    </div>
</template>
