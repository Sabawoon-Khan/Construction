<script setup lang="ts">
import { computed } from 'vue';
import { Badge } from '@/components/ui/badge';
import type { BadgeVariants } from '@/components/ui/badge';
import { translateStatus } from '@/lib/translateStatus';
import { useTranslations } from '@/composables/useTranslations';
import { cn } from '@/lib/utils';

type StatusTone = NonNullable<BadgeVariants['variant']>;

const props = withDefaults(
    defineProps<{
        status?: string | null;
        label?: string;
        /** Override automatic tone mapping with a Badge variant. */
        variant?: StatusTone;
        /** Map status keys to Badge variants (takes precedence over defaults). */
        map?: Record<string, StatusTone>;
        class?: string;
    }>(),
    {
        status: '',
        label: undefined,
        variant: undefined,
        map: undefined,
        class: undefined,
    },
);

const successTone =
    'border-emerald-200 bg-emerald-50 text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-300';
const warningTone =
    'border-amber-200 bg-amber-50 text-amber-800 dark:border-amber-900 dark:bg-amber-950/40 dark:text-amber-300';
const dangerTone =
    'border-rose-200 bg-rose-50 text-rose-800 dark:border-rose-900 dark:bg-rose-950/40 dark:text-rose-300';
const neutralTone = 'border-border bg-muted/60 text-foreground';

/** Colored outline tones covering project/sale/unit/payment statuses. */
const toneDefaults: Record<string, string> = {
    // success / positive
    sold: successTone,
    paid: successTone,
    posted: successTone,
    active: successTone,
    completed: successTone,
    available: successTone,
    success: successTone,
    sent: successTone,
    login_success: successTone,
    ok: successTone,
    // warning / in-progress
    reserved: warningTone,
    partial: warningTone,
    pending: warningTone,
    planning: warningTone,
    in_progress: warningTone,
    under_construction: warningTone,
    ready_for_sale: warningTone,
    processing: warningTone,
    queued: warningTone,
    warning: warningTone,
    paused: warningTone,
    on_hold: warningTone,
    // danger / negative
    cancelled: dangerTone,
    canceled: dangerTone,
    inactive: dangerTone,
    overdue: dangerTone,
    failed: dangerTone,
    error: dangerTone,
    danger: dangerTone,
    rejected: dangerTone,
    login_failed: dangerTone,
    disabled: dangerTone,
    // neutral
    draft: neutralTone,
};

const variantDefaults: Record<string, StatusTone> = {
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

const statusKey = computed(() =>
    String(props.status || '')
        .toLowerCase()
        .replace(/\s+/g, '_'),
);

const resolvedVariant = computed<StatusTone>(() => {
    if (props.variant) {
        return props.variant;
    }
    const key = statusKey.value;
    return props.map?.[key] ?? props.map?.[props.status ?? ''] ?? variantDefaults[key] ?? 'outline';
});

const toneClass = computed(() => {
    // Explicit Badge variant override skips colored outline tones.
    if (props.variant || props.map) {
        return undefined;
    }
    return toneDefaults[statusKey.value] ?? neutralTone;
});

const { t } = useTranslations();

const display = computed(() => props.label ?? translateStatus(props.status, t));
</script>

<template>
    <Badge
        :variant="toneClass ? 'outline' : resolvedVariant"
        :class="cn(toneClass, props.class)"
    >
        <slot>{{ display }}</slot>
    </Badge>
</template>
