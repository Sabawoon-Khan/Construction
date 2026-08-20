<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { Eye, Pencil, Trash2 } from '@lucide/vue';
import { ref } from 'vue';
import EmptyState from '@/components/EmptyState.vue';
import IncomeRecordDialog from '@/components/IncomeRecordDialog.vue';
import { MisFilterBar, MisSearchInput, MisStatCard, MisStatGrid } from '@/components/mis';
import Pagination from '@/components/Pagination.vue';
import RowActionsMenu from '@/components/RowActionsMenu.vue';
import { useCurrency } from '@/composables/useCurrency';
import { useMisPage } from '@/composables/useMisPage';
import { useTranslations } from '@/composables/useTranslations';
import type { RowActionItem } from '@/lib/row-actions';
import { money, moneyByCurrency } from '@/lib/utils';

const props = defineProps<{
    items: { data: any[]; links: any[]; meta?: any };
    filters: { search?: string };
    totals: { count: number; amount: number; amount_base: number; this_month: number; this_month_base: number; average: number; average_base: number; amount_by_currency?: Record<string, number>; this_month_by_currency?: Record<string, number> };
}>();

const search = ref(props.filters.search ?? '');
const { t } = useTranslations();
const { baseCurrency } = useCurrency();
const { can, formatDate } = useMisPage();
const dialogOpen = ref(false);
const selected = ref<Record<string, any> | null>(null);
const startEditing = ref(false);

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Incomes', href: '/incomes' },
        ],
    },
});

const apply = () => router.get('/incomes', { search: search.value || undefined }, { preserveState: true, replace: true });
const clearFilters = () => {
    search.value = '';
    apply();
};

function openRecord(item: Record<string, any> | null, edit = false) {
    selected.value = item;
    startEditing.value = edit && !!item;
    dialogOpen.value = true;
}

function rowActions(item: any): RowActionItem[] {
    return [
        { label: t('View'), icon: Eye, onClick: () => openRecord(item, false) },
        { label: t('Edit'), icon: Pencil, onClick: () => openRecord(item, true), hidden: !can('incomes.edit') },
        { separator: true, isLabel: true, label: t('Danger') },
        {
            label: t('Delete'),
            icon: Trash2,
            method: 'delete',
            href: `/incomes/${item.id}`,
            variant: 'destructive' as const,
            hidden: !can('incomes.delete'),
            confirm: {
                title: t('Delete income'),
                description: t('Are you sure you want to delete this income? This action cannot be undone.'),
                confirmLabel: t('Delete'),
            },
        },
    ];
}
</script>

<template>
    <Head :title="t('Incomes')" />
    <div class="flex h-full flex-1 flex-col gap-4">
        <MisFilterBar :apply-label="t('Apply')" :clear-label="t('All')" @apply="apply" @clear="clearFilters">
            <MisSearchInput v-model="search" class="min-w-[220px] flex-1" :placeholder="t('Search...')" />
        </MisFilterBar>

        <MisStatGrid :columns="4">
            <MisStatCard :label="t('Entries')" :value="totals.count" />
            <MisStatCard :label="t('Total income')" :value="moneyByCurrency(totals.amount_by_currency, totals.amount)" tone="success" />
            <MisStatCard :label="t('This month')" :value="moneyByCurrency(totals.this_month_by_currency, totals.this_month)" />
            <MisStatCard :label="t('Average')" :value="moneyByCurrency(totals.amount_by_currency, totals.average)" />
        </MisStatGrid>

        <div class="overflow-hidden rounded-2xl border border-border/80 bg-card shadow-soft">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-muted/30 text-start text-muted-foreground">
                        <tr>
                            <th class="px-4 py-3 font-medium">{{ t('Date') }}</th>
                            <th class="px-4 py-3 font-medium">{{ t('Description') }}</th>
                            <th class="px-4 py-3 font-medium">{{ t('Total amount') }}</th>
                            <th class="px-4 py-3 font-medium">{{ t('Added by') }}</th>

                            <th class="px-4 py-3 font-medium text-end">{{ t('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="item in items.data"
                            :key="item.id"
                            class="cursor-pointer border-b last:border-0 transition-colors hover:bg-muted/30"
                            @click="openRecord(item)"
                        >
                            <td class="px-4 py-3">{{ formatDate(item.income_date) }}</td>
                            <td class="px-4 py-3">{{ item.description || '—' }}</td>
                            <td class="px-4 py-3 tabular-nums">{{ money(item.amount, item.currency || baseCurrency) }}</td>
                            <td class="px-4 py-3 text-muted-foreground">{{ item.creator?.name || '—' }}</td>

                            <td class="px-4 py-3" @click.stop>
                                <div class="flex justify-end">
                                    <RowActionsMenu :actions="rowActions(item)" />
                                </div>
                            </td>
                        </tr>
                        <EmptyState v-if="!items.data.length" :colspan="5" :title="t('No income records yet')" />
                    </tbody>
                </table>
            </div>
            <Pagination :paginator="items" />
        </div>

        <IncomeRecordDialog v-model:open="dialogOpen" :item="selected" :start-editing="startEditing" />
    </div>
</template>
