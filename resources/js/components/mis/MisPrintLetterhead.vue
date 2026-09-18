<script setup lang="ts">
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { useCalendar } from '@/composables/useCalendar';
import { useTranslations } from '@/composables/useTranslations';

type PublicCompany = {
    name?: string | null;
    name_primary?: string | null;
    name_en?: string | null;
    phone?: string | null;
    email?: string | null;
    address?: string | null;
};

const props = withDefaults(
    defineProps<{
        /** Document title under the brand (e.g. Projects). */
        title?: string;
        subtitle?: string;
    }>(),
    {
        title: undefined,
        subtitle: undefined,
    },
);

const page = usePage();
const { t } = useTranslations();
const calendar = useCalendar();

const publicCompany = computed(
    () => (page.props.publicCompany as PublicCompany | null) ?? null,
);
const locale = computed(() => (page.props.locale as string) || 'en');
const isEnglish = computed(() => locale.value === 'en');

const brandPrimary = computed(() => {
    if (isEnglish.value) {
        return (
            publicCompany.value?.name_en?.trim() ||
            (page.props.name as string | undefined) ||
            ''
        );
    }

    return (
        publicCompany.value?.name_primary?.trim() ||
        (page.props.name as string | undefined) ||
        ''
    );
});

const brandSecondary = computed(() => {
    if (isEnglish.value) {
        return null;
    }

    return publicCompany.value?.name_en?.trim() || null;
});

const logo = computed(
    () => (page.props.logo as string | null) ?? '/favicon.png',
);

const contactLine = computed(() => {
    const parts = [
        publicCompany.value?.address?.trim(),
        publicCompany.value?.phone?.trim(),
        publicCompany.value?.email?.trim(),
    ].filter(Boolean);

    return parts.join(' · ');
});

const documentTitle = computed(() => {
    if (props.title?.trim()) {
        return props.title.trim();
    }

    if (typeof document === 'undefined') {
        return t('Report');
    }

    return document.title.replace(/\s*[|·\-–—]\s*.*$/, '').trim() || t('Report');
});

const printedAt = computed(
    () => calendar.formatDateTime(new Date()) || '',
);
</script>

<template>
    <header class="print-letterhead">
        <div class="print-letterhead-brand">
            <img
                v-if="logo"
                :src="logo"
                alt=""
                class="print-letterhead-logo"
            />
            <div class="print-letterhead-names">
                <p class="print-letterhead-primary">{{ brandPrimary }}</p>
                <p v-if="brandSecondary" class="print-letterhead-secondary">
                    {{ brandSecondary }}
                </p>
            </div>
        </div>
        <div class="print-letterhead-meta">
            <p class="print-letterhead-doc">{{ documentTitle }}</p>
            <p v-if="subtitle" class="print-letterhead-sub">{{ subtitle }}</p>
            <p v-if="contactLine" class="print-letterhead-contact">
                {{ contactLine }}
            </p>
            <p class="print-letterhead-date">
                {{ t('Printed') }} {{ printedAt }}
            </p>
        </div>
    </header>
</template>
