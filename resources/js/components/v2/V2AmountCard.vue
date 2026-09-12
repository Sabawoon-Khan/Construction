<script setup lang="ts">
withDefaults(
    defineProps<{
        label?: string;
        tone?: 'default' | 'rose' | 'emerald' | 'warm';
    }>(),
    {
        label: undefined,
        tone: 'default',
    },
);
</script>

<template>
    <article class="v2-amount-card reveal" :class="tone">
        <span v-if="label || $slots.label" class="v2-amount-label">
            <slot name="label">{{ label }}</slot>
        </span>
        <strong class="v2-amount-value">
            <slot />
        </strong>
        <p v-if="$slots.meta" class="v2-amount-meta">
            <slot name="meta" />
        </p>
    </article>
</template>
