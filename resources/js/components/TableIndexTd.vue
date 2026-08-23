<script setup lang="ts">
import { computed } from 'vue';
import { tableRowNumber } from '@/composables/useTableColumns';
import { cn } from '@/lib/utils';

const props = defineProps<{
    index: number;
    from?: number | null;
    class?: string;
}>();

const value = computed(() => tableRowNumber(props.index, props.from));
</script>

<template>
    <td :class="cn('w-12 px-4 py-3 tabular-nums text-muted-foreground', $props.class)">
        {{ value }}
    </td>
</template>
