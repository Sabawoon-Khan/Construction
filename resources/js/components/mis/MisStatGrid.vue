<script setup lang="ts">
import { cn } from '@/lib/utils';

withDefaults(
    defineProps<{
        columns?: 2 | 3 | 4;
        class?: string;
    }>(),
    {
        columns: 3,
        class: undefined,
    },
);
</script>

<template>
    <div
        :class="
            cn(
                'grid gap-3',
                columns === 2 && 'grid-cols-1 sm:grid-cols-2',
                columns === 3 && 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-3',
                columns === 4 && 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-4',
                $props.class,
            )
        "
    >
        <slot />
    </div>
</template>
