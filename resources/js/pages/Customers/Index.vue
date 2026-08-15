<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Search, Trash2 } from '@lucide/vue';
import { ref } from 'vue';
import EmptyState from '@/components/EmptyState.vue';
import Heading from '@/components/Heading.vue';
import { MisCreateButton, MisStatCard, MisStatGrid } from '@/components/mis';
import Pagination from '@/components/Pagination.vue';
import RowActionsMenu from '@/components/RowActionsMenu.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { useMisPage } from '@/composables/useMisPage';
import { useTranslations } from '@/composables/useTranslations';
import type { RowActionItem } from '@/lib/row-actions';
import { money } from '@/lib/utils';

type Item = Record<string, any>;

const props = defineProps<{
    items: { data: Item[]; links: any[]; meta?: any };
    filters: { search?: string };
    totals: { count: number; with_sales: number; sales_total: number; payments_total: number };
}>();

const search = ref(props.filters.search ?? '');
const { t } = useTranslations();
const { viewAction, editAction, can } = useMisPage();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Customers', href: '/customers' },
        ],
    },
});

function applySearch() {
    router.get('/customers', { search: search.value || undefined }, { preserveState: true, replace: true });
}

function rowActions(item: Item): RowActionItem[] {
    return [
        viewAction(`/customers/${item.id}`),
        editAction(`/customers/${item.id}/edit`, 'customers.edit'),
        { separator: true, isLabel: true, label: t('Danger') },
        {
            label: t('Delete'),
            icon: Trash2,
            method: 'delete',
            href: `/customers/${item.id}`,
            variant: 'destructive' as const,
            hidden: !can('customers.delete'),
            confirm: {
                title: t('Delete customer'),
                description: t('Are you sure you want to delete this customer? This action cannot be undone.'),
                confirmLabel: t('Delete'),
            },
        },
    ];
}
</script>

<template>
    <Head :title="t('Customers')" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <Heading :title="t('Customers')" :description="t('People buying homes and units across your projects.')" />
            <div class="flex flex-wrap gap-2">
                <div class="relative min-w-[220px] flex-1">
                    <Search class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                    <Input v-model="search" class="pl-9" :placeholder="t('Search customers...')" @keyup.enter="applySearch" />
                </div>
                <Button variant="secondary" @click="applySearch">{{ t('Search') }}</Button>
                <MisCreateButton href="/customers/create" permission="customers.create">{{ t('Add customer') }}</MisCreateButton>
            </div>
        </div>

        <MisStatGrid :columns="4">
            <MisStatCard :label="t('Customers')" :value="totals.count" />
            <MisStatCard :label="t('With sales')" :value="totals.with_sales" />
            <MisStatCard :label="t('Sales total')" :value="money(totals.sales_total)" />
            <MisStatCard :label="t('Payments received')" :value="money(totals.payments_total)" tone="success" />
        </MisStatGrid>

        <div class="overflow-hidden rounded-xl border border-sidebar-border/70 bg-card shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="border-b bg-muted/40 text-left text-muted-foreground">
                        <tr>
                            <th class="px-4 py-3 font-medium">{{ t('Customer No') }}</th>
                            <th class="px-4 py-3 font-medium">{{ t('Name') }}</th>
                            <th class="px-4 py-3 font-medium">{{ t('Phone') }}</th>
                            <th class="px-4 py-3 font-medium">{{ t('National ID') }}</th>
                            <th class="px-4 py-3 font-medium text-right">{{ t('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="item in items.data"
                            :key="item.id"
                            class="border-b last:border-0 transition-colors hover:bg-muted/30"
                        >
                            <td class="px-4 py-3 font-medium">
                                <Link :href="`/customers/${item.id}`" class="hover:text-primary hover:underline">
                                    {{ item.customer_no || '—' }}
                                </Link>
                            </td>
                            <td class="px-4 py-3">{{ item.name || '—' }}</td>
                            <td class="px-4 py-3">{{ item.phone || '—' }}</td>
                            <td class="px-4 py-3">{{ item.national_id || '—' }}</td>
                            <td class="px-4 py-3">
                                <div class="flex justify-end">
                                    <RowActionsMenu :actions="rowActions(item)" />
                                </div>
                            </td>
                        </tr>
                        <EmptyState
                            v-if="!items.data.length"
                            :colspan="5"
                            :title="t('No customers yet')"
                            :description="t('Add your first customer to start recording sales.')"
                        />
                    </tbody>
                </table>
            </div>
            <Pagination :paginator="items" />
        </div>
    </div>
</template>
