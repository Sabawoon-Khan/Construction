import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { resolveCurrency } from '@/lib/utils';

export function useCurrency() {
    const page = usePage();
    const baseCurrency = computed(() =>
        resolveCurrency((page.props.company?.base_currency as string) ?? 'USD'),
    );

    return {
        baseCurrency,
    };
}
