<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppContent from '@/components/AppContent.vue';
import AppModuleTabs from '@/components/AppModuleTabs.vue';
import AppShell from '@/components/AppShell.vue';
import AppSidebar from '@/components/AppSidebar.vue';
import AppSidebarHeader from '@/components/AppSidebarHeader.vue';
import OfflineBanner from '@/components/OfflineBanner.vue';
import OfflineConflictPanel from '@/components/OfflineConflictPanel.vue';
import { Toaster } from '@/components/ui/sonner';
import { useLocale } from '@/composables/useLocale';
import type { BreadcrumbItem } from '@/types';

type Props = {
    breadcrumbs?: BreadcrumbItem[];
};

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});

const page = usePage();
const offlineEnabled = computed(() => Boolean((page.props as { offlineEnabled?: boolean }).offlineEnabled));
const { dir } = useLocale();

const toasterPosition = computed(() =>
    dir.value === 'rtl' ? 'top-left' : 'top-right',
);

</script>

<template>
    <AppShell variant="sidebar">
        <AppSidebar class="print:hidden" />
        <AppContent
            variant="sidebar"
            class="min-w-0 flex-1 overflow-x-hidden"
        >
            <OfflineBanner class="print:hidden" :enabled="offlineEnabled" />
            <AppSidebarHeader class="print:hidden" :breadcrumbs="breadcrumbs" />
            <div class="px-6 pt-4 md:px-8 print:hidden">
                <AppModuleTabs />
            </div>
            <div class="flex flex-1 flex-col space-y-6 px-6 pt-4 pb-12 md:px-8 print:p-0 print:pt-0">
                <OfflineConflictPanel v-if="offlineEnabled" />
                <slot />
            </div>
        </AppContent>
        <Toaster :position="toasterPosition" :duration="5000" rich-colors />
    </AppShell>
</template>
