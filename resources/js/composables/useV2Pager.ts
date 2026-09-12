import { computed, toValue, type MaybeRefOrGetter } from 'vue';

export type V2PagerLink = {
    url: string | null;
    label: string;
    active: boolean;
};

export type V2PagerItems = {
    data: unknown[];
    links?: V2PagerLink[];
    meta?: {
        from?: number | null;
        to?: number | null;
        total?: number;
        current_page?: number;
        last_page?: number;
        links?: V2PagerLink[];
    };
    from?: number | null;
    to?: number | null;
    total?: number;
    current_page?: number;
    last_page?: number;
};

export function pageLabel(label: string): string {
    const plain = label.replace(/&laquo;|&raquo;|<[^>]+>/g, '').trim();
    if (/^\d+$/.test(plain)) return plain;
    if (plain === '...') return '…';
    return plain;
}

export function useV2Pager(items: MaybeRefOrGetter<V2PagerItems | null | undefined>) {
    const pageMeta = computed(() => {
        const resolved = toValue(items) ?? { data: [] as unknown[] };
        const meta = resolved.meta ?? {};
        return {
            from: meta.from ?? resolved.from ?? 0,
            to: meta.to ?? resolved.to ?? 0,
            total: meta.total ?? resolved.total ?? resolved.data?.length ?? 0,
            current: meta.current_page ?? resolved.current_page ?? 1,
            last: meta.last_page ?? resolved.last_page ?? 1,
            links: resolved.links ?? meta.links ?? [],
        };
    });

    const pageNav = computed(() => {
        const links = pageMeta.value.links.filter((link) => link.label);
        if (links.length < 2) {
            return { prev: null, next: null, pages: [] as V2PagerLink[] };
        }
        return {
            prev: links[0] ?? null,
            next: links[links.length - 1] ?? null,
            pages: links.slice(1, -1),
        };
    });

    const showPager = computed(
        () => pageMeta.value.last > 1 || pageMeta.value.links.length > 3,
    );

    return { pageMeta, pageNav, showPager, pageLabel };
}
