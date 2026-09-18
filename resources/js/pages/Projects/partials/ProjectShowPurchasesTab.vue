<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import Can from '@/components/Can.vue';
import ConfirmActionDialog from '@/components/ConfirmActionDialog.vue';
import EmptyState from '@/components/EmptyState.vue';
import { MisTableActions, MisMoneyStack, MisSearchInput } from '@/components/mis';
import type { ExportCell } from '@/composables/useTableExport';
import SortableTh from '@/components/SortableTh.vue';
import TableFrame from '@/components/TableFrame.vue';
import TableIndexTd from '@/components/TableIndexTd.vue';
import TableIndexTh from '@/components/TableIndexTh.vue';
import { indexTableColumn } from '@/composables/useTableColumns';
import StatusBadge from '@/components/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import { CreateButton } from '@/components/ui/create-button';
import { Input } from '@/components/ui/input';
import { V2Panel } from '@/components/v2';
import { useCalendar } from '@/composables/useCalendar';
import { useCurrency } from '@/composables/useCurrency';
import { useTableSort } from '@/composables/useTableSort';
import { useTranslations } from '@/composables/useTranslations';
import { formatNumber, money } from '@/lib/utils';

export type PurchaseMaterial = {
    material_id: number;
    name: string;
    code?: string | null;
    unit?: string | null;
    purchase_count: number;
    quantity: number;
    total_cost_base: number;
    total_cost_by_currency: Record<string, number>;
    lines: {
        id: number;
        purchase_id: number;
        purchase_no: string;
        purchase_date: string | null;
        supplier?: string | null;
        quantity: number;
        unit_cost: number;
        total_cost: number;
        currency?: string;
        status?: string;
    }[];
};

const props = defineProps<{
    item: Record<string, any>;
    purchases?: {
        id: number;
        purchase_no: string;
        purchase_date: string;
        total_amount: number;
        paid_amount: number;
        remaining_amount?: number;
        currency?: string;
        status: string;
        supplier?: { id: number; name: string };
        created_at?: string;
    }[];
    purchase_materials?: PurchaseMaterial[];
}>();

const emit = defineEmits<{
    'open-material': [row: PurchaseMaterial];
}>();

const { t } = useTranslations();
const tableColumnsMaterials = computed(() => [
    indexTableColumn(),
    { key: 'name', label: t('Material') },
    { key: 'purchase_count', label: t('Purchases') },
    { key: 'quantity', label: t('Total qty') },
    { key: 'total_cost_base', label: t('Total cost') },
]);
const tableColumnsPurchases = computed(() => [
    indexTableColumn(),
    { key: 'purchase_no', label: t('Purchase') },
    { key: 'purchase_date', label: t('Date') },
    { key: 'supplier.name', label: t('Supplier') },
    { key: 'total_amount', label: t('Total') },
    { key: 'remaining_amount', label: t('Remaining') },
    { key: 'status', label: t('Status') },
    { key: 'created_at', label: t('Created at') },
    { key: 'pay', label: t('Pay'), locked: true },
]);
const { baseCurrency } = useCurrency();
const calendar = useCalendar();
const formatDate = (value: string | null | undefined) =>
    calendar.format(value) || '—';

const materialQuery = ref('');

const filteredPurchaseMaterials = computed(() => {
    const q = materialQuery.value.trim().toLowerCase();
    const rows = props.purchase_materials ?? [];
    if (!q) {
        return rows;
    }

    return rows.filter((row) =>
        [row.name, row.code, row.unit]
            .filter(Boolean)
            .join(' ')
            .toLowerCase()
            .includes(q),
    );
});

const materialSort = useTableSort(filteredPurchaseMaterials, { defaultKey: 'material_id' });
const purchaseSort = useTableSort(() => props.purchases ?? []);

type PayDraft = {
    purchaseId: number;
    purchaseNo: string;
    amount: number;
    currency: string;
    payment_method: string;
    reference_no: string;
    paid_at: string;
};

const payConfirmOpen = ref(false);
const payProcessing = ref(false);
const payDraft = ref<PayDraft | null>(null);

