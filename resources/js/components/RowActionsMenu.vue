<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import { MoreHorizontal } from '@lucide/vue';
import { computed, ref } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { useTranslations } from '@/composables/useTranslations';
import type { RowActionItem } from '@/lib/row-actions';

const props = withDefaults(
    defineProps<{
        actions: RowActionItem[];
        align?: 'start' | 'center' | 'end';
    }>(),
    { align: 'end' },
);

const { t } = useTranslations();

const visibleActions = computed(() =>
    props.actions.filter((action) => !action.hidden),
);

const pendingAction = ref<RowActionItem | null>(null);
const processing = ref(false);

const isLinkAction = (action: RowActionItem): boolean =>
    Boolean(
        action.href &&
            !action.method &&
            !action.onClick &&
            !action.confirm &&
            !action.download,
    );

const isDownloadAction = (action: RowActionItem): boolean =>
    Boolean(action.href && action.download);

function handleClick(action: RowActionItem): void {
    if (action.confirm) {
        pendingAction.value = action;

        return;
    }

    runAction(action);
}

function runAction(action: RowActionItem): void {
    if (action.onClick) {
        action.onClick();
        pendingAction.value = null;

        return;
    }

    if (!action.href || !action.method) {
        return;
    }

    processing.value = true;

    const payload = action.data ?? {};

    const options = {
        preserveScroll: true,
        onFinish: () => {
            processing.value = false;
            pendingAction.value = null;
        },
    };

    switch (action.method) {
        case 'delete':
            router.delete(action.href, { ...options, data: payload });
            break;
        case 'post':
            router.post(action.href, payload, options);
            break;
        case 'put':
            router.put(action.href, payload, options);
            break;
        case 'patch':
            router.patch(action.href, payload, options);
            break;
    }
}

function confirmPendingAction(): void {
    if (pendingAction.value) {
        runAction(pendingAction.value);
    }
}
</script>

<template>
    <DropdownMenu v-if="visibleActions.length > 0">
        <DropdownMenuTrigger as-child>
            <Button variant="ghost" size="icon" class="size-8">
                <MoreHorizontal class="size-4" />
                <span class="sr-only">{{ t('Actions') }}</span>
            </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent :align="align" class="w-48">
            <template
                v-for="(action, index) in visibleActions"
                :key="`${action.label}-${index}`"
            >
                <DropdownMenuSeparator
                    v-if="action.separator && index > 0"
                />
                <DropdownMenuLabel v-if="action.isLabel">
                    {{ action.label }}
                </DropdownMenuLabel>
                <DropdownMenuItem
                    v-else-if="isDownloadAction(action)"
                    :variant="action.variant"
                    :disabled="action.disabled"
                    as-child
                >
                    <a v-if="action.href" :href="action.href">
                        <component :is="action.icon" v-if="action.icon" />
                        {{ action.label }}
                    </a>
                </DropdownMenuItem>
                <DropdownMenuItem
                    v-else-if="isLinkAction(action)"
                    :variant="action.variant"
                    :disabled="action.disabled"
                    as-child
                >
                    <Link v-if="action.href" :href="action.href">
                        <component :is="action.icon" v-if="action.icon" />
                        {{ action.label }}
                    </Link>
                </DropdownMenuItem>
                <DropdownMenuItem
                    v-else
                    :variant="action.variant"
                    :disabled="action.disabled"
                    @click="handleClick(action)"
                >
                    <component :is="action.icon" v-if="action.icon" />
                    {{ action.label }}
                </DropdownMenuItem>
            </template>
        </DropdownMenuContent>
    </DropdownMenu>

    <Dialog
        :open="pendingAction !== null"
        @update:open="(open) => !open && (pendingAction = null)"
    >
        <DialogContent v-if="pendingAction?.confirm">
            <DialogHeader>
                <DialogTitle>{{ pendingAction.confirm.title }}</DialogTitle>
                <DialogDescription>
                    {{ pendingAction.confirm.description }}
                </DialogDescription>
            </DialogHeader>
            <DialogFooter class="gap-2">
                <DialogClose as-child>
                    <Button variant="secondary" :disabled="processing">
                        {{ t('Cancel') }}
                    </Button>
                </DialogClose>
                <Button
                    :variant="pendingAction.confirmVariant ?? 'destructive'"
                    :disabled="processing"
                    @click="confirmPendingAction"
                >
                    {{
                        processing
                            ? t('Processing...')
                            : (pendingAction.confirm.confirmLabel ?? t('Confirm'))
                    }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
