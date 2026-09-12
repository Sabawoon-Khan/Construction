<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { useTranslations } from '@/composables/useTranslations';
import { cn } from '@/lib/utils';

const open = defineModel<boolean>('open', { required: true });

const props = withDefaults(
    defineProps<{
        title: string;
        description?: string;
        confirmLabel?: string;
        cancelLabel?: string;
        variant?: 'default' | 'destructive' | 'secondary' | 'outline';
        processing?: boolean;
        class?: HTMLAttributes['class'];
    }>(),
    {
        description: undefined,
        confirmLabel: undefined,
        cancelLabel: undefined,
        variant: 'destructive',
        processing: false,
        class: undefined,
    },
);

const emit = defineEmits<{
    confirm: [];
}>();

const { t } = useTranslations();

const resolvedConfirm = computed(
    () => props.confirmLabel ?? t('Confirm'),
);
const resolvedCancel = computed(() => props.cancelLabel ?? t('Cancel'));
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent :class="cn('sm:max-w-md', props.class)" :show-close-button="!processing">
            <DialogHeader>
                <DialogTitle>{{ title }}</DialogTitle>
                <DialogDescription v-if="description">
                    {{ description }}
                </DialogDescription>
            </DialogHeader>
            <DialogFooter class="gap-2">
                <Button
                    type="button"
                    variant="secondary"
                    class="flex-1 sm:flex-none"
                    :disabled="processing"
                    @click="open = false"
                >
                    {{ resolvedCancel }}
                </Button>
                <Button
                    type="button"
                    :variant="variant"
                    class="flex-1 sm:flex-none"
                    :disabled="processing"
                    @click="emit('confirm')"
                >
                    {{ processing ? t('Processing...') : resolvedConfirm }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
