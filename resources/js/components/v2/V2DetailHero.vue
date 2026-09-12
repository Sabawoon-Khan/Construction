<script setup lang="ts">
withDefaults(
    defineProps<{
        image?: string;
        alt?: string;
        compact?: boolean;
    }>(),
    {
        image: '/images/dashboard-v2-building.png',
        alt: '',
        compact: false,
    },
);
</script>

<template>
    <section class="hero detail-hero reveal" :class="{ compact }">
        <img class="hero-image" :src="image" :alt="alt" />
        <div class="hero-fade" />
        <div class="hero-body detail-hero-body">
            <div class="hero-copy">
                <slot name="copy">
                    <p v-if="$slots.eyebrow" class="eyebrow">
                        <slot name="eyebrow" />
                    </p>
                    <h1 v-if="$slots.title"><slot name="title" /></h1>
                    <p v-if="$slots.description">
                        <slot name="description" />
                    </p>
                </slot>
            </div>
            <div v-if="$slots.side || $slots.actions" class="hero-side detail-hero-side">
                <div v-if="$slots.actions" class="detail-actions">
                    <slot name="actions" />
                </div>
                <slot name="side" />
            </div>
        </div>
        <slot name="stats" />
    </section>
</template>
