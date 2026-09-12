<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowLeft } from '@lucide/vue';
import { computed } from 'vue';
import { useLocale } from '@/composables/useLocale';
import { useTranslations } from '@/composables/useTranslations';

const props = withDefaults(
    defineProps<{
        title: string;
        eyebrow?: string;
        description?: string;
        /** When set, shows Back that goes here (typically edit forms). Omit on create. */
        backHref?: string | null;
        dir?: 'ltr' | 'rtl';
    }>(),
    {
        eyebrow: undefined,
        description: undefined,
        backHref: null,
        dir: undefined,
    },
);

const { t } = useTranslations();
const { dir: localeDir } = useLocale();
const resolvedDir = computed(() => props.dir ?? localeDir.value);
</script>

<template>
    <div class="v2-list-page v2-form-page" :dir="resolvedDir">
        <header class="v2-form-page-head">
            <div class="v2-form-page-copy">
                <p v-if="eyebrow || $slots.eyebrow" class="v2-form-page-eyebrow">
                    <slot name="eyebrow">{{ eyebrow }}</slot>
                </p>
                <h1>{{ title }}</h1>
                <p
                    v-if="description || $slots.description"
                    class="v2-form-page-desc"
                >
                    <slot name="description">{{ description }}</slot>
                </p>
            </div>
            <div
                v-if="backHref || $slots.actions"
                class="v2-form-page-actions"
            >
                <Link
                    v-if="backHref"
                    :href="backHref"
                    class="detail-btn ghost"
                >
                    <ArrowLeft class="size-4 rtl:rotate-180" />
                    {{ t('Back') }}
                </Link>
                <slot name="actions" />
            </div>
        </header>

        <div class="v2-form-shell">
            <div class="v2-form-layout" :class="{ 'has-aside': !!$slots.aside }">
                <div class="v2-form-main">
                    <slot />
                </div>
                <aside v-if="$slots.aside" class="v2-form-aside">
                    <slot name="aside" />
                </aside>
            </div>
        </div>
    </div>
</template>
