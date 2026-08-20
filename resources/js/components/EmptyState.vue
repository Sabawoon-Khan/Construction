<script setup lang="ts">
import { useTranslations } from '@/composables/useTranslations';

defineProps<{
    title?: string;
    colspan?: number;
}>();

const { t } = useTranslations();
</script>

<template>
    <tr>
        <td :colspan="colspan ?? 6" class="px-4 py-14 text-center">
            <div class="mx-auto max-w-sm space-y-1">
                <p class="text-sm font-medium">
                    {{ title || t('No records yet') }}
                </p>
            </div>
        </td>
    </tr>
</template>
