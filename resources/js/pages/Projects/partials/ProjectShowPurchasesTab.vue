<script setup lang="ts">
import { Form, Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import Can from '@/components/Can.vue';
import InputError from '@/components/InputError.vue';
import { MisSearchInput } from '@/components/mis';
import SortableTh from '@/components/SortableTh.vue';
import TableFrame from '@/components/TableFrame.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { useCalendar } from '@/composables/useCalendar';
import { useCurrency } from '@/composables/useCurrency';
import { useTableSort } from '@/composables/useTableSort';
import { useTranslations } from '@/composables/useTranslations';
import { formatNumber, money, moneyByCurrency } from '@/lib/utils';

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
    }[];
    purchase_materials?: PurchaseMaterial[];
}>();

const emit = defineEmits<{
    'open-material': [row: PurchaseMaterial];
}>();

const { t } = useTranslations();
const tableColumnsMaterials = computed(() => [
    { key: 'name', label: t('Material') },
    { key: 'purchase_count', label: t('Purchases') },
    { key: 'quantity', label: t('Total qty') },
    { key: 'total_cost_base', label: t('Total cost') },
]);
const tableColumnsPurchases = computed(() => [
    { key: 'purchase_no', label: t('Purchase') },
    { key: 'purchase_date', label: t('Date') },
    { key: 'supplier.name', label: t('Supplier') },
    { key: 'total_amount', label: t('Total') },
    { key: 'remaining_amount', label: t('Remaining') },
    { key: 'status', label: t('Status') },
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
</script>

<template>
    <div class="space-y-4">
        <div class="overflow-hidden rounded-xl border bg-card shadow-sm">
            <div
                class="flex flex-wrap items-center justify-between gap-3 border-b px-5 py-4"
            >
                <div>
                    <h3 class="text-sm font-semibold">
                        {{ t('Materials purchased') }}
                    </h3>
                    <p class="mt-0.5 text-sm text-muted-foreground">
                        {{ t('Qty and cost by material for this project') }}
                    </p>
                </div>
                <Can permission="purchases.create">
                    <Button as-child size="sm">
                        <Link
                            :href="`/purchases/create?project_id=${item.id}`"
                            >{{ t('New purchase') }}</Link
                        >
                    </Button>
                </Can>
            </div>
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
                        class="bg-muted/40 text-start text-muted-foreground"
                    >
                        <tr>
                            <SortableTh
                                column="name"
                                :sort-key="materialSort.sortKey.value"
                                :sort-dir="materialSort.sortDir.value"
                                @sort="materialSort.sortBy"
                            >
                                {{ t('Material') }}
                            </SortableTh>
                            <SortableTh
                                column="purchase_count"
                                :sort-key="materialSort.sortKey.value"
                                :sort-dir="materialSort.sortDir.value"
                                @sort="materialSort.sortBy"
                            >
                                {{ t('Purchases') }}
                            </SortableTh>
                            <SortableTh
                                column="quantity"
                                :sort-key="materialSort.sortKey.value"
                                :sort-dir="materialSort.sortDir.value"
                                @sort="materialSort.sortBy"
                            >
                                {{ t('Total qty') }}
                            </SortableTh>
                            <SortableTh
                                column="total_cost_base"
                                :sort-key="materialSort.sortKey.value"
                                :sort-dir="materialSort.sortDir.value"
                                @sort="materialSort.sortBy"
                            >
                                {{ t('Total cost') }}
                            </SortableTh>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="row in materialSort.sortedRows"
                            :key="row.material_id"
                            class="cursor-pointer border-t hover:bg-muted/30"
                            @click="emit('open-material', row)"
                        >
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
                            <td class="px-4 py-3 font-medium tabular-nums">
                                {{
                                    moneyByCurrency(
                                        row.total_cost_by_currency,
                                        row.total_cost_base,
                                    )
                                }}
                            </td>
                        </tr>
                        <tr v-if="!filteredPurchaseMaterials.length">
                            <td
                                colspan="4"
                                class="px-4 py-10 text-center text-muted-foreground"
                            >
                                {{
                                    materialQuery.trim()
                                        ? t('No matching materials')
                                        : t('No materials purchased yet')
                                }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </TableFrame>
        </div>

        <div class="overflow-hidden rounded-xl border bg-card shadow-sm">
            <div class="border-b px-5 py-4">
                <h3 class="text-sm font-semibold">
                    {{ t('Project purchases') }}
                </h3>
            </div>
            <TableFrame table-id="project-purchases" :columns="tableColumnsPurchases">
                <table class="min-w-full text-sm">
                    <thead class="bg-muted/40 text-start text-muted-foreground">
                        <tr>
                            <SortableTh
                                column="purchase_no"
                                :sort-key="purchaseSort.sortKey.value"
                                :sort-dir="purchaseSort.sortDir.value"
                                @sort="purchaseSort.sortBy"
                            >
                                {{ t('Purchase') }}
                            </SortableTh>
                            <SortableTh
                                column="purchase_date"
                                :sort-key="purchaseSort.sortKey.value"
                                :sort-dir="purchaseSort.sortDir.value"
                                @sort="purchaseSort.sortBy"
                            >
                                {{ t('Date') }}
                            </SortableTh>
                            <SortableTh
                                column="supplier.name"
                                :sort-key="purchaseSort.sortKey.value"
                                :sort-dir="purchaseSort.sortDir.value"
                                @sort="purchaseSort.sortBy"
                            >
                                {{ t('Supplier') }}
                            </SortableTh>
                            <SortableTh
                                column="total_amount"
                                :sort-key="purchaseSort.sortKey.value"
                                :sort-dir="purchaseSort.sortDir.value"
                                @sort="purchaseSort.sortBy"
                            >
                                {{ t('Total') }}
                            </SortableTh>
                            <SortableTh
                                column="remaining_amount"
                                :sort-key="purchaseSort.sortKey.value"
                                :sort-dir="purchaseSort.sortDir.value"
                                @sort="purchaseSort.sortBy"
                            >
                                {{ t('Remaining') }}
                            </SortableTh>
                            <SortableTh
                                column="status"
                                :sort-key="purchaseSort.sortKey.value"
                                :sort-dir="purchaseSort.sortDir.value"
                                @sort="purchaseSort.sortBy"
                            >
                                {{ t('Status') }}
                            </SortableTh>
                            <th class="px-4 py-3 font-medium text-end">
                                {{ t('Pay') }}
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="purchase in purchaseSort.sortedRows"
                            :key="purchase.id"
                            class="border-t hover:bg-muted/30"
                        >
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
                                        purchase.remaining_amount ??
                                            Math.max(
                                                0,
                                                Number(purchase.total_amount) -
                                                    Number(
                                                        purchase.paid_amount,
                                                    ),
                                            ),
                                        purchase.currency || baseCurrency,
                                    )
                                }}
                            </td>
                            <td class="px-4 py-3">
                                <StatusBadge :status="purchase.status" />
                            </td>
                            <td class="px-4 py-3 text-end">
                                <Can permission="purchases.edit">
                                    <Form
                                        v-if="(purchase.remaining_amount ?? Math.max(0, Number(purchase.total_amount) - Number(purchase.paid_amount))) > 0"
                                        :action="`/projects/${item.id}/purchases/${purchase.id}/pay-remaining`"
                                        method="post"
                                        class="flex flex-wrap items-center justify-end gap-2"
                                        v-slot="{ errors, processing }"
                                    >
                                        <input type="hidden" name="amount" :value="purchase.remaining_amount ?? Math.max(0, Number(purchase.total_amount) - Number(purchase.paid_amount))" />
                                        <select name="payment_method" class="h-8 rounded-md border border-input bg-transparent px-2 text-xs">
                                            <option value="cash">{{ t('Cash') }}</option>
                                            <option value="bank">{{ t('Bank') }}</option>
                                            <option value="transfer">{{ t('Transfer') }}</option>
                                        </select>
                                        <Input name="reference_no" type="text" class="h-8 w-[100px] text-xs" :placeholder="t('Ref')" />
                                        <Input name="paid_at" type="date" class="h-8 w-[130px] text-xs" :default-value="new Date().toISOString().slice(0, 10)" />
                                        <Button type="submit" size="sm" :disabled="processing">{{ t('Pay') }}</Button>
                                        <InputError :message="errors.amount" />
                                    </Form>
                                    <span v-else class="text-xs text-muted-foreground">—</span>
                                </Can>
                            </td>
                        </tr>
                        <tr v-if="!(purchases || []).length">
                            <td
                                colspan="7"
                                class="px-4 py-10 text-center text-muted-foreground"
                            >
                                {{ t('No purchases yet') }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </TableFrame>
        </div>
    </div>
</template>
