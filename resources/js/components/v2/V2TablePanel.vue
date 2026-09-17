<script setup lang="ts">
import { computed, toRef } from 'vue';
import MisPrintFooter from '@/components/mis/MisPrintFooter.vue';
import MisPrintLetterhead from '@/components/mis/MisPrintLetterhead.vue';
import {
    provideTableColumns,
    type TableColumnDef,
} from '@/composables/useTableColumns';

const props = withDefaults(
    defineProps<{
        pending?: boolean;
        tableId: string;
        columns: TableColumnDef[];
        delay?: boolean;
        /** Title shown on the A4 print letterhead. */
        printTitle?: string;
        printSubtitle?: string;
    }>(),
    {
        pending: false,
        delay: true,
        printTitle: undefined,
        printSubtitle: undefined,
    },
);

const columnsState = provideTableColumns(
    props.tableId,
    toRef(props, 'columns'),
);

const hideColumnClasses = computed(() => {
    void columnsState.visibleCount.value;
    return columnsState.columns
        .map((column, index) =>
            columnsState.isVisible(column.key) ? '' : `tf-hide-${index + 1}`,
        )
        .filter(Boolean);
});

const visibleColCount = columnsState.visibleCount;

defineExpose({
    columnsState,
    visibleColCount,
});
</script>

<template>
    <section
        class="table-panel reveal print-target print-sheet"
        :class="{ delay, pending }"
    >
        <MisPrintLetterhead
            class="hidden print:block"
            :title="printTitle"
            :subtitle="printSubtitle"
        />
        <div class="print:hidden">
            <slot name="filters" :visible-col-count="visibleColCount" />
        </div>
        <div class="table-wrap print-sheet-body" :class="hideColumnClasses">
            <slot :visible-col-count="visibleColCount" />
        </div>
        <div class="print:hidden">
            <slot name="pager" :visible-col-count="visibleColCount" />
        </div>
        <MisPrintFooter class="hidden print:flex" />
    </section>
</template>
