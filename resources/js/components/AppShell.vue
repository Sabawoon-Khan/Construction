<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import LocaleSync from '@/components/LocaleSync.vue';
import { SidebarProvider } from '@/components/ui/sidebar';
import type { AppVariant } from '@/types';

type Props = {
    variant?: AppVariant;
};

withDefaults(defineProps<Props>(), {
    variant: 'sidebar',
});

const isOpen = usePage().props.sidebarOpen;
</script>

<template>
    <LocaleSync>
        <div
            v-if="variant === 'header'"
            class="app-shell-header flex h-dvh min-h-0 w-full flex-col overflow-hidden"
        >
            <slot />
        </div>
        <SidebarProvider v-else :default-open="isOpen">
            <slot />
        </SidebarProvider>
    </LocaleSync>
</template>
