import { nextTick, onBeforeUnmount, ref } from 'vue';
import { printPage } from '@/lib/voucher';

/**
 * Isolate an A4 print sheet: hide the rest of the app while printing.
 */
export function useA4Print() {
    const sheetRef = ref<HTMLElement | null>(null);
    let onAfterPrint: (() => void) | null = null;

    function cleanup() {
        document.body.classList.remove('printing-a4');
        if (onAfterPrint) {
            window.removeEventListener('afterprint', onAfterPrint);
            onAfterPrint = null;
        }
    }

    async function printA4(prepare?: () => void | Promise<void>) {
        cleanup();
        await prepare?.();
        await nextTick();
        await nextTick();
        document.body.classList.add('printing-a4');
        const startedAt = Date.now();
        onAfterPrint = () => {
            // Chrome can fire afterprint when the dialog opens — keep the class.
            if (Date.now() - startedAt < 800) {
                return;
            }
            cleanup();
        };
        window.addEventListener('afterprint', onAfterPrint);
        window.setTimeout(cleanup, 60_000);
        printPage();
    }

    onBeforeUnmount(cleanup);

    return {
        sheetRef,
        printA4,
        cleanup,
    };
}
