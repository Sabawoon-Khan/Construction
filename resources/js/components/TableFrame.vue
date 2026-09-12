<script setup lang="ts">
import { computed, useSlots } from 'vue';
import TableToolbar from '@/components/TableToolbar.vue';
import { provideTableColumns, type TableColumnDef } from '@/composables/useTableColumns';
import { cn } from '@/lib/utils';

const props = defineProps<{
    tableId: string;
    columns: TableColumnDef[];
    class?: string;
}>();

const slots = useSlots();
const columnsState = provideTableColumns(
    props.tableId,
    () => props.columns,
);

const hideClasses = computed(() => {
    void columnsState.visibleCount.value;
    return props.columns
        .map((column, index) =>
            columnsState.isVisible(column.key) ? '' : `tf-hide-${index + 1}`,
        )
        .filter(Boolean);
});

const hasToolbar = computed(() => Boolean(slots.toolbar));
const hasColumnsToggle = computed(
    () => columnsState.columns.filter((column) => !column.locked).length > 0,
);
const showChrome = computed(() => hasToolbar.value || hasColumnsToggle.value);
</script>

<template>
    <div class="relative">
        <div
            v-if="showChrome"
            class="table-chrome-toolbar flex items-center justify-end gap-1.5 border-b px-2.5 py-1.5"
        >
            <div class="min-w-0">
                <slot name="toolbar" />
            </div>
            <div class="flex shrink-0 items-center gap-1">
                <slot name="actions" />
                <TableToolbar />
            </div>
        </div>
        <div
            :class="
                cn(
                    'overflow-x-auto overscroll-x-contain [-webkit-overflow-scrolling:touch]',
                    hideClasses,
                    $props.class,
                )
            "
        >
            <slot />
        </div>
    </div>
</template>

<!-- Keep empty style so Vite HMR can resolve type=style after rules moved to table-col-hide.css -->
<style></style>
