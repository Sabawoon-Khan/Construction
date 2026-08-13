<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, Pencil } from '@lucide/vue';
import DetailGrid from '@/components/DetailGrid.vue';
import Heading from '@/components/Heading.vue';
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
            { title: 'Suppliers', href: '/suppliers' },
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
                    <Link href="/suppliers"><ArrowLeft class="size-4" /> Back</Link>
                </Button>
                <Heading :title="item.name" :description="`Supplier ${item.supplier_no || ''}`" />
            </div>
            <Button as-child>
                <Link :href="`/suppliers/${item.id}/edit`"><Pencil class="size-4" /> Edit</Link>
            </Button>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div class="rounded-xl border bg-card p-4 shadow-sm">
                <div class="text-sm text-muted-foreground">Expense records</div>
                <div class="mt-1 text-2xl font-semibold tabular-nums">{{ stats.expenses_count }}</div>
            </div>
            <div class="rounded-xl border bg-card p-4 shadow-sm">
                <div class="text-sm text-muted-foreground">Total expenses</div>
                <div class="mt-1 text-2xl font-semibold tabular-nums text-rose-700">{{ money(stats.expenses_total) }}</div>
            </div>
        </div>

        <DetailGrid
            title="Supplier details"
            :items="[
                { label: 'Supplier No', value: item.supplier_no },
                { label: 'Contact person', value: item.contact_person },
                { label: 'Phone', value: item.phone },
                { label: 'Email', value: item.email },
                { label: 'Address', value: item.address, span: true },
                { label: 'Notes', value: item.notes, span: true },
            ]"
        />

        <div class="overflow-hidden rounded-xl border bg-card shadow-sm">
            <div class="border-b px-5 py-3">
                <h3 class="text-sm font-semibold">Recent expenses</h3>
            </div>
            <table class="min-w-full text-sm">
                <thead class="bg-muted/40 text-left text-muted-foreground">
                    <tr>
                        <th class="px-4 py-3 font-medium">Date</th>
                        <th class="px-4 py-3 font-medium">No</th>
                        <th class="px-4 py-3 font-medium">Project</th>
                        <th class="px-4 py-3 font-medium">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="expense in item.expenses" :key="expense.id" class="border-t hover:bg-muted/30">
                        <td class="px-4 py-3">{{ expense.expense_date }}</td>
                        <td class="px-4 py-3">
                            <Link :href="`/expenses/${expense.id}`" class="font-medium text-primary hover:underline">{{ expense.expense_no }}</Link>
                        </td>
                        <td class="px-4 py-3">{{ expense.project?.name || '—' }}</td>
                        <td class="px-4 py-3 tabular-nums">{{ money(expense.amount) }}</td>
                    </tr>
                    <tr v-if="!item.expenses?.length">
                        <td colspan="4" class="px-4 py-10 text-center text-muted-foreground">No expenses yet</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
