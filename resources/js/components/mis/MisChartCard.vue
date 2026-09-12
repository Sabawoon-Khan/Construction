<script setup lang="ts">
import type { Component } from 'vue';
import MisChart from '@/components/mis/MisChart.vue';
import type { MisChartDataset } from '@/components/mis/MisChart.vue';
import { cn } from '@/lib/utils';

withDefaults(
    defineProps<{
        title: string;
        description?: string;
        type: 'bar' | 'line' | 'pie';
        labels: string[];
        datasets: MisChartDataset[];
        orientation?: 'vertical' | 'horizontal';
        showValues?: boolean;
        pageSize?: number;
        icon?: Component;
        class?: string;
    }>(),
    {
        description: undefined,
        orientation: 'vertical',
        showValues: true,
        pageSize: undefined,
        icon: undefined,
        class: undefined,
    },
);
</script>

<template>
    <div
        :class="
            cn(
                'rounded-2xl border border-white/70 bg-white/70 p-5 shadow-soft backdrop-blur-xl dark:border-white/10 dark:bg-white/5',
                $props.class,
            )
        "
    >
        <div class="mb-4 flex items-start justify-between gap-3">
            <div class="min-w-0 space-y-1">
                <div class="flex items-center gap-2.5">
                    <span
                        v-if="icon"
                        class="inline-flex size-8 shrink-0 items-center justify-center rounded-xl bg-foreground/5 text-foreground/70"
                    >
                        <component :is="icon" class="size-4" />
                    </span>
                    <h3 class="text-[15px] font-semibold tracking-tight text-foreground">
                        {{ title }}
                    </h3>
                </div>
                <p
                    v-if="description"
                    class="text-xs leading-relaxed text-muted-foreground"
                >
                    {{ description }}
                </p>
            </div>
            <div v-if="$slots.actions" class="shrink-0">
                <slot name="actions" />
            </div>
        </div>
        <MisChart
            :type="type"
            :labels="labels"
            :datasets="datasets"
            :orientation="orientation"
            :show-values="showValues"
            :page-size="pageSize"
        />
        <div v-if="$slots.footer" class="mt-4 border-t border-border/60 pt-3">
            <slot name="footer" />
        </div>
    </div>
</template>
