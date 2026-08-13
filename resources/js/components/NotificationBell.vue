<script setup lang="ts">
import {
    AlertCircle,
    AlertTriangle,
    Bell,
    CheckCircle2,
    Info,
    LoaderCircle,
} from '@lucide/vue';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { useNotifications } from '@/composables/useNotifications';
import { useTranslations } from '@/composables/useTranslations';
import type { AppNotification, NotificationType } from '@/types/notifications';

const typeStyles: Record<
    NotificationType,
    { icon: typeof Info; iconClass: string }
> = {
    info: {
        icon: Info,
        iconClass: 'text-blue-600 bg-blue-50 dark:bg-blue-950/50 dark:text-blue-400',
    },
    success: {
        icon: CheckCircle2,
        iconClass:
            'text-emerald-600 bg-emerald-50 dark:bg-emerald-950/50 dark:text-emerald-400',
    },
    warning: {
        icon: AlertTriangle,
        iconClass:
            'text-amber-600 bg-amber-50 dark:bg-amber-950/50 dark:text-amber-400',
    },
    error: {
        icon: AlertCircle,
        iconClass: 'text-red-600 bg-red-50 dark:bg-red-950/50 dark:text-red-400',
    },
};

const {
    items,
    loading,
    open,
    unreadCount,
    hasUnread,
    openPanel,
    closePanel,
    markAllAsRead,
    handleNotificationClick,
} = useNotifications();

const { t } = useTranslations();

function onOpenChange(isOpen: boolean): void {
    if (isOpen) {
        void openPanel();
    } else {
        closePanel();
    }
}

function notificationIcon(type: NotificationType) {
    return typeStyles[type]?.icon ?? Info;
}

function notificationIconClass(type: NotificationType) {
    return typeStyles[type]?.iconClass ?? typeStyles.info.iconClass;
}

function onClick(notification: AppNotification): void {
    void handleNotificationClick(notification);
}
</script>

<template>
    <DropdownMenu :open="open" @update:open="onOpenChange">
        <DropdownMenuTrigger as-child>
            <Button variant="ghost" size="icon" class="relative size-8">
                <Bell class="size-4" />
                <span
                    v-if="hasUnread"
                    class="absolute end-1 top-1 flex size-4 items-center justify-center rounded-full bg-primary text-[10px] text-primary-foreground"
                >
                    {{ unreadCount > 9 ? '9+' : unreadCount }}
                </span>
                <span class="sr-only">{{ t('Notifications') }}</span>
            </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end" class="w-80 p-0">
            <div class="flex items-center justify-between border-b px-4 py-3">
                <p class="text-sm font-medium">{{ t('Notifications') }}</p>
                <button
                    v-if="hasUnread"
                    type="button"
                    class="text-xs text-primary hover:underline"
                    @click="markAllAsRead"
                >
                    {{ t('Mark all as read') }}
                </button>
            </div>

            <div v-if="loading" class="flex justify-center py-8">
                <LoaderCircle class="size-5 animate-spin text-muted-foreground" />
            </div>

            <div v-else-if="items.length === 0" class="px-4 py-8 text-center text-sm text-muted-foreground">
                {{ t('No notifications') }}
            </div>

            <div v-else class="max-h-80 overflow-y-auto">
                <DropdownMenuItem
                    v-for="item in items"
                    :key="item.id"
                    class="flex cursor-pointer gap-3 rounded-none px-4 py-3"
                    @click="onClick(item)"
                >
                    <div
                        class="flex size-8 shrink-0 items-center justify-center rounded-full"
                        :class="notificationIconClass(item.type)"
                    >
                        <component :is="notificationIcon(item.type)" class="size-4" />
                    </div>
                    <div class="min-w-0 flex-1 text-start">
                        <p class="truncate text-sm font-medium" :class="{ 'opacity-60': item.read_at }">
                            {{ item.title }}
                        </p>
                        <p v-if="item.body" class="truncate text-xs text-muted-foreground">
                            {{ item.body }}
                        </p>
                    </div>
                </DropdownMenuItem>
            </div>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
