<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Banknote, Trash2 } from '@lucide/vue';
import { ref } from 'vue';
import EmptyState from '@/components/EmptyState.vue';
import { MisFilterBar, MisSearchInput, MisStatCard, MisStatGrid } from '@/components/mis';
import Pagination from '@/components/Pagination.vue';
import RowActionsMenu from '@/components/RowActionsMenu.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { useCurrency } from '@/composables/useCurrency';
import { useMisPage } from '@/composables/useMisPage';
import { useTranslations } from '@/composables/useTranslations';
import type { RowActionItem } from '@/lib/row-actions';
import { money } from '@/lib/utils';

const props = defineProps<{
    items: { data: any[]; links: any[]; meta?: any };
    filters: { search?: string };
    totals: { count: number; total_amount: number; total_amount_base: number; paid: number; paid_base: number; remaining: number; remaining_base: number };
}>();
const search = ref(props.filters.search ?? '');
const { t } = useTranslations();
const { baseCurrency } = useCurrency();
const { viewAction, editAction, can, formatDate } = useMisPage();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Purchases', href: '/purchases' },
        ],
    },
});

const apply = () => router.get('/purchases', { search: search.value || undefined }, { preserveState: true, replace: true });
const clearFilters = () => {
    search.value = '';
    apply();
};

function rowActions(item: any): RowActionItem[] {
    return [
        viewAction(`/purchases/${item.id}`),
        {
            label: t('Record payment'),
            icon: Banknote,
            href: `/purchases/${item.id}#pay`,
            hidden: !can('purchases.edit') || Number(item.remaining_amount ?? 0) <= 0,
        },
        editAction(`/purchases/${item.id}/edit`, 'purchases.edit'),
        { separator: true, isLabel: true, label: t('Danger') },
        {
            label: t('Delete'),
            icon: Trash2,
            method: 'delete',
            href: `/purchases/${item.id}`,
            variant: 'destructive' as const,
            hidden: !can('purchases.delete'),
            confirm: {
                title: t('Delete purchase'),
                description: t('This also removes the linked project expense and stock in, if the stock is still unused.'),
                confirmLabel: t('Delete'),
            },
        },
    ];
}
</script>

<template>
    <Head :title="t('Purchases')" />
    <div class="flex h-full flex-1 flex-col gap-4">
        <MisFilterBar :apply-label="t('Apply')" :clear-label="t('All')" @apply="apply" @clear="clearFilters">
            <MisSearchInput v-model="search" class="min-w-[220px] flex-1" :placeholder="t('Search...')" />
        </MisFilterBar>

        <MisStatGrid :columns="4">
            <MisStatCard :label="t('Bills')" :value="totals.count" />
            <MisStatCard :label="t('Purchase total')" :value="money(totals.total_amount_base ?? totals.total_amount, baseCurrency)" />
            <MisStatCard :label="t('Paid')" :value="money(totals.paid_base ?? totals.paid, baseCurrency)" tone="success" />
            <MisStatCard :label="t('Remaining owed')" :value="money(totals.remaining_base ?? totals.remaining, baseCurrency)" tone="warning" />
        </MisStatGrid>

        <div class="overflow-hidden rounded-2xl border border-border/80 bg-card shadow-soft">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-muted/30 text-left text-muted-foreground">
                        <tr>
                            <th class="px-4 py-3 font-medium">{{ t('Purchase No') }}</th>
                            <th class="px-4 py-3 font-medium">{{ t('Date') }}</th>
                            <th class="px-4 py-3 font-medium">{{ t('Project') }}</th>
                            <th class="px-4 py-3 font-medium">{{ t('Supplier') }}</th>
                            <th class="px-4 py-3 font-medium">{{ t('Total') }}</th>
                            <th class="px-4 py-3 font-medium">{{ t('Paid') }}</th>
                            <th class="px-4 py-3 font-medium">{{ t('Remaining') }}</th>
                            <th class="px-4 py-3 font-medium">{{ t('Status') }}</th>
                            <th class="px-4 py-3 font-medium text-right">{{ t('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="item in items.data" :key="item.id" class="border-b last:border-0 transition-colors hover:bg-muted/30">
                            <td class="px-4 py-3 font-medium">
                                <Link :href="`/purchases/${item.id}`" class="hover:text-primary hover:underline">{{ item.purchase_no }}</Link>
                            </td>
                            <td class="px-4 py-3">{{ formatDate(item.purchase_date) }}</td>
                            <td class="px-4 py-3">{{ item.project?.name }}</td>
                            <td class="px-4 py-3">{{ item.supplier?.name }}</td>
                            <td class="px-4 py-3 tabular-nums">{{ money(item.total_amount, item.currency || baseCurrency) }}</td>
                            <td class="px-4 py-3 tabular-nums">{{ money(item.paid_amount, item.currency || baseCurrency) }}</td>
                            <td class="px-4 py-3 tabular-nums text-amber-700">{{ money(item.remaining_amount, item.currency || baseCurrency) }}</td>
                            <td class="px-4 py-3"><StatusBadge :status="item.status" /></td>
                            <td class="px-4 py-3">
                                <div class="flex justify-end">
                                    <RowActionsMenu :actions="rowActions(item)" />
                                </div>
                            </td>
                        </tr>
                        <EmptyState v-if="!items.data.length" :colspan="9" :title="t('No purchases yet')" />
                    </tbody>
                </table>
            </div>
            <Pagination :paginator="items" />
        </div>
    </div>
</template>
