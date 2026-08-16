<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import {
    ArrowRight,
    Building2,
    Check,
    HardHat,
    Hammer,
    LayoutGrid,
    Menu,
    ShieldCheck,
    Timer,
    X,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import LanguageSwitcher from '@/components/LanguageSwitcher.vue';
import { useTranslations } from '@/composables/useTranslations';
import { dashboard, login, register } from '@/routes';
import './welcome/welcome-premium.css';

const page = usePage();
const { t } = useTranslations();
const appName = computed(() => page.props.name || 'Yaqeen Construction');
const isAuthenticated = computed(() => !!page.props.auth.user);
const mobileOpen = ref(false);

function lt(key: string, fallback: string): string {
    const value = t(key);

    return value === key ? fallback : value;
}

const navItems = computed(() => [
    { href: '#services', label: lt('home.nav.services', 'Services') },
    { href: '#projects', label: lt('home.nav.projects', 'Projects') },
    { href: '#safety', label: lt('home.nav.safety', 'Safety') },
    { href: '#contact', label: lt('home.nav.contact', 'Contact') },
]);

const services = [
    {
        icon: Building2,
        titleKey: 'home.services.commercial.title',
        title: 'Commercial Construction',
        bodyKey: 'home.services.commercial.body',
        body: 'End-to-end construction for offices, retail spaces, and industrial sites with predictable quality and timelines.',
        image: 'https://images.unsplash.com/photo-1503387762-592deb58ef4e?auto=format&fit=crop&w=2400&q=80',
    },
    {
        icon: LayoutGrid,
        titleKey: 'home.services.design.title',
        title: 'Design & Build',
        bodyKey: 'home.services.design.body',
        body: 'Integrated design and execution teams that reduce rework, align budgets early, and speed up approvals.',
        image: 'https://images.unsplash.com/photo-1489515217757-5fd1be406fef?auto=format&fit=crop&w=2400&q=80',
    },
    {
        icon: HardHat,
        titleKey: 'home.services.renovation.title',
        title: 'Renovation & Fit-Out',
        bodyKey: 'home.services.renovation.body',
        body: 'Fast, low-disruption upgrades for active facilities, including MEP, interiors, and compliance updates.',
        image: 'https://images.unsplash.com/photo-1541888946425-d81bb19240f5?auto=format&fit=crop&w=2400&q=80',
    },
];

const milestones = [
    {
        step: '01',
        titleKey: 'home.milestones.plan.title',
        title: 'Planning & Estimation',
        bodyKey: 'home.milestones.plan.body',
        body: 'Detailed BOQ, scheduling, and risk mapping before field work starts.',
    },
    {
        step: '02',
        titleKey: 'home.milestones.execute.title',
        title: 'Execution & Supervision',
        bodyKey: 'home.milestones.execute.body',
        body: 'On-site teams coordinate labor, procurement, and quality checkpoints daily.',
    },
    {
        step: '03',
        titleKey: 'home.milestones.handover.title',
        title: 'Handover & Support',
        bodyKey: 'home.milestones.handover.body',
        body: 'Structured closeout, documentation, and post-delivery support for smooth operations.',
    },
];

const projectImages = [
    {
        src: 'https://images.unsplash.com/photo-1504307651254-35680f356dfd?auto=format&fit=crop&w=2400&q=80',
        titleKey: 'home.gallery.highrise',
        title: 'High-Rise Development',
    },
    {
        src: 'https://images.unsplash.com/photo-1581092160607-ee22621dd758?auto=format&fit=crop&w=2400&q=80',
        titleKey: 'home.gallery.industrial',
        title: 'Industrial Facility',
    },
    {
        src: 'https://images.unsplash.com/photo-1621905252507-b35492cc74b4?auto=format&fit=crop&w=2400&q=80',
        titleKey: 'home.gallery.infrastructure',
        title: 'Infrastructure Upgrade',
    },
];
</script>

<template>
    <Head :title="`Home | ${appName}`" />

    <div class="construct">
        <header class="construct-nav">
            <div class="construct-container construct-nav-inner">
                <a href="#top" class="construct-brand">
                    <span><Hammer :size="16" /></span>
                    {{ appName }}
                </a>

                <nav class="construct-links" aria-label="Primary navigation">
                    <a
                        v-for="item in navItems"
                        :key="item.href"
                        :href="item.href"
                    >
                        {{ item.label }}
                    </a>
                </nav>

                <div class="construct-nav-actions">
                    <LanguageSwitcher />
                    <Link
                        v-if="isAuthenticated"
                        :href="dashboard()"
                        class="construct-login"
                    >
                        {{ t('Dashboard') }}
                    </Link>
                    <Link
                        v-else
                        :href="login()"
                        class="construct-login"
                    >
                        {{ t('Log in') }}
                    </Link>
                    <button
                        type="button"
                        class="construct-menu"
                        :aria-label="mobileOpen ? 'Close menu' : 'Open menu'"
                        @click="mobileOpen = !mobileOpen"
                    >
                        <X v-if="mobileOpen" :size="18" />
                        <Menu v-else :size="18" />
                    </button>
                </div>
            </div>

            <nav v-if="mobileOpen" class="construct-mobile">
                <a
                    v-for="item in navItems"
                    :key="item.href"
                    :href="item.href"
                    @click="mobileOpen = false"
                >
                    {{ item.label }}
                </a>
            </nav>
        </header>

        <main>
            <section id="top" class="construct-hero">
                <img
                    class="construct-hero-bg"
                    src="https://images.unsplash.com/photo-1541976590-713941681591?auto=format&fit=crop&w=2400&q=80"
                    alt=""
                    aria-hidden="true"
                />
                <div class="construct-overlay" />

                <div class="construct-container construct-hero-grid">
                    <div class="construct-copy">
                        <span class="construct-eyebrow">{{
                            lt(
                                'home.hero.badge',
                                'Trusted Construction Partner',
                            )
                        }}</span>
                        <h1>
                            {{
                                lt(
                                    'home.hero.title',
                                    "Building Tomorrow's Infrastructure with Precision.",
                                )
                            }}
                        </h1>
                        <p>
                            {{
                                lt(
                                    'home.hero.lead',
                                    'We deliver high-performance construction projects with strict safety standards, transparent execution, and on-time handover.',
                                )
                            }}
                        </p>
                        <ul class="construct-hero-points">
                            <li>
                                <Check :size="15" />
                                {{
                                    lt(
                                        'home.hero.point1',
                                        'Dedicated project manager on every site',
                                    )
                                }}
                            </li>
                            <li>
                                <Check :size="15" />
                                {{
                                    lt(
                                        'home.hero.point2',
                                        'Weekly progress reporting with clear milestones',
                                    )
                                }}
                            </li>
                            <li>
                                <Check :size="15" />
                                {{
                                    lt(
                                        'home.hero.point3',
                                        'Quality-first delivery with certified teams',
                                    )
                                }}
                            </li>
                        </ul>
                        <div class="construct-actions">
                            <Link
                                v-if="isAuthenticated"
                                :href="dashboard()"
                                class="construct-button construct-button-primary"
                            >
                                {{ t('Open dashboard') }}
                                <ArrowRight :size="15" />
                            </Link>
                            <Link
                                v-else
                                :href="login()"
                                class="construct-button construct-button-primary"
                            >
                                {{ t('Log in') }}
                                <ArrowRight :size="15" />
                            </Link>
                            <a
                                href="#projects"
                                class="construct-button construct-button-ghost"
                            >
                                {{
                                    lt(
                                        'home.hero.ctaSecondary',
                                        'View Projects',
                                    )
                                }}
                            </a>
                        </div>
                    </div>

                    <aside class="construct-hero-panel">
                        <div class="construct-hero-photo">
                            <img
                                src="https://images.unsplash.com/photo-1503387762-592deb58ef4e?auto=format&fit=crop&w=1600&q=80"
                                :alt="
                                    lt(
                                        'home.hero.imageAlt',
                                        'Modern construction site',
                                    )
                                "
                            />
                        </div>
                        <div class="construct-hero-note">
                            <strong>{{
                                lt(
                                    'home.hero.tagTitle',
                                    '24/7 Site Supervision',
                                )
                            }}</strong>
                            <span>{{
                                lt(
                                    'home.hero.tagBody',
                                    'Real-time coordination from foundation to handover',
                                )
                            }}</span>
                        </div>
                    </aside>
                </div>
            </section>

            <section class="construct-stats-wrap" aria-label="Key results">
                <div class="construct-container construct-stats">
                    <article>
                        <strong>250+</strong>
                        <span>{{
                            lt('home.stats.projects', 'Projects Delivered')
                        }}</span>
                    </article>
                    <article>
                        <strong>18</strong>
                        <span>{{
                            lt('home.stats.years', 'Years of Experience')
                        }}</span>
                    </article>
                    <article>
                        <strong>99%</strong>
                        <span>{{
                            lt('home.stats.ontime', 'On-Time Completion')
                        }}</span>
                    </article>
                    <div class="construct-trust">
                        <span>{{
                            lt('home.hero.trust1', 'ISO-Aligned Processes')
                        }}</span>
                        <span>{{
                            lt('home.hero.trust2', 'HSE Compliance')
                        }}</span>
                        <span>{{
                            lt('home.hero.trust3', 'Transparent Contracting')
                        }}</span>
                    </div>
                </div>
            </section>

            <section id="services" class="construct-section">
                <div class="construct-container">
                    <div class="construct-heading">
                        <span>{{
                            lt('home.services.kicker', 'Our Services')
                        }}</span>
                        <h2>
                            {{
                                lt(
                                    'home.services.title',
                                    'Full-Scope Construction Solutions',
                                )
                            }}
                        </h2>
                    </div>
                    <div class="construct-cards">
                        <article
                            v-for="item in services"
                            :key="item.titleKey"
                        >
                            <div class="construct-card-media">
                                <img :src="item.image" :alt="item.title" />
                            </div>
                            <div class="construct-card-body">
                                <component :is="item.icon" :size="20" />
                                <h3>{{ lt(item.titleKey, item.title) }}</h3>
                                <p>{{ lt(item.bodyKey, item.body) }}</p>
                            </div>
                        </article>
                    </div>
                </div>
            </section>

            <section
                id="projects"
                class="construct-section construct-section-dark"
            >
                <div class="construct-container construct-project-grid">
                    <div>
                        <span class="construct-kicker">{{
                            lt('home.projects.kicker', 'How We Deliver')
                        }}</span>
                        <h2>
                            {{
                                lt(
                                    'home.projects.title',
                                    'Disciplined Project Lifecycle',
                                )
                            }}
                        </h2>
                        <p>
                            {{
                                lt(
                                    'home.projects.lead',
                                    'Every project follows a controlled process that keeps cost, quality, and schedule aligned from kickoff to handover.',
                                )
                            }}
                        </p>
                    </div>
                    <div class="construct-milestones">
                        <article
                            v-for="item in milestones"
                            :key="item.titleKey"
                        >
                            <span>{{ item.step }}</span>
                            <div>
                                <h3>{{ lt(item.titleKey, item.title) }}</h3>
                                <p>{{ lt(item.bodyKey, item.body) }}</p>
                            </div>
                        </article>
                    </div>
                </div>
                <div class="construct-container construct-gallery">
                    <article
                        v-for="item in projectImages"
                        :key="item.titleKey"
                    >
                        <img :src="item.src" :alt="item.title" />
                        <span>{{ lt(item.titleKey, item.title) }}</span>
                    </article>
                </div>
            </section>

            <section id="safety" class="construct-section">
                <div class="construct-container construct-safety">
                    <div class="construct-safety-card">
                        <ShieldCheck :size="22" />
                        <h3>
                            {{
                                lt(
                                    'home.safety.card1.title',
                                    'Safety-First Culture',
                                )
                            }}
                        </h3>
                        <p>
                            {{
                                lt(
                                    'home.safety.card1.body',
                                    'Daily toolbox talks, site audits, and strict PPE compliance ensure safe and uninterrupted operations.',
                                )
                            }}
                        </p>
                    </div>
                    <div class="construct-safety-card">
                        <Timer :size="22" />
                        <h3>
                            {{
                                lt(
                                    'home.safety.card2.title',
                                    'Schedule Reliability',
                                )
                            }}
                        </h3>
                        <p>
                            {{
                                lt(
                                    'home.safety.card2.body',
                                    'Structured look-ahead planning and procurement control keep milestones on track and stakeholders informed.',
                                )
                            }}
                        </p>
                    </div>
                </div>
            </section>

            <section id="contact" class="construct-cta">
                <img
                    class="construct-cta-bg"
                    src="https://images.unsplash.com/photo-1504307651254-35680f356dfd?auto=format&fit=crop&w=2400&q=80"
                    alt=""
                    aria-hidden="true"
                />
                <div class="construct-cta-shade" />
                <div class="construct-container construct-cta-inner">
                    <div>
                        <span>{{
                            lt('home.cta.kicker', 'Start Your Next Build')
                        }}</span>
                        <h2>
                            {{
                                lt(
                                    'home.cta.title',
                                    'Let us turn your construction goals into finished assets.',
                                )
                            }}
                        </h2>
                    </div>
                    <Link
                        v-if="isAuthenticated"
                        :href="dashboard()"
                        class="construct-button construct-button-light"
                    >
                        {{ t('Open dashboard') }}
                        <ArrowRight :size="15" />
                    </Link>
                    <Link
                        v-else
                        :href="register()"
                        class="construct-button construct-button-light"
                    >
                        {{ lt('home.cta.button', 'Contact Our Team') }}
                        <ArrowRight :size="15" />
                    </Link>
                </div>
            </section>
        </main>
    </div>
</template>
