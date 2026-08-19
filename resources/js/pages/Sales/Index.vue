<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Banknote, Printer, Trash2 } from '@lucide/vue';
import { ref } from 'vue';
import EmptyState from '@/components/EmptyState.vue';
import Heading from '@/components/Heading.vue';
import { MisCreateButton, MisStatCard, MisStatGrid } from '@/components/mis';
import Pagination from '@/components/Pagination.vue';
import RowActionsMenu from '@/components/RowActionsMenu.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { useCurrency } from '@/composables/useCurrency';
import { useMisPage } from '@/composables/useMisPage';
import { useTranslations } from '@/composables/useTranslations';
import type { RowActionItem } from '@/lib/row-actions';
import { money } from '@/lib/utils';

const props = defineProps<{
    items: { data: any[]; links: any[]; meta?: any };
    filters: { search?: string };
    totals: { count: number; total_price: number; total_price_base: number; remaining: number; remaining_base: number };
}>();
const search = ref(props.filters.search ?? '');
const { t } = useTranslations();
const { baseCurrency } = useCurrency();
const { viewAction, editAction, can } = useMisPage();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Sales', href: '/sales' },
        ],
    },
});

const apply = () => router.get('/sales', { search: search.value || undefined }, { preserveState: true, replace: true });
const saleTotal = (item: any) => Math.max(0, Number(item.unit?.list_price ?? 0) - Number(item.discount ?? 0));
const saleRemaining = (item: any) => Math.max(0, saleTotal(item) - Number(item.paid_total ?? 0));

const saleStatuses = ['draft', 'active', 'completed', 'cancelled'];

function rowActions(item: any): RowActionItem[] {
    return [
        viewAction(`/sales/${item.id}`),
        {
            label: t('Receive payment'),
            icon: Banknote,
            href: `/sales/${item.id}#receive-payment`,
            hidden: !can('payments.create'),
        },
        { label: t('Print'), icon: Printer, href: `/sales/${item.id}` },
        editAction(`/sales/${item.id}/edit`, 'sales.edit'),
        ...(can('sales.edit')
            ? [
                  { separator: true, isLabel: true, label: t('Change status') },
                  ...saleStatuses.map((status) => ({
                      label: t(status),
                      method: 'patch' as const,
                      href: `/sales/${item.id}/status`,
                      data: { status },
                      disabled: item.status === status,
                  })),
              ]
            : []),
        { separator: true, isLabel: true, label: t('Danger') },
        {
            label: t('Delete'),
            icon: Trash2,
            method: 'delete',
            href: `/sales/${item.id}`,
            variant: 'destructive' as const,
            hidden: !can('sales.delete'),
            confirm: {
                title: t('Delete sale'),
                description: t('Are you sure you want to delete this sale? This action cannot be undone.'),
                confirmLabel: t('Delete'),
            },
        },
    ];
}
</script>

<template>
    <Head :title="t('Sales')" />
    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <Heading :title="t('Home Sales')" :description="t('Contracts, installments and collection status.')" />
            <div class="flex flex-wrap gap-2">
                <Input v-model="search" :placeholder="t('Search...')" @keyup.enter="apply" />
                <Button variant="secondary" @click="apply">{{ t('Search') }}</Button>
                <MisCreateButton href="/sales/create" permission="sales.create">{{ t('New sale') }}</MisCreateButton>
            </div>
        </div>

        <MisStatGrid :columns="3">
            <MisStatCard :label="t('Contracts')" :value="totals.count" />
            <MisStatCard :label="t('Sales total')" :value="money(totals.total_price_base ?? totals.total_price, baseCurrency)" />
            <MisStatCard :label="t('Remaining')" :value="money(totals.remaining_base ?? totals.remaining, baseCurrency)" tone="warning" />
        </MisStatGrid>

        <div class="overflow-hidden rounded-xl border border-sidebar-border/70 bg-card shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="border-b bg-muted/40 text-left text-muted-foreground">
                        <tr>
                            <th class="px-4 py-3 font-medium">{{ t('Sale No') }}</th>
                            <th class="px-4 py-3 font-medium">{{ t('Customer') }}</th>
                            <th class="px-4 py-3 font-medium">{{ t('Project') }}</th>
                            <th class="px-4 py-3 font-medium">{{ t('Unit') }}</th>
                            <th class="px-4 py-3 font-medium">{{ t('Sale price') }}</th>
                            <th class="px-4 py-3 font-medium">{{ t('Discount') }}</th>
                            <th class="px-4 py-3 font-medium">{{ t('Total') }}</th>
                            <th class="px-4 py-3 font-medium">{{ t('Remaining') }}</th>
                            <th class="px-4 py-3 font-medium">{{ t('Status') }}</th>
                            <th class="px-4 py-3 font-medium text-right">{{ t('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="item in items.data" :key="item.id" class="border-b last:border-0 transition-colors hover:bg-muted/30">
                            <td class="px-4 py-3 font-medium">
                                <Link :href="`/sales/${item.id}`" class="hover:text-primary hover:underline">{{ item.sale_no }}</Link>
                            </td>
                            <td class="px-4 py-3">{{ item.customer?.name }}</td>
                            <td class="px-4 py-3">{{ item.project?.name }}</td>
                            <td class="px-4 py-3">{{ item.unit?.unit_no }}</td>
                            <td class="px-4 py-3 tabular-nums">{{ money(item.unit?.list_price, item.currency || baseCurrency) }}</td>
                            <td class="px-4 py-3 tabular-nums">{{ money(item.discount, item.currency || baseCurrency) }}</td>
                            <td class="px-4 py-3 tabular-nums">{{ money(saleTotal(item), item.currency || baseCurrency) }}</td>
                            <td class="px-4 py-3 tabular-nums text-amber-700">{{ money(saleRemaining(item), item.currency || baseCurrency) }}</td>
                            <td class="px-4 py-3"><StatusBadge :status="item.status" /></td>
                            <td class="px-4 py-3">
                                <div class="flex justify-end">
                                    <RowActionsMenu :actions="rowActions(item)" />
                                </div>
                            </td>
                        </tr>
                        <EmptyState v-if="!items.data.length" :colspan="10" :title="t('No sales yet')" />
                    </tbody>
                </table>
            </div>
            <Pagination :paginator="items" />
        </div>
    </div>
</template>
