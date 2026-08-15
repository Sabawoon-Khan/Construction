<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Printer, Trash2 } from '@lucide/vue';
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

const props = defineProps<{
    items: { data: any[]; links: any[]; meta?: any };
    filters: { search?: string };
    totals: { count: number; amount: number; this_month: number; average: number };
}>();

const search = ref(props.filters.search ?? '');
const { t } = useTranslations();
const { viewAction, editAction, can } = useMisPage();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Incomes', href: '/incomes' },
        ],
    },
});

const apply = () => router.get('/incomes', { search: search.value || undefined }, { preserveState: true, replace: true });

function rowActions(item: any): RowActionItem[] {
    return [
        viewAction(`/incomes/${item.id}`),
        { label: t('Print'), icon: Printer, href: `/incomes/${item.id}` },
        editAction(`/incomes/${item.id}/edit`, 'incomes.edit'),
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
    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <Heading :title="t('General income')" :description="t('Company-wide income not tied to a project.')" />
            <div class="flex flex-wrap gap-2">
                <Input v-model="search" :placeholder="t('Search...')" @keyup.enter="apply" />
                <Button variant="secondary" @click="apply">{{ t('Search') }}</Button>
                <MisCreateButton href="/incomes/create" permission="incomes.create">{{ t('Add income') }}</MisCreateButton>
            </div>
        </div>

        <MisStatGrid :columns="4">
            <MisStatCard :label="t('Entries')" :value="totals.count" :hint="t('Approved income')" />
            <MisStatCard :label="t('Total income')" :value="money(totals.amount)" tone="success" />
            <MisStatCard :label="t('This month')" :value="money(totals.this_month)" />
            <MisStatCard :label="t('Average')" :value="money(totals.average)" />
        </MisStatGrid>

        <div class="overflow-hidden rounded-xl border border-sidebar-border/70 bg-card shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="border-b bg-muted/40 text-left text-muted-foreground">
                        <tr>
                            <th class="px-4 py-3 font-medium">{{ t('Date') }}</th>
                            <th class="px-4 py-3 font-medium">{{ t('Description') }}</th>
                            <th class="px-4 py-3 font-medium">{{ t('Total amount') }}</th>
                            <th class="px-4 py-3 font-medium text-right">{{ t('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="item in items.data" :key="item.id" class="border-b last:border-0 transition-colors hover:bg-muted/30">
                            <td class="px-4 py-3">{{ item.income_date?.slice?.(0, 10) ?? item.income_date }}</td>
                            <td class="px-4 py-3">{{ item.description || '—' }}</td>
                            <td class="px-4 py-3 tabular-nums">{{ money(item.amount) }}</td>
                            <td class="px-4 py-3">
                                <div class="flex justify-end">
                                    <RowActionsMenu :actions="rowActions(item)" />
                                </div>
                            </td>
                        </tr>
                        <EmptyState v-if="!items.data.length" :colspan="4" :title="t('No income records yet')" />
                    </tbody>
                </table>
            </div>
            <Pagination :paginator="items" />
        </div>
    </div>
</template>
