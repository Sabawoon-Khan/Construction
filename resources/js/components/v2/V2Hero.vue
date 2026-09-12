<script setup lang="ts">
defineProps<{
    image: string;
    alt?: string;
}>();
</script>

<template>
    <section class="hero reveal">
        <img class="hero-image" :src="image" :alt="alt ?? ''" />
        <div class="hero-fade" />
        <div class="hero-body">
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
            <div v-if="$slots.side" class="hero-side">
                <slot name="side" />
            </div>
        </div>
        <slot name="stats" />
    </section>
</template>
