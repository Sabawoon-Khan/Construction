<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    ArrowLeft,
    ArrowRight,
    Building2,
    Car,
    Layers,
    MapPin,
    Maximize2,
    X,
} from '@lucide/vue';
import { computed, onMounted, onUnmounted, ref } from 'vue';
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
    description?: string | null;
    location?: string | null;
    category?: string | null;
    client_name?: string | null;
    scope?: string | null;
    area_size?: string | null;
    duration?: string | null;
    total_apartments?: number | null;
    floors?: number | null;
    plot_size?: string | null;
    parking_spaces?: number | null;
    project_status?: string | null;
    year?: number | null;
    completed_on?: string | null;
    cover?: PortfolioMedia | null;
    media: PortfolioMedia[];
};

const props = defineProps<{
    project: PortfolioItem;
    related?: PortfolioItem[];
}>();

const { t } = useTranslations();

const lightboxOpen = ref(false);
const activeMediaIndex = ref(0);

function lt(key: string, fallback: string): string {
    const value = t(key);
    return value === key ? fallback : value;
}

function statusLabel(status?: string | null): string {
    if (status === 'completed') {
        return lt('home.portfolio.status.completed', 'Completed');
    }
    if (status === 'ongoing') {
        return lt('home.portfolio.status.ongoing', 'Ongoing');
    }
    if (status === 'planned') {
        return lt('home.portfolio.status.planned', 'Planned');
    }
    return status || '';
}

const specs = computed(() =>
    [
        {
            key: 'apartments',
            icon: Building2,
            label: lt('home.portfolio.spec.apartments', 'Apartments'),
            value:
                props.project.total_apartments != null
                    ? String(props.project.total_apartments)
                    : null,
        },
        {
            key: 'floors',
            icon: Layers,
            label: lt('home.portfolio.spec.floors', 'Floors'),
            value:
                props.project.floors != null
                    ? String(props.project.floors)
                    : null,
        },
        {
            key: 'area',
            icon: Maximize2,
            label: lt('home.portfolio.spec.builtUp', 'Built-up area'),
            value: props.project.area_size,
        },
        {
            key: 'plot',
            icon: Maximize2,
            label: lt('home.portfolio.spec.plot', 'Plot size'),
            value: props.project.plot_size,
        },
        {
            key: 'parking',
            icon: Car,
            label: lt('home.portfolio.spec.parking', 'Parking'),
            value:
                props.project.parking_spaces != null
                    ? String(props.project.parking_spaces)
                    : null,
        },
    ].filter((row) => !!row.value),
);

const facts = computed(() =>
    [
        {
            label: lt('home.portfolio.fact.status', 'Status'),
            value: statusLabel(props.project.project_status),
        },
        {
            label: lt('home.portfolio.fact.year', 'Year'),
            value: props.project.year != null ? String(props.project.year) : null,
        },
        {
            label: lt('home.portfolio.fact.client', 'Client'),
            value: props.project.client_name,
        },
        {
            label: lt('home.portfolio.fact.scope', 'Scope'),
            value: props.project.scope,
        },
        {
            label: lt('home.portfolio.fact.duration', 'Duration'),
            value: props.project.duration,
        },
        {
            label: lt('home.portfolio.fact.completed', 'Completed'),
            value: props.project.completed_on,
        },
        {
            label: lt('home.portfolio.fact.location', 'Location'),
            value: props.project.location,
        },
        {
            label: lt('home.portfolio.fact.category', 'Category'),
            value: props.project.category,
        },
    ].filter((row) => !!row.value),
);

const descriptionParagraphs = computed(() => {
    const text = props.project.description?.trim();
    if (!text) {
        return [];
    }
    return text
        .split(/\n{2,}/)
        .map((p) => p.trim())
        .filter(Boolean);
});

const activeMedia = computed(
    () => props.project.media[activeMediaIndex.value] ?? null,
);

function openLightbox(index: number) {
    activeMediaIndex.value = index;
    lightboxOpen.value = true;
    document.body.style.overflow = 'hidden';
}

function closeLightbox() {
    lightboxOpen.value = false;
    document.body.style.overflow = '';
}