function remainingOf(purchase: {
    total_amount: number;
    paid_amount: number;
    remaining_amount?: number;
}) {
    return (
        purchase.remaining_amount ??
        Math.max(0, Number(purchase.total_amount) - Number(purchase.paid_amount))
    );
}

function openPayConfirm(
    purchase: {
        id: number;
        purchase_no: string;
        total_amount: number;
        paid_amount: number;
        remaining_amount?: number;
        currency?: string;
    },
    formEl: HTMLFormElement | null,
) {
    if (!formEl) {
        return;
    }

    const data = new FormData(formEl);
    payDraft.value = {
        purchaseId: purchase.id,
        purchaseNo: purchase.purchase_no,
        amount: remainingOf(purchase),
        currency: purchase.currency || baseCurrency.value,
        payment_method: String(data.get('payment_method') || 'cash'),
        reference_no: String(data.get('reference_no') || ''),
        paid_at: String(data.get('paid_at') || new Date().toISOString().slice(0, 10)),
    };
    payConfirmOpen.value = true;
}

function confirmPay() {
    if (!payDraft.value) {
        return;
    }

    const draft = payDraft.value;
    payProcessing.value = true;
    router.post(
        `/projects/${props.item.id}/purchases/${draft.purchaseId}/pay-remaining`,
        {
            amount: draft.amount,
            payment_method: draft.payment_method,
            reference_no: draft.reference_no || undefined,
            paid_at: draft.paid_at,
        },
        {
            preserveScroll: true,
            onFinish: () => {
                payProcessing.value = false;
                payConfirmOpen.value = false;
                payDraft.value = null;
            },
        },
    );
}

const materialExportHeaders = computed(() => [
    t('Material'),
    t('Purchases'),
    t('Total qty'),
    t('Total cost'),
]);

function mapMaterialExportRow(row: PurchaseMaterial): ExportCell[] {
    return [
        row.name,
        row.purchase_count,
        row.quantity,
        row.total_cost_base,
    ];
}

const materialExportRows = computed(() =>
    (props.purchase_materials ?? []).map(mapMaterialExportRow),
);
const materialFilteredExportRows = computed(() =>
    materialSort.sortedRows.map(mapMaterialExportRow),
);

const purchaseExportHeaders = computed(() => [
    t('Purchase'),
    t('Date'),
    t('Supplier'),
    t('Total'),
    t('Remaining'),
    t('Status'),
    t('Created at'),
]);

function mapPurchaseExportRow(
    purchase: NonNullable<typeof props.purchases>[number],
): ExportCell[] {
    return [
        purchase.purchase_no,
        formatDate(purchase.purchase_date),
        purchase.supplier?.name || null,
        Number(purchase.total_amount),
        remainingOf(purchase),
        purchase.status,
        formatDate(purchase.created_at),
    ];
}

const purchaseExportRows = computed(() =>
    (props.purchases ?? []).map(mapPurchaseExportRow),
);
const purchaseFilteredExportRows = computed(() =>
    purchaseSort.sortedRows.map(mapPurchaseExportRow),
);
</script>

