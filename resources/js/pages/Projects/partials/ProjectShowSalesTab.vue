<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import EmptyState from '@/components/EmptyState.vue';
import SortableTh from '@/components/SortableTh.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import TableFrame from '@/components/TableFrame.vue';
import TableIndexTd from '@/components/TableIndexTd.vue';
import TableIndexTh from '@/components/TableIndexTh.vue';
import { MisTableActions } from '@/components/mis';
import type { ExportCell } from '@/composables/useTableExport';
import { CreateButton } from '@/components/ui/create-button';
import { V2Panel } from '@/components/v2';
import { indexTableColumn } from '@/composables/useTableColumns';
import { useMisPage } from '@/composables/useMisPage';
import { useTableSort } from '@/composables/useTableSort';
import { useTranslations } from '@/composables/useTranslations';
import { money } from '@/lib/utils';

const props = defineProps<{
    item: Record<string, any>;
}>();

const { t } = useTranslations();
const salesColumns = computed(() => [
    indexTableColumn(),
    { key: 'sale_no', label: t('Sale') },
    { key: 'customer.name', label: t('Customer') },
    { key: 'unit.list_price', label: t('Amount') },
    { key: 'created_at', label: t('Created at') },
]);
const unitsColumns = computed(() => [
    indexTableColumn(),
    { key: 'unit_no', label: t('Unit') },
    { key: 'status', label: t('Status') },
    { key: 'list_price', label: t('List price') },
]);
const { formatDate } = useMisPage();
const salesSort = useTableSort(
    () => (props.item.sales ?? []) as Record<string, any>[],
);
const unitsSort = useTableSort(
    () => (props.item.units ?? []) as Record<string, any>[],
);

const salesExportHeaders = computed(() => [
    t('Sale'),
    t('Customer'),
    t('Amount'),
    t('Created at'),
]);

function saleAmount(sale: Record<string, any>) {
    return Math.max(
        0,
        Number(sale.unit?.list_price ?? 0) - Number(sale.discount ?? 0),
    );
}

function mapSaleExportRow(sale: Record<string, any>): ExportCell[] {
    return [
        sale.sale_no,
        sale.customer?.name || null,
        saleAmount(sale),
        formatDate(sale.created_at),
    ];
}

const salesExportRows = computed(() =>
    ((props.item.sales ?? []) as Record<string, any>[]).map(mapSaleExportRow),
);
const salesFilteredExportRows = computed(() =>
    salesSort.sortedRows.map(mapSaleExportRow),
);

const unitsExportHeaders = computed(() => [
    t('Unit'),
    t('Status'),
    t('List price'),
]);

function mapUnitExportRow(unit: Record<string, any>): ExportCell[] {
    return [
        unit.unit_no || unit.code || String(unit.id),
        unit.status,
        Number(unit.list_price ?? 0),
    ];
}

const unitsExportRows = computed(() =>
    ((props.item.units ?? []) as Record<string, any>[]).map(mapUnitExportRow),
);
const unitsFilteredExportRows = computed(() =>
    unitsSort.sortedRows.map(mapUnitExportRow),
);
</script>

