<script setup lang="ts">
import { computed } from 'vue';
import { Badge } from '@/components/ui/badge';
import type { BadgeVariants } from '@/components/ui/badge';
import { translateStatus } from '@/lib/translateStatus';
import { useTranslations } from '@/composables/useTranslations';

type StatusTone = NonNullable<BadgeVariants['variant']>;

const props = withDefaults(
    defineProps<{
        status: string;
        label?: string;
        /** Override automatic tone mapping. */
        variant?: StatusTone;
        map?: Record<string, StatusTone>;
    }>(),
    {
        label: undefined,
        variant: undefined,
        map: undefined,
    },
);

const defaults: Record<string, StatusTone> = {
    active: 'default',
    success: 'default',
    completed: 'default',
    sent: 'default',
    login_success: 'default',
    ok: 'default',
    pending: 'secondary',
    processing: 'secondary',
    queued: 'secondary',
    warning: 'secondary',
    paused: 'secondary',
    draft: 'outline',
    inactive: 'outline',
    disabled: 'destructive',
    failed: 'destructive',
    error: 'destructive',
    danger: 'destructive',
    rejected: 'destructive',
    login_failed: 'destructive',
};

const resolved = computed(() => {
    if (props.variant) {
        return props.variant;
    }
    const key = props.status.toLowerCase().replace(/\s+/g, '_');
    return props.map?.[key] ?? props.map?.[props.status] ?? defaults[key] ?? 'outline';
});

const { t } = useTranslations();

const display = computed(() => props.label ?? translateStatus(props.status, t));
</script>

<template>
    <Badge :variant="resolved">
        <slot>{{ display }}</slot>
    </Badge>
</template>
