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
import { useTranslations } from '@/composables/useTranslations';
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
const { t } = useTranslations();

const toasterPosition = computed(() =>
    dir.value === 'rtl' ? 'top-left' : 'top-right',
);
</script>

<template>
    <AppShell variant="header">
        <a
            href="#main-content"
            class="sr-only focus:not-sr-only focus:absolute focus:start-4 focus:top-4 focus:z-[100] focus:rounded-md focus:bg-background focus:px-3 focus:py-2 focus:text-sm focus:shadow-lg"
        >
            {{ t('Skip to main content') }}
        </a>
        <AppHeader class="print:hidden" :breadcrumbs="breadcrumbs" />
        <AppContent variant="header">
            <OfflineBanner class="print:hidden" :enabled="offlineEnabled" />
            <div class="mx-auto flex w-full max-w-[90rem] flex-col px-3 pt-3 sm:px-4 sm:pt-4 md:px-6 print:hidden">
                <AppModuleTabs />
            </div>
            <div
                id="main-content"
                class="mx-auto flex w-full max-w-[90rem] flex-1 flex-col space-y-4 px-3 py-4 pt-3 sm:space-y-6 sm:px-4 sm:py-6 sm:pt-4 md:px-6 md:pb-12 print:p-0"
                tabindex="-1"
            >
                <OfflineConflictPanel v-if="offlineEnabled" />
                <slot />
            </div>
        </AppContent>
        <Toaster
            :position="toasterPosition"
            :duration="5000"
            close-button
            close-button-position="top-right"
        />
    </AppShell>
</template>