<template>
    <div class="space-y-4">
        <V2Panel :padded="false" :title="t('Recent sales')">
            <template #actions>
                
            <MisTableActions
                    :filename="`project-${item.id}-sales`"
                    :headers="salesExportHeaders"
                    :rows="salesExportRows"
                    :filtered-rows="salesFilteredExportRows"
                >
                <CreateButton
                    permission="sales.create"
                    :href="`/sales/create?project_id=${item.id}`"
                >
                    {{ t('Create sale') }}
                </CreateButton>
            </MisTableActions>
        
            </template>
            <TableFrame table-id="project-sales" :columns="salesColumns">
                <table class="min-w-full text-sm">
                    <thead class="table-chrome-head text-start text-muted-foreground">
                        <tr>
                            <TableIndexTh />
                            <SortableTh
                                column="sale_no"
                                :sort-key="salesSort.sortKey"
                                :sort-dir="salesSort.sortDir"
                                @sort="salesSort.sortBy"
                            >
                                {{ t('Sale') }}
                            </SortableTh>
                            <SortableTh
                                column="customer.name"
                                :sort-key="salesSort.sortKey"
                                :sort-dir="salesSort.sortDir"
                                @sort="salesSort.sortBy"
                            >
                                {{ t('Customer') }}
                            </SortableTh>
                            <SortableTh
                                column="unit.list_price"
                                :sort-key="salesSort.sortKey"
                                :sort-dir="salesSort.sortDir"
                                @sort="salesSort.sortBy"
                            >
                                {{ t('Amount') }}
                            </SortableTh>
                            <SortableTh
                                column="created_at"
                                :sort-key="salesSort.sortKey"
                                :sort-dir="salesSort.sortDir"
                                @sort="salesSort.sortBy"
                            >
                                {{ t('Created at') }}
                            </SortableTh>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="(sale, index) in salesSort.sortedRows"
                            :key="sale.id"
                            class="border-t hover:bg-muted/30"
                        >
                            <TableIndexTd :index="index" />
                            <td class="px-4 py-3">
                                <Link
                                    :href="`/sales/${sale.id}`"
                                    class="font-medium text-primary hover:underline"
                                    >{{ sale.sale_no }}</Link
                                >
                            </td>
                            <td class="px-4 py-3">
                                {{ sale.customer?.name || '—' }}
                            </td>
                            <td class="px-4 py-3 tabular-nums">
                                {{
                                    money(
                                        Math.max(
                                            0,
                                            Number(sale.unit?.list_price ?? 0) -
                                                Number(sale.discount ?? 0),
                                        ),
                                        sale.currency,
                                    )
                                }}
                            </td>
                            <td
                                class="px-4 py-3 whitespace-nowrap text-muted-foreground"
                            >
                                {{ formatDate(sale.created_at) }}
                            </td>
                        </tr>
                        <EmptyState
                            v-if="!item.sales?.length"
                            :colspan="5"
                            :title="t('No sales yet')"
                        >
                        </EmptyState>
                    </tbody>
                </table>
            </TableFrame>
        </V2Panel>

        <V2Panel :padded="false" :title="t('Project units')">
            <template #actions>
                
            <MisTableActions
                    :filename="`project-${item.id}-units`"
                    :headers="unitsExportHeaders"
                    :rows="unitsExportRows"
                    :filtered-rows="unitsFilteredExportRows"
                >
                <CreateButton
                    permission="units.create"
                    :href="`/units/create?project_id=${item.id}`"
                >
                    {{ t('Add unit') }}
                </CreateButton>
            </MisTableActions>
        
            </template>
            <TableFrame table-id="project-units" :columns="unitsColumns">
                <table class="min-w-full text-sm">
                    <thead class="table-chrome-head text-start text-muted-foreground">
                        <tr>
                            <TableIndexTh />
                            <SortableTh
                                column="unit_no"
                                :sort-key="unitsSort.sortKey"
                                :sort-dir="unitsSort.sortDir"
                                @sort="unitsSort.sortBy"
                            >
                                {{ t('Unit') }}
                            </SortableTh>
                            <SortableTh
                                column="status"
                                :sort-key="unitsSort.sortKey"
                                :sort-dir="unitsSort.sortDir"
                                @sort="unitsSort.sortBy"
                            >
                                {{ t('Status') }}
                            </SortableTh>
                            <SortableTh
                                column="list_price"
                                :sort-key="unitsSort.sortKey"
                                :sort-dir="unitsSort.sortDir"
                                @sort="unitsSort.sortBy"
                            >
                                {{ t('List price') }}
                            </SortableTh>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="(unit, index) in unitsSort.sortedRows"
                            :key="unit.id"
                            class="border-t hover:bg-muted/30"
                        >
                            <TableIndexTd :index="index" />
                            <td class="px-4 py-3">
                                <Link
                                    :href="`/units/${unit.id}`"
                                    class="font-medium text-primary hover:underline"
                                >
                                    {{ unit.unit_no || unit.code || unit.id }}
                                </Link>
                            </td>
                            <td class="px-4 py-3">
                                <StatusBadge :status="unit.status" />
                            </td>
                            <td class="px-4 py-3 tabular-nums">
                                {{
                                    money(
                                        unit.list_price,
                                        unit.currency,
                                    )
                                }}
                            </td>
                        </tr>
                        <EmptyState
                            v-if="!item.units?.length"
                            :colspan="4"
                            :title="t('No units yet')"
                        >
                        </EmptyState>
                    </tbody>
                </table>
            </TableFrame>
        </V2Panel>
    </div>
</template>
