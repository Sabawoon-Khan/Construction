<script setup lang="ts">
import { computed } from 'vue';
import { Badge } from '@/components/ui/badge';
import { useTranslations } from '@/composables/useTranslations';
import { translateStatus } from '@/lib/translateStatus';
import { cn } from '@/lib/utils';

const props = withDefaults(
    defineProps<{
        status?: string | null;
        class?: string;
    }>(),
    { status: '' },
);

const { t } = useTranslations();

const label = computed(() => translateStatus(props.status, t));

const tone = computed(() => {
    const s = String(props.status || '').toLowerCase();
    if (['sold', 'paid', 'posted', 'active', 'completed', 'available'].includes(s)) {
        return 'border-emerald-200 bg-emerald-50 text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-300';
    }
    if (['reserved', 'partial', 'pending', 'planning', 'in_progress', 'under_construction', 'ready_for_sale'].includes(s)) {
        return 'border-amber-200 bg-amber-50 text-amber-800 dark:border-amber-900 dark:bg-amber-950/40 dark:text-amber-300';
    }
    if (['cancelled', 'canceled', 'inactive', 'overdue', 'failed'].includes(s)) {
        return 'border-rose-200 bg-rose-50 text-rose-800 dark:border-rose-900 dark:bg-rose-950/40 dark:text-rose-300';
    }
    return 'border-border bg-muted/60 text-foreground';
});
</script>

<template>
    <Badge variant="outline" :class="cn(tone, props.class)">
        {{ label }}
    </Badge>
</template>
