<script setup lang="ts">
import type { InertiaLinkProps } from '@inertiajs/vue3';
import { CreateButton } from '@/components/ui/create-button';
import type { ButtonVariants } from '@/components/ui/button';

const props = withDefaults(
    defineProps<{
        href: NonNullable<InertiaLinkProps['href']>;
        permission: string;
        size?: ButtonVariants['size'];
        variant?: 'default' | 'outline' | 'secondary' | 'ghost';
        class?: string;
        tourAnchor?: boolean;
    }>(),
    {
        size: 'sm',
        variant: 'default',
        tourAnchor: false,
    },
);
</script>

<template>
    <CreateButton
        :href="props.href"
        :permission="props.permission"
        :size="props.size"
        :variant="props.variant"
        :class="props.class"
        :tour-anchor="props.tourAnchor"
    >
        <slot />
    </CreateButton>
</template>
