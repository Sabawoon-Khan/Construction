<script setup lang="ts">
import { computed, toRef } from 'vue';
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
    }>(),
    {
        pending: false,
        delay: true,
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
        class="table-panel reveal"
        :class="{ delay, pending }"
    >
        <slot name="filters" :visible-col-count="visibleColCount" />
        <div class="table-wrap" :class="hideColumnClasses">
            <slot :visible-col-count="visibleColCount" />
        </div>
        <slot name="pager" :visible-col-count="visibleColCount" />
    </section>
</template>
