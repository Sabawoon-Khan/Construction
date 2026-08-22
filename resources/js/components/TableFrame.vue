<script setup lang="ts">
import { computed } from 'vue';
import TableToolbar from '@/components/TableToolbar.vue';
import { provideTableColumns, type TableColumnDef } from '@/composables/useTableColumns';
import { cn } from '@/lib/utils';

const props = defineProps<{
    tableId: string;
    columns: TableColumnDef[];
    class?: string;
}>();

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
</script>

<template>
    <div class="relative">
        <TableToolbar />
        <div :class="cn('overflow-x-auto', hideClasses, $props.class)">
            <slot />
        </div>
    </div>
</template>

<style>
.tf-hide-1 :is(thead, tbody) tr > :nth-child(1):not([colspan]),
.tf-hide-2 :is(thead, tbody) tr > :nth-child(2):not([colspan]),
.tf-hide-3 :is(thead, tbody) tr > :nth-child(3):not([colspan]),
.tf-hide-4 :is(thead, tbody) tr > :nth-child(4):not([colspan]),
.tf-hide-5 :is(thead, tbody) tr > :nth-child(5):not([colspan]),
.tf-hide-6 :is(thead, tbody) tr > :nth-child(6):not([colspan]),
.tf-hide-7 :is(thead, tbody) tr > :nth-child(7):not([colspan]),
.tf-hide-8 :is(thead, tbody) tr > :nth-child(8):not([colspan]),
.tf-hide-9 :is(thead, tbody) tr > :nth-child(9):not([colspan]),
.tf-hide-10 :is(thead, tbody) tr > :nth-child(10):not([colspan]),
.tf-hide-11 :is(thead, tbody) tr > :nth-child(11):not([colspan]),
.tf-hide-12 :is(thead, tbody) tr > :nth-child(12):not([colspan]),
.tf-hide-13 :is(thead, tbody) tr > :nth-child(13):not([colspan]),
.tf-hide-14 :is(thead, tbody) tr > :nth-child(14):not([colspan]),
.tf-hide-15 :is(thead, tbody) tr > :nth-child(15):not([colspan]),
.tf-hide-16 :is(thead, tbody) tr > :nth-child(16):not([colspan]),
.tf-hide-17 :is(thead, tbody) tr > :nth-child(17):not([colspan]),
.tf-hide-18 :is(thead, tbody) tr > :nth-child(18):not([colspan]),
.tf-hide-19 :is(thead, tbody) tr > :nth-child(19):not([colspan]),
.tf-hide-20 :is(thead, tbody) tr > :nth-child(20):not([colspan]) {
    display: none !important;
}
</style>
