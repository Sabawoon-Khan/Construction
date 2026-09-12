<script setup lang="ts">
withDefaults(
    defineProps<{
        delay?: boolean;
        padded?: boolean;
        title?: string;
        description?: string;
    }>(),
    {
        delay: false,
        padded: true,
        title: undefined,
        description: undefined,
    },
);
</script>

<template>
    <section class="v2-panel reveal" :class="{ delay, padded }">
        <header
            v-if="title || description || $slots.header || $slots.actions"
            class="v2-panel-header"
        >
            <div class="v2-panel-heading">
                <slot name="header">
                    <h2 v-if="title">{{ title }}</h2>
                    <p v-if="description">{{ description }}</p>
                </slot>
            </div>
            <div v-if="$slots.actions" class="v2-panel-actions">
                <slot name="actions" />
            </div>
        </header>
        <div class="v2-panel-body" :class="{ 'has-header': title || description || $slots.header || $slots.actions }">
            <slot />
        </div>
    </section>
</template>
