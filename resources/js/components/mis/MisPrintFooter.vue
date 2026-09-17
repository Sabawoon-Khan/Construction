<script setup lang="ts">
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { useTranslations } from '@/composables/useTranslations';

const page = usePage();
const { t } = useTranslations();

const brand = computed(() => {
    const company = page.props.publicCompany as
        | { name_en?: string | null; name_primary?: string | null }
        | null;
    const locale = (page.props.locale as string) || 'en';

    if (locale === 'en') {
        return (
            company?.name_en?.trim() ||
            (page.props.name as string | undefined) ||
            ''
        );
    }

    return (
        company?.name_primary?.trim() ||
        (page.props.name as string | undefined) ||
        ''
    );
});

const year = new Date().getFullYear();
</script>

<template>
    <footer class="print-sheet-footer">
        <span>{{ brand }}</span>
        <span>{{ t('All rights reserved.') }} · © {{ year }}</span>
    </footer>
</template>
