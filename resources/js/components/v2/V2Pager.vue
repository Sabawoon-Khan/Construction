<script setup lang="ts">
import { useTranslations } from '@/composables/useTranslations';
import {
    pageLabel,
    useV2Pager,
    type V2PagerItems,
} from '@/composables/useV2Pager';
import { ChevronLeft, ChevronRight } from '@lucide/vue';
import { Link } from '@inertiajs/vue3';
import { toRef } from 'vue';

const props = defineProps<{
    items: V2PagerItems;
    only?: string[];
}>();

const { t } = useTranslations();
const { pageMeta, pageNav, showPager } = useV2Pager(toRef(props, 'items'));
</script>

<template>
    <div v-if="showPager" class="pager">
        <p class="pager-summary">
            {{ t('Showing') }}
            <b>{{ pageMeta.from || 0 }}</b>
            {{ t('to') }}
            <b>{{ pageMeta.to || 0 }}</b>
            {{ t('of') }}
            <b>{{ pageMeta.total }}</b>
        </p>
        <nav class="pager-nav" :aria-label="t('Pagination')">
            <Link
                v-if="pageNav.prev?.url"
                :href="pageNav.prev.url"
                class="pager-btn"
                preserve-scroll
                preserve-state
                :only="only"
            >
                <ChevronLeft />
                <span>{{ t('Previous') }}</span>
            </Link>
            <span v-else class="pager-btn disabled">
                <ChevronLeft />
                <span>{{ t('Previous') }}</span>
            </span>

            <div class="pager-pages">
                <template
                    v-for="(link, index) in pageNav.pages"
                    :key="`${link.label}-${index}`"
                >
                    <Link
                        v-if="link.url"
                        :href="link.url"
                        class="pager-page"
                        :class="{ active: link.active }"
                        preserve-scroll
                        preserve-state
                        :only="only"
                    >
                        {{ pageLabel(link.label) }}
                    </Link>
                    <span
                        v-else
                        class="pager-page"
                        :class="{
                            active: link.active,
                            gap: pageLabel(link.label) === '…',
                        }"
                    >
                        {{ pageLabel(link.label) }}
                    </span>
                </template>
            </div>

            <Link
                v-if="pageNav.next?.url"
                :href="pageNav.next.url"
                class="pager-btn"
                preserve-scroll
                preserve-state
                :only="only"
            >
                <span>{{ t('Next') }}</span>
                <ChevronRight />
            </Link>
            <span v-else class="pager-btn disabled">
                <span>{{ t('Next') }}</span>
                <ChevronRight />
            </span>
        </nav>
    </div>
</template>
