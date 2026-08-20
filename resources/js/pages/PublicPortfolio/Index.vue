<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import { Search } from '@lucide/vue';
import { ref, watch } from 'vue';
import PublicSiteFooter from '@/components/public/PublicSiteFooter.vue';
import PublicSiteHeader from '@/components/public/PublicSiteHeader.vue';
import SeoHead from '@/components/public/SeoHead.vue';
import { useTranslations } from '@/composables/useTranslations';
import { publicStockImages } from '@/lib/publicStockImages';
import '@/pages/welcome/welcome-premium.css';

type PortfolioMedia = {
    id: number;
    type: 'image' | 'video';
    url: string;
    is_cover?: boolean;
    mime_type?: string | null;
};

type PortfolioItem = {
    id: number;
    title: string;
    slug: string;
    summary?: string | null;
    location?: string | null;
    category?: string | null;
    completed_on?: string | null;
    cover?: PortfolioMedia | null;
    media: PortfolioMedia[];
};

const props = defineProps<{
    projects: {
        data: PortfolioItem[];
        links: { url: string | null; label: string; active: boolean }[];
        meta?: { current_page: number; last_page: number; total: number };
    };
    filters: { search?: string | null; category?: string | null };
    categories: string[];
}>();

const { t } = useTranslations();
const search = ref(props.filters.search ?? '');
const category = ref(props.filters.category ?? '');

function lt(key: string, fallback: string): string {
    const value = t(key);
    return value === key ? fallback : value;
}

watch(
    () => props.filters,
    (next) => {
        search.value = next.search ?? '';
        category.value = next.category ?? '';
    },
);

function applyFilters() {
    router.get(
        '/work',
        {
            search: search.value || undefined,
            category: category.value || undefined,
        },
        { preserveState: true, replace: true },
    );
}

function clearFilters() {
    search.value = '';
    category.value = '';
    applyFilters();
}

function goPage(url: string | null) {
    if (!url) {
        return;
    }
    router.get(url, {}, { preserveState: true, preserveScroll: true });
}
</script>

<template>
    <SeoHead
        :title="lt('home.work.title', 'Our work')"
        :description="
            lt(
                'home.work.lead',
                'Selected residential developments delivered with refined planning and on-site craftsmanship.',
            )
        "
        :image="publicStockImages.workHero"
        path="/work"
    />

    <div class="construct">
        <PublicSiteHeader />

        <main>
            <section class="construct-work-hero">
                <div class="construct-work-hero-media">
                    <img
                        :src="publicStockImages.workHero"
                        :alt="
                            lt(
                                'home.work.heroAlt',
                                'Modern luxury residential interior',
                            )
                        "
                    />
                </div>
                <div class="construct-work-hero-veil" />
                <div class="construct-container">
                    <div class="construct-heading">
                        <span>{{
                            lt('home.portfolio.kicker', 'Portfolio')
                        }}</span>
                        <h1>{{ lt('home.work.title', 'Our residences') }}</h1>
                        <p class="construct-section-lead">
                            {{
                                lt(
                                    'home.work.lead',
                                    'Selected residential developments delivered with refined planning and on-site craftsmanship.',
                                )
                            }}
                        </p>
                    </div>

                    <form
                        class="construct-work-filters"
                        @submit.prevent="applyFilters"
                    >
                        <label class="construct-work-search">
                            <Search :size="16" />
                            <input
                                v-model="search"
                                type="search"
                                :placeholder="
                                    lt('home.work.search', 'Search projects…')
                                "
                            />
                        </label>
                        <select v-model="category" @change="applyFilters">
                            <option value="">
                                {{
                                    lt(
                                        'home.work.allCategories',
                                        'All categories',
                                    )
                                }}
                            </option>
                            <option
                                v-for="cat in categories"
                                :key="cat"
                                :value="cat"
                            >
                                {{ cat }}
                            </option>
                        </select>
                        <button
                            type="submit"
                            class="construct-button construct-button-primary"
                        >
                            {{ lt('home.work.apply', 'Apply') }}
                        </button>
                        <button
                            v-if="filters.search || filters.category"
                            type="button"
                            class="construct-button construct-button-ghost"
                            @click="clearFilters"
                        >
                            {{ lt('home.work.clear', 'Clear') }}
                        </button>
                    </form>
                </div>
            </section>

            <section class="construct-work-body">
                <div class="construct-container">
                    <div v-if="projects.data.length" class="construct-gallery">
                        <Link
                            v-for="item in projects.data"
                            :key="item.id"
                            :href="`/portfolio/${item.slug}`"
                            class="construct-gallery-item"
                        >
                            <img
                                v-if="
                                    item.cover?.type === 'image' &&
                                    item.cover.url
                                "
                                :src="item.cover.url"
                                :alt="item.title"
                            />
                            <video
                                v-else-if="
                                    item.cover?.type === 'video' &&
                                    item.cover.url
                                "
                                :src="item.cover.url"
                                muted
                                playsinline
                                preload="metadata"
                            />
                            <img
                                v-else
                                :src="publicStockImages.fallback"
                                :alt="item.title"
                            />
                            <span>
                                <strong>{{ item.title }}</strong>
                                <em v-if="item.category || item.location">
                                    {{
                                        [item.category, item.location]
                                            .filter(Boolean)
                                            .join(' · ')
                                    }}
                                </em>
                            </span>
                        </Link>
                    </div>

                    <div v-else class="construct-portfolio-empty">
                        <p>
                            {{
                                lt(
                                    'home.portfolio.empty',
                                    'New project showcases will appear here soon.',
                                )
                            }}
                        </p>
                    </div>

                    <nav
                        v-if="projects.meta && projects.meta.last_page > 1"
                        class="construct-work-pagination"
                        :aria-label="lt('home.work.pagination', 'Pagination')"
                    >
                        <button
                            v-for="(link, index) in projects.links"
                            :key="index"
                            type="button"
                            class="construct-button"
                            :class="
                                link.active
                                    ? 'construct-button-primary'
                                    : 'construct-button-ghost'
                            "
                            :disabled="!link.url"
                            v-html="link.label"
                            @click="goPage(link.url)"
                        />
                    </nav>
                </div>
            </section>
        </main>

        <PublicSiteFooter />
    </div>
</template>
