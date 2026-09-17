<script setup lang="ts">
import MisExportButton from '@/components/mis/MisExportButton.vue';
import MisPrintButton from '@/components/mis/MisPrintButton.vue';
import type { ExportCell } from '@/composables/useTableExport';
import { cn } from '@/lib/utils';

type FilterValue = string | number | boolean | null | undefined;

const props = withDefaults(
    defineProps<{
        /** Server export URL. */
        exportUrl?: string;
        filters?: Record<string, FilterValue>;
        /** Client-side export. */
        filename?: string;
        headers?: string[];
        rows?: ExportCell[][];
        filteredRows?: ExportCell[][];
        showExport?: boolean;
        showPrint?: boolean;
        class?: string;
    }>(),
    {
        exportUrl: undefined,
        filters: () => ({}),
        filename: undefined,
        headers: undefined,
        rows: undefined,
        filteredRows: undefined,
        showExport: true,
        showPrint: true,
        class: undefined,
    },
);
</script>

<template>
    <div
        :class="cn('mis-table-actions flex flex-wrap items-center gap-2', props.class)"
    >
        <slot />
        <MisExportButton
            v-if="showExport"
            :url="exportUrl"
            :filters="filters"
            :filename="filename"
            :headers="headers"
            :rows="rows"
            :filtered-rows="filteredRows"
        />
        <MisPrintButton v-if="showPrint" />
    </div>
</template>
