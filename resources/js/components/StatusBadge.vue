<script setup lang="ts">
import MisStatusBadge from '@/components/mis/MisStatusBadge.vue';

withDefaults(
    defineProps<{
        status?: string | null;
        class?: string;
    }>(),
    { status: '', class: undefined },
);
</script>

<template>
    <MisStatusBadge :status="status" :class="$props.class" />
</template>
