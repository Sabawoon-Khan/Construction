<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import type { InertiaLinkProps } from '@inertiajs/vue3';
import { Plus } from '@lucide/vue';
import { computed } from 'vue';
import { usePermissions } from '@/composables/usePermissions';
import { cn } from '@/lib/utils';

const props = withDefaults(
    defineProps<{
        href: NonNullable<InertiaLinkProps['href']>;
        permission: string;
        size?: 'default' | 'sm' | 'lg' | 'icon';
        variant?: 'default' | 'outline' | 'secondary' | 'ghost';
        class?: string;
        tourAnchor?: boolean;
    }>(),
    {
        size: 'default',
        variant: 'default',
        tourAnchor: false,
    },
);

const { can } = usePermissions();

const classes = computed(() =>
    cn(
        'create-btn',
        props.size === 'sm' && 'h-9 px-4 text-[11px]',
        props.size === 'lg' && 'h-11 px-5 text-[13px]',
        props.variant === 'ghost' && 'create-btn-ghost',
        props.variant === 'outline' && 'create-btn-outline',
        props.class,
    ),
);
</script>

<template>
    <Link
        v-if="can(permission)"
        :href="href"
        :class="classes"
        :data-tour="tourAnchor ? 'create-primary' : undefined"
    >
        <Plus class="size-4" />
        <slot />
    </Link>
</template>
