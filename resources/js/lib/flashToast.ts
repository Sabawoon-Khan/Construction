import { router } from '@inertiajs/vue3';
import { toast } from 'vue-sonner';
import {
    consumePendingTrashUndo,
    toastMovedToTrash,
} from '@/lib/trashUndoToast';
import type { FlashToast } from '@/types/ui';

const recentlyShown = new Set<string>();

function showOnce(key: string, show: () => void): void {
    if (recentlyShown.has(key)) {
        return;
    }

    recentlyShown.add(key);
    show();
    window.setTimeout(() => recentlyShown.delete(key), 1500);
}

function handleFlashPayload(flash: {
    success?: string | null;
    error?: string | null;
    toast?: FlashToast;
    trash?: { type?: string; id?: string | number; message?: string } | null;
}): void {
    const pendingTrash = consumePendingTrashUndo();
    const flashTrash =
        flash?.trash && flash.trash.type != null && flash.trash.id != null
            ? {
                  type: String(flash.trash.type),
                  id: flash.trash.id,
                  message: flash.trash.message,
              }
            : null;
    const trashMeta = pendingTrash ?? flashTrash;

    if (trashMeta) {
        const message =
            (flash?.toast?.type === 'success' && flash.toast.message) ||
            (typeof flash?.success === 'string' && flash.success) ||
            trashMeta.message ||
            'Moved to trash.';

        showOnce(`trash:${trashMeta.type}:${trashMeta.id}:${message}`, () => {
            toastMovedToTrash({
                type: trashMeta.type,
                id: trashMeta.id,
                message,
            });
        });

        // Prevent the paired flash/success listeners from also showing a plain toast.
        recentlyShown.add(`success:${message}`);
        recentlyShown.add(`toast:success:${message}`);

        if (typeof flash?.error === 'string' && flash.error) {
            showOnce(`error:${flash.error}`, () => {
                toast.error(flash.error as string);
            });
        }

        return;
    }

    const data = flash?.toast;

    if (data?.message) {
        showOnce(`toast:${data.type}:${data.message}`, () => {
            toast[data.type](data.message);
        });
    }

    if (typeof flash?.success === 'string' && flash.success) {
        showOnce(`success:${flash.success}`, () => {
            toast.success(flash.success as string);
        });
    }

    if (typeof flash?.error === 'string' && flash.error) {
        showOnce(`error:${flash.error}`, () => {
            toast.error(flash.error as string);
        });
    }
}

export function initializeFlashToast(): void {
    router.on('flash', (event) => {
        const flash = (event as CustomEvent).detail?.flash;
        if (flash) {
            handleFlashPayload(flash);
        }
    });

    router.on('success', (event) => {
        const page = (event as CustomEvent).detail?.page;
        const flash = page?.props?.flash as
            | {
                  success?: string | null;
                  error?: string | null;
                  trash?: { type?: string; id?: string | number } | null;
              }
            | undefined;

        if (flash) {
            handleFlashPayload(flash);
        }
    });
}
