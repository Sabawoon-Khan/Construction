<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Eye, Pencil, Plus, Printer, Search, Trash2 } from '@lucide/vue';
import { ref } from 'vue';
import EmptyState from '@/components/EmptyState.vue';
import Heading from '@/components/Heading.vue';
import { MisStatCard, MisStatGrid } from '@/components/mis';
import Pagination from '@/components/Pagination.vue';
import RowActionsMenu from '@/components/RowActionsMenu.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { useTranslations } from '@/composables/useTranslations';
import type { RowActionItem } from '@/lib/row-actions';
import { money } from '@/lib/utils';

const props = defineProps<{
    items: { data: any[]; links: any[]; meta?: any };
    filters: { search?: string };
    totals: { count: number; amount: number; this_month: number; average: number };
}>();

const search = ref(props.filters.search ?? '');
const { t } = useTranslations();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Expenses', href: '/expenses' },
        ],
    },
});

function applyFilters() {
    router.get('/expenses', { search: search.value || undefined }, { preserveState: true, replace: true });
}

function rowActions(item: any): RowActionItem[] {
    return [
        { label: t('View'), icon: Eye, href: `/expenses/${item.id}` },
        { label: t('Print'), icon: Printer, href: `/expenses/${item.id}` },
        { label: t('Edit'), icon: Pencil, href: `/expenses/${item.id}/edit` },
        { separator: true, isLabel: true, label: t('Danger') },
        {
            label: t('Delete'),
            icon: Trash2,
            method: 'delete',
            href: `/expenses/${item.id}`,
            variant: 'destructive' as const,
            confirm: {
                title: t('Delete expense'),
                description: t('Are you sure you want to delete this expense? This action cannot be undone.'),
                confirmLabel: t('Delete'),
            },
        },
    ];
}
</script>

<template>
    <Head :title="t('Expenses')" />
    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <Heading :title="t('General Expenses')" :description="t('Company-wide costs not tied to a project. Use the project page for project expenses.')" />
            <div class="flex flex-wrap gap-2">
                <Input v-model="search" class="min-w-[180px]" :placeholder="t('Search...')" @keyup.enter="applyFilters" />
                <Button variant="secondary" @click="applyFilters"><Search class="size-4" /> {{ t('Filter') }}</Button>
                <Button as-child><Link href="/expenses/create"><Plus class="size-4" /> {{ t('Add expense') }}</Link></Button>
            </div>
        </div>

        <MisStatGrid :columns="4">
            <MisStatCard :label="t('Entries')" :value="totals.count" :hint="t('Approved expenses')" />
            <MisStatCard :label="t('Total spent')" :value="money(totals.amount)" tone="danger" />
            <MisStatCard :label="t('This month')" :value="money(totals.this_month)" />
            <MisStatCard :label="t('Average')" :value="money(totals.average)" />
        </MisStatGrid>

        <div class="overflow-hidden rounded-xl border border-sidebar-border/70 bg-card shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="border-b bg-muted/40 text-left text-muted-foreground">
                        <tr>
                            <th class="px-4 py-3 font-medium">{{ t('Date') }}</th>
                            <th class="px-4 py-3 font-medium">{{ t('Paid to') }}</th>
                            <th class="px-4 py-3 font-medium">{{ t('Description') }}</th>
                            <th class="px-4 py-3 font-medium">{{ t('Unit') }}</th>
                            <th class="px-4 py-3 font-medium">{{ t('Item amount') }}</th>
                            <th class="px-4 py-3 font-medium">{{ t('Per price') }}</th>
                            <th class="px-4 py-3 font-medium">{{ t('Total cost') }}</th>
                            <th class="px-4 py-3 font-medium text-right">{{ t('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="item in items.data" :key="item.id" class="border-b last:border-0 transition-colors hover:bg-muted/30">
                            <td class="px-4 py-3">{{ item.expense_date?.slice?.(0, 10) ?? item.expense_date }}</td>
                            <td class="px-4 py-3">{{ item.paid_to || '—' }}</td>
                            <td class="px-4 py-3">{{ item.description || '—' }}</td>
                            <td class="px-4 py-3">{{ item.expense_unit?.unit_name || '—' }}</td>
                            <td class="px-4 py-3 tabular-nums">{{ item.item_amount ?? '—' }}</td>
                            <td class="px-4 py-3 tabular-nums">{{ item.per_price != null ? money(item.per_price) : '—' }}</td>
                            <td class="px-4 py-3 tabular-nums">{{ money(item.amount) }}</td>
                            <td class="px-4 py-3">
                                <div class="flex justify-end">
                                    <RowActionsMenu :actions="rowActions(item)" />
                                </div>
                            </td>
                        </tr>
                        <EmptyState v-if="!items.data.length" :colspan="8" :title="t('No expenses yet')" />
                    </tbody>
                </table>
            </div>
            <Pagination :paginator="items" />
        </div>
    </div>
</template>
