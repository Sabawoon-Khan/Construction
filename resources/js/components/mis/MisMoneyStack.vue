<script setup lang="ts">
import { computed } from 'vue';
import { cn, currencyAmountLines } from '@/lib/utils';

const props = withDefaults(
    defineProps<{
        /** Per-currency totals, e.g. `{ AFN: 100, USD: 20 }`. */
        amounts?: Record<string, number> | null;
        /** Used only when `amounts` is missing/null/undefined. */
        fallback?: unknown;
        /**
         * `stack` = AFN / USD rows (cards).
         * `inline` = compact `AFN · USD` (tables).
         */
        layout?: 'stack' | 'inline';
        class?: string;
    }>(),
    {
        amounts: undefined,
        fallback: undefined,
        layout: 'stack',
        class: undefined,
    },
);

const lines = computed(() =>
    currencyAmountLines(props.amounts, props.fallback).map((line) => ({
        ...line,
        amountLabel: new Intl.NumberFormat('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        }).format(line.value),
        isZero: line.value === 0,
        isNegative: line.value < 0,
    })),
);

const inlineText = computed(() =>
    lines.value.map((line) => `${line.code} ${line.amountLabel}`).join(' · '),
);
</script>

<template>
    <div
        v-if="layout === 'stack'"
        :class="cn('space-y-2.5', props.class)"
    >
        <div
            v-for="line in lines"
            :key="line.code"
            class="flex items-baseline justify-between gap-3"
        >
            <span
                class="inline-flex min-w-12 items-center justify-center rounded-md border border-border/60 bg-background/80 px-2 py-0.5 text-[11px] font-semibold tracking-wide text-foreground/75"
            >
                {{ line.code }}
            </span>
            <span
                class="text-right text-lg font-semibold tabular-nums tracking-tight sm:text-xl"
                :class="
                    line.isZero
                        ? 'text-muted-foreground/70'
                        : line.isNegative
                          ? 'text-rose-600 dark:text-rose-400'
                          : 'text-foreground'
                "
            >
                {{ line.amountLabel }}
            </span>
        </div>
    </div>
    <span
        v-else
        :class="cn('tabular-nums tracking-tight', props.class)"
    >
        {{ inlineText }}
    </span>
</template>
