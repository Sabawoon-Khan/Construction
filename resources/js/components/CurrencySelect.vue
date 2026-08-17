<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps<{
    modelValue: string;
    id?: string;
    disabled?: boolean;
    class?: string;
}>();

const emit = defineEmits<{
    (e: 'update:modelValue', value: string): void;
}>();

const currencies = [
    { code: 'USD', label: 'USD - US Dollar' },
    { code: 'AFN', label: 'AFN - Afghan Afghani' },
];

const baseCurrency = computed(() => usePage().props.company?.base_currency ?? 'USD');

const value = computed({
    get: () => props.modelValue || baseCurrency.value,
    set: (val) => emit('update:modelValue', val),
});
</script>

<template>
    <select
        :id="id"
        v-model="value"
        :disabled="disabled"
        :class="[
            'h-9 rounded-md border border-input bg-transparent px-3 text-sm',
            props.class,
        ]"
    >
        <option v-for="currency in currencies" :key="currency.code" :value="currency.code">
            {{ currency.label }}
        </option>
    </select>
</template>
