<script setup lang="ts">
import type { Component } from 'vue';
import { computed } from 'vue';
import MisMoneyStack from '@/components/mis/MisMoneyStack.vue';
import { cn } from '@/lib/utils';

const props = withDefaults(
    defineProps<{
        label: string;
        /** Optional short hint under the label */
        hint?: string;
        value?: string | number;
        /** Dual-currency map — when set (or fallbackAmount is set), value is ignored. */
        amounts?: Record<string, number> | null;
        fallbackAmount?: unknown;
        icon?: Component;
        /** Visual tone for KPI emphasis. */
        tone?: 'default' | 'muted' | 'success' | 'warning' | 'danger';
        /** Hide the main value line when using the default slot for custom content. */
        showValue?: boolean;
        class?: string;
    }>(),
    {
        hint: undefined,
        icon: undefined,
        value: '',
        amounts: undefined,
        fallbackAmount: undefined,
        tone: 'default',
        showValue: true,
        class: undefined,
    },
);

const shellClass: Record<string, string> = {
    default:
        'border-white/55 bg-white/40 backdrop-blur-xl shadow-soft dark:border-white/10 dark:bg-white/5',
    muted: 'border-transparent bg-white/25 backdrop-blur-md dark:bg-white/5',
    success:
        'border-emerald-200/50 bg-emerald-50/55 backdrop-blur-xl dark:border-emerald-900/50 dark:bg-emerald-950/30',
    warning:
        'border-amber-200/50 bg-amber-50/55 backdrop-blur-xl dark:border-amber-900/50 dark:bg-amber-950/25',
    danger:
        'border-rose-200/50 bg-rose-50/50 backdrop-blur-xl dark:border-rose-900/50 dark:bg-rose-950/25',
};

const iconWrapClass: Record<string, string> = {
    default: 'bg-muted text-muted-foreground',
    muted: 'bg-background/70 text-muted-foreground',
    success: 'bg-emerald-500/15 text-emerald-700 dark:text-emerald-300',
    warning: 'bg-amber-500/15 text-amber-700 dark:text-amber-300',
    danger: 'bg-rose-500/15 text-rose-700 dark:text-rose-300',
};

const labelClass: Record<string, string> = {
    default: 'text-muted-foreground',
    muted: 'text-muted-foreground',
    success: 'text-emerald-800 dark:text-emerald-300',
    warning: 'text-amber-800 dark:text-amber-300',
    danger: 'text-rose-800 dark:text-rose-300',
};

const valueClass: Record<string, string> = {
    default: 'text-foreground',
    muted: 'text-foreground',
    success: 'text-emerald-900 dark:text-emerald-100',
    warning: 'text-amber-900 dark:text-amber-100',
    danger: 'text-rose-900 dark:text-rose-100',
};

const showCurrencyStack = computed(
    () => props.amounts !== undefined || props.fallbackAmount !== undefined,
);
</script>

<template>
    <div
        :class="
            cn(
                'rounded-2xl border p-4 shadow-soft sm:p-5',
                shellClass[tone],
                $props.class,
            )
        "
    >
        <div class="flex items-start justify-between gap-3">
            <div class="min-w-0 space-y-0.5">
                <div
                    :class="
                        cn('text-sm font-medium', labelClass[tone])
                    "
                >
                    {{ label }}
                </div>
                <p
                    v-if="hint"
                    class="text-xs text-muted-foreground"
                >
                    {{ hint }}
                </p>
            </div>
            <div
                v-if="icon || $slots.trailing"
                class="flex shrink-0 items-center gap-2"
            >
                <div
                    v-if="icon"
                    :class="
                        cn(
                            'flex size-8 items-center justify-center rounded-xl',
                            iconWrapClass[tone],
                        )
                    "
                >
                    <component :is="icon" class="size-4" />
                </div>
                <slot name="trailing" />
            </div>
        </div>

        <div :class="cn('mt-4', valueClass[tone])">
            <MisMoneyStack
                v-if="showValue && showCurrencyStack"
                :amounts="amounts"
                :fallback="fallbackAmount"
            />
            <p
                v-else-if="showValue"
                class="text-2xl font-semibold tracking-tight tabular-nums"
            >
                {{ value }}
            </p>
            <slot />
        </div>
    </div>
</template>
