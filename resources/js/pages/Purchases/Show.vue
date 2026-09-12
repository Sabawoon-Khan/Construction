<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { CircleDollarSign, Package, Pencil, Printer, Wallet } from '@lucide/vue';
import Can from '@/components/Can.vue';
import EmptyState from '@/components/EmptyState.vue';
import RecordAttachments from '@/components/RecordAttachments.vue';
import InputError from '@/components/InputError.vue';
import { computed } from 'vue';
import SortableTh from '@/components/SortableTh.vue';
import TableFrame from '@/components/TableFrame.vue';
import TableIndexTd from '@/components/TableIndexTd.vue';
import TableIndexTh from '@/components/TableIndexTh.vue';
import { indexTableColumn } from '@/composables/useTableColumns';
import StatusBadge from '@/components/StatusBadge.vue';
import { MisDetailGrid } from '@/components/mis';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    V2DetailHero,
    V2ListPage,
    V2Panel,
    V2StatCard,
    V2StatGrid,
} from '@/components/v2';
import { useCalendar } from '@/composables/useCalendar';
import { useCurrency } from '@/composables/useCurrency';
import { useTableSort } from '@/composables/useTableSort';
import { useTranslations } from '@/composables/useTranslations';
import { formatNumber, money } from '@/lib/utils';

const props = defineProps<{
    purchase: any;
    paid_amount: number;
    paid_amount_base: number;
    remaining_amount: number;
    remaining_amount_base: number;
    total_amount: number;
    total_amount_base: number;
}>();
const { t } = useTranslations();
const tableColumnsItems = computed(() => [
    indexTableColumn(),
    { key: 'material.name', label: t('Material') },
    { key: 'quantity', label: t('Qty') },
    { key: 'unit_cost', label: t('Unit cost') },
    { key: 'total_cost', label: t('Total') },
    { key: 'created_at', label: t('Created at') },
]);
const tableColumnsPayments = computed(() => [
    indexTableColumn(),
    { key: 'payment_no', label: t('No') },
    { key: 'paid_at', label: t('Date') },
    { key: 'amount', label: t('Amount') },
    { key: 'payment_method', label: t('Method') },
    { key: 'created_at', label: t('Created at') },
]);
const itemsSort = useTableSort(() => (props.purchase.items ?? []) as Array<Record<string, any>>);
const paymentsSort = useTableSort(
    () => (props.purchase.payments ?? []) as Array<Record<string, any>>,
    {
        defaultKey: 'paid_at',
        defaultDir: 'desc',
    },
);
const { baseCurrency } = useCurrency();
const calendar = useCalendar();
const formatDate = (value: string | null | undefined) => calendar.format(value) || '—';
const purchaseCurrency = computed(() => props.purchase.currency || baseCurrency.value);

const payForm = useForm({
    amount: String(props.remaining_amount || ''),
    payment_method: 'cash',
    reference_no: '',
    notes: '',
});

function submitPayment() {
    payForm.post(`/purchases/${props.purchase.id}/pay`, {
        preserveScroll: true,
        onSuccess: () => payForm.reset('amount', 'reference_no', 'notes'),
    });
}

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Purchases', href: '/purchases' },
            { title: 'Details', href: '#' },
        ],
    },
});
</script>

