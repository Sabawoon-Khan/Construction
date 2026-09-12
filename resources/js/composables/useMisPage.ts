import { Eye, Pencil, Trash2 } from '@lucide/vue';
import { useCalendar } from '@/composables/useCalendar';
import { usePermissions } from '@/composables/usePermissions';
import { useTranslations } from '@/composables/useTranslations';
import type { RowActionItem } from '@/lib/row-actions';

export function useMisPage() {
    const { t } = useTranslations();
    const { can } = usePermissions();
    const calendar = useCalendar();

    const formatDate = (
        value: Date | string | null | undefined,
        fallback = '—',
    ): string => calendar.format(value) || fallback;

    const formatDateTime = (
        value: Date | string | null | undefined,
        fallback = '—',
    ): string => calendar.formatDateTime(value) || fallback;

    const gated = (
        action: RowActionItem,
        permission?: string,
    ): RowActionItem => ({
        ...action,
        hidden: action.hidden || (permission ? !can(permission) : false),
    });

    const viewAction = (href: string): RowActionItem => ({
        label: t('View'),
        icon: Eye,
        href,
    });

    const editAction = (href: string, permission?: string): RowActionItem =>
        gated(
            {
                label: t('Edit'),
                icon: Pencil,
                href,
            },
            permission,
        );

    const deleteAction = (
        options: {
            href: string;
            title: string;
            description: string;
            method?: 'delete' | 'post' | 'put';
            trashType?: string;
            trashId?: string | number;
        },
        permission?: string,
    ): RowActionItem =>
        gated(
            {
                label: t('Move to trash'),
                icon: Trash2,
                variant: 'destructive',
                separator: true,
                href: options.href,
                method: options.method ?? 'delete',
                trashType: options.trashType,
                trashId:
                    options.trashId ??
                    options.href.split('/').filter(Boolean).pop(),
                confirm: {
                    title: options.title,
                    description: options.description,
                    confirmLabel: t('Move to trash'),
                },
            },
            permission,
        );

    const gateActions = (
        actions: RowActionItem[],
        permission: string,
    ): RowActionItem[] => actions.map((action) => gated(action, permission));

    return {
        t,
        can,
        formatDate,
        formatDateTime,
        viewAction,
        editAction,
        deleteAction,
        gateActions,
    };
}
