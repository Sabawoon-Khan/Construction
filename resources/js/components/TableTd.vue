<script setup lang="ts">
import { computed } from 'vue';
import { useProvidedTableColumns } from '@/composables/useTableColumns';
import { cn } from '@/lib/utils';

const props = defineProps<{
    column: string;
    class?: string;
}>();

const columns = useProvidedTableColumns();
const visible = computed(() => columns?.isVisible(props.column) ?? true);
</script>

<template>
    <td v-if="visible" :class="cn('px-4 py-3 align-middle', $props.class)">
        <slot />
    </td>
</template>
