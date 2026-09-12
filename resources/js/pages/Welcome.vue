<script setup lang="ts">
import { Link, useForm, usePage } from '@inertiajs/vue3';
import {
    ArrowRight,
    Building2,
    CheckCircle2,
    ClipboardCheck,
    HardHat,
    LayoutGrid,
    Mail,
    MapPin,
    Phone,
    ShieldCheck,
    Sparkles,
    Timer,
    Trees,
    Users,
} from '@lucide/vue';
import { computed, onMounted, onUnmounted, watch } from 'vue';
import PublicSiteFooter from '@/components/public/PublicSiteFooter.vue';
import PublicSiteHeader from '@/components/public/PublicSiteHeader.vue';
import SeoHead from '@/components/public/SeoHead.vue';
import { useTranslations } from '@/composables/useTranslations';
import { publicStockImages } from '@/lib/publicStockImages';
import './welcome/welcome-premium.css';

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

type PublicCompany = {
    name?: string | null;
    name_primary?: string | null;
    name_en?: string | null;
    phone?: string | null;
    email?: string | null;
    address?: string | null;
};

const props = defineProps<{
    portfolio?: PortfolioItem[];
}>();

const page = usePage();
const { t } = useTranslations();

function lt(key: string, fallback: string): string {
    const value = t(key);
    return value === key ? fallback : value;
}

const publicCompany = computed(
    () => (page.props.publicCompany as PublicCompany | null) ?? null,
);
const locale = computed(() => (page.props.locale as string) || 'en');
const isEnglish = computed(() => locale.value === 'en');
const brandEn = computed(
    () => publicCompany.value?.name_en?.trim() || 'Shahin Samim Barakzai',
);
const brandFa = computed(
    () => publicCompany.value?.name_primary?.trim() || 'شاهین صمیم بارکزی',
);
const portfolioItems = computed(() => props.portfolio ?? []);
const flashSuccess = computed(() => page.props.flash?.success as string | null);

const hasRealEmail = computed(() => !!publicCompany.value?.email?.trim());
const hasRealPhone = computed(() => !!publicCompany.value?.phone?.trim());
const contactEmail = computed(
    () =>
        publicCompany.value?.email?.trim() ||
        lt('home.contact.emailPlaceholder', 'info@shahinsamimbarakzai.com'),
);
const contactPhone = computed(
    () =>
        publicCompany.value?.phone?.trim() ||
        lt('home.contact.phonePlaceholder', '+93 70 000 0000'),
);
const contactAddress = computed(
    () =>
        publicCompany.value?.address?.trim() ||
        lt('home.contact.addressPlaceholder', 'Kabul, Afghanistan'),
);

const contactForm = useForm({
    name: '',
    phone: '',
    email: '',
    message: '',
});

function submitContact() {
    contactForm.post('/contact', {
        preserveScroll: true,
        onSuccess: () => contactForm.reset(),
    });
}

const services = [
    {
        icon: Building2,
        image: publicStockImages.services[0],
        titleKey: 'home.services.commercial.title',
        title: 'Residential Developments',
        bodyKey: 'home.services.commercial.body',
        body: 'Modern apartment towers and residential compounds planned for comfort, light, and lasting value.',
    },
    {
        icon: LayoutGrid,
        image: publicStockImages.services[1],
        titleKey: 'home.services.design.title',
        title: 'Design & Build',
        bodyKey: 'home.services.design.body',
        body: 'Integrated architecture and construction so façades, interiors, and site planning feel like one vision.',
    },
    {
        icon: HardHat,
        image: publicStockImages.services[2],
        titleKey: 'home.services.renovation.title',
        title: 'Premium Interiors',
        bodyKey: 'home.services.renovation.body',
        body: 'Bright, refined finishes and fit-outs that elevate everyday living across every residence.',
    },
    {
        icon: Trees,
        image: publicStockImages.services[3],
        titleKey: 'home.services.landscape.title',
        title: 'Landscape & Amenities',
        bodyKey: 'home.services.landscape.body',
        body: 'Courtyards, gardens, and shared spaces designed for calm daily living and lasting community value.',
    },
];

