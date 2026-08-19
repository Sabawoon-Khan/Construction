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
</script>

<template>
    <div
        :class="cn('flex items-end justify-between gap-2 border-b border-border/70 pb-0', $props.class)"
    >
        <div
            class="min-w-0 flex-1"
            :class="nowrap ? 'overflow-x-auto' : undefined"
        >
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
            :class="
                cn(
                    'inline-flex shrink-0 items-center gap-2 whitespace-nowrap border-b-2 px-3 py-2.5 text-sm font-medium transition -mb-px',
                    model === tab.id
                        ? 'border-primary text-primary'
                        : 'border-transparent text-muted-foreground hover:border-border hover:text-foreground',
                )
            "
            @click="model = tab.id"
        >
            <component :is="tab.icon" v-if="tab.icon" class="size-4 shrink-0" />
            {{ tab.label }}
        </Link>
        <Button
            v-for="tab in tabs.filter((t) => !t.href)"
            :key="`btn-${tab.id}`"
            type="button"
            variant="ghost"
            size="sm"
            role="tab"
            :aria-selected="model === tab.id"
            :class="
                cn(
                    'rounded-b-none border-b-2',
                    model === tab.id
                        ? 'border-primary bg-muted/50'
                        : 'border-transparent',
                )
            "
            @click="model = tab.id"
        >
            <component :is="tab.icon" v-if="tab.icon" class="size-4 shrink-0" />
            {{ tab.label }}
        </Button>
            </div>
        </div>
        <div v-if="$slots.actions" class="mb-1.5 shrink-0">
            <slot name="actions" />
        </div>
    </div>
</template>
