import { router } from '@inertiajs/vue3';
import { toast } from 'vue-sonner';

export type TrashUndoOptions = {
    type: string;
    id: string | number;
    message?: string;
};

let pendingTrashUndo: TrashUndoOptions | null = null;

/** Mark the next flash success as a soft-delete so we can show Undo. */
export function expectTrashUndo(options: TrashUndoOptions): void {
    pendingTrashUndo = options;
}

export function consumePendingTrashUndo(): TrashUndoOptions | null {
    const next = pendingTrashUndo;
    pendingTrashUndo = null;
    return next;
}

export function toastMovedToTrash(options: TrashUndoOptions): void {
    toast.success(options.message ?? 'Moved to trash.', {
        action: {
            label: 'Undo',
            onClick: () => {
                router.post(
                    '/settings/trash/restore',
                    { type: options.type, id: options.id },
                    { preserveScroll: true },
                );
            },
        },
    });
}
