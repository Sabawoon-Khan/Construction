<script setup lang="ts">
defineProps<{
    label: string;
    modelValue?: string | number | null;
}>();

const emit = defineEmits<{
    'update:modelValue': [value: string];
    change: [value: string];
}>();

function onChange(event: Event) {
    const value = (event.target as HTMLSelectElement).value;
    emit('update:modelValue', value);
    emit('change', value);
}
</script>

<template>
    <label class="filter-select">
        <span>{{ label }}</span>
        <select :value="modelValue ?? ''" @change="onChange">
            <slot />
        </select>
    </label>
</template>
