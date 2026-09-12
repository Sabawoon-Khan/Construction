<script setup lang="ts">
defineProps<{
    /** Extra classes e.g. inventory-card, money-card */
    cardClass?: string;
}>();
</script>

<template>
    <article class="indicator-card" :class="cardClass">
        <div v-if="$slots.head || $slots.meta" class="indicator-head">
            <span v-if="$slots.head"><slot name="head" /></span>
            <em v-if="$slots.meta"><slot name="meta" /></em>
        </div>
        <slot />
    </article>
</template>