<template>
    <Head :title="`${t('Purchase')} ${purchase.purchase_no}`" />
    <V2ListPage>
        <V2DetailHero image="/images/dashboard-v2-building.png">
            <template #eyebrow>{{ t('Purchases') }}</template>
            <template #title>{{ purchase.purchase_no }}</template>
            <template #description>
                {{ purchase.supplier?.name || '—' }} · {{ purchase.project?.name || '—' }}
            </template>
            <template #actions>
                <StatusBadge :status="purchase.status" />
                <template v-if="purchase.expense?.id">
                    <Button as-child variant="outline">
                        <Link :href="`/expenses/${purchase.expense.id}`"><Printer class="size-4" /> {{ t('Voucher') }}</Link>
                    </Button>
                </template>
                <Can permission="purchases.edit">
                    <Button as-child variant="outline">
                        <Link :href="`/purchases/${purchase.id}/edit`"><Pencil class="size-4" /> {{ t('Edit') }}</Link>
                    </Button>
                </Can>
            </template>
            <template #stats>
                <V2StatGrid class="detail-stat-grid">
                    <V2StatCard :delay="0" :title="t('Purchase total')">
                        <template #icon><Package /></template>
                        <span>{{ t('Purchase total') }}</span>
                        <strong>{{ money(total_amount, purchaseCurrency) }}</strong>
                        <span class="text-xs text-muted-foreground tabular-nums">
                            {{ money(total_amount_base, baseCurrency) }} {{ t('base') }}
                        </span>
                    </V2StatCard>
                    <V2StatCard :delay="1" icon-tone="teal" :title="t('Paid')">
                        <template #icon><Wallet /></template>
                        <span>{{ t('Paid') }}</span>
                        <strong>{{ money(paid_amount, purchaseCurrency) }}</strong>
                        <span class="text-xs text-muted-foreground tabular-nums">
                            {{ money(paid_amount_base, baseCurrency) }} {{ t('base') }}
                        </span>
                    </V2StatCard>
                    <V2StatCard :delay="2" accent icon-tone="orange" :title="t('Remaining owed')">
                        <template #icon><CircleDollarSign /></template>
                        <span>{{ t('Remaining owed') }}</span>
                        <strong>{{ money(remaining_amount, purchaseCurrency) }}</strong>
                        <span class="text-xs text-muted-foreground tabular-nums">
                            {{ money(remaining_amount_base, baseCurrency) }} {{ t('base') }}
                        </span>
                    </V2StatCard>
                </V2StatGrid>
            </template>
        </V2DetailHero>

        <V2Panel :title="t('Purchase details')">
            <template #actions>
                <Button
                    v-if="purchase.expense_id || purchase.expense?.id"
                    as-child
                    variant="outline"
                    size="sm"
                >
                    <Link :href="`/expenses/${purchase.expense.id}`">{{ t('View project expense') }}</Link>
                </Button>
            </template>
            <MisDetailGrid
                :items="[
                    { label: t('Supplier'), value: purchase.supplier?.name },
                    { label: t('Project'), value: purchase.project?.name },
                    { label: t('Date'), value: formatDate(purchase.purchase_date) },
                    { label: t('Reference'), value: purchase.reference_no },
                    { label: t('Expense'), value: purchase.expense?.expense_no },
                    { label: t('Currency'), value: purchaseCurrency },
                    { label: t('Notes'), value: purchase.notes, span: 3 },
                ]"
            />
        </V2Panel>

        <RecordAttachments
            attachable="purchases"
            :attachable-id="purchase.id"
            :attachments="purchase.attachments"
            boxed
        />

        <V2Panel :padded="false" :title="t('Materials (in stock)')">
            <TableFrame table-id="purchase-items" :columns="tableColumnsItems">
                <table class="min-w-full text-sm">
                    <thead class="table-chrome-head text-start text-muted-foreground">
                        <tr>
                            <TableIndexTh />
                            <SortableTh column="material.name" :sort-key="itemsSort.sortKey" :sort-dir="itemsSort.sortDir" @sort="itemsSort.sortBy">{{ t('Material') }}</SortableTh>
                            <SortableTh column="quantity" :sort-key="itemsSort.sortKey" :sort-dir="itemsSort.sortDir" @sort="itemsSort.sortBy">{{ t('Qty') }}</SortableTh>
                            <SortableTh column="unit_cost" :sort-key="itemsSort.sortKey" :sort-dir="itemsSort.sortDir" @sort="itemsSort.sortBy">{{ t('Unit cost') }}</SortableTh>
                            <SortableTh column="total_cost" :sort-key="itemsSort.sortKey" :sort-dir="itemsSort.sortDir" @sort="itemsSort.sortBy">{{ t('Total') }}</SortableTh>
                            <SortableTh column="created_at" :sort-key="itemsSort.sortKey" :sort-dir="itemsSort.sortDir" @sort="itemsSort.sortBy">{{ t('Created at') }}</SortableTh>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(line, index) in itemsSort.sortedRows" :key="line.id" class="border-t">
                            <TableIndexTd :index="index" />
                            <td class="px-4 py-3">{{ line.material?.name }}</td>
                            <td class="px-4 py-3 tabular-nums">
                                {{ formatNumber(line.quantity, 3) }}
                                <span v-if="line.material?.unit?.unit_name" class="text-muted-foreground">
                                    {{ line.material.unit.unit_name }}
                                </span>
                            </td>
                            <td class="px-4 py-3 tabular-nums">{{ money(line.unit_cost, purchaseCurrency) }}</td>
                            <td class="px-4 py-3 tabular-nums">{{ money(line.total_cost, purchaseCurrency) }}</td>
                            <td class="px-4 py-3 whitespace-nowrap text-muted-foreground">{{ formatDate(line.created_at) }}</td>
                        </tr>
                        <EmptyState
                            v-if="!purchase.items?.length"
                            :colspan="6"
                            :title="t('No materials yet')"
                        />
                    </tbody>
                </table>
            </TableFrame>
        </V2Panel>

        <V2Panel v-if="remaining_amount > 0" id="pay" :title="t('Pay supplier')">
            <form class="grid gap-4 sm:grid-cols-2" @submit.prevent="submitPayment">
                <div class="grid gap-2">
                    <Label>{{ t('Amount') }} ({{ purchaseCurrency }})</Label>
                    <Input v-model="payForm.amount" type="number" step="0.01" min="0.01" required :placeholder="String(remaining_amount)" />
                    <InputError :message="payForm.errors.amount" />
                </div>
                <div class="grid gap-2">
                    <Label>{{ t('Method') }}</Label>
                    <select v-model="payForm.payment_method" class="h-9 rounded-md border px-3 text-sm">
                        <option value="cash">{{ t('Cash') }}</option>
                        <option value="bank">{{ t('Bank') }}</option>
                        <option value="transfer">{{ t('Transfer') }}</option>
                    </select>
                </div>
                <div class="grid gap-2">
                    <Label>{{ t('Reference') }}</Label>
                    <Input v-model="payForm.reference_no" />
                </div>
                <div class="grid gap-2">
                    <Label>{{ t('Notes') }}</Label>
                    <Input v-model="payForm.notes" />
                </div>
                <div class="sm:col-span-2">
                    <Can permission="purchases.edit">
                        <Button type="submit" :disabled="payForm.processing">{{ t('Record payment') }}</Button>
                    </Can>
                </div>
            </form>
        </V2Panel>

        <V2Panel :padded="false" :title="t('Payments to supplier')">
            <TableFrame table-id="purchase-payments" :columns="tableColumnsPayments">
                <table class="min-w-full text-sm">
                    <thead class="table-chrome-head text-start text-muted-foreground">
                        <tr>
                            <TableIndexTh />
                            <SortableTh column="payment_no" :sort-key="paymentsSort.sortKey" :sort-dir="paymentsSort.sortDir" @sort="paymentsSort.sortBy">{{ t('No') }}</SortableTh>
                            <SortableTh column="paid_at" :sort-key="paymentsSort.sortKey" :sort-dir="paymentsSort.sortDir" @sort="paymentsSort.sortBy">{{ t('Date') }}</SortableTh>
                            <SortableTh column="amount" :sort-key="paymentsSort.sortKey" :sort-dir="paymentsSort.sortDir" @sort="paymentsSort.sortBy">{{ t('Amount') }}</SortableTh>
                            <SortableTh column="payment_method" :sort-key="paymentsSort.sortKey" :sort-dir="paymentsSort.sortDir" @sort="paymentsSort.sortBy">{{ t('Method') }}</SortableTh>
                            <SortableTh column="created_at" :sort-key="paymentsSort.sortKey" :sort-dir="paymentsSort.sortDir" @sort="paymentsSort.sortBy">{{ t('Created at') }}</SortableTh>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(payment, index) in paymentsSort.sortedRows" :key="payment.id" class="border-t">
                            <TableIndexTd :index="index" />
                            <td class="px-4 py-3">
                                <Link :href="`/payments/${payment.id}`" class="hover:underline">{{ payment.payment_no }}</Link>
                            </td>
                            <td class="px-4 py-3">{{ formatDate(payment.paid_at) }}</td>
                            <td class="px-4 py-3 tabular-nums">{{ money(payment.amount, payment.currency || baseCurrency) }}</td>
                            <td class="px-4 py-3 capitalize">{{ payment.payment_method }}</td>
                            <td class="px-4 py-3 whitespace-nowrap text-muted-foreground">{{ formatDate(payment.created_at) }}</td>
                        </tr>
                        <EmptyState
                            v-if="!purchase.payments?.length"
                            :colspan="6"
                            :title="t('No supplier payments yet')"
                        />
                    </tbody>
                </table>
            </TableFrame>
        </V2Panel>
    </V2ListPage>
</template>