const milestones = [
    {
        step: '01',
        titleKey: 'home.milestones.plan.title',
        title: 'Consultation & Vision',
        bodyKey: 'home.milestones.plan.body',
        body: 'We map your brief, site constraints, and living goals into a clear residential roadmap.',
    },
    {
        step: '02',
        titleKey: 'home.milestones.design.title',
        title: 'Design & Planning',
        bodyKey: 'home.milestones.design.body',
        body: 'Layouts, materials, and schedules are refined so every detail supports comfort and craft.',
    },
    {
        step: '03',
        titleKey: 'home.milestones.execute.title',
        title: 'Build & Supervise',
        bodyKey: 'home.milestones.execute.body',
        body: 'On-site leadership coordinates craftsmanship, procurement, and quality checkpoints daily.',
    },
    {
        step: '04',
        titleKey: 'home.milestones.handover.title',
        title: 'Handover & Care',
        bodyKey: 'home.milestones.handover.body',
        body: 'Structured closeout, documentation, and support so your residence is ready to live in.',
    },
];

const visionItems = [
    {
        icon: Sparkles,
        titleKey: 'home.vision.craft.title',
        title: 'Refined Craftsmanship',
        bodyKey: 'home.vision.craft.body',
        body: 'From structure to finishing, every stage is inspected so living spaces feel precise and enduring.',
    },
    {
        icon: Timer,
        titleKey: 'home.vision.delivery.title',
        title: 'Disciplined Delivery',
        bodyKey: 'home.vision.delivery.body',
        body: 'Transparent milestones and look-ahead planning keep schedule and stakeholders aligned.',
    },
    {
        icon: ShieldCheck,
        titleKey: 'home.vision.trust.title',
        title: 'Trusted Partnership',
        bodyKey: 'home.vision.trust.body',
        body: 'One accountable team from first sketch through handover — clear communication at every step.',
    },
];

const lifestyleItems = [
    {
        titleKey: 'home.lifestyle.light.title',
        title: 'Light-filled living',
        bodyKey: 'home.lifestyle.light.body',
        body: 'Generous openings and bright interiors designed for calm, modern daily life.',
    },
    {
        titleKey: 'home.lifestyle.amenity.title',
        title: 'Shared amenities',
        bodyKey: 'home.lifestyle.amenity.body',
        body: 'Courtyards, gardens, and community spaces that elevate the whole compound.',
    },
    {
        titleKey: 'home.lifestyle.finish.title',
        title: 'Lasting finishes',
        bodyKey: 'home.lifestyle.finish.body',
        body: 'Materials chosen for beauty, durability, and refined residential character.',
    },
];

const whyUs = [
    {
        icon: ShieldCheck,
        titleKey: 'home.safety.card1.title',
        title: 'Safety-First Culture',
        bodyKey: 'home.safety.card1.body',
        body: 'Daily toolbox talks, site audits, and strict PPE compliance ensure safe and uninterrupted operations.',
    },
    {
        icon: Timer,
        titleKey: 'home.safety.card2.title',
        title: 'Schedule Reliability',
        bodyKey: 'home.safety.card2.body',
        body: 'Structured look-ahead planning and procurement control keep milestones on track.',
    },
    {
        icon: ClipboardCheck,
        titleKey: 'home.why.quality.title',
        title: 'Quality Control',
        bodyKey: 'home.why.quality.body',
        body: 'Inspected workmanship at every stage — from foundations to finishing and commissioning.',
    },
    {
        icon: Users,
        titleKey: 'home.why.team.title',
        title: 'Dedicated Leadership',
        bodyKey: 'home.why.team.body',
        body: 'A named project manager and field supervisors keep communication clear from kickoff to handover.',
    },
];

let revealObserver: IntersectionObserver | null = null;

function observeReveals() {
    document
        .querySelectorAll('.lx-reveal:not(.is-visible)')
        .forEach((el) => revealObserver?.observe(el));
}

onMounted(() => {
    revealObserver = new IntersectionObserver(
        (entries) => {
            for (const entry of entries) {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                    revealObserver?.unobserve(entry.target);
                }
            }
        },
        { threshold: 0.12, rootMargin: '0px 0px -40px 0px' },
    );
    observeReveals();
});

