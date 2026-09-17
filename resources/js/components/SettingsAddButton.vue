<script setup lang="ts">
import type { InertiaLinkProps } from '@inertiajs/vue3';
import { CreateButton } from '@/components/ui/create-button';

const props = withDefaults(
    defineProps<{
        href?: NonNullable<InertiaLinkProps['href']>;
        disabled?: boolean;
        variant?: 'default' | 'outline' | 'secondary' | 'ghost';
    }>(),
    {
        variant: 'default',
    },
);

const emit = defineEmits<{ click: [] }>();
</script>

<template>
    <CreateButton
        v-if="href"
        :href="props.href"
        :variant="props.variant"
        :disabled="props.disabled"
    >
        <slot />
    </CreateButton>
    <CreateButton
        v-else
        :variant="props.variant"
        :disabled="props.disabled"
        @click="emit('click')"
    >
        <slot />
    </CreateButton>
</template>
