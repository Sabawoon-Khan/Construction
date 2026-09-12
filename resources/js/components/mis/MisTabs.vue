<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import type { Component } from 'vue';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';

export type MisTabItem = {
    id: string;
    label: string;
    href?: string;
    icon?: Component;
};

withDefaults(
    defineProps<{
        tabs: MisTabItem[];
        class?: string;
        nowrap?: boolean;
    }>(),
    {
        class: undefined,
        nowrap: false,
    },
);

const model = defineModel<string>({ required: true });

const tabClass = (active: boolean) =>
    cn(
        'inline-flex shrink-0 items-center gap-2 whitespace-nowrap border-b-2 px-3 py-2.5 text-sm font-medium transition -mb-px touch-manipulation',
        active
            ? 'border-primary text-primary'
            : 'border-transparent text-muted-foreground hover:border-border hover:text-foreground',
    );
</script>

<template>
    <div
        :class="cn('relative flex items-end justify-between gap-2 border-b border-border/70 pb-0', $props.class)"
    >
        <div
            class="relative min-w-0 flex-1"
            :class="nowrap ? 'overflow-x-auto overscroll-x-contain [-webkit-overflow-scrolling:touch] [scrollbar-width:none] [&::-webkit-scrollbar]:hidden' : undefined"
        >
            <div
                v-if="nowrap"
                class="pointer-events-none absolute inset-y-0 start-0 z-[1] w-6 bg-gradient-to-r from-background to-transparent rtl:bg-gradient-to-l"
                aria-hidden="true"
            />
            <div
                v-if="nowrap"
                class="pointer-events-none absolute inset-y-0 end-0 z-[1] w-6 bg-gradient-to-l from-background to-transparent rtl:bg-gradient-to-r"
                aria-hidden="true"
            />
            <div
                class="flex gap-1"
                :class="nowrap ? 'flex-nowrap' : 'flex-wrap'"
                role="tablist"
            >
                <Link
                    v-for="tab in tabs.filter((t) => t.href)"
                    :key="tab.id"
                    :href="tab.href!"
                    preserve-scroll
                    role="tab"
                    :aria-selected="model === tab.id"
                    :class="tabClass(model === tab.id)"
                    @click="model = tab.id"
                >
                    <component :is="tab.icon" v-if="tab.icon" class="size-4 shrink-0" />
                    {{ tab.label }}
                </Link>
                <button
                    v-for="tab in tabs.filter((t) => !t.href)"
                    :key="`btn-${tab.id}`"
                    type="button"
                    role="tab"
                    :aria-selected="model === tab.id"
                    :class="tabClass(model === tab.id)"
                    @click="model = tab.id"
                >
                    <component :is="tab.icon" v-if="tab.icon" class="size-4 shrink-0" />
                    {{ tab.label }}
                </button>
            </div>
        </div>
        <div v-if="$slots.actions" class="relative z-[2] mb-1.5 shrink-0">
            <slot name="actions" />
        </div>
    </div>
</template>
