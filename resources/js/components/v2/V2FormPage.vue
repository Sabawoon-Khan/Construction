<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowLeft } from '@lucide/vue';
import { computed } from 'vue';
import { useLocale } from '@/composables/useLocale';
import { useTranslations } from '@/composables/useTranslations';
import V2DetailHero from './V2DetailHero.vue';

const props = withDefaults(
    defineProps<{
        title: string;
        eyebrow?: string;
        description?: string;
        /** When set, shows Back that goes here (typically edit forms). Omit on create. */
        backHref?: string | null;
        image?: string;
        dir?: 'ltr' | 'rtl';
    }>(),
    {
        eyebrow: undefined,
        description: undefined,
        backHref: null,
        image: '/images/dashboard-v2-building.png',
        dir: undefined,
    },
);

const { t } = useTranslations();
const { dir: localeDir } = useLocale();
const resolvedDir = computed(() => props.dir ?? localeDir.value);
</script>

<template>
    <div class="v2-list-page" :dir="resolvedDir">
        <V2DetailHero :image="image" compact>
            <template v-if="eyebrow || $slots.eyebrow" #eyebrow>
                <slot name="eyebrow">{{ eyebrow }}</slot>
            </template>
            <template #title>{{ title }}</template>
            <template v-if="description || $slots.description" #description>
                <slot name="description">{{ description }}</slot>
            </template>
            <template v-if="backHref || $slots.actions" #actions>
                <Link
                    v-if="backHref"
                    :href="backHref"
                    class="detail-btn ghost"
                >
                    <ArrowLeft class="size-4 rtl:rotate-180" />
                    {{ t('Back') }}
                </Link>
                <slot name="actions" />
            </template>
        </V2DetailHero>

        <div class="v2-form-shell reveal delay">
            <slot />
        </div>
    </div>
</template>
