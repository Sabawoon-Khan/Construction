<script setup lang="ts">
import { cn } from '@/lib/utils';

withDefaults(
    defineProps<{
        class?: string;
    }>(),
    {
        class: undefined,
    },
);
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
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-muted/30 text-left text-muted-foreground">
                    <slot name="head" />
                </thead>
                <tbody>
                    <slot />
                </tbody>
            </table>
        </div>
        <slot name="footer" />
    </div>
</template>
