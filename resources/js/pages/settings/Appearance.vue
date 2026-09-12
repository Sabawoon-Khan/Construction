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
import { useAppearance } from '@/composables/useAppearance';
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
const { interfaceStyle } = useAppearance();
</script>

<template>
    <Head :title="t('Appearance settings')" />

    <h1 class="sr-only">{{ t('Appearance settings') }}</h1>

    <div class="space-y-6 settings-appearance">
        <Card class="overflow-hidden">
            <CardHeader class="border-b border-border/60">
                <CardTitle>{{ t('Interface style') }}</CardTitle>
                <p class="mt-1 text-sm text-muted-foreground">
                    {{
                        t(
                            'Choose Glass for frosted panels and the soft construction workspace look.',
                        )
                    }}
                </p>
            </CardHeader>
            <CardContent class="pt-6">
                <InterfaceStyleTabs />
                <p
                    v-if="interfaceStyle === 'glass'"
                    class="mt-4 rounded-xl border border-primary/20 bg-primary/5 px-3.5 py-2.5 text-xs leading-5 text-muted-foreground"
                >
                    {{
                        t(
                            'Tip: pick the Structo glass preset below to match orange accents and soft rounded panels.',
                        )
                    }}
                </p>
            </CardContent>
        </Card>

        <Card class="overflow-hidden">
            <CardHeader class="border-b border-border/60">
                <CardTitle>{{ t('Color mode') }}</CardTitle>
            </CardHeader>
            <CardContent class="pt-6">
                <AppearanceTabs />
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
