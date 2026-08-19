<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppContent from '@/components/AppContent.vue';
import AppHeader from '@/components/AppHeader.vue';
import AppModuleTabs from '@/components/AppModuleTabs.vue';
import AppShell from '@/components/AppShell.vue';
import OfflineBanner from '@/components/OfflineBanner.vue';
import OfflineConflictPanel from '@/components/OfflineConflictPanel.vue';
import { Toaster } from '@/components/ui/sonner';
import { useLocale } from '@/composables/useLocale';
import type { BreadcrumbItem } from '@/types';

withDefaults(
    defineProps<{
        breadcrumbs?: BreadcrumbItem[];
    }>(),
    {
        breadcrumbs: () => [],
    },
);

const page = usePage();
const offlineEnabled = computed(() =>
    Boolean((page.props as { offlineEnabled?: boolean }).offlineEnabled),
);
const { dir } = useLocale();

const toasterPosition = computed(() =>
    dir.value === 'rtl' ? 'top-left' : 'top-right',
);
</script>

<template>
    <AppShell variant="header">
        <AppHeader class="print:hidden" :breadcrumbs="breadcrumbs" />
        <AppContent variant="header">
            <OfflineBanner class="print:hidden" :enabled="offlineEnabled" />
            <div class="mx-auto flex w-full max-w-[90rem] flex-col px-4 pt-4 md:px-6 print:hidden">
                <AppModuleTabs />
            </div>
            <div class="mx-auto flex w-full max-w-[90rem] flex-1 flex-col space-y-6 px-4 py-6 pt-4 md:px-6 md:pb-12 print:p-0">
                <OfflineConflictPanel v-if="offlineEnabled" />
                <slot />
            </div>
        </AppContent>
        <Toaster :position="toasterPosition" :duration="5000" rich-colors />
    </AppShell>
</template>
