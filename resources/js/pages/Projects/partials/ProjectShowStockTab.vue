<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { computed, ref, toValue } from 'vue';
import Can from '@/components/Can.vue';
import ConfirmActionDialog from '@/components/ConfirmActionDialog.vue';
import EmptyState from '@/components/EmptyState.vue';
import InputError from '@/components/InputError.vue';
import { MisTableActions, TabPrintHost } from '@/components/mis';
import type { ExportCell } from '@/composables/useTableExport';
import { useTabPrint } from '@/composables/useTabPrint';
import RelatedCreateLink from '@/components/RelatedCreateLink.vue';
import SortableTh from '@/components/SortableTh.vue';
import TableFrame from '@/components/TableFrame.vue';
import TableIndexTd from '@/components/TableIndexTd.vue';
import TableIndexTh from '@/components/TableIndexTh.vue';
import { indexTableColumn } from '@/composables/useTableColumns';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { V2Panel } from '@/components/v2';
import { useCalendar } from '@/composables/useCalendar';
import { useTableSort } from '@/composables/useTableSort';
import { useTranslations } from '@/composables/useTranslations';
import { formatNumber } from '@/lib/utils';

const props = defineProps<{
    item: Record<string, any>;
    stock?: {
        material_id: number;
        purchased: number;
        issued: number;
        on_hand: number;
        last_issue_at?: string | null;
    }[];
    stock_materials?: {
        id: number;
        name: string;
        code: string;
        unit?: { unit_name: string } | null;
    }[];
    stock_issues?: {
        id: number;
        material_id: number;
        quantity: number;
        transaction_date?: string | null;
        material?: { id: number; name: string } | null;
    }[];
}>();

const { t } = useTranslations();
const tableColumnsStock = computed(() => [
    indexTableColumn(),
    { key: 'material_id', label: t('Material') },
    { key: 'unit', label: t('Unit') },
    { key: 'on_hand', label: t('On hand') },
]);
const tableColumnsIssues = computed(() => [
    indexTableColumn(),
    { key: 'material.name', label: t('Material') },
    { key: 'quantity', label: t('Issued qty') },
    { key: 'transaction_date', label: t('Issue date') },
]);
const stockSort = useTableSort(() => props.stock ?? [], { defaultKey: 'material_id' });
const issueSort = useTableSort(() => props.stock_issues ?? []);
const calendar = useCalendar();
const formatDate = (value: string | null | undefined) =>
    calendar.format(value) || '—';

const issueForm = useForm({
    project_id: props.item.id,
    material_id: '',
    quantity: '',
    transaction_date: new Date().toISOString().slice(0, 10),
    redirect_to: `/projects/${props.item.id}`,
});

const issueConfirmOpen = ref(false);

function materialName(materialId: number) {
    return (
        props.stock_materials?.find((m) => m.id === materialId)?.name ||
        String(materialId)
    );
}

function materialUnit(materialId: number) {
    return (
        props.stock_materials?.find((m) => m.id === materialId)?.unit?.unit_name ||
        ''
    );
}

function requestIssue() {
    if (!issueForm.material_id || !issueForm.quantity) {
        return;
    }
    issueConfirmOpen.value = true;
}

function confirmIssue() {
    issueForm.post('/inventory', {
        preserveScroll: true,
        onFinish: () => {
            issueConfirmOpen.value = false;
        },
        onSuccess: () => {
            issueForm.reset('material_id', 'quantity');
            issueForm.transaction_date = new Date().toISOString().slice(0, 10);
        },
    });
}

const stockExportHeaders = computed(() => [
    t('Material'),
    t('Unit'),
    t('On hand'),
]);

function mapStockExportRow(
    row: NonNullable<typeof props.stock>[number],
): ExportCell[] {
    return [
        materialName(row.material_id),
        materialUnit(row.material_id) || null,
        row.on_hand,
    ];
}

const stockExportRows = computed(() => (props.stock ?? []).map(mapStockExportRow));
const stockFilteredExportRows = computed(() =>
    stockSort.sortedRows.map(mapStockExportRow),
);

const issueExportHeaders = computed(() => [
    t('Material'),
    t('Issued qty'),
    t('Issue date'),
]);

function mapIssueExportRow(
    issue: NonNullable<typeof props.stock_issues>[number],
): ExportCell[] {
    return [
        issue.material?.name || materialName(issue.material_id),
        issue.quantity,
        issue.transaction_date ? formatDate(issue.transaction_date) : null,
    ];
}

