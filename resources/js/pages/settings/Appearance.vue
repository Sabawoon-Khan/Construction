<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AppearanceTabs from '@/components/AppearanceTabs.vue';
import BrandingSettings from '@/components/BrandingSettings.vue';
import InterfaceStyleTabs from '@/components/InterfaceStyleTabs.vue';
import ThemeColorSettings from '@/components/ThemeColorSettings.vue';
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { useTranslations } from '@/composables/useTranslations';
import type { ThemeColorSettings as ThemeColors } from '@/lib/theme-colors';
import { edit } from '@/routes/appearance';

defineProps<{
    themeColors: ThemeColors;
    themeColorsCustomized: boolean;
    canManageTheme: boolean;
    branding: {
        app_name: string;
        app_name_en: string;
        default_locale: string;
        calendar_system?: 'gregorian' | 'jalali' | 'hijri';
        logo_url: string | null;
        favicon_url: string | null;
    };
    locales: Array<{ code: string; name: string; native: string }>;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Appearance settings',
                href: edit(),
            },
        ],
    },
});

const { t } = useTranslations();
</script>

<template>
    <Head :title="t('Appearance settings')" />

    <h1 class="sr-only">{{ t('Appearance settings') }}</h1>

    <div class="space-y-6">
        <Card class="overflow-hidden">
            <CardHeader class="border-b border-border/60 bg-muted/20">
                <CardTitle>{{ t('Color mode') }}</CardTitle>
            </CardHeader>
            <CardContent class="pt-6">
                <AppearanceTabs />
            </CardContent>
        </Card>

        <Card class="overflow-hidden">
            <CardHeader class="border-b border-border/60 bg-muted/20">
                <CardTitle>{{ t('Interface style') }}</CardTitle>
            </CardHeader>
            <CardContent class="pt-6">
                <InterfaceStyleTabs />
            </CardContent>
        </Card>

        <BrandingSettings
            :settings="branding"
            :locales="locales"
            :can-manage="canManageTheme"
        />

        <ThemeColorSettings />
    </div>
</template>
