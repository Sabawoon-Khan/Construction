<script setup lang="ts">
import type { Component } from 'vue';
import MisChart from '@/components/mis/MisChart.vue';
import type { MisChartDataset } from '@/components/mis/MisChart.vue';
import { cn } from '@/lib/utils';

withDefaults(
    defineProps<{
        title: string;
        type: 'bar' | 'line' | 'pie';
        labels: string[];
        datasets: MisChartDataset[];
        icon?: Component;
        description?: string;
        class?: string;
    }>(),
    {
        icon: undefined,
        description: undefined,
        class: undefined,
    },
);
</script>

<template>
    <div
        :class="
            cn(
                'rounded-2xl border border-border/70 bg-card p-5 shadow-soft',
                $props.class,
            )
        "
    >
        <div class="mb-4 flex items-start justify-between gap-3">
            <div class="min-w-0 space-y-1">
                <div class="flex items-center gap-2">
                    <component
                        :is="icon"
                        v-if="icon"
                        class="size-4 shrink-0 text-muted-foreground"
                    />
                    <h3 class="text-sm font-semibold tracking-tight">
                        {{ title }}
                    </h3>
                </div>
                <p
                    v-if="description"
                    class="text-xs text-muted-foreground"
                >
                    {{ description }}
                </p>
            </div>
            <div v-if="$slots.actions" class="shrink-0">
                <slot name="actions" />
            </div>
        </div>
        <MisChart :type="type" :labels="labels" :datasets="datasets" />
        <div v-if="$slots.footer" class="mt-4 border-t border-border/60 pt-3">
            <slot name="footer" />
        </div>
    </div>
</template>
