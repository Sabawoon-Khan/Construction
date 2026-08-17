import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

export function useCurrency() {
    const page = usePage();
    const baseCurrency = computed(() => (page.props.company?.base_currency as string) ?? 'USD');

    return {
        baseCurrency,
    };
}
