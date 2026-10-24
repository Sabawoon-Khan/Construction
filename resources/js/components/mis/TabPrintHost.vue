<script setup lang="ts">
import PrintRecordsDialog from '@/components/mis/PrintRecordsDialog.vue';
import RecordsListPrintSheet from '@/components/mis/RecordsListPrintSheet.vue';
import type { useTabPrint } from '@/composables/useTabPrint';

defineProps<{
    state: ReturnType<typeof useTabPrint>;
}>();
</script>

<template>
    <PrintRecordsDialog
        :open="state.pickerOpen"
        :selected-keys="state.selectedKeys"
        :records="state.records"
        :title="state.title"
        :empty-label="state.emptyLabel"
        @update:open="state.setPickerOpen"
        @update:selected-keys="state.setSelectedKeys"
        @confirm="state.confirm"
    />
    <Teleport to="body">
        <div v-if="state.printRows.length" class="a4-print-host">
            <RecordsListPrintSheet
                :title="state.title"
                :subtitle="state.subtitle"
                :columns="state.columns"
                :rows="state.printRows"
            />
        </div>
    </Teleport>
</template>