function onKeydown(event: KeyboardEvent) {
    if (!lightboxOpen.value) {
        return;
    }
    if (event.key === 'Escape') {
        closeLightbox();
    }
    const total = props.project.media.length;
    if (total < 2) {
        return;
    }
    if (event.key === 'ArrowRight') {
        activeMediaIndex.value = (activeMediaIndex.value + 1) % total;
    }
    if (event.key === 'ArrowLeft') {
        activeMediaIndex.value =
            (activeMediaIndex.value - 1 + total) % total;
    }
}

onMounted(() => window.addEventListener('keydown', onKeydown));
onUnmounted(() => {
    window.removeEventListener('keydown', onKeydown);
    document.body.style.overflow = '';
});
</script>

<template>
    <SeoHead
        :title="project.title"
        :description="project.summary || project.description"
        :image="project.cover?.url"
        :image-alt="project.title"
        :path="`/portfolio/${project.slug}`"
        type="article"
    />

    <div class="construct">
        <PublicSiteHeader />

        <main>
            <section class="construct-project-hero">
                <img
                    v-if="project.cover?.type === 'image' && project.cover.url"
                    class="construct-hero-bg"
                    :src="project.cover.url"
                    :alt="project.title"
                />
                <video
                    v-else-if="project.cover?.type === 'video' && project.cover.url"
                    class="construct-hero-bg"
                    :src="project.cover.url"
                    muted
                    playsinline
                    autoplay
                    loop
                />
                <img
                    v-else
                    class="construct-hero-bg"
                    :src="publicStockImages.hero"
                    :alt="project.title"
                />
                <div class="construct-overlay construct-overlay-light" />

                <div class="construct-container construct-project-hero-copy">
                    <Link href="/work" class="construct-back-link">
                        <ArrowLeft class="construct-back-icon" :size="15" />
                        {{ lt('home.portfolio.back', 'All projects') }}
                    </Link>
                    <div class="construct-project-hero-meta">
                        <p v-if="project.category" class="construct-kicker">
                            {{ project.category }}
                        </p>
                        <span
                            v-if="project.project_status"
                            class="construct-status-pill"
                            :data-status="project.project_status"
                        >
                            {{ statusLabel(project.project_status) }}
                        </span>
                    </div>
                    <h1>{{ project.title }}</h1>
                    <p v-if="project.summary" class="construct-project-summary">
                        {{ project.summary }}
                    </p>
                    <div class="construct-project-hero-tags">
                        <p
                            v-if="project.location"
                            class="construct-project-location"
                        >
                            <MapPin :size="14" />
                            {{ project.location }}
                        </p>
                        <p v-if="project.year" class="construct-project-year">
                            {{ project.year }}
                        </p>
                    </div>
                </div>
            </section>

            <section v-if="specs.length" class="construct-specs-wrap">
                <div class="construct-container construct-specs">
                    <article v-for="spec in specs" :key="spec.key">
                        <component :is="spec.icon" :size="18" />
                        <div>
                            <span>{{ spec.label }}</span>
                            <strong>{{ spec.value }}</strong>
                        </div>
                    </article>
                </div>
            </section>

            <section class="construct-section">
                <div class="construct-container construct-project-layout">
                    <div class="construct-project-body">
                        <h2>
                            {{
                                lt(
                                    'home.portfolio.overview',
                                    'Project overview',
                                )
                            }}
                        </h2>
                        <div
                            v-if="descriptionParagraphs.length"
                            class="construct-prose"
                        >
                            <p
                                v-for="(para, i) in descriptionParagraphs"
                                :key="i"
                            >
                                {{ para }}
                            </p>
                        </div>
                        <p v-else-if="project.summary" class="construct-prose">
                            {{ project.summary }}
                        </p>
                        <p v-else class="construct-muted">
                            {{
                                lt(
                                    'home.portfolio.noDescription',
                                    'Project details will be published soon.',
                                )
                            }}
                        </p>

                        <div
                            v-if="project.media.length"
                            class="construct-project-gallery"
                        >
                            <div class="construct-project-gallery-head">
                                <h2>
                                    {{
                                        lt(
                                            'home.portfolio.gallery',
                                            'Gallery',
                                        )
                                    }}
                                </h2>
                                <p>
                                    {{
                                        lt(
                                            'home.portfolio.galleryLead',
                                            'Photos from the residence and compound.',
                                        )
                                    }}
                                </p>
                            </div>
                            <div class="construct-gallery construct-gallery-detail">
                                <button
                                    v-for="(media, index) in project.media"
                                    :key="media.id"
                                    type="button"
                                    class="construct-gallery-item"
                                    @click="openLightbox(index)"
                                >
                                    <img
                                        v-if="media.type === 'image'"
                                        :src="media.url"
                                        :alt="`${project.title} ${index + 1}`"
                                    />
                                    <video
                                        v-else
                                        :src="media.url"
                                        muted
                                        playsinline
                                        preload="metadata"
                                    />
                                </button>
                            </div>
                        </div>
                    </div>

                    <aside class="construct-project-facts">
                        <h3>
                            {{
                                lt(
                                    'home.portfolio.facts',
                                    'Project facts',
                                )
                            }}
                        </h3>
                        <dl v-if="facts.length">
                            <div v-for="fact in facts" :key="fact.label">
                                <dt>{{ fact.label }}</dt>
                                <dd>{{ fact.value }}</dd>
                            </div>
                        </dl>
                        <p v-else class="construct-muted">
                            {{
                                lt(
                                    'home.portfolio.factsEmpty',
                                    'Key facts will appear here as they are published.',
                                )
                            }}
                        </p>
                        <Link
                            href="/#contact"
                            class="construct-button construct-button-primary"
                        >
                            {{
                                lt(
                                    'home.portfolio.cta',
                                    'Discuss a similar project',
                                )
                            }}
                            <ArrowRight :size="15" />
                        </Link>
                    </aside>
                </div>
            </section>

            <section
                v-if="related?.length"
                class="construct-section construct-section-soft"
            >
                <div class="construct-container">
                    <div class="construct-heading">
                        <span>{{
                            lt('home.portfolio.relatedKicker', 'More work')
                        }}</span>
                        <h2>
                            {{
                                lt(
                                    'home.portfolio.relatedTitle',
                                    'Related projects',
                                )
                            }}
                        </h2>
                    </div>
                    <div class="construct-gallery">
                        <Link
                            v-for="item in related"
                            :key="item.id"
                            :href="`/portfolio/${item.slug}`"
                            class="construct-gallery-item"
                        >
                            <img
                                v-if="item.cover?.type === 'image' && item.cover.url"
                                :src="item.cover.url"
                                :alt="item.title"
                            />
                            <video
                                v-else-if="
                                    item.cover?.type === 'video' && item.cover.url
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
                </div>
            </section>
        </main>

        <PublicSiteFooter />

        <div
            v-if="lightboxOpen && activeMedia"
            class="construct-lightbox"
            role="dialog"
            aria-modal="true"
            :aria-label="project.title"
            @click.self="closeLightbox"
        >
            <button
                type="button"
                class="construct-lightbox-close"
                :aria-label="lt('home.portfolio.close', 'Close')"
                @click="closeLightbox"
            >
                <X :size="18" />
            </button>
            <div class="construct-lightbox-panel">
                <div class="construct-lightbox-media">
                    <video
                        v-if="activeMedia.type === 'video'"
                        :key="activeMedia.id"
                        :src="activeMedia.url"
                        controls
                        playsinline
                        autoplay
                    />
                    <img
                        v-else
                        :key="activeMedia.id"
                        :src="activeMedia.url"
                        :alt="project.title"
                    />
                </div>
                <div class="construct-lightbox-meta">
                    <div>
                        <h3>{{ project.title }}</h3>
                        <p
                            v-if="project.category || project.location"
                            class="construct-lightbox-tags"
                        >
                            {{
                                [project.category, project.location]
                                    .filter(Boolean)
                                    .join(' · ')
                            }}
                        </p>
                    </div>
                    <div
                        v-if="project.media.length > 1"
                        class="construct-lightbox-thumbs"
                    >
                        <button
                            v-for="(media, index) in project.media"
                            :key="media.id"
                            type="button"
                            :class="{ active: index === activeMediaIndex }"
                            @click="activeMediaIndex = index"
                        >
                            <img
                                v-if="media.type === 'image'"
                                :src="media.url"
                                alt=""
                            />
                            <video
                                v-else
                                :src="media.url"
                                muted
                                playsinline
                                preload="metadata"
                            />
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
