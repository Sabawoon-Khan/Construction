<script setup lang="ts">
import { ChevronDown, ChevronUp, ChevronsUpDown } from '@lucide/vue';
import { computed } from 'vue';
import { useProvidedTableSort } from '@/composables/useTableSort';
import { cn } from '@/lib/utils';

const props = defineProps<{
    column: string;
    align?: 'start' | 'center' | 'end';
    class?: string;
    sortKey?: string;
    sortDir?: 'asc' | 'desc';
}>();

const emit = defineEmits<{
    sort: [column: string];
}>();

const provided = useProvidedTableSort();
const sortKey = computed(() => props.sortKey ?? provided?.sortKey.value ?? '');
const sortDir = computed(() => props.sortDir ?? provided?.sortDir.value ?? 'desc');
const canSort = computed(() => Boolean(props.sortKey || provided));
const active = computed(() => sortKey.value === props.column);

function onSort(): void {
    if (props.sortKey) {
        emit('sort', props.column);
        return;
    }

    provided?.sortBy(props.column);
}

function alignClass(): string {
    if (props.align === 'center') {
        return 'text-center';
    }
    if (props.align === 'end') {
        return 'text-end';
    }

    return 'text-start';
}
</script>

<template>
    <th
        :class="cn('px-4 py-3 font-medium whitespace-nowrap', alignClass(), $props.class)"
        :aria-sort="
            canSort && active
                ? sortDir === 'asc'
                    ? 'ascending'
                    : 'descending'
                : canSort
                  ? 'none'
                  : undefined
        "
    >
        <button
            v-if="canSort"
            type="button"
            class="inline-flex items-center gap-1 text-inherit hover:text-foreground data-[active=true]:text-[var(--brand-accent,var(--primary))]"
            :data-active="canSort && active ? 'true' : undefined"
            @click="onSort"
        >
            <slot />
            <ChevronUp v-if="active && sortDir === 'asc'" class="size-3.5 shrink-0" />
            <ChevronDown v-else-if="active && sortDir === 'desc'" class="size-3.5 shrink-0" />
            <ChevronsUpDown v-else class="size-3.5 shrink-0 opacity-70" />
        </button>
        <slot v-else />
    </th>
</template>
