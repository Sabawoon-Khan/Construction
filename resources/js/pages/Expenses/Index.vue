<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { Eye, Pencil, Printer, Trash2 } from '@lucide/vue';
import { computed, ref } from 'vue';
import EmptyState from '@/components/EmptyState.vue';
import SortableTh from '@/components/SortableTh.vue';
import TableFrame from '@/components/TableFrame.vue';
import ExpenseRecordDialog from '@/components/ExpenseRecordDialog.vue';
import AttachmentLinks from '@/components/AttachmentLinks.vue';
import { MisFilterBar, MisSearchInput, MisStatCard, MisStatGrid } from '@/components/mis';
import Pagination from '@/components/Pagination.vue';
import RowActionsMenu from '@/components/RowActionsMenu.vue';
import { useCurrency } from '@/composables/useCurrency';
import { useMisPage } from '@/composables/useMisPage';
import { provideTableSort } from '@/composables/useTableSort';
import { useTranslations } from '@/composables/useTranslations';
import type { RowActionItem } from '@/lib/row-actions';
import { money, moneyByCurrency } from '@/lib/utils';

const props = defineProps<{
    items: { data: any[]; links: any[]; meta?: any };
    filters: { search?: string };
    totals: { count: number; amount: number; amount_base: number; this_month: number; this_month_base: number; average: number; average_base: number; amount_by_currency?: Record<string, number>; this_month_by_currency?: Record<string, number> };
    categories?: { id: number; name: string; code: string }[];
    units?: { id: number; unit_name: string }[];
}>();

const search = ref(props.filters.search ?? '');
const { sortedRows } = provideTableSort(() => props.items.data);
const { t } = useTranslations();
const tableColumns = computed(() => [
    { key: 'expense_date', label: t('Date') },
    { key: 'paid_to', label: t('Paid to') },
    { key: 'description', label: t('Description') },
    { key: 'expense_unit.unit_name', label: t('Unit') },
    { key: 'item_amount', label: t('Item amount') },
    { key: 'per_price', label: t('Per price') },
    { key: 'amount', label: t('Total cost') },
    { key: 'creator.name', label: t('Added by') },
    { key: 'attachments', label: t('Attachments') },
    { key: 'created_at', label: t('Created at') },
    { key: 'actions', label: t('Actions'), locked: true },
]);
const { baseCurrency } = useCurrency();
const { can, formatDate } = useMisPage();
const dialogOpen = ref(false);
const selected = ref<Record<string, any> | null>(null);
const startEditing = ref(false);

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

function clearFilters() {
    search.value = '';
    applyFilters();
}

function openRecord(item: Record<string, any> | null, edit = false) {
    selected.value = item;
    startEditing.value = edit && !!item && !item.purchase_id;
    dialogOpen.value = true;
}

function rowActions(item: any): RowActionItem[] {
    return [
        { label: t('View'), icon: Eye, onClick: () => openRecord(item, false) },
        { label: t('Print'), icon: Printer, href: `/expenses/${item.id}` },
        { label: t('Edit'), icon: Pencil, onClick: () => openRecord(item, true), hidden: !can('expenses.edit') || !!item.purchase_id },
        { separator: true, isLabel: true, label: t('Danger') },
        {
            label: t('Delete'),
            icon: Trash2,
            method: 'delete',
            href: `/expenses/${item.id}`,
            variant: 'destructive' as const,
            hidden: !can('expenses.delete') || !!item.purchase_id,
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
    <div class="flex h-full flex-1 flex-col gap-4">
        <MisFilterBar :apply-label="t('Apply')" :clear-label="t('All')" @apply="applyFilters" @clear="clearFilters">
            <MisSearchInput v-model="search" class="min-w-[220px] flex-1" :placeholder="t('Search...')" />
        </MisFilterBar>

        <MisStatGrid :columns="4">
            <MisStatCard :label="t('Entries')" :value="totals.count" />
            <MisStatCard :label="t('Total spent')" :value="moneyByCurrency(totals.amount_by_currency, totals.amount)" tone="danger" />
            <MisStatCard :label="t('This month')" :value="moneyByCurrency(totals.this_month_by_currency, totals.this_month)" />
            <MisStatCard :label="t('Average')" :value="moneyByCurrency(totals.amount_by_currency, totals.average)" />
        </MisStatGrid>

        <div class="overflow-hidden rounded-2xl border border-border/80 bg-card shadow-soft">
            <TableFrame table-id="expenses" :columns="tableColumns">
                <table class="min-w-full text-sm">
                    <thead class="bg-muted/30 text-start text-muted-foreground">
                        <tr>
                            <SortableTh column="expense_date">{{ t('Date') }}</SortableTh>
                            <SortableTh column="paid_to">{{ t('Paid to') }}</SortableTh>
                            <SortableTh column="description">{{ t('Description') }}</SortableTh>
                            <SortableTh column="expense_unit.unit_name">{{ t('Unit') }}</SortableTh>
                            <SortableTh column="item_amount">{{ t('Item amount') }}</SortableTh>
                            <SortableTh column="per_price">{{ t('Per price') }}</SortableTh>
                            <SortableTh column="amount">{{ t('Total cost') }}</SortableTh>
                            <SortableTh column="creator.name">{{ t('Added by') }}</SortableTh>
                            <th class="px-4 py-3 font-medium">{{ t('Attachments') }}</th>
                            <SortableTh column="created_at">{{ t('Created at') }}</SortableTh>
                            <th class="px-4 py-3 font-medium text-end">{{ t('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="item in sortedRows"
                            :key="item.id"
                            class="cursor-pointer border-b last:border-0 transition-colors hover:bg-muted/30"
                            @click="openRecord(item)"
                        >
                            <td class="px-4 py-3">{{ formatDate(item.expense_date) }}</td>
                            <td class="px-4 py-3">{{ item.paid_to || '—' }}</td>
                            <td class="px-4 py-3">{{ item.description || '—' }}</td>
                            <td class="px-4 py-3">{{ item.expense_unit?.unit_name || '—' }}</td>
                            <td class="px-4 py-3 tabular-nums">{{ item.item_amount ?? '—' }}</td>
                            <td class="px-4 py-3 tabular-nums">{{ item.per_price != null ? money(item.per_price, item.currency || baseCurrency) : '—' }}</td>
                            <td class="px-4 py-3 tabular-nums">{{ money(item.amount, item.currency || baseCurrency) }}</td>
                            <td class="px-4 py-3 text-muted-foreground">{{ item.creator?.name || '—' }}</td>
                            <td class="px-4 py-3" @click.stop>
                                <AttachmentLinks :files="item.attachments" />
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-muted-foreground">{{ formatDate(item.created_at) }}</td>
                            <td class="px-4 py-3" @click.stop>
                                <div class="flex justify-end">
                                    <RowActionsMenu :actions="rowActions(item)" />
                                </div>
                            </td>
                        </tr>
                        <EmptyState v-if="!items.data.length" :colspan="11" :title="t('No expenses yet')" />
                    </tbody>
                </table>
            </TableFrame>
            <Pagination :paginator="items" />
        </div>

        <ExpenseRecordDialog
            v-model:open="dialogOpen"
            :item="selected"
            :start-editing="startEditing"
            :categories="categories"
            :units="units"
        />
    </div>
</template>
