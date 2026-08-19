<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { cn, resolveCurrency } from '@/lib/utils';

const props = defineProps<{
    modelValue: string;
    id?: string;
    disabled?: boolean;
    class?: string;
    name?: string;
}>();

const emit = defineEmits<{
    (e: 'update:modelValue', value: string): void;
}>();

const currencies = [
    { code: 'AFN', label: 'AFN' },
    { code: 'USD', label: 'USD' },
] as const;

const baseCurrency = computed(() => resolveCurrency(usePage().props.company?.base_currency));

const value = computed({
    get() {
        const current = resolveCurrency(props.modelValue || baseCurrency.value);
        return current === 'AFN' || current === 'USD' ? current : resolveCurrency(baseCurrency.value);
    },
    set(val: string) {
        emit('update:modelValue', val);
    },
});
</script>

<template>
    <div
        :id="id"
        role="group"
        :aria-label="'Currency'"
        :class="cn('inline-flex h-10 w-full min-w-[8.5rem] rounded-lg border border-input bg-transparent p-0.5 shadow-xs', props.class)"
    >
        <button
            v-for="currency in currencies"
            :key="currency.code"
            type="button"
            :disabled="disabled"
            :class="
                cn(
                    'flex-1 rounded-md text-sm font-medium transition',
                    value === currency.code
                        ? 'bg-primary text-primary-foreground shadow-xs'
                        : 'text-muted-foreground hover:text-foreground',
                )
            "
            @click="value = currency.code"
        >
            {{ currency.label }}
        </button>
        <input v-if="name" type="hidden" :name="name" :value="value" />
    </div>
</template>