const issueExportRows = computed(() =>
    (props.stock_issues ?? []).map(mapIssueExportRow),
);
const issueFilteredExportRows = computed(() =>
    issueSort.sortedRows.map(mapIssueExportRow),
);

const projectSubtitle = () =>
    [props.item.name, props.item.code].filter(Boolean).join(' · ');

const stockPrint = useTabPrint({
    title: () => t('Stock on hand'),
    subtitle: projectSubtitle,
    columns: () => [t('Material'), t('Unit'), t('On hand')],
    source: () => toValue(stockSort.sortedRows),
    emptyLabel: () => t('No stock yet'),
    option: (row) => ({
        key: String(row.material_id),
        title: materialName(row.material_id),
        subtitle: materialUnit(row.material_id) || '—',
        amount: formatNumber(row.on_hand, 3),
    }),
    cells: (row) => [
        materialName(row.material_id),
        materialUnit(row.material_id) || '—',
        formatNumber(row.on_hand, 3),
    ],
});

const issuesPrint = useTabPrint({
    title: () => t('Stock issue dates'),
    subtitle: projectSubtitle,
    columns: () => [t('Material'), t('Issued qty'), t('Issue date')],
    source: () => toValue(issueSort.sortedRows),
    emptyLabel: () => t('No issues yet'),
    option: (row) => ({
        key: String(row.id),
        title: row.material?.name || materialName(row.material_id),
        subtitle: row.transaction_date
            ? formatDate(row.transaction_date)
            : '—',
        amount: formatNumber(row.quantity, 3),
    }),
    cells: (row) => [
        row.material?.name || materialName(row.material_id),
        formatNumber(row.quantity, 3),
        row.transaction_date ? formatDate(row.transaction_date) : '—',
    ],
});
</script>

