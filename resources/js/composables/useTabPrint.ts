import { computed, reactive, ref, toValue, type MaybeRefOrGetter } from 'vue';
import { useA4Print } from '@/composables/useA4Print';
import type { PrintRecordOption } from '@/lib/printRecords';

export function useTabPrint<T>(config: {
    title: MaybeRefOrGetter<string>;
    subtitle?: MaybeRefOrGetter<string | null | undefined>;
    columns: MaybeRefOrGetter<string[]>;
    source: MaybeRefOrGetter<T[]>;
    option: (row: T) => PrintRecordOption;
    cells: (row: T) => Array<string | number | null | undefined>;
    emptyLabel?: MaybeRefOrGetter<string>;
}) {
    const pickerOpen = ref(false);
    const selectedKeys = ref<string[]>([]);
    const printRows = ref<string[][]>([]);
    const { printA4 } = useA4Print();

    const records = computed(() =>
        (toValue(config.source) ?? []).map(config.option),
    );
    const title = computed(() => toValue(config.title));
    const subtitle = computed(() => toValue(config.subtitle) || null);
    const columns = computed(() => toValue(config.columns));
    const emptyLabel = computed(() => toValue(config.emptyLabel) || '');

    function setPickerOpen(value: boolean) {
        pickerOpen.value = value;
    }

    function setSelectedKeys(keys: string[]) {
        selectedKeys.value = keys;
    }

    function openPicker() {
        selectedKeys.value = records.value.map((record) => record.key);
        pickerOpen.value = true;
    }

    function confirm(keys: string[]) {
        const selected = new Set(keys);
        const rows = (toValue(config.source) ?? []).filter((row) =>
            selected.has(config.option(row).key),
        );

        if (!rows.length) {
            return;
        }

        printRows.value = rows.map((row) =>
            config.cells(row).map((cell) =>
                cell == null || cell === '' ? '—' : String(cell),
            ),
        );
        pickerOpen.value = false;
        void printA4();
        window.addEventListener(
            'afterprint',
            () => {
                printRows.value = [];
            },
            { once: true },
        );
    }

    return reactive({
        pickerOpen,
        selectedKeys,
        records,
        printRows,
        title,
        subtitle,
        columns,
        emptyLabel,
        openPicker,
        setPickerOpen,
        setSelectedKeys,
        confirm,
    });
}
