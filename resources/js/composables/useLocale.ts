import { router, usePage } from '@inertiajs/vue3';
import type { ComputedRef } from 'vue';
import { computed, ref } from 'vue';
import type { LocaleCode, LocaleOption, TextDirection } from '@/types/locale';

/** Keeps document `dir` and Vue layout (e.g. sidebar side) in sync during locale POST. */
const pendingLocale = ref<{
    code: LocaleCode;
    dir: TextDirection;
} | null>(null);

const setCookie = (name: string, value: string, days = 365): void => {
    if (typeof document === 'undefined') {
        return;
    }

    const maxAge = days * 24 * 60 * 60;

    document.cookie = `${name}=${value};path=/;max-age=${maxAge};SameSite=Lax`;
};

export function applyDocumentLocale(
    locale: LocaleCode,
    dir: TextDirection,
): void {
    if (typeof document === 'undefined') {
        return;
    }

    document.documentElement.lang = locale;
    document.documentElement.dir = dir;
}

export function initializeLocale(): void {
    if (typeof document === 'undefined') {
        return;
    }

    applyDocumentLocale(
        document.documentElement.lang as LocaleCode,
        (document.documentElement.dir || 'ltr') as TextDirection,
    );
}

export type UseLocaleReturn = {
    locale: ComputedRef<LocaleCode>;
    dir: ComputedRef<TextDirection>;
    locales: ComputedRef<LocaleOption[]>;
    sidebarSide: ComputedRef<'left' | 'right'>;
    updateLocale: (locale: LocaleCode) => void;
};

export function useLocale(): UseLocaleReturn {
    const page = usePage();

    const locale = computed(
        () => pendingLocale.value?.code ?? (page.props.locale as LocaleCode),
    );
    const dir = computed(
        () => pendingLocale.value?.dir ?? (page.props.dir as TextDirection),
    );
    const locales = computed(() => page.props.locales as LocaleOption[]);
    const sidebarSide = computed(() =>
        dir.value === 'rtl' ? 'right' : 'left',
    );

    function updateLocale(value: LocaleCode): void {
        const nextLocale = locales.value.find((item) => item.code === value);

        if (nextLocale) {
            pendingLocale.value = { code: value, dir: nextLocale.dir };
            applyDocumentLocale(value, nextLocale.dir);
        }

        setCookie('locale', value);

        router.post(`/locale/${value}`, {}, {
            preserveScroll: true,
            onFinish: () => {
                pendingLocale.value = null;
            },
        });
    }

    return {
        locale,
        dir,
        locales,
        sidebarSide,
        updateLocale,
    };
}
