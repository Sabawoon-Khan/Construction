<script setup lang="ts">
import { computed } from 'vue';
import { Spinner } from '@/components/ui/spinner';
import { useTranslations } from '@/composables/useTranslations';
import { cn } from '@/lib/utils';

const props = withDefaults(
    defineProps<{
        label?: string;
        class?: string;
    }>(),
    {
        class: undefined,
    },
);

const { t } = useTranslations();
const resolvedLabel = computed(() => props.label ?? t('Loading…'));
</script>

<template>
    <div
        role="status"
        :aria-busy="true"
        :class="
            cn(
                'flex flex-col items-center justify-center gap-3 rounded-2xl border border-dashed border-border/80 bg-muted/20 px-6 py-14 text-center',
                $props.class,
            )
        "
    >
        <Spinner class="size-6 text-muted-foreground" />
        <p class="text-sm text-muted-foreground">
            <slot>{{ resolvedLabel }}</slot>
        </p>
    </div>
</template>
