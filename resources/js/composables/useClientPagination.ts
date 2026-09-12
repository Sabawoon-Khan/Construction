import {
    computed,
    reactive,
    ref,
    watch,
    type MaybeRefOrGetter,
    toValue,
} from 'vue';

export function useClientPagination<T>(
    rows: MaybeRefOrGetter<T[]>,
    perPage = 10,
) {
    const currentPage = ref(1);

    const total = computed(() => toValue(rows).length);
    const totalPages = computed(() =>
        Math.max(1, Math.ceil(total.value / perPage)),
    );

    const pageRows = computed(() => {
        const start = (currentPage.value - 1) * perPage;
        return toValue(rows).slice(start, start + perPage);
    });

    const from = computed(() =>
        total.value ? (currentPage.value - 1) * perPage + 1 : 0,
    );

    const to = computed(() =>
        Math.min(currentPage.value * perPage, total.value),
    );

    const showControls = computed(() => total.value > perPage);

    watch(totalPages, (pages) => {
        if (currentPage.value > pages) {
            currentPage.value = pages;
        }
    });

    watch(
        () => toValue(rows),
        () => {
            currentPage.value = 1;
        },
    );

    return reactive({
        currentPage,
        perPage,
        total,
        totalPages,
        pageRows,
        from,
        to,
        showControls,
    });
}
