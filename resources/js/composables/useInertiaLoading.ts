import { onMounted, onUnmounted, ref } from 'vue';
import { router } from '@inertiajs/vue3';

/**
 * Tracks Inertia visit start/finish for list loading UI.
 */
export function useInertiaLoading() {
    const loading = ref(false);
    let removeStart: (() => void) | undefined;
    let removeFinish: (() => void) | undefined;

    onMounted(() => {
        removeStart = router.on('start', (event) => {
            if (event.detail.visit.prefetch) {
                return;
            }
            loading.value = true;
        });
        removeFinish = router.on('finish', () => {
            loading.value = false;
        });
    });

    onUnmounted(() => {
        removeStart?.();
        removeFinish?.();
    });

    return { loading };
}
