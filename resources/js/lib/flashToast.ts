import { router } from '@inertiajs/vue3';
import { toast } from 'vue-sonner';
import type { FlashToast } from '@/types/ui';

export function initializeFlashToast(): void {
    router.on('flash', (event) => {
        const flash = (event as CustomEvent).detail?.flash;
        const data = flash?.toast as FlashToast | undefined;

        if (data) {
            toast[data.type](data.message);
        }

        if (typeof flash?.success === 'string' && flash.success) {
            toast.success(flash.success);
        }

        if (typeof flash?.error === 'string' && flash.error) {
            toast.error(flash.error);
        }
    });

    router.on('success', (event) => {
        const page = (event as CustomEvent).detail?.page;
        const flash = page?.props?.flash as
            | { success?: string | null; error?: string | null }
            | undefined;

        if (flash?.success) {
            toast.success(flash.success);
        }

        if (flash?.error) {
            toast.error(flash.error);
        }
    });
}
