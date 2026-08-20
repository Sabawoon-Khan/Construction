<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Plus } from '@lucide/vue';
import { useTranslations } from '@/composables/useTranslations';

withDefaults(
    defineProps<{
        href: string;
        label?: string;
    }>(),
    {
        label: undefined,
    },
);

const { t } = useTranslations();
</script>

<template>
    <Link
        :href="href"
        class="inline-flex items-center gap-1 text-xs font-medium text-primary hover:underline"
    >
        <Plus class="size-3.5" />
        {{ label || t('Add') }}
    </Link>
</template>
