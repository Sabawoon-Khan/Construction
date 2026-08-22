import {
    computed,
    inject,
    provide,
    reactive,
    ref,
    toValue,
    type ComputedRef,
    type InjectionKey,
    type MaybeRefOrGetter,
    type Ref,
    type UnwrapNestedRefs,
} from 'vue';

export type SortDir = 'asc' | 'desc';

export type TableSortState<T extends Record<string, unknown> = Record<string, unknown>> = {
    sortedRows: Ref<T[]>;
    sortKey: Ref<string>;
    sortDir: Ref<SortDir>;
    sortBy: (key: string) => void;
};

/** Reactive view of table sort state — nested refs auto-unwrap in templates (SSR-safe). */
export type ReactiveTableSortState<T extends Record<string, unknown> = Record<string, unknown>> =
    UnwrapNestedRefs<{
        sortedRows: ComputedRef<T[]>;
        sortKey: Ref<string>;
        sortDir: Ref<SortDir>;
        sortBy: (key: string) => void;
    }>;

const TABLE_SORT_KEY: InjectionKey<TableSortState> = Symbol('tableSort');

function getByPath(row: Record<string, unknown>, path: string): unknown {
    return path.split('.').reduce<unknown>((acc, key) => {
        if (acc && typeof acc === 'object' && key in acc) {
            return (acc as Record<string, unknown>)[key];
        }

        return undefined;
    }, row);
}

function compareValues(a: unknown, b: unknown): number {
    if (a == null && b == null) {
        return 0;
    }
    if (a == null) {
        return 1;
    }
    if (b == null) {
        return -1;
    }

    if (typeof a === 'number' && typeof b === 'number') {
        return a - b;
    }

    if (typeof a === 'boolean' && typeof b === 'boolean') {
        return Number(a) - Number(b);
    }

    const as = String(a);
    const bs = String(b);
    const aTime = Date.parse(as);
    const bTime = Date.parse(bs);

    if (!Number.isNaN(aTime) && !Number.isNaN(bTime) && /[-T:]/.test(as) && /[-T:]/.test(bs)) {
        return aTime - bTime;
    }

    return as.localeCompare(bs, undefined, { numeric: true, sensitivity: 'base' });
}

function createTableSortState<T extends Record<string, unknown>>(
    rows: MaybeRefOrGetter<T[]>,
    options: { defaultKey?: string; defaultDir?: SortDir } = {},
): TableSortState<T> {
    const sortKey = ref(options.defaultKey ?? 'id');
    const sortDir = ref<SortDir>(options.defaultDir ?? 'desc');

    const sortedRows = computed(() => {
        const list = [...toValue(rows)];
        const key = sortKey.value;
        const dir = sortDir.value === 'asc' ? 1 : -1;

        list.sort((left, right) => dir * compareValues(getByPath(left, key), getByPath(right, key)));

        return list;
    });

    function sortBy(key: string): void {
        if (sortKey.value === key) {
            sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc';
            return;
        }

        sortKey.value = key;
        sortDir.value = 'asc';
    }

    return {
        sortedRows,
        sortKey,
        sortDir,
        sortBy,
    };
}

export function useTableSort<T extends Record<string, unknown>>(
    rows: MaybeRefOrGetter<T[]>,
    options: { defaultKey?: string; defaultDir?: SortDir } = {},
): ReactiveTableSortState<T> {
    return reactive(createTableSortState(rows, options)) as ReactiveTableSortState<T>;
}

export function provideTableSort<T extends Record<string, unknown>>(
    rows: MaybeRefOrGetter<T[]>,
    options: { defaultKey?: string; defaultDir?: SortDir } = {},
): TableSortState<T> {
    const state = createTableSortState(rows, options);
    provide(TABLE_SORT_KEY, state as TableSortState);

    return state;
}

export function useProvidedTableSort(): TableSortState | null {
    return inject(TABLE_SORT_KEY, null);
}
