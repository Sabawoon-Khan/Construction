<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, Banknote, Info, Paperclip, Pencil } from '@lucide/vue';
import { computed, ref } from 'vue';
import Can from '@/components/Can.vue';
import DetailGrid from '@/components/DetailGrid.vue';
import Heading from '@/components/Heading.vue';
import { MisTabs } from '@/components/mis';
import RecordAttachments from '@/components/RecordAttachments.vue';
import SortableTh from '@/components/SortableTh.vue';
import TableFrame from '@/components/TableFrame.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import { useCalendar } from '@/composables/useCalendar';
import { provideTableSort } from '@/composables/useTableSort';
import { useTranslations } from '@/composables/useTranslations';
import { money, moneyByCurrency } from '@/lib/utils';

const tab = ref('info');
const { t } = useTranslations();
const tableColumns = computed(() => [
    { key: 'sale_no', label: t('Sale') },
    { key: 'project.name', label: t('Project') },
    { key: 'unit.unit_no', label: t('Unit') },
    { key: 'unit.list_price', label: t('Net') },
    { key: 'status', label: t('Status') },
    { key: 'created_at', label: t('Created at') },
]);
const calendar = useCalendar();
const formatDate = (value: string | null | undefined) => calendar.format(value) || '—';
const tabs = [
    { id: 'info', label: t('Customer info'), icon: Info },
    { id: 'sales', label: t('Sales'), icon: Banknote },
    { id: 'attachments', label: t('Attachments'), icon: Paperclip },
];

const props = defineProps<{
    item: Record<string, any>;
    stats: {
        sales_count: number;
        sales_total: number;
        payments_total: number;
        remaining?: number;
        last_purchase_at?: string | null;
        sales_by_currency?: Record<string, number>;
        payments_by_currency?: Record<string, number>;
    };
}>();

const { sortedRows } = provideTableSort(() => props.item.sales ?? []);

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Customers', href: '/customers' },
            { title: 'Details', href: '#' },
        ],
    },
});
</script>

<template>
    <Head :title="item.name" />
    <div class="flex h-full flex-1 flex-col gap-4">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div class="space-y-3">
                <Button as-child variant="ghost" size="sm" class="-ml-2 w-fit">
                    <Link href="/customers"><ArrowLeft class="size-4" /> {{ t('Back') }}</Link>
                </Button>
                <Heading :title="item.name" :description="`${t('Customer')} ${item.customer_no || ''}`" />
            </div>
            <Can permission="customers.edit">
                <Button as-child>
                    <Link :href="`/customers/${item.id}/edit`"><Pencil class="size-4" /> {{ t('Edit') }}</Link>
                </Button>
            </Can>
        </div>

        <div class="grid gap-4 sm:grid-cols-4">
            <div class="rounded-xl border bg-card p-4 shadow-sm">
                <div class="text-sm text-muted-foreground">{{ t('Sales') }}</div>
                <div class="mt-1 text-2xl font-semibold tabular-nums">{{ stats.sales_count }}</div>
            </div>
            <div class="rounded-xl border bg-card p-4 shadow-sm">
                <div class="text-sm text-muted-foreground">{{ t('Purchase amount') }}</div>
                <div class="mt-1 text-2xl font-semibold tabular-nums">{{ moneyByCurrency(stats.sales_by_currency, stats.sales_total) }}</div>
            </div>
            <div class="rounded-xl border bg-card p-4 shadow-sm">
                <div class="text-sm text-muted-foreground">{{ t('Amount paid') }}</div>
                <div class="mt-1 text-2xl font-semibold tabular-nums text-emerald-700">{{ moneyByCurrency(stats.payments_by_currency, stats.payments_total) }}</div>
            </div>
            <div class="rounded-xl border bg-card p-4 shadow-sm">
                <div class="text-sm text-muted-foreground">{{ t('Remaining') }}</div>
                <div class="mt-1 text-2xl font-semibold tabular-nums">{{ money(stats.remaining ?? 0) }}</div>
            </div>
        </div>

        <MisTabs v-model="tab" :tabs="tabs" />

        <DetailGrid
            v-show="tab === 'info'"
            :title="t('Customer details')"
            :items="[
                { label: t('Customer No'), value: item.customer_no },
                { label: t('Last name'), value: item.takhallus },
                { label: t('Father name'), value: item.father_name },
                { label: t('Phone'), value: item.phone },
                { label: t('Address'), value: item.address, span: true },
                { label: t('Registration date'), value: formatDate(item.registered_at) },
                { label: t('Customer type'), value: item.type === 'wholesale' ? t('Wholesale') : t('Retail') },
                { label: t('Purchase amount'), value: moneyByCurrency(stats.sales_by_currency, stats.sales_total) },
                { label: t('Amount paid'), value: moneyByCurrency(stats.payments_by_currency, stats.payments_total) },
                { label: t('Remaining'), value: money(stats.remaining ?? 0) },
                { label: t('Last purchase'), value: formatDate(stats.last_purchase_at) },
                { label: t('Status'), value: item.status === 'inactive' ? t('Inactive') : t('Active') },
                { label: t('Email'), value: item.email },
                { label: t('National ID'), value: item.national_id },
                { label: t('Notes'), value: item.notes, span: true },
            ]"
        />

        <div v-show="tab === 'sales'" class="overflow-hidden rounded-xl border bg-card shadow-sm">
            <div class="border-b px-5 py-3">
                <h3 class="text-sm font-semibold">{{ t('Recent sales') }}</h3>
            </div>
            <TableFrame table-id="customer-sales" :columns="tableColumns">
            <table class="min-w-full text-sm">
                <thead class="bg-muted/40 text-start text-muted-foreground">
                    <tr>
                        <SortableTh column="sale_no">{{ t('Sale') }}</SortableTh>
                        <SortableTh column="project.name">{{ t('Project') }}</SortableTh>
                        <SortableTh column="unit.unit_no">{{ t('Unit') }}</SortableTh>
                        <SortableTh column="unit.list_price">{{ t('Net') }}</SortableTh>
                        <SortableTh column="status">{{ t('Status') }}</SortableTh>
                        <SortableTh column="created_at">{{ t('Created at') }}</SortableTh>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="sale in sortedRows" :key="sale.id" class="border-t hover:bg-muted/30">
                        <td class="px-4 py-3">
                            <Link :href="`/sales/${sale.id}`" class="font-medium text-primary hover:underline">{{ sale.sale_no }}</Link>
                        </td>
                        <td class="px-4 py-3">{{ sale.project?.name || '—' }}</td>
                        <td class="px-4 py-3">{{ sale.unit?.unit_no || '—' }}</td>
                        <td class="px-4 py-3 tabular-nums">{{ money(Math.max(0, Number(sale.unit?.list_price ?? 0) - Number(sale.discount ?? 0)), sale.currency) }}</td>
                        <td class="px-4 py-3"><StatusBadge :status="sale.status" /></td>
                        <td class="px-4 py-3 whitespace-nowrap text-muted-foreground">{{ formatDate(sale.created_at) }}</td>
                    </tr>
                    <tr v-if="!item.sales?.length">
                        <td colspan="6" class="px-4 py-10 text-center text-muted-foreground">{{ t('No sales yet') }}</td>
                    </tr>
                </tbody>
            </table>
            </TableFrame>
        </div>

        <div v-show="tab === 'attachments'">
            <RecordAttachments
                boxed
                attachable="customers"
                :attachable-id="item.id"
                :attachments="item.attachments"
            />
        </div>
    </div>
</template>
