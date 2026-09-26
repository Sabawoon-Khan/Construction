<script setup lang="ts">
import { nextTick, onBeforeUnmount, ref } from 'vue';
import { Printer } from '@lucide/vue';
import PrintRecordsDialog from '@/components/mis/PrintRecordsDialog.vue';
import { Button } from '@/components/ui/button';
import type { ButtonVariants } from '@/components/ui/button';
import { useTranslations } from '@/composables/useTranslations';
import {
    applyPrintRowSelection,
    collectPrintableRows,
    findPrintHost,
    type CollectedPrintRow,
    type PrintRecordOption,
} from '@/lib/printRecords';
import { printPage } from '@/lib/voucher';
import { cn } from '@/lib/utils';

const props = withDefaults(
    defineProps<{
        size?: ButtonVariants['size'];
        variant?: 'default' | 'outline' | 'secondary' | 'ghost';
        label?: string;
        class?: string;
        /** When set, called instead of the record picker. */
        onPrint?: () => void;
    }>(),
    {
        size: 'sm',
        variant: 'outline',
        label: undefined,
        class: undefined,
        onPrint: undefined,
    },
);

const { t } = useTranslations();
const buttonRef = ref<HTMLElement | null>(null);
const pickerOpen = ref(false);
const records = ref<PrintRecordOption[]>([]);
const selectedKeys = ref<string[]>([]);
let collected: CollectedPrintRow[] = [];
let restoreRows: (() => void) | null = null;

function cleanup() {
    restoreRows?.();
    restoreRows = null;
    window.removeEventListener('afterprint', cleanup);
}

function handleClick() {
    if (props.onPrint) {
        props.onPrint();
        return;
    }

    const host = findPrintHost(buttonRef.value);
    collected = host ? collectPrintableRows(host) : [];

    if (!collected.length) {
        printPage();
        return;
    }

    records.value = collected.map(({ element: _element, ...record }) => record);
    selectedKeys.value = records.value.map((record) => record.key);
    pickerOpen.value = true;
}

async function confirmPrint(keys: string[]) {
    cleanup();
    restoreRows = applyPrintRowSelection(collected, keys);
    pickerOpen.value = false;
    window.addEventListener('afterprint', cleanup);
    window.setTimeout(cleanup, 60_000);
    await nextTick();
    await new Promise((resolve) => window.setTimeout(resolve, 280));
    printPage();
}

onBeforeUnmount(cleanup);
</script>

<template>
    <span ref="buttonRef" class="inline-flex">
        <Button
            type="button"
            :variant="variant"
            :size="size"
            :class="
                cn(
                    'h-8 gap-1.5 border-border/80 bg-background/80 font-medium shadow-none',
                    props.class,
                )
            "
            @click="handleClick"
        >
            <Printer class="size-3.5 text-slate-600 dark:text-slate-300" />
            <span>{{ label ?? t('Print') }}</span>
        </Button>
    </span>

    <PrintRecordsDialog
        v-model:open="pickerOpen"
        v-model:selected-keys="selectedKeys"
        :records="records"
        @confirm="confirmPrint"
    />
</template>
