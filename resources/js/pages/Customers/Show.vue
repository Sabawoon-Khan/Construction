<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    Banknote,
    CircleDollarSign,
    Info,
    Paperclip,
    Pencil,
    ShoppingBag,
    Wallet,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import Can from '@/components/Can.vue';
import { MisDetailGrid, MisMoneyStack, MisTabs } from '@/components/mis';
import RecordAttachments from '@/components/RecordAttachments.vue';
import SortableTh from '@/components/SortableTh.vue';
import TableFrame from '@/components/TableFrame.vue';
import TableIndexTd from '@/components/TableIndexTd.vue';
import TableIndexTh from '@/components/TableIndexTh.vue';
import { indexTableColumn } from '@/composables/useTableColumns';
import StatusBadge from '@/components/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import {
    V2DetailHero,
    V2ListPage,
    V2Panel,
    V2StatCard,
    V2StatGrid,
} from '@/components/v2';
import { useCalendar } from '@/composables/useCalendar';
import { provideTableSort } from '@/composables/useTableSort';
import { useTranslations } from '@/composables/useTranslations';
import { money, moneyByCurrency } from '@/lib/utils';

const tab = ref('info');
const { t } = useTranslations();
const tableColumns = computed(() => [
    indexTableColumn(),
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
    <V2ListPage>
        <V2DetailHero image="/images/dashboard-v2-building.png">
            <template #eyebrow>{{ t('Customers') }}</template>
            <template #title>{{ item.name }}</template>
            <template #description>
                {{ t('Customer') }} {{ item.customer_no || '' }}
            </template>
            <template #actions>
                <Can permission="customers.edit">
                    <Button as-child>
                        <Link :href="`/customers/${item.id}/edit`">
                            <Pencil class="size-4" /> {{ t('Edit') }}
                        </Link>
                    </Button>
                </Can>
            </template>
            <template #stats>
                <V2StatGrid class="detail-stat-grid">
                    <V2StatCard
                        :delay="0"
                        :title="t('Sales')"
                        :value="stats.sales_count"
                    >
                        <template #icon><ShoppingBag /></template>
                    </V2StatCard>
                    <V2StatCard :delay="1" icon-tone="warm" :title="t('Purchase amount')">
                        <template #icon><CircleDollarSign /></template>
                        <span>{{ t('Purchase amount') }}</span>
                        <MisMoneyStack
                            :amounts="stats.sales_by_currency"
                            :fallback="stats.sales_total"
                        />
                    </V2StatCard>
                    <V2StatCard :delay="2" icon-tone="teal" :title="t('Amount paid')">
                        <template #icon><Wallet /></template>
                        <span>{{ t('Amount paid') }}</span>
                        <MisMoneyStack
                            :amounts="stats.payments_by_currency"
                            :fallback="stats.payments_total"
                        />
                    </V2StatCard>
                    <V2StatCard
                        :delay="3"
                        accent
                        icon-tone="orange"
                        :title="t('Remaining')"
                        :value="money(stats.remaining ?? 0)"
                    >
                        <template #icon><Banknote /></template>
                    </V2StatCard>
                </V2StatGrid>
            </template>
        </V2DetailHero>

        <MisTabs v-model="tab" :tabs="tabs" />

        <V2Panel v-show="tab === 'info'" :title="t('Customer details')">
            <MisDetailGrid
                :items="[
                    { label: t('Customer No'), value: item.customer_no },
                    { label: t('Last name'), value: item.takhallus },
                    { label: t('Father name'), value: item.father_name },
                    { label: t('Phone'), value: item.phone },
                    { label: t('Address'), value: item.address, span: 2 },
                    { label: t('Registration date'), value: formatDate(item.registered_at) },
                    { label: t('Customer type'), value: item.type === 'wholesale' ? t('Wholesale') : t('Retail') },
                    { label: t('Purchase amount'), value: moneyByCurrency(stats.sales_by_currency, stats.sales_total) },
                    { label: t('Amount paid'), value: moneyByCurrency(stats.payments_by_currency, stats.payments_total) },
                    { label: t('Remaining'), value: money(stats.remaining ?? 0) },
                    { label: t('Last purchase'), value: formatDate(stats.last_purchase_at) },
                    { label: t('Status'), value: item.status === 'inactive' ? t('Inactive') : t('Active') },
                    { label: t('Email'), value: item.email },
                    { label: t('National ID'), value: item.national_id },
                    { label: t('Notes'), value: item.notes, span: 2 },
                ]"
            />
        </V2Panel>

        <V2Panel v-show="tab === 'sales'" :padded="false" :title="t('Recent sales')">
            <TableFrame table-id="customer-sales" :columns="tableColumns">
                <table class="min-w-full text-sm">
                    <thead class="table-chrome-head text-start text-muted-foreground">
                        <tr>
                            <TableIndexTh />
                            <SortableTh column="sale_no">{{ t('Sale') }}</SortableTh>
                            <SortableTh column="project.name">{{ t('Project') }}</SortableTh>
                            <SortableTh column="unit.unit_no">{{ t('Unit') }}</SortableTh>
                            <SortableTh column="unit.list_price">{{ t('Net') }}</SortableTh>
                            <SortableTh column="status">{{ t('Status') }}</SortableTh>
                            <SortableTh column="created_at">{{ t('Created at') }}</SortableTh>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(sale, index) in sortedRows" :key="sale.id" class="border-t hover:bg-muted/30">
                            <TableIndexTd :index="index" />
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
                            <td colspan="7" class="px-4 py-10 text-center text-muted-foreground">{{ t('No sales yet') }}</td>
                        </tr>
                    </tbody>
                </table>
            </TableFrame>
        </V2Panel>

        <div v-show="tab === 'attachments'">
            <RecordAttachments
                boxed
                attachable="customers"
                :attachable-id="item.id"
                :attachments="item.attachments"
            />
        </div>
    </V2ListPage>
</template>
