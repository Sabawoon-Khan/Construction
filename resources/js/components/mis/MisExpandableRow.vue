<script setup lang="ts">
import { ChevronDown, ChevronUp } from '@lucide/vue';
import { cn } from '@/lib/utils';

const open = defineModel<boolean>('open', { default: false });

withDefaults(
    defineProps<{
        class?: string;
    }>(),
    {
        class: undefined,
    },
);

function toggle(): void {
    open.value = !open.value;
}
</script>

<template>
    <div
        :class="
            cn(
                'overflow-hidden rounded-2xl border border-border/80 bg-card shadow-soft',
                $props.class,
            )
        "
    >
        <button
            type="button"
            class="flex w-full items-start gap-3 px-4 py-3.5 text-start transition hover:bg-muted/30"
            :aria-expanded="open"
            @click="toggle"
        >
            <div class="min-w-0 flex-1">
                <slot />
            </div>
            <component
                :is="open ? ChevronUp : ChevronDown"
                class="mt-0.5 size-4 shrink-0 text-muted-foreground"
            />
        </button>
        <div
            v-if="open"
            class="border-t border-border/70 bg-muted/20 px-4 py-3"
        >
            <slot name="detail" />
        </div>
    </div>
</template>
