<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Eye, Printer } from '@lucide/vue';
import { ref } from 'vue';
import EmptyState from '@/components/EmptyState.vue';
import Heading from '@/components/Heading.vue';
import { MisStatCard, MisStatGrid } from '@/components/mis';
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
    totals: { count: number; amount: number; amount_base: number; paid_out?: number; paid_out_base?: number; this_month: number; this_month_base: number; cash: number; cash_base: number };
}>();
const search = ref(props.filters.search ?? '');
const { t } = useTranslations();
const { baseCurrency } = useCurrency();
const { formatDate } = useMisPage();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Payments', href: '/payments' },
        ],
    },
});

const apply = () => router.get('/payments', { search: search.value || undefined }, { preserveState: true, replace: true });

function rowActions(item: any): RowActionItem[] {
    return [
        { label: t('View'), icon: Eye, href: `/payments/${item.id}` },
        { label: t('Print'), icon: Printer, href: `/payments/${item.id}` },
    ];
}
</script>

<template>
    <Head :title="t('Payments')" />
    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <Heading :title="t('Payments')" :description="t('Money in from sales and money out to suppliers.')" />
            <div class="flex flex-wrap gap-2">
                <Input v-model="search" :placeholder="t('Search...')" @keyup.enter="apply" />
                <Button variant="secondary" @click="apply">{{ t('Search') }}</Button>
                <Button as-child variant="outline"><Link href="/sales">{{ t('Go to sales') }}</Link></Button>
                <Button as-child variant="outline"><Link href="/purchases">{{ t('Go to purchases') }}</Link></Button>
            </div>
        </div>

        <MisStatGrid :columns="4">
            <MisStatCard :label="t('Entries')" :value="totals.count" />
            <MisStatCard :label="t('Collected')" :value="money(totals.amount_base ?? totals.amount, baseCurrency)" tone="success" />
            <MisStatCard :label="t('Paid to suppliers')" :value="money(totals.paid_out_base ?? totals.paid_out ?? 0, baseCurrency)" tone="danger" />
            <MisStatCard :label="t('This month')" :value="money(totals.this_month_base ?? totals.this_month, baseCurrency)" />
        </MisStatGrid>

        <div class="overflow-hidden rounded-xl border border-sidebar-border/70 bg-card shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="border-b bg-muted/40 text-left text-muted-foreground">
                        <tr>
                            <th class="px-4 py-3 font-medium">{{ t('No') }}</th>
                            <th class="px-4 py-3 font-medium">{{ t('Date') }}</th>
                            <th class="px-4 py-3 font-medium">{{ t('Type') }}</th>
                            <th class="px-4 py-3 font-medium">{{ t('Party') }}</th>
                            <th class="px-4 py-3 font-medium">{{ t('Linked') }}</th>
                            <th class="px-4 py-3 font-medium">{{ t('Amount') }}</th>
                            <th class="px-4 py-3 font-medium">{{ t('Method') }}</th>
                            <th class="px-4 py-3 font-medium">{{ t('Status') }}</th>
                            <th class="px-4 py-3 font-medium text-right">{{ t('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="item in items.data" :key="item.id" class="border-b last:border-0 transition-colors hover:bg-muted/30">
                            <td class="px-4 py-3 font-medium">
                                <Link :href="`/payments/${item.id}`" class="hover:text-primary hover:underline">{{ item.payment_no }}</Link>
                            </td>
                            <td class="px-4 py-3">{{ formatDate(item.paid_at) }}</td>
                            <td class="px-4 py-3 capitalize">{{ item.type === 'disbursement' ? t('Paid out') : t('Receipt') }}</td>
                            <td class="px-4 py-3">{{ item.supplier?.name || item.customer?.name || '—' }}</td>
                            <td class="px-4 py-3">{{ item.purchase?.purchase_no || item.sale?.sale_no || '—' }}</td>
                            <td class="px-4 py-3 tabular-nums">{{ money(item.amount, item.currency || baseCurrency) }}</td>
                            <td class="px-4 py-3 capitalize">{{ item.payment_method }}</td>
                            <td class="px-4 py-3"><StatusBadge :status="item.status" /></td>
                            <td class="px-4 py-3">
                                <div class="flex justify-end">
                                    <RowActionsMenu :actions="rowActions(item)" />
                                </div>
                            </td>
                        </tr>
                        <EmptyState v-if="!items.data.length" :colspan="9" :title="t('No payments yet')" :description="t('Receipts from sales and payments to suppliers appear here.')" />
                    </tbody>
                </table>
            </div>
            <Pagination :paginator="items" />
        </div>
    </div>
</template>
