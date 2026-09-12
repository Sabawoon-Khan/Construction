<script setup lang="ts">
import { computed } from 'vue';
import SortableTh from '@/components/SortableTh.vue';
import TableFrame from '@/components/TableFrame.vue';
import TableIndexTd from '@/components/TableIndexTd.vue';
import TableIndexTh from '@/components/TableIndexTh.vue';
import { Spinner } from '@/components/ui/spinner';
import { indexTableColumn } from '@/composables/useTableColumns';
import { provideTableSort } from '@/composables/useTableSort';
import { useTranslations } from '@/composables/useTranslations';
import { cn } from '@/lib/utils';

export type MisDataTableColumn = {
    key: string;
    label: string;
    class?: string;
    headerClass?: string;
    align?: 'start' | 'center' | 'end';
    sortable?: boolean;
};

const props = withDefaults(
    defineProps<{
        columns: MisDataTableColumn[];
        rows: Array<Record<string, unknown>>;
        class?: string;
        emptyLabel?: string;
        loading?: boolean;
        density?: 'comfortable' | 'compact';
        /** Row identity key; falls back to index. */
        rowKey?: string;
        stickyHeader?: boolean;
        tableId?: string;
    }>(),
    {
        class: undefined,
        loading: false,
        density: 'comfortable',
        rowKey: undefined,
        stickyHeader: false,
        tableId: 'mis-data-table',
    },
);

const { t } = useTranslations();
const resolvedEmptyLabel = computed(() => props.emptyLabel ?? t('No results'));
const { sortedRows } = provideTableSort(() => props.rows);
const frameColumns = computed(() =>
    [indexTableColumn(), ...props.columns.map((column) => ({
        key: column.key,
        label: column.label,
        locked: column.sortable === false,
    }))],
);

function cellValue(row: Record<string, unknown>, key: string): string {
    const value = row[key];

    if (value === null || value === undefined) {
        return '—';
    }

    if (typeof value === 'object') {
        return JSON.stringify(value);
    }

    return String(value);
}

function alignClass(align?: 'start' | 'center' | 'end'): string {
    if (align === 'center') {
        return 'text-center';
    }
    if (align === 'end') {
        return 'text-end';
    }
    return 'text-start';
}

function rowId(row: Record<string, unknown>, index: number, key?: string): string | number {
    if (key && row[key] != null) {
        return String(row[key]);
    }
    return index;
}
</script>

<template>
    <div
        :class="
            cn(
                'relative overflow-hidden rounded-2xl border border-border/80 bg-card shadow-soft',
                $props.class,
            )
        "
    >
        <div
            v-if="loading"
            class="absolute inset-0 z-10 flex items-center justify-center bg-background/60 backdrop-blur-[1px]"
        >
            <Spinner class="size-6" />
        </div>
        <TableFrame :table-id="tableId" :columns="frameColumns">
            <table class="w-full min-w-[28rem] text-sm sm:min-w-[32rem]">
                <thead>
                    <tr
                        :class="
                            cn(
                                'table-chrome-head border-b border-border/70',
                                stickyHeader && 'sticky top-0 z-[1]',
                            )
                        "
                    >
                        <TableIndexTh />
                        <template v-for="column in columns" :key="column.key">
                            <th
                                v-if="column.sortable === false"
                                :class="
                                    cn(
                                        'font-semibold tracking-[0.12em] text-muted-foreground uppercase rtl:tracking-normal',
                                        density === 'compact'
                                            ? 'px-3 py-2 text-[10px]'
                                            : 'px-4 py-3 text-[11px]',
                                        alignClass(column.align),
                                        column.headerClass,
                                        column.class,
                                    )
                                "
                            >
                                {{ column.label }}
                            </th>
                            <SortableTh
                                v-else
                                :column="column.key"
                                :align="column.align"
                                :class="
                                    cn(
                                        'font-semibold tracking-[0.12em] text-muted-foreground uppercase rtl:tracking-normal',
                                        density === 'compact'
                                            ? 'px-3 py-2 text-[10px]'
                                            : 'px-4 py-3 text-[11px]',
                                        column.headerClass,
                                        column.class,
                                    )
                                "
                            >
                                {{ column.label }}
                            </SortableTh>
                        </template>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="!loading && rows.length === 0">
                        <td
                            :colspan="columns.length + 1"
                            class="px-4 py-10 text-center text-muted-foreground"
                        >
                            <slot name="empty">{{ resolvedEmptyLabel }}</slot>
                        </td>
                    </tr>
                    <tr
                        v-for="(row, rowIndex) in sortedRows"
                        :key="rowId(row, rowIndex, rowKey)"
                        class="table-chrome-row border-b border-border/50 transition-colors last:border-0"
                    >
                        <TableIndexTd :index="rowIndex" />
                        <td
                            v-for="column in columns"
                            :key="column.key"
                            :class="
                                cn(
                                    'align-middle',
                                    density === 'compact' ? 'px-3 py-2' : 'px-4 py-3',
                                    alignClass(column.align),
                                    column.class,
                                )
                            "
                        >
                            <slot
                                :name="`cell-${column.key}`"
                                :row="row"
                                :value="row[column.key]"
                                :index="rowIndex"
                            >
                                {{ cellValue(row, column.key) }}
                            </slot>
                        </td>
                    </tr>
                </tbody>
            </table>
        </TableFrame>
    </div>
</template>
