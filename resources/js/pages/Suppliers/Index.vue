<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Trash2 } from '@lucide/vue';
import { ref } from 'vue';
import EmptyState from '@/components/EmptyState.vue';
import { MisFilterBar, MisSearchInput, MisStatCard, MisStatGrid } from '@/components/mis';
import Pagination from '@/components/Pagination.vue';
import RowActionsMenu from '@/components/RowActionsMenu.vue';
import { useMisPage } from '@/composables/useMisPage';
import { useTranslations } from '@/composables/useTranslations';
import type { RowActionItem } from '@/lib/row-actions';
import { moneyByCurrency } from '@/lib/utils';

type Item = Record<string, any>;

const props = defineProps<{
    items: { data: Item[]; links: any[]; meta?: any };
    filters: { search?: string };
    totals: {
        count: number;
        purchases_total: number;
        paid_total: number;
        remaining_total: number;
        purchases_by_currency?: Record<string, number>;
        paid_by_currency?: Record<string, number>;
        remaining_by_currency?: Record<string, number>;
    };
}>();

const search = ref(props.filters.search ?? '');
const { t } = useTranslations();
const { viewAction, editAction, can } = useMisPage();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Suppliers', href: '/suppliers' },
        ],
    },
});

function applySearch() {
    router.get('/suppliers', { search: search.value || undefined }, { preserveState: true, replace: true });
}

function clearFilters() {
    search.value = '';
    applySearch();
}

function rowActions(item: Item): RowActionItem[] {
    return [
        viewAction(`/suppliers/${item.id}`),
        editAction(`/suppliers/${item.id}/edit`, 'suppliers.edit'),
        { separator: true, isLabel: true, label: t('Danger') },
        {
            label: t('Delete'),
            icon: Trash2,
            method: 'delete',
            href: `/suppliers/${item.id}`,
            variant: 'destructive' as const,
            hidden: !can('suppliers.delete'),
            confirm: {
                title: t('Delete supplier'),
                description: t('Are you sure you want to delete this supplier? This action cannot be undone.'),
                confirmLabel: t('Delete'),
            },
        },
    ];
}
</script>

<template>
    <Head :title="t('Suppliers')" />

    <div class="flex h-full flex-1 flex-col gap-4">
        <MisFilterBar :apply-label="t('Apply')" :clear-label="t('All')" @apply="applySearch" @clear="clearFilters">
            <MisSearchInput v-model="search" class="min-w-[220px] flex-1" :placeholder="t('Search suppliers...')" />
        </MisFilterBar>

        <MisStatGrid :columns="4">
            <MisStatCard :label="t('Suppliers')" :value="totals.count" />
            <MisStatCard :label="t('Purchases')" :value="moneyByCurrency(totals.purchases_by_currency, totals.purchases_total)" />
            <MisStatCard :label="t('Paid')" :value="moneyByCurrency(totals.paid_by_currency, totals.paid_total)" tone="success" />
            <MisStatCard :label="t('Remaining owed')" :value="moneyByCurrency(totals.remaining_by_currency, totals.remaining_total)" tone="warning" />
        </MisStatGrid>

        <div class="overflow-hidden rounded-2xl border border-border/80 bg-card shadow-soft">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-muted/30 text-start text-muted-foreground">
                        <tr>
                            <th class="px-4 py-3 font-medium">{{ t('Supplier No') }}</th>
                            <th class="px-4 py-3 font-medium">{{ t('Name') }}</th>
                            <th class="px-4 py-3 font-medium">{{ t('Contact') }}</th>
                            <th class="px-4 py-3 font-medium">{{ t('Phone') }}</th>
                            <th class="px-4 py-3 font-medium">{{ t('Purchases') }}</th>
                            <th class="px-4 py-3 font-medium">{{ t('Added by') }}</th>

                            <th class="px-4 py-3 font-medium text-end">{{ t('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="item in items.data"
                            :key="item.id"
                            class="border-b last:border-0 transition-colors hover:bg-muted/30"
                        >
                            <td class="px-4 py-3 font-medium">
                                <Link :href="`/suppliers/${item.id}`" class="hover:text-primary hover:underline">
                                    {{ item.supplier_no || '—' }}
                                </Link>
                            </td>
                            <td class="px-4 py-3">{{ item.name || '—' }}</td>
                            <td class="px-4 py-3">{{ item.contact_person || '—' }}</td>
                            <td class="px-4 py-3">{{ item.phone || '—' }}</td>
                            <td class="px-4 py-3 tabular-nums">{{ item.purchases_count ?? 0 }}</td>
                            <td class="px-4 py-3 text-muted-foreground">{{ item.creator?.name || '—' }}</td>

                            <td class="px-4 py-3">
                                <div class="flex justify-end">
                                    <RowActionsMenu :actions="rowActions(item)" />
                                </div>
                            </td>
                        </tr>
                        <EmptyState
                            v-if="!items.data.length"
                            :colspan="7"
                            :title="t('No suppliers yet')"
                        />
                    </tbody>
                </table>
            </div>
            <Pagination :paginator="items" />
        </div>
    </div>
</template>