<template>
    <div class="space-y-4">
        <V2Panel
            :padded="false"
            :title="t('Materials purchased')"
            :description="t('Qty and cost by material for this project')"
        >
            <template #actions>
                
            <MisTableActions
                    :filename="`project-${item.id}-purchase-materials`"
                    :headers="materialExportHeaders"
                    :rows="materialExportRows"
                    :filtered-rows="materialFilteredExportRows"
                    :filters="{ search: materialQuery }"
                >
                <CreateButton
                    :href="`/purchases/create?project_id=${item.id}`"
                    permission="purchases.create"
                >
                    {{ t('New purchase') }}
                </CreateButton>
            </MisTableActions>
        
            </template>
            <div class="border-b px-5 py-3">
                <MisSearchInput
                    v-model="materialQuery"
                    class="max-w-sm"
                    :placeholder="t('Search materials...')"
                />
            </div>
            <TableFrame table-id="project-purchase-materials" :columns="tableColumnsMaterials">
                <table class="min-w-full text-sm">
                    <thead
                        class="table-chrome-head text-start text-muted-foreground"
                    >
                        <tr>
                            <TableIndexTh />
                            <SortableTh
                                column="name"
                                :sort-key="materialSort.sortKey"
                                :sort-dir="materialSort.sortDir"
                                @sort="materialSort.sortBy"
                            >
                                {{ t('Material') }}
                            </SortableTh>
                            <SortableTh
                                column="purchase_count"
                                :sort-key="materialSort.sortKey"
                                :sort-dir="materialSort.sortDir"
                                @sort="materialSort.sortBy"
                            >
                                {{ t('Purchases') }}
                            </SortableTh>
                            <SortableTh
                                column="quantity"
                                :sort-key="materialSort.sortKey"
                                :sort-dir="materialSort.sortDir"
                                @sort="materialSort.sortBy"
                            >
                                {{ t('Total qty') }}
                            </SortableTh>
                            <SortableTh
                                column="total_cost_base"
                                :sort-key="materialSort.sortKey"
                                :sort-dir="materialSort.sortDir"
                                @sort="materialSort.sortBy"
                            >
                                {{ t('Total cost') }}
                            </SortableTh>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="(row, index) in materialSort.sortedRows"
                            :key="row.material_id"
                            class="cursor-pointer border-t hover:bg-muted/30"
                            @click="emit('open-material', row)"
                        >
                            <TableIndexTd :index="index" />
                            <td class="px-4 py-3">
                                <div class="font-medium text-primary">
                                    {{ row.name }}
                                </div>
                                <div
                                    v-if="row.code || row.unit"
                                    class="text-xs text-muted-foreground"
                                >
                                    {{
                                        [row.code, row.unit]
                                            .filter(Boolean)
                                            .join(' · ')
                                    }}
                                </div>
                            </td>
                            <td class="px-4 py-3 tabular-nums">
                                {{ formatNumber(row.purchase_count, 0) }}
                            </td>
                            <td class="px-4 py-3 tabular-nums">
                                {{ formatNumber(row.quantity, 3) }}
                                <span
                                    v-if="row.unit"
                                    class="text-muted-foreground"
                                >
                                    {{ row.unit }}
                                </span>
                            </td>
                            <td class="px-4 py-3 font-medium">
                                <MisMoneyStack
                                    layout="inline"
                                    class="font-medium"
                                    :amounts="row.total_cost_by_currency"
                                    :fallback="row.total_cost_base"
                                />
                            </td>
                        </tr>
                        <EmptyState
                            v-if="!filteredPurchaseMaterials.length"
                            :colspan="5"
                            :title="
                                materialQuery.trim()
                                    ? t('No matching materials')
                                    : t('No materials purchased yet')
                            "
                        >
                        </EmptyState>
                    </tbody>
                </table>
            </TableFrame>
        </V2Panel>

        <V2Panel :padded="false" :title="t('Project purchases')">
            <template #actions>
                <MisTableActions
                    :filename="`project-${item.id}-purchases`"
                    :headers="purchaseExportHeaders"
                    :rows="purchaseExportRows"
                    :filtered-rows="purchaseFilteredExportRows"
                />
            </template>
            <TableFrame table-id="project-purchases" :columns="tableColumnsPurchases">
                <table class="min-w-full text-sm">
                    <thead class="table-chrome-head text-start text-muted-foreground">
                        <tr>
                            <TableIndexTh />
                            <SortableTh
                                column="purchase_no"
                                :sort-key="purchaseSort.sortKey"
                                :sort-dir="purchaseSort.sortDir"
                                @sort="purchaseSort.sortBy"
                            >
                                {{ t('Purchase') }}
                            </SortableTh>
                            <SortableTh
                                column="purchase_date"
                                :sort-key="purchaseSort.sortKey"
                                :sort-dir="purchaseSort.sortDir"
                                @sort="purchaseSort.sortBy"
                            >
                                {{ t('Date') }}
                            </SortableTh>
                            <SortableTh
                                column="supplier.name"
                                :sort-key="purchaseSort.sortKey"
                                :sort-dir="purchaseSort.sortDir"
                                @sort="purchaseSort.sortBy"
                            >
                                {{ t('Supplier') }}
                            </SortableTh>
                            <SortableTh
                                column="total_amount"
                                :sort-key="purchaseSort.sortKey"
                                :sort-dir="purchaseSort.sortDir"
                                @sort="purchaseSort.sortBy"
                            >
                                {{ t('Total') }}
                            </SortableTh>
                            <SortableTh
                                column="remaining_amount"
                                :sort-key="purchaseSort.sortKey"
                                :sort-dir="purchaseSort.sortDir"
                                @sort="purchaseSort.sortBy"
                            >
                                {{ t('Remaining') }}
                            </SortableTh>
                            <SortableTh
                                column="status"
                                :sort-key="purchaseSort.sortKey"
                                :sort-dir="purchaseSort.sortDir"
                                @sort="purchaseSort.sortBy"
                            >
                                {{ t('Status') }}
                            </SortableTh>
                            <SortableTh
                                column="created_at"
                                :sort-key="purchaseSort.sortKey"
                                :sort-dir="purchaseSort.sortDir"
                                @sort="purchaseSort.sortBy"
                            >
                                {{ t('Created at') }}
                            </SortableTh>
                            <th class="px-4 py-3 font-medium text-end">
                                {{ t('Pay') }}
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="(purchase, index) in purchaseSort.sortedRows"
                            :key="purchase.id"
                            class="border-t hover:bg-muted/30"
                        >
                            <TableIndexTd :index="index" />
                            <td class="px-4 py-3">
                                <Link
                                    :href="`/purchases/${purchase.id}`"
                                    class="font-medium text-primary hover:underline"
                                    >{{ purchase.purchase_no }}</Link
                                >
                            </td>
                            <td class="px-4 py-3">
                                {{ formatDate(purchase.purchase_date) }}
                            </td>
                            <td class="px-4 py-3">
                                {{ purchase.supplier?.name || '—' }}
                            </td>
                            <td class="px-4 py-3 tabular-nums">
                                {{
                                    money(
                                        purchase.total_amount,
                                        purchase.currency || baseCurrency,
                                    )
                                }}
                            </td>
                            <td class="px-4 py-3 text-amber-700 tabular-nums">
                                {{
                                    money(
                                        remainingOf(purchase),
                                        purchase.currency || baseCurrency,
                                    )
                                }}
                            </td>
                            <td class="px-4 py-3">
                                <StatusBadge :status="purchase.status" />
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-muted-foreground">
                                {{ formatDate(purchase.created_at) }}
                            </td>
                            <td class="px-4 py-3 text-end">
                                <Can permission="purchases.edit">
                                    <form
                                        v-if="remainingOf(purchase) > 0"
                                        class="flex flex-wrap items-center justify-end gap-2"
                                        @submit.prevent="openPayConfirm(purchase, ($event.target as HTMLFormElement))"
                                    >
                                        <select name="payment_method" class="h-8 rounded-md border border-input bg-transparent px-2 text-xs">
                                            <option value="cash">{{ t('Cash') }}</option>
                                            <option value="bank">{{ t('Bank') }}</option>
                                            <option value="transfer">{{ t('Transfer') }}</option>
                                        </select>
                                        <Input name="reference_no" type="text" class="h-8 w-[100px] text-xs" :placeholder="t('Ref')" />
                                        <Input name="paid_at" type="date" class="h-8 w-[130px] text-xs" :default-value="new Date().toISOString().slice(0, 10)" />
                                        <Button type="submit" size="sm">{{ t('Pay') }}</Button>
                                    </form>
                                    <span v-else class="text-xs text-muted-foreground">—</span>
                                </Can>
                            </td>
                        </tr>
                        <EmptyState
                            v-if="!(purchases || []).length"
                            :colspan="9"
                            :title="t('No purchases yet')"
                        >
                        </EmptyState>
                    </tbody>
                </table>
            </TableFrame>
        </V2Panel>

        <ConfirmActionDialog
            v-model:open="payConfirmOpen"
            :title="t('Record payment')"
            :description="
                payDraft
                    ? `${payDraft.purchaseNo} · ${money(payDraft.amount, payDraft.currency)}`
                    : undefined
            "
            :confirm-label="t('Pay')"
            variant="default"
            :processing="payProcessing"
            @confirm="confirmPay"
        />
    </div>
</template>
