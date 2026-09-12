<script setup lang="ts">
import { ListFilter, Search } from '@lucide/vue';
import { onClickOutside } from '@vueuse/core';
import { computed, ref, useSlots, useTemplateRef } from 'vue';
import { Button } from '@/components/ui/button';
import { useTranslations } from '@/composables/useTranslations';
import { cn } from '@/lib/utils';

const props = withDefaults(
    defineProps<{
        applyLabel?: string;
        clearLabel?: string;
        showActions?: boolean;
        filtersLabel?: string;
        /** `card` = bordered panel; `plain` = compact table toolbar. */
        variant?: 'card' | 'plain';
        class?: string;
        /** When true, filter dropdown shows an active indicator. */
        filtersActive?: boolean;
    }>(),
    {
        showActions: true,
        variant: 'card',
        class: undefined,
        filtersActive: false,
    },
);

const { t } = useTranslations();
const slots = useSlots();

const resolvedApplyLabel = computed(() => props.applyLabel ?? t('Search'));
const resolvedClearLabel = computed(() => props.clearLabel ?? t('Clear'));
const resolvedFiltersLabel = computed(() => props.filtersLabel ?? t('Filters'));
const hasFilters = computed(() => Boolean(slots.filters));

const filtersOpen = ref(false);
const filtersPanel = useTemplateRef<HTMLElement>('filtersPanel');

onClickOutside(filtersPanel, () => {
    filtersOpen.value = false;
});

const emit = defineEmits<{
    apply: [];
    clear: [];
}>();

function applyAndClose(): void {
    filtersOpen.value = false;
    emit('apply');
}

function clearAndClose(): void {
    filtersOpen.value = false;
    emit('clear');
}
</script>

<template>
    <form
        :class="
            cn(
                variant === 'card' &&
                    'rounded-2xl border border-border/80 bg-card p-3 shadow-soft sm:p-4',
                variant === 'plain' && 'min-w-0',
                $props.class,
            )
        "
        @submit.prevent="emit('apply')"
    >
        <div
            v-if="variant === 'plain'"
            class="flex items-center justify-end gap-1.5"
        >
            <div
                class="[&_[data-slot=input]]:h-8 [&_input]:h-8 [&_.relative]:w-[12.5rem] sm:[&_.relative]:w-[15rem]"
            >
                <slot />
            </div>

            <Button
                v-if="showActions"
                type="submit"
                size="sm"
                class="h-8 gap-1.5 px-2.5"
            >
                <Search class="size-3.5" />
                <span class="hidden sm:inline">{{ resolvedApplyLabel }}</span>
            </Button>

            <div v-if="hasFilters" ref="filtersPanel" class="relative">
                <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    class="h-8 gap-1.5 px-2.5"
                    :aria-expanded="filtersOpen"
                    :aria-label="resolvedFiltersLabel"
                    @click="filtersOpen = !filtersOpen"
                >
                    <ListFilter class="size-3.5" />
                    <span class="hidden sm:inline">{{ resolvedFiltersLabel }}</span>
                    <span
                        v-if="filtersActive"
                        class="size-1.5 rounded-full bg-primary"
                        aria-hidden="true"
                    />
                </Button>

                <div
                    v-if="filtersOpen"
                    class="absolute end-0 top-[calc(100%+0.35rem)] z-30 w-[min(18rem,calc(100vw-2rem))] rounded-xl border border-border/80 bg-popover p-3 text-popover-foreground shadow-lg"
                >
                    <div class="space-y-2.5 [&_label]:text-xs [&_select]:h-9 [&_select]:w-full [&_input]:h-9 [&_input]:w-full">
                        <slot name="filters" />
                    </div>
                    <div class="mt-3 flex items-center justify-end gap-1.5 border-t border-border/60 pt-2.5">
                        <Button
                            type="button"
                            variant="ghost"
                            size="sm"
                            class="h-8 text-muted-foreground"
                            @click="clearAndClose"
                        >
                            {{ resolvedClearLabel }}
                        </Button>
                        <Button
                            type="button"
                            size="sm"
                            class="h-8 gap-1.5"
                            @click="applyAndClose"
                        >
                            <Search class="size-3.5" />
                            {{ resolvedApplyLabel }}
                        </Button>
                    </div>
                </div>
            </div>
        </div>

        <template v-else>
            <div class="flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-end">
                <div
                    class="grid min-w-0 flex-1 grid-cols-1 gap-3 sm:flex sm:flex-wrap sm:items-end [&_[data-slot=input]]:w-full [&_select]:w-full sm:[&_[data-slot=input]]:w-auto sm:[&_select]:w-auto"
                >
                    <slot />
                </div>
                <div
                    v-if="showActions || $slots.actions"
                    class="flex flex-wrap items-center gap-2"
                >
                    <slot name="actions">
                        <Button type="submit" size="sm" class="flex-1 gap-1.5 sm:flex-none">
                            <Search class="size-3.5" />
                            {{ resolvedApplyLabel }}
                        </Button>
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            class="flex-1 sm:flex-none"
                            @click="emit('clear')"
                        >
                            {{ resolvedClearLabel }}
                        </Button>
                    </slot>
                </div>
            </div>
            <div v-if="$slots.advanced || $slots.filters" class="mt-3 space-y-3">
                <slot name="advanced" />
                <slot name="filters" />
            </div>
        </template>
    </form>
</template>
