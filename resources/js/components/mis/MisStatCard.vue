<script setup lang="ts">
import type { Component } from 'vue';
import { cn } from '@/lib/utils';

withDefaults(
    defineProps<{
        label: string;
        value?: string | number;
        icon?: Component;
        /** Visual tone for KPI emphasis. */
        tone?: 'default' | 'muted' | 'success' | 'warning' | 'danger';
        /** Hide the main value line when using the default slot for custom content. */
        showValue?: boolean;
        class?: string;
    }>(),
    {
        icon: undefined,
        value: '',
        tone: 'default',
        showValue: true,
        class: undefined,
    },
);

const toneClass: Record<string, string> = {
    default: 'border-border/80 bg-card shadow-soft',
    muted: 'border-transparent bg-muted/40',
    success:
        'border-emerald-500/25 bg-emerald-500/[0.06] dark:bg-emerald-500/10',
    warning: 'border-amber-500/30 bg-amber-500/[0.06] dark:bg-amber-500/10',
    danger: 'border-destructive/30 bg-destructive/[0.06] dark:bg-destructive/10',
};
</script>

<template>
    <div
        :class="
            cn(
                'rounded-2xl border px-4 py-3.5 transition-colors',
                toneClass[tone],
                $props.class,
            )
        "
    >
        <div class="flex items-center gap-2 text-muted-foreground">
            <component :is="icon" v-if="icon" class="size-4 shrink-0" />
            <span
                class="text-[11px] font-semibold tracking-[0.12em] uppercase"
            >
                {{ label }}
            </span>
            <div v-if="$slots.trailing" class="ms-auto">
                <slot name="trailing" />
            </div>
        </div>
        <p v-if="showValue" class="mt-2 text-2xl font-semibold tracking-tight tabular-nums">
            {{ value }}
        </p>
        <slot />
    </div>
</template>
