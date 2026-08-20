<script setup lang="ts">
import { computed } from 'vue';
import { Spinner } from '@/components/ui/spinner';
import { useTranslations } from '@/composables/useTranslations';
import { cn } from '@/lib/utils';

export type MisDataTableColumn = {
    key: string;
    label: string;
    class?: string;
    headerClass?: string;
    align?: 'start' | 'center' | 'end';
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
    }>(),
    {
        class: undefined,
        loading: false,
        density: 'comfortable',
        rowKey: undefined,
        stickyHeader: false,
    },
);

const { t } = useTranslations();
const resolvedEmptyLabel = computed(() => props.emptyLabel ?? t('No results'));

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
        <div class="overflow-x-auto">
            <table class="w-full min-w-[32rem] text-sm">
                <thead>
                    <tr
                        :class="
                            cn(
                                'border-b border-border/70 bg-muted/30',
                                stickyHeader && 'sticky top-0 z-[1]',
                            )
                        "
                    >
                        <th
                            v-for="column in columns"
                            :key="column.key"
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
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="!loading && rows.length === 0">
                        <td
                            :colspan="columns.length"
                            class="px-4 py-10 text-center text-muted-foreground"
                        >
                            <slot name="empty">{{ resolvedEmptyLabel }}</slot>
                        </td>
                    </tr>
                    <tr
                        v-for="(row, rowIndex) in rows"
                        :key="rowId(row, rowIndex, rowKey)"
                        class="border-b border-border/50 transition-colors last:border-0 hover:bg-muted/20"
                    >
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
        </div>
    </div>
</template>
