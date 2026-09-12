<script setup lang="ts">
withDefaults(
    defineProps<{
        title: string;
        description?: string;
        plain?: boolean;
    }>(),
    {
        description: undefined,
        plain: false,
    },
);
</script>

<template>
    <section
        class="v2-form-section"
        :class="{ 'v2-form-section-plain': plain }"
    >
        <header class="v2-form-section-head">
            <h2>{{ title }}</h2>
            <p v-if="description">{{ description }}</p>
        </header>
        <slot />
    </section>
</template>
