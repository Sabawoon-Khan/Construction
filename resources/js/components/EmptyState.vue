<script setup lang="ts">
import { useTranslations } from '@/composables/useTranslations';

withDefaults(
    defineProps<{
        title?: string;
        description?: string;
        colspan?: number;
    }>(),
    {
        title: undefined,
        description: undefined,
        colspan: 6,
    },
);

const { t } = useTranslations();
</script>

<template>
    <tr>
        <td :colspan="colspan" class="px-4 py-8 text-center sm:py-10">
            <div
                class="mx-auto flex max-w-sm flex-col items-center gap-2 rounded-[var(--radius-control,0.75rem)] border border-dashed border-border bg-muted/20 px-6 py-10"
            >
                <p class="text-sm font-medium text-foreground">
                    {{ title || t('No records yet') }}
                </p>
                <p
                    v-if="description || $slots.description"
                    class="max-w-sm text-sm text-muted-foreground"
                >
                    <slot name="description">{{ description }}</slot>
                </p>
                <div
                    v-if="$slots.actions"
                    class="mt-3 flex flex-wrap items-center justify-center gap-2"
                >
                    <slot name="actions" />
                </div>
            </div>
        </td>
    </tr>
</template>
