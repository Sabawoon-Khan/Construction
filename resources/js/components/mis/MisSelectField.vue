<script setup lang="ts">
import MisField from '@/components/mis/MisField.vue';
import { cn } from '@/lib/utils';

export type MisSelectOption = {
    value: string;
    label: string;
    disabled?: boolean;
};

const model = defineModel<string>({ default: '' });

withDefaults(
    defineProps<{
        id?: string;
        label?: string;
        error?: string;
        options: MisSelectOption[];
        placeholder?: string;
        disabled?: boolean;
        class?: string;
        selectClass?: string;
        required?: boolean;
    }>(),
    {
        id: undefined,
        label: undefined,
        error: undefined,
        placeholder: undefined,
        disabled: false,
        class: undefined,
        selectClass: undefined,
        required: false,
    },
);
</script>

<template>
    <MisField
        v-if="label || error || required"
        :label="label"
        :html-for="id"
        :error="error"
        :required="required"
        :class="$props.class"
    >
        <select
            :id="id"
            v-model="model"
            :disabled="disabled"
            :required="required"
            :class="
                cn(
                    'h-9 w-full rounded-md border bg-background px-3 text-sm outline-none transition focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] disabled:cursor-not-allowed disabled:opacity-50',
                    selectClass,
                )
            "
        >
            <option v-if="placeholder !== undefined" value="">
                {{ placeholder }}
            </option>
            <option
                v-for="option in options"
                :key="option.value"
                :value="option.value"
                :disabled="option.disabled"
            >
                {{ option.label }}
            </option>
        </select>
    </MisField>
    <select
        v-else
        :id="id"
        v-model="model"
        :disabled="disabled"
        :required="required"
        :aria-label="placeholder || undefined"
        :class="
            cn(
                'h-9 rounded-md border bg-background px-3 text-sm outline-none transition focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] disabled:cursor-not-allowed disabled:opacity-50',
                $props.class,
                selectClass,
            )
        "
    >
        <option v-if="placeholder !== undefined" value="">
            {{ placeholder }}
        </option>
        <option
            v-for="option in options"
            :key="option.value"
            :value="option.value"
            :disabled="option.disabled"
        >
            {{ option.label }}
        </option>
    </select>
</template>