watch(
    () => portfolioItems.value.length,
    () => observeReveals(),
);

onUnmounted(() => {
    revealObserver?.disconnect();
    revealObserver = null;
});
</script>

<template>
    <SeoHead
        :title="lt('home.seo.title', 'Home')"
        :description="
            lt(
                'home.hero.lead',
                                'Luxury residential developments — apartments built and finished for modern living.',
            )
        "
        :image="publicStockImages.hero"
        path="/"
        site-name-only
    />

    <div class="construct">
        <PublicSiteHeader on-home />

        <main>
            <!-- Hero: soft-light luxury apartment tower -->
            <section id="top" class="lx-hero">
                <div class="lx-hero-backdrop" aria-hidden="true">
                    <img
                        class="lx-hero-media"
                        :src="publicStockImages.hero"
                        :alt="
                            lt(
                                'home.hero.imageAlt',
                                'Luxury residential apartment tower',
                            )
                        "
                    />
                    <div class="lx-hero-veil" />
                    <div class="lx-hero-edge" />
                </div>

                <div class="construct-container lx-hero-stage">
                    <div class="lx-hero-copy">
                        <span class="lx-hero-accent" aria-hidden="true" />
                        <div class="lx-hero-copy-body">
                            <p class="lx-hero-eyebrow lx-rise">
                                {{
                                    lt(
                                        'home.hero.eyebrow',
                                        'Luxury residential development',
                                    )
                                }}
                            </p>
                            <p class="lx-hero-brand lx-rise lx-rise-delay-1">
                                <template v-if="isEnglish">
                                    <span class="lx-hero-brand-en">{{
                                        brandEn
                                    }}</span>
                                </template>
                                <template v-else>
                                    <span class="lx-hero-brand-en is-fa">{{
                                        brandFa
                                    }}</span>
                                    <span class="lx-hero-brand-ar is-en">{{
                                        brandEn
                                    }}</span>
                                </template>
                            </p>
                            <h1 class="lx-rise lx-rise-delay-2">
                                {{
                                    lt(
                                        'home.hero.title',
                                        'Building residences of lasting elegance.',
                                    )
                                }}
                            </h1>
                            <p class="lx-hero-lead lx-rise lx-rise-delay-3">
                                {{
                                    lt(
                                        'home.hero.lead',
                                        'From concept to turnkey living — bright towers and refined compounds delivered with craft, clarity, and care.',
                                    )
                                }}
                            </p>
                            <div
                                class="lx-hero-actions lx-rise lx-rise-delay-4"
                            >
                                <a href="#contact" class="lx-btn lx-btn-gold">
                                    {{
                                        lt(
                                            'home.hero.ctaPrimary',
                                            'Get in touch',
                                        )
                                    }}
                                    <ArrowRight :size="15" />
                                </a>
                                <a
                                    href="#projects"
                                    class="lx-btn lx-btn-ghost"
                                >
                                    {{
                                        lt(
                                            'home.hero.ctaSecondary',
                                            'View projects',
                                        )
                                    }}
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="lx-hero-base" aria-hidden="true">
                    <div class="lx-hero-base-line" />
                    <div class="lx-hero-scroll">
                        <span>{{ lt('home.hero.scroll', 'Scroll') }}</span>
                    </div>
                </div>
            </section>

            <!-- Stats -->
            <section
                class="lx-stats lx-reveal"
                :aria-label="lt('home.stats.label', 'Key results')"
            >
                <div class="construct-container lx-stats-row">
                    <article>
                        <strong>{{ portfolioItems.length || '—' }}</strong>
                        <span>{{
                            lt('home.stats.projects', 'Featured projects')
                        }}</span>
                    </article>
                    <article>
                        <strong>{{
                            lt('home.stats.yearsValue', '18+')
                        }}</strong>
                        <span>{{
                            lt('home.stats.years', 'Years of experience')
                        }}</span>
                    </article>
                    <article>
                        <strong>{{
                            lt('home.stats.ontimeValue', '100%')
                        }}</strong>
                        <span>{{
                            lt('home.stats.ontime', 'Delivery focus')
                        }}</span>
                    </article>
                    <article>
                        <strong>{{
                            lt('home.stats.qualityValue', 'Premium')
                        }}</strong>
                        <span>{{
                            lt('home.stats.quality', 'Residential craft')
                        }}</span>
                    </article>
                </div>
            </section>

            <!-- About -->
            <section id="about" class="lx-section lx-reveal">
                <div class="construct-container lx-about">
                    <div class="lx-about-media">
                        <img
                            :src="publicStockImages.about"
                            :alt="
                                lt(
                                    'home.about.imageAlt',
                                    'Modern luxury residential architecture',
                                )
                            "
                        />
                        <div class="lx-about-frame" aria-hidden="true" />
                    </div>
                    <div class="lx-about-copy">
                        <span class="lx-kicker">{{
                            lt('home.about.kicker', 'About us')
                        }}</span>
                        <h2>
                            {{
                                lt(
                                    'home.about.title',
                                    'A trusted partner for luxury residential living.',
                                )
                            }}
                        </h2>
                        <p>
                            {{
                                lt(
                                    'home.about.body',
                                    'Shahin Samim Barakzai plans and delivers modern apartment communities with disciplined site control. From early design through final handover, quality, schedule, and living comfort stay visible to every stakeholder.',
                                )
                            }}
                        </p>
                        <ul class="lx-checklist">
                            <li>
                                {{
                                    lt(
                                        'home.hero.point1',
                                        'A dedicated project manager on every site',
                                    )
                                }}
                            </li>
                            <li>
                                {{
                                    lt(
                                        'home.hero.point2',
                                        'Weekly progress reporting with clear milestones',
                                    )
                                }}
                            </li>
                            <li>
                                {{
                                    lt(
                                        'home.hero.point3',
                                        'Documented quality checks before handover',
                                    )
                                }}
                            </li>
                        </ul>
                        <a href="#contact" class="lx-text-link">
                            {{ lt('home.about.cta', 'Speak with our team') }}
                            <ArrowRight :size="15" />
                        </a>
                    </div>
                </div>
            </section>

            <!-- Vision band -->
            <section class="lx-vision lx-reveal">
                <div class="lx-vision-media">
                    <img
                        :src="publicStockImages.vision"
                        :alt="
                            lt(
                                'home.vision.imageAlt',
                                'Refined luxury villa interior and craftsmanship',
                            )
                        "
                    />
                    <div class="lx-vision-veil" />
                </div>
                <div class="construct-container lx-vision-inner">
                    <div class="lx-vision-intro">
                        <span class="lx-kicker lx-kicker-light">{{
                            lt('home.vision.kicker', 'Our vision')
                        }}</span>
                        <h2>
                            {{
                                lt(
                                    'home.vision.title',
                                    'Easing the path to exceptional homes.',
                                )
                            }}
                        </h2>
                        <p>
                            {{
                                lt(
                                    'home.vision.lead',
                                    'Whether you are building a dream residence or investing in a refined compound — we handle design, construction, and delivery as one accountable partner.',
                                )
                            }}
                        </p>
                    </div>
                    <div class="lx-vision-grid">
                        <article
                            v-for="item in visionItems"
                            :key="item.titleKey"
                        >
                            <component :is="item.icon" :size="22" />
                            <h3>{{ lt(item.titleKey, item.title) }}</h3>
                            <p>{{ lt(item.bodyKey, item.body) }}</p>
                        </article>
                    </div>
                </div>
            </section>

            <!-- Services -->
            <section id="services" class="lx-section lx-section-soft lx-reveal">
                <div class="construct-container">
                    <div class="lx-heading">
                        <span class="lx-kicker">{{
                            lt('home.services.kicker', 'Our services')
                        }}</span>
                        <h2>
                            {{
                                lt(
                                    'home.services.title',
                                    'Comprehensive residential solutions',
                                )
                            }}
                        </h2>
                        <p>
                            {{
                                lt(
                                    'home.services.lead',
                                    'Luxury development, design-build, interiors, and amenities — delivered as one seamless experience.',
                                )
                            }}
                        </p>
                    </div>
                    <div class="lx-service-grid">
                        <article
                            v-for="(item, index) in services"
                            :key="item.titleKey"
                            class="lx-service-card"
                        >
                            <div class="lx-service-media">
                                <img
                                    :src="item.image"
                                    :alt="lt(item.titleKey, item.title)"
                                />
                                <span class="lx-service-index">0{{
                                    index + 1
                                }}</span>
                            </div>
                            <div class="lx-service-body">
                                <component :is="item.icon" :size="18" />
                                <h3>{{ lt(item.titleKey, item.title) }}</h3>
                                <p>{{ lt(item.bodyKey, item.body) }}</p>
                            </div>
                        </article>
                    </div>
                </div>
            </section>

            <!-- Process -->
            <section class="lx-process lx-reveal">
                <div class="construct-container">
                    <div class="lx-heading lx-heading-light">
                        <span class="lx-kicker lx-kicker-light">{{
                            lt('home.projects.kicker', 'How we work')
                        }}</span>
                        <h2>
                            {{
                                lt(
                                    'home.projects.title',
                                    'From concept to completion',
                                )
                            }}
                        </h2>
                        <p>
                            {{
                                lt(
                                    'home.projects.lead',
                                    'A clear four-stage journey that keeps cost, quality, and schedule aligned from kickoff to keys.',
                                )
                            }}
                        </p>
                    </div>
                    <div class="lx-process-grid">
                        <article
                            v-for="item in milestones"
                            :key="item.titleKey"
                        >
                            <span class="lx-process-step">{{ item.step }}</span>
                            <h3>{{ lt(item.titleKey, item.title) }}</h3>
                            <p>{{ lt(item.bodyKey, item.body) }}</p>
                        </article>
                    </div>
                </div>
            </section>

            <!-- Portfolio (DB covers OK here only) -->
            <section id="projects" class="lx-section lx-reveal">
                <div class="construct-container">
                    <div class="lx-heading lx-heading-split">
                        <div>
                            <span class="lx-kicker">{{
                                lt('home.portfolio.kicker', 'Portfolio')
                            }}</span>
                            <h2>
                                {{
                                    lt(
                                        'home.portfolio.title',
                                        'Selected residences',
                                    )
                                }}
                            </h2>
                            <p>
                                {{
                                    lt(
                                        'home.portfolio.lead',
                                        'Published projects from our portfolio — planned, built, and finished with residential care.',
                                    )
                                }}
                            </p>
                        </div>
                        <Link href="/work" class="lx-btn lx-btn-outline">
                            {{ lt('home.portfolio.viewAll', 'View all') }}
                            <ArrowRight :size="15" />
                        </Link>
                    </div>

                    <div
                        v-if="portfolioItems.length"
                        class="lx-portfolio-grid"
                    >
                        <Link
                            v-for="item in portfolioItems"
                            :key="item.id"
                            :href="`/portfolio/${item.slug}`"
                            class="lx-portfolio-card"
                        >
                            <div class="lx-portfolio-media">
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
                            </div>
                            <div class="lx-portfolio-meta">
                                <em
                                    v-if="item.category || item.location"
                                >
                                    {{
                                        [item.category, item.location]
                                            .filter(Boolean)
                                            .join(' · ')
                                    }}
                                </em>
                                <strong>{{ item.title }}</strong>
                            </div>
                        </Link>
                    </div>

                    <div v-else class="lx-empty">
                        <p>
                            {{
                                lt(
                                    'home.portfolio.empty',
                                    'New project showcases will appear here soon.',
                                )
                            }}
                        </p>
                    </div>
                </div>
            </section>

            <!-- Lifestyle / amenities — marketing stock only -->
            <section class="lx-lifestyle lx-reveal">
                <div class="lx-lifestyle-split">
                    <div class="lx-lifestyle-copy">
                        <span class="lx-kicker">{{
                            lt('home.lifestyle.kicker', 'Lifestyle')
                        }}</span>
                        <h2>
                            {{
                                lt(
                                    'home.lifestyle.title',
                                    'Spaces designed for elevated everyday living.',
                                )
                            }}
                        </h2>
                        <p>
                            {{
                                lt(
                                    'home.lifestyle.lead',
                                    'Beyond structure — we shape light, amenity, and finish so every residence feels calm, bright, and enduring.',
                                )
                            }}
                        </p>
                        <div class="lx-lifestyle-list">
                            <article
                                v-for="item in lifestyleItems"
                                :key="item.titleKey"
                            >
                                <h3>{{ lt(item.titleKey, item.title) }}</h3>
                                <p>{{ lt(item.bodyKey, item.body) }}</p>
                            </article>
                        </div>
                    </div>
                    <div class="lx-lifestyle-media">
                        <img
                            :src="publicStockImages.lifestyle"
                            :alt="
                                lt(
                                    'home.lifestyle.imageAlt',
                                    'Bright luxury residential interior',
                                )
                            "
                        />
                        <img
                            class="lx-lifestyle-secondary"
                            :src="publicStockImages.amenity"
                            :alt="
                                lt(
                                    'home.lifestyle.amenityAlt',
                                    'Residential courtyard and outdoor amenity',
                                )
                            "
                        />
                    </div>
                </div>
            </section>

            <!-- Why us -->
            <section
                id="why"
                class="lx-section lx-section-soft lx-reveal"
            >
                <div class="construct-container">
                    <div class="lx-heading">
                        <span class="lx-kicker">{{
                            lt('home.why.kicker', 'Why work with us')
                        }}</span>
                        <h2>
                            {{
                                lt(
                                    'home.why.title',
                                    'Safety, schedule, and craftsmanship',
                                )
                            }}
                        </h2>
                    </div>
                    <div class="lx-why-grid">
                        <article
                            v-for="item in whyUs"
                            :key="item.titleKey"
                        >
                            <component :is="item.icon" :size="22" />
                            <h3>{{ lt(item.titleKey, item.title) }}</h3>
                            <p>{{ lt(item.bodyKey, item.body) }}</p>
                        </article>
                    </div>
                </div>
            </section>

            <!-- Contact -->
            <section id="contact" class="lx-section lx-reveal">
                <div class="construct-container">
                    <div class="lx-heading">
                        <span class="lx-kicker">{{
                            lt('home.contact.kicker', 'Contact')
                        }}</span>
                        <h2>
                            {{
                                lt(
                                    'home.contact.title',
                                    'Get in touch with us',
                                )
                            }}
                        </h2>
                        <p>
                            {{
                                lt(
                                    'home.contact.lead',
                                    'Share a project brief, preferred location, or drawings — we will follow up with clear next steps.',
                                )
                            }}
                        </p>
                    </div>

                    <div class="lx-contact">
                        <aside class="lx-contact-aside">
                            <a
                                v-if="hasRealEmail"
                                class="lx-contact-row"
                                :href="`mailto:${contactEmail}`"
                            >
                                <span><Mail :size="18" /></span>
                                <div>
                                    <em>{{
                                        lt('home.contact.emailLabel', 'Email')
                                    }}</em>
                                    <strong>{{ contactEmail }}</strong>
                                </div>
                            </a>
                            <div v-else class="lx-contact-row">
                                <span><Mail :size="18" /></span>
                                <div>
                                    <em>{{
                                        lt('home.contact.emailLabel', 'Email')
                                    }}</em>
                                    <strong>{{ contactEmail }}</strong>
                                </div>
                            </div>
                            <a
                                v-if="hasRealPhone"
                                class="lx-contact-row"
                                :href="`tel:${contactPhone.replace(/\s+/g, '')}`"
                            >
                                <span><Phone :size="18" /></span>
                                <div>
                                    <em>{{
                                        lt('home.contact.phoneLabel', 'Phone')
                                    }}</em>
                                    <strong>{{ contactPhone }}</strong>
                                </div>
                            </a>
                            <div v-else class="lx-contact-row">
                                <span><Phone :size="18" /></span>
                                <div>
                                    <em>{{
                                        lt('home.contact.phoneLabel', 'Phone')
                                    }}</em>
                                    <strong>{{ contactPhone }}</strong>
                                </div>
                            </div>
                            <div class="lx-contact-row">
                                <span><MapPin :size="18" /></span>
                                <div>
                                    <em>{{
                                        lt(
                                            'home.contact.officeLabel',
                                            'Address',
                                        )
                                    }}</em>
                                    <strong>{{ contactAddress }}</strong>
                                </div>
                            </div>
                            <p class="lx-contact-note">
                                {{
                                    lt(
                                        'home.contact.note',
                                        'Typical response within one business day.',
                                    )
                                }}
                            </p>
                        </aside>

                        <form
                            class="lx-contact-form"
                            @submit.prevent="submitContact"
                        >
                            <div class="lx-contact-form-head">
                                <h3>
                                    {{
                                        lt(
                                            'home.contact.formTitle',
                                            'Send a message',
                                        )
                                    }}
                                </h3>
                                <p>
                                    {{
                                        lt(
                                            'home.contact.formLead',
                                            'We will review your note and reply with clear next steps.',
                                        )
                                    }}
                                </p>
                            </div>

                            <div
                                v-if="flashSuccess"
                                class="lx-contact-success"
                                role="status"
                            >
                                <CheckCircle2 :size="18" />
                                <p>{{ flashSuccess }}</p>
                            </div>

                            <div class="lx-contact-fields">
                                <label>
                                    <span>{{
                                        lt('home.contact.form.name', 'Name')
                                    }}</span>
                                    <input
                                        v-model="contactForm.name"
                                        type="text"
                                        name="name"
                                        autocomplete="name"
                                        required
                                    />
                                    <em v-if="contactForm.errors.name">{{
                                        contactForm.errors.name
                                    }}</em>
                                </label>
                                <label>
                                    <span>{{
                                        lt('home.contact.form.phone', 'Phone')
                                    }}</span>
                                    <input
                                        v-model="contactForm.phone"
                                        type="tel"
                                        name="phone"
                                        autocomplete="tel"
                                    />
                                    <em v-if="contactForm.errors.phone">{{
                                        contactForm.errors.phone
                                    }}</em>
                                </label>
                                <label class="lx-span-2">
                                    <span>{{
                                        lt('home.contact.form.email', 'Email')
                                    }}</span>
                                    <input
                                        v-model="contactForm.email"
                                        type="email"
                                        name="email"
                                        autocomplete="email"
                                        required
                                    />
                                    <em v-if="contactForm.errors.email">{{
                                        contactForm.errors.email
                                    }}</em>
                                </label>
                                <label class="lx-span-2">
                                    <span>{{
                                        lt(
                                            'home.contact.form.message',
                                            'Message',
                                        )
                                    }}</span>
                                    <textarea
                                        v-model="contactForm.message"
                                        name="message"
                                        rows="5"
                                        required
                                    />
                                    <em v-if="contactForm.errors.message">{{
                                        contactForm.errors.message
                                    }}</em>
                                </label>
                            </div>

                            <button
                                type="submit"
                                class="lx-btn lx-btn-gold"
                                :disabled="contactForm.processing"
                            >
                                {{
                                    contactForm.processing
                                        ? lt(
                                              'home.contact.form.sending',
                                              'Sending…',
                                          )
                                        : lt(
                                              'home.contact.form.submit',
                                              'Send message',
                                          )
                                }}
                                <ArrowRight :size="15" />
                            </button>
                        </form>
                    </div>
                </div>
            </section>

            <!-- CTA — marketing stock only -->
            <section class="lx-cta lx-reveal">
                <img
                    class="lx-cta-media"
                    :src="publicStockImages.cta"
                    :alt="
                        lt(
                            'home.cta.imageAlt',
                            'Premium city skyline at golden hour',
                        )
                    "
                />
                <div class="lx-cta-veil" />
                <div class="construct-container lx-cta-inner">
                    <span class="lx-kicker lx-kicker-light">{{
                        lt('home.cta.kicker', 'Begin your residence')
                    }}</span>
                    <h2>
                        {{
                            lt(
                                'home.cta.title',
                                'Let us shape your next home with elegance and precision.',
                            )
                        }}
                    </h2>
                    <a href="#contact" class="lx-btn lx-btn-light">
                        {{ lt('home.cta.button', 'Contact our team') }}
                        <ArrowRight :size="15" />
                    </a>
                </div>
            </section>
        </main>

        <PublicSiteFooter />
    </div>
</template>
