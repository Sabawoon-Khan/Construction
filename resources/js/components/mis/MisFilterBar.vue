<script setup lang="ts">
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import { useTranslations } from '@/composables/useTranslations';
import { cn } from '@/lib/utils';

const props = withDefaults(
    defineProps<{
        applyLabel?: string;
        clearLabel?: string;
        showActions?: boolean;
        /** `card` = bordered panel; `plain` = no chrome (inline toolbar). */
        variant?: 'card' | 'plain';
        class?: string;
    }>(),
    {
        showActions: true,
        variant: 'card',
        class: undefined,
    },
);

const { t } = useTranslations();

const resolvedApplyLabel = computed(() => props.applyLabel ?? t('Apply'));
const resolvedClearLabel = computed(() => props.clearLabel ?? t('Clear'));

const emit = defineEmits<{
    apply: [];
    clear: [];
}>();
</script>

<template>
    <form
        :class="
            cn(
                variant === 'card' &&
                    'rounded-2xl border border-border/80 bg-card p-4 shadow-soft',
                variant === 'plain' && 'space-y-3',
                $props.class,
            )
        "
        @submit.prevent="emit('apply')"
    >
        <div class="flex flex-wrap items-end gap-3">
            <slot />
            <div
                v-if="showActions || $slots.actions"
                class="flex flex-wrap items-center gap-2 pb-px"
            >
                <slot name="actions">
                    <Button type="submit" size="sm">
                        {{ resolvedApplyLabel }}
                    </Button>
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        @click="emit('clear')"
                    >
                        {{ resolvedClearLabel }}
                    </Button>
                </slot>
            </div>
        </div>
        <div v-if="$slots.advanced" class="mt-3">
            <slot name="advanced" />
        </div>
    </form>
</template>
