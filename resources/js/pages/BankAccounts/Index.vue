<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Search, Trash2 } from '@lucide/vue';
import { ref } from 'vue';
import EmptyState from '@/components/EmptyState.vue';
import Heading from '@/components/Heading.vue';
import { MisCreateButton, MisStatCard, MisStatGrid } from '@/components/mis';
import Pagination from '@/components/Pagination.vue';
import RowActionsMenu from '@/components/RowActionsMenu.vue';
import StatusBadge from '@/components/StatusBadge.vue';
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
    totals: { count: number; active: number; opening_balance: number; receipts: number };
}>();
const search = ref(props.filters.search ?? '');
const { t } = useTranslations();
const { viewAction, editAction, can } = useMisPage();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Bank Accounts', href: '/bank-accounts' },
        ],
    },
});

const applySearch = () => router.get('/bank-accounts', { search: search.value || undefined }, { preserveState: true, replace: true });

function rowActions(item: Item): RowActionItem[] {
    return [
        viewAction(`/bank-accounts/${item.id}`),
        editAction(`/bank-accounts/${item.id}/edit`, 'bank_accounts.edit'),
        { separator: true, isLabel: true, label: t('Danger') },
        {
            label: t('Delete'),
            icon: Trash2,
            method: 'delete',
            href: `/bank-accounts/${item.id}`,
            variant: 'destructive' as const,
            hidden: !can('bank_accounts.delete'),
            confirm: {
                title: t('Delete bank account'),
                description: t('Are you sure you want to delete this bank account? This action cannot be undone.'),
                confirmLabel: t('Delete'),
            },
        },
    ];
}
</script>

<template>
    <Head :title="t('Bank Accounts')" />
    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <Heading :title="t('Bank Accounts')" :description="t('Cash and bank accounts used for receipts and payments.')" />
            <div class="flex flex-wrap gap-2">
                <div class="relative min-w-[220px] flex-1">
                    <Search class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                    <Input v-model="search" class="pl-9" :placeholder="t('Search...')" @keyup.enter="applySearch" />
                </div>
                <Button variant="secondary" @click="applySearch">{{ t('Search') }}</Button>
                <MisCreateButton href="/bank-accounts/create" permission="bank_accounts.create">{{ t('Add') }}</MisCreateButton>
            </div>
        </div>
        <MisStatGrid :columns="4">
            <MisStatCard :label="t('Accounts')" :value="totals.count" />
            <MisStatCard :label="t('Active')" :value="totals.active" tone="success" />
            <MisStatCard :label="t('Opening balance')" :value="money(totals.opening_balance)" />
            <MisStatCard :label="t('Receipts')" :value="money(totals.receipts)" />
        </MisStatGrid>

        <div class="overflow-hidden rounded-xl border border-sidebar-border/70 bg-card shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="border-b bg-muted/40 text-left text-muted-foreground">
                        <tr>
                            <th class="px-4 py-3 font-medium">{{ t('Name') }}</th>
                            <th class="px-4 py-3 font-medium">{{ t('Bank') }}</th>
                            <th class="px-4 py-3 font-medium">{{ t('Account No') }}</th>
                            <th class="px-4 py-3 font-medium">{{ t('Currency') }}</th>
                            <th class="px-4 py-3 font-medium">{{ t('Type') }}</th>
                            <th class="px-4 py-3 font-medium text-right">{{ t('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="item in items.data" :key="item.id" class="border-b last:border-0 transition-colors hover:bg-muted/30">
                            <td class="px-4 py-3 font-medium">
                                <Link :href="`/bank-accounts/${item.id}`" class="hover:text-primary hover:underline">{{ item.name || '—' }}</Link>
                            </td>
                            <td class="px-4 py-3">{{ item.bank_name || '—' }}</td>
                            <td class="px-4 py-3">{{ item.account_number || '—' }}</td>
                            <td class="px-4 py-3">{{ item.currency || '—' }}</td>
                            <td class="px-4 py-3"><StatusBadge :status="item.is_cash ? 'cash' : 'bank'" /></td>
                            <td class="px-4 py-3">
                                <div class="flex justify-end">
                                    <RowActionsMenu :actions="rowActions(item)" />
                                </div>
                            </td>
                        </tr>
                        <EmptyState v-if="!items.data.length" :colspan="6" :title="t('No bank accounts yet')" />
                    </tbody>
                </table>
            </div>
            <Pagination :paginator="items" />
        </div>
    </div>
</template>
