<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import type { InertiaLinkProps } from '@inertiajs/vue3';
import { Plus } from '@lucide/vue';
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import type { ButtonVariants } from '@/components/ui/button';
import { usePermissions } from '@/composables/usePermissions';
import { cn } from '@/lib/utils';

const props = withDefaults(
    defineProps<{
        href?: NonNullable<InertiaLinkProps['href']>;
        permission?: string;
        variant?: 'default' | 'outline' | 'secondary' | 'ghost';
        size?: ButtonVariants['size'];
        class?: string;
        tourAnchor?: boolean;
        type?: 'button' | 'submit';
        disabled?: boolean;
    }>(),
    {
        variant: 'default',
        size: 'sm',
        type: 'button',
        tourAnchor: false,
        disabled: false,
    },
);

const emit = defineEmits<{
    click: [event: MouseEvent];
}>();

const { can } = usePermissions();

const allowed = computed(
    () => props.permission === undefined || can(props.permission),
);
</script>

<template>
    <Button
        v-if="allowed && href"
        as-child
        :size="size"
        :variant="variant"
        :disabled="disabled"
        :class="cn('h-8 gap-1.5', props.class)"
    >
        <Link
            :href="href"
            :data-tour="tourAnchor ? 'create-primary' : undefined"
        >
            <Plus class="size-3.5" />
            <slot />
        </Link>
    </Button>
    <Button
        v-else-if="allowed"
        :type="type"
        :size="size"
        :variant="variant"
        :disabled="disabled"
        :class="cn('h-8 gap-1.5', props.class)"
        :data-tour="tourAnchor ? 'create-primary' : undefined"
        @click="emit('click', $event)"
    >
        <Plus class="size-3.5" />
        <slot />
    </Button>
</template>
