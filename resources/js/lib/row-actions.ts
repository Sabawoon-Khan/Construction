import type { Component } from 'vue';

export interface RowActionItem {
    label: string;
    icon?: Component;
    href?: string;
    method?: 'delete' | 'post' | 'put' | 'patch';
    data?: Record<string, unknown>;
    onClick?: () => void;
    variant?: 'default' | 'destructive';
    disabled?: boolean;
    hidden?: boolean;
    confirm?: {
        title: string;
        description: string;
        confirmLabel?: string;
    };
    confirmVariant?: 'destructive' | 'default';
    separator?: boolean;
    /** Use a native browser navigation for file downloads instead of Inertia. */
    download?: boolean;
    /** Render as a non-interactive dropdown label instead of an action item. */
    isLabel?: boolean;
    /** Soft-delete registry key for Undo toast after a successful delete. */
    trashType?: string;
    /** Soft-deleted record id (defaults to last path segment of href). */
    trashId?: string | number;
}
