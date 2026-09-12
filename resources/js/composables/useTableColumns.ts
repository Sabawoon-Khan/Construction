import { computed, inject, provide, ref, toValue, type InjectionKey, type MaybeRefOrGetter } from 'vue';

export type TableColumnDef = {
    key: string;
    label: string;
    locked?: boolean;
};

export const TABLE_INDEX_KEY = '__index';

export function indexTableColumn(label = '#'): TableColumnDef {
    return { key: TABLE_INDEX_KEY, label, locked: true };
}

export function withIndexColumn(columns: TableColumnDef[], label = '#'): TableColumnDef[] {
    return [indexTableColumn(label), ...columns];
}

export function tableRowNumber(rowIndex: number, from?: number | null): number {
    if (from != null && from > 0) {
        return from + rowIndex;
    }

    return rowIndex + 1;
}

export type TableColumnsState = {
    columns: TableColumnDef[];
    isVisible: (key: string) => boolean;
    toggle: (key: string) => void;
    setVisible: (key: string, visible: boolean) => void;
    visibleCount: ReturnType<typeof computed<number>>;
};

const TABLE_COLUMNS_KEY: InjectionKey<TableColumnsState> = Symbol('tableColumns');

function readHidden(tableId: string): string[] {
    if (typeof window === 'undefined') {
        return [];
    }

    try {
        const raw = window.localStorage.getItem(`table-cols:${tableId}`);
        const parsed = raw ? JSON.parse(raw) : [];

        return Array.isArray(parsed) ? parsed.filter((key) => typeof key === 'string') : [];
    } catch {
        return [];
    }
}

function writeHidden(tableId: string, hidden: string[]): void {
    if (typeof window === 'undefined') {
        return;
    }

    window.localStorage.setItem(`table-cols:${tableId}`, JSON.stringify(hidden));
}

export function provideTableColumns(
    tableId: string,
    columns: MaybeRefOrGetter<TableColumnDef[]>,
): TableColumnsState {
    const hidden = ref(new Set(readHidden(tableId)));

    const resolved = computed(() => toValue(columns));

    function isVisible(key: string): boolean {
        const column = resolved.value.find((item) => item.key === key);

        if (column?.locked) {
            return true;
        }

        return !hidden.value.has(key);
    }

    function setVisible(key: string, visible: boolean): void {
        const column = resolved.value.find((item) => item.key === key);

        if (!column || column.locked) {
            return;
        }

        const next = new Set(hidden.value);

        if (visible) {
            next.delete(key);
        } else {
            next.add(key);
        }

        hidden.value = next;
        writeHidden(tableId, [...next]);
    }

    function toggle(key: string): void {
        setVisible(key, !isVisible(key));
    }

    const visibleCount = computed(
        () => resolved.value.filter((column) => isVisible(column.key)).length,
    );

    const state: TableColumnsState = {
        get columns() {
            return resolved.value;
        },
        isVisible,
        toggle,
        setVisible,
        visibleCount,
    };

    provide(TABLE_COLUMNS_KEY, state);

    return state;
}

export function useProvidedTableColumns(): TableColumnsState | null {
    return inject(TABLE_COLUMNS_KEY, null);
}