<template>
    <div class="space-y-4">
        <V2Panel :title="t('Issue from stock')">
            <form
                class="grid gap-4 sm:grid-cols-4"
                @submit.prevent="requestIssue"
            >
            <div class="grid gap-2 sm:col-span-2">
                <div class="flex items-center justify-between gap-2">
                    <Label>{{ t('Material') }}</Label>
                    <RelatedCreateLink href="/materials/create" :label="t('Add material')" />
                </div>
                <select
                    v-model="issueForm.material_id"
                    required
                    class="h-9 rounded-md border px-3 text-sm"
                >
                    <option value="">{{ t('Select material') }}</option>
                    <option
                        v-for="row in (stock || []).filter(
                            (s) => s.on_hand > 0,
                        )"
                        :key="row.material_id"
                        :value="String(row.material_id)"
                    >
                        {{ materialName(row.material_id) }}
                        ({{ t('on hand') }} {{ formatNumber(row.on_hand, 3) }}
                        <template v-if="materialUnit(row.material_id)">
                            {{ materialUnit(row.material_id) }}
                        </template>)
                    </option>
                </select>
                <InputError :message="issueForm.errors.material_id" />
            </div>
            <div class="grid gap-2">
                <Label>{{ t('Qty to issue') }}</Label>
                <Input
                    v-model="issueForm.quantity"
                    type="number"
                    step="0.001"
                    min="0.001"
                    required
                />
                <InputError :message="issueForm.errors.quantity" />
            </div>
            <div class="grid gap-2">
                <Label>{{ t('Date') }}</Label>
                <Input
                    v-model="issueForm.transaction_date"
                    type="date"
                />
            </div>
            <div class="sm:col-span-4">
                <Can permission="inventory.create">
                    <Button type="submit" :disabled="issueForm.processing">
                        {{ issueForm.processing ? t('Saving…') : t('Issue from stock') }}
                    </Button>
                </Can>
            </div>
            </form>
        </V2Panel>

        <div class="grid gap-4 lg:grid-cols-2">
            <V2Panel :padded="false" :title="t('Stock on hand')">
                <template #actions>
                    <MisTableActions
                        :filename="`project-${item.id}-stock`"
                        :headers="stockExportHeaders"
                        :rows="stockExportRows"
                        :filtered-rows="stockFilteredExportRows"
                        :on-print="stockPrint.openPicker"
                    />
                </template>
                <TableFrame table-id="project-stock-on-hand" :columns="tableColumnsStock">
                <table class="min-w-full text-sm">
                    <thead
                        class="table-chrome-head text-start text-muted-foreground"
                    >
                        <tr>
                            <TableIndexTh />
                            <SortableTh
                                column="material_id"
                                :sort-key="stockSort.sortKey"
                                :sort-dir="stockSort.sortDir"
                                @sort="stockSort.sortBy"
                            >
                                {{ t('Material') }}
                            </SortableTh>
                            <th class="px-4 py-3 font-medium">
                                {{ t('Unit') }}
                            </th>
                            <SortableTh
                                column="on_hand"
                                :sort-key="stockSort.sortKey"
                                :sort-dir="stockSort.sortDir"
                                @sort="stockSort.sortBy"
                            >
                                {{ t('On hand') }}
                            </SortableTh>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="(row, index) in stockSort.sortedRows"
                            :key="`onhand-${row.material_id}`"
                            class="border-t"
                        >
                            <TableIndexTd :index="index" />
                            <td class="px-4 py-3">
                                {{ materialName(row.material_id) }}
                            </td>
                            <td class="px-4 py-3">
                                {{ materialUnit(row.material_id) || '—' }}
                            </td>
                            <td class="px-4 py-3 font-medium tabular-nums">
                                {{ formatNumber(row.on_hand, 3) }}
                                <span
                                    v-if="materialUnit(row.material_id)"
                                    class="text-muted-foreground"
                                >
                                    {{ materialUnit(row.material_id) }}
                                </span>
                            </td>
                        </tr>
                        <EmptyState
                            v-if="!(stock || []).length"
                            :colspan="4"
                            :title="t('No stock yet')"
                        >
                        </EmptyState>
                    </tbody>
                </table>
                </TableFrame>
            </V2Panel>

            <V2Panel :padded="false" :title="t('Stock issue dates')">
                <template #actions>
                    <MisTableActions
                        :filename="`project-${item.id}-stock-issues`"
                        :headers="issueExportHeaders"
                        :rows="issueExportRows"
                        :filtered-rows="issueFilteredExportRows"
                        :on-print="issuesPrint.openPicker"
                    />
                </template>
                <TableFrame table-id="project-stock-issues" :columns="tableColumnsIssues">
                <table class="min-w-full text-sm">
                    <thead
                        class="table-chrome-head text-start text-muted-foreground"
                    >
                        <tr>
                            <TableIndexTh />
                            <SortableTh
                                column="material.name"
                                :sort-key="issueSort.sortKey"
                                :sort-dir="issueSort.sortDir"
                                @sort="issueSort.sortBy"
                            >
                                {{ t('Material') }}
                            </SortableTh>
                            <SortableTh
                                column="quantity"
                                :sort-key="issueSort.sortKey"
                                :sort-dir="issueSort.sortDir"
                                @sort="issueSort.sortBy"
                            >
                                {{ t('Issued qty') }}
                            </SortableTh>
                            <SortableTh
                                column="transaction_date"
                                :sort-key="issueSort.sortKey"
                                :sort-dir="issueSort.sortDir"
                                @sort="issueSort.sortBy"
                            >
                                {{ t('Issue date') }}
                            </SortableTh>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="(issue, index) in issueSort.sortedRows"
                            :key="`issued-${issue.id}`"
                            class="border-t"
                        >
                            <TableIndexTd :index="index" />
                            <td class="px-4 py-3">
                                {{
                                    issue.material?.name ||
                                    materialName(issue.material_id)
                                }}
                            </td>
                            <td class="px-4 py-3 tabular-nums">
                                {{ formatNumber(issue.quantity, 3) }}
                                <span
                                    v-if="materialUnit(issue.material_id)"
                                    class="text-muted-foreground"
                                >
                                    {{ materialUnit(issue.material_id) }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                {{
                                    issue.transaction_date
                                        ? formatDate(issue.transaction_date)
                                        : '—'
                                }}
                            </td>
                        </tr>
                        <EmptyState
                            v-if="!(stock_issues || []).length"
                            :colspan="4"
                            :title="t('No issue dates yet')"
                        />
                    </tbody>
                </table>
                </TableFrame>
            </V2Panel>
        </div>

        <ConfirmActionDialog
            v-model:open="issueConfirmOpen"
            :title="t('Issue from stock')"
            :description="t('Remove this quantity from project stock on hand?')"
            :confirm-label="t('Issue from stock')"
            variant="default"
            :processing="issueForm.processing"
            @confirm="confirmIssue"
        />

        <TabPrintHost :state="stockPrint" />
        <TabPrintHost :state="issuesPrint" />
    </div>
</template>
