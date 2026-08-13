<script setup lang="ts">
export type DetailItem = {
    label: string;
    value?: string | number | null;
    span?: boolean;
};

defineProps<{
    items: DetailItem[];
    title?: string;
}>();
</script>

<template>
    <div class="rounded-xl border border-sidebar-border/70 bg-card shadow-sm">
        <div v-if="title" class="border-b px-5 py-3">
            <h3 class="text-sm font-semibold tracking-tight">{{ title }}</h3>
        </div>
        <dl class="grid gap-x-6 gap-y-4 p-5 sm:grid-cols-2">
            <div v-for="item in items" :key="item.label" :class="item.span ? 'sm:col-span-2' : ''">
                <dt class="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                    {{ item.label }}
                </dt>
                <dd class="mt-1 text-sm font-medium wrap-break-word">
                    <slot :name="item.label" :item="item">
                        {{ item.value || '—' }}
                    </slot>
                </dd>
            </div>
        </dl>
        <div v-if="$slots.default" class="border-t px-5 py-4">
            <slot />
        </div>
    </div>
</template>
