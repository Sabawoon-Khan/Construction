<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import {
    ArrowRight,
    BarChart3,
    Bot,
    Boxes,
    Check,
    ChevronDown,
    Code2,
    DatabaseBackup,
    FileText,
    Globe2,
    Languages,
    LayoutDashboard,
    Menu,
    ShieldCheck,
    Sparkles,
    Terminal,
    Users,
    WandSparkles,
    X,
    Zap,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import LanguageSwitcher from '@/components/LanguageSwitcher.vue';
import { useTranslations } from '@/composables/useTranslations';
import { dashboard, login, register } from '@/routes';
import { aiCapabilities, faqs, featureGroups } from './welcome/landing-content';
import './welcome/welcome-premium.css';

const page = usePage();
const { t } = useTranslations();

const appName = computed(() => page.props.name || 'Yaqeen Kit');
const isAuthenticated = computed(() => !!page.props.auth.user);
const mobileOpen = ref(false);
const activeFeature = ref(0);
const openFaq = ref(0);
const billing = ref<'once' | 'annual'>('once');

function lt(key: string, fallback: string): string {
    const value = t(key);

    return value === key ? fallback : value;
}

const featureIcons = [
    ShieldCheck,
    Languages,
    LayoutDashboard,
    DatabaseBackup,
    Code2,
    Zap,
];

const features = computed(() =>
    featureGroups.map((group, index) => ({
        title: lt(group.titleKey, group.title),
        lead: lt(group.leadKey, group.lead),
        icon: featureIcons[index] ?? Boxes,
        items: group.items.map((item) => ({
            title: lt(item.titleKey, item.title),
            body: lt(item.bodyKey, item.body),
        })),
    })),
);

const aiIcons = [WandSparkles, FileText, Bot, BarChart3, FileText, Boxes];
const aiItems = computed(() =>
    aiCapabilities.slice(0, 4).map((item, index) => ({
        title: lt(item.titleKey, item.title),
        body: lt(item.bodyKey, item.body),
        icon: aiIcons[index] ?? Sparkles,
    })),
);

const faqItems = computed(() =>
    faqs.map((item) => ({
        question: lt(item.qKey, item.q),
        answer: lt(item.aKey, item.a),
    })),
);

const benefits = computed(() => [
    {
        icon: ShieldCheck,
        title: lt(
            'landing.benefit.secure.title',
            'Production-ready from day one',
        ),
        body: lt(
            'landing.benefit.secure.body',
            'Authentication, permissions, audit trails, backups and session security are already wired.',
        ),
    },
    {
        icon: Code2,
        title: lt(
            'landing.benefit.fast.title',
            'Build only what makes you different',
        ),
        body: lt(
            'landing.benefit.fast.body',
            'Scaffold your domain modules instead of rebuilding the same infrastructure for every product.',
        ),
    },
    {
        icon: Globe2,
        title: lt('landing.benefit.global.title', 'Ready for every user'),
        body: lt(
            'landing.benefit.global.body',
            'Multilingual and RTL interfaces, localized calendars, offline workflows and responsive screens.',
        ),
    },
]);

const plans = computed(() => {
    const annual = billing.value === 'annual';

    return [
        {
            name: lt('landing.plan.open.name', 'Open'),
            price: lt('landing.plan.open.price', 'Free'),
            period: '',
            description: lt(
                'landing.plan.open.blurb',
                'The self-hosted foundation for independent builders.',
            ),
            href: isAuthenticated.value ? dashboard() : register(),
            cta: isAuthenticated.value
                ? t('Open dashboard')
                : lt('landing.plan.open.cta', 'Start free'),
            featured: false,
            items: [
                lt('landing.plan.open.f1', 'Authentication and RBAC'),
                lt('landing.plan.open.f2', 'Multilingual admin shell'),
                lt('landing.plan.open.f3', 'Backups, audit and notifications'),
                lt('landing.plan.open.f4', 'Scaffold, themes and lookups'),
            ],
        },
        {
            name: lt('landing.plan.builder.name', 'Builder'),
            price: annual
                ? lt('landing.plan.builder.priceAnnual', '$129')
                : lt('landing.plan.builder.priceOnce', '$1,490'),
            period: annual
                ? lt('landing.plan.mo', '/month')
                : lt('landing.plan.once', 'one-time'),
            description: lt(
                'landing.plan.builder.blurb',
                'The complete product stack for growing teams.',
            ),
            href: isAuthenticated.value ? dashboard() : register(),
            cta: lt('landing.plan.builder.cta', 'Choose Builder'),
            featured: true,
            items: [
                lt('landing.plan.builder.f1', 'Everything in Open'),
                lt('landing.plan.builder.f2', 'AI assist, chat and analytics'),
                lt('landing.plan.builder.f3', 'Offline PWA and conflict sync'),
                lt(
                    'landing.plan.builder.f4',
                    'API and integration foundations',
                ),
            ],
        },
        {
            name: lt('landing.plan.org.name', 'Organization'),
            price: lt('landing.plan.org.price', 'Custom'),
            period: '',
            description: lt(
                'landing.plan.org.blurb',
                'Implementation help for serious deployments.',
            ),
            href: isAuthenticated.value ? dashboard() : login(),
            cta: lt('landing.plan.org.cta', 'Contact us'),
            featured: false,
            items: [
                lt('landing.plan.org.f1', 'Everything in Builder'),
                lt('landing.plan.org.f2', 'Architecture and onboarding'),
                lt('landing.plan.org.f3', 'Brand and deployment setup'),
                lt('landing.plan.org.f4', 'Multi-project rights and SLA'),
            ],
        },
    ];
});

const navItems = computed(() => [
    { href: '#platform', label: lt('landing.nav.platform', 'Platform') },
    { href: '#ai', label: lt('landing.nav.ai', 'AI') },
    { href: '#pricing', label: lt('landing.nav.pricing', 'Pricing') },
    { href: '#faq', label: lt('landing.nav.faq', 'FAQ') },
]);
</script>

<template>
    <Head :title="lt('landing.title', 'The foundation for your next MIS')" />

    <div class="clean">
        <header class="clean-nav">
            <div class="clean-container clean-nav-inner">
                <a href="#top" class="clean-brand">
                    <span><Boxes :size="17" /></span>
                    {{ appName }}
                </a>

                <nav class="clean-links" aria-label="Primary navigation">
                    <a
                        v-for="item in navItems"
                        :key="item.href"
                        :href="item.href"
                    >
                        {{ item.label }}
                    </a>
                </nav>

                <div class="clean-nav-actions">
                    <LanguageSwitcher />
                    <Link
                        v-if="isAuthenticated"
                        :href="dashboard()"
                        class="clean-login"
                    >
                        {{ t('Dashboard') }}
                    </Link>
                    <template v-else>
                        <Link :href="login()" class="clean-login">{{
                            t('Log in')
                        }}</Link>
                        <Link
                            :href="register()"
                            class="clean-button clean-button-small"
                        >
                            {{ lt('landing.nav.start', 'Start free') }}
                        </Link>
                    </template>
                    <button
                        type="button"
                        class="clean-menu"
                        :aria-label="mobileOpen ? 'Close menu' : 'Open menu'"
                        @click="mobileOpen = !mobileOpen"
                    >
                        <X v-if="mobileOpen" :size="19" />
                        <Menu v-else :size="19" />
                    </button>
                </div>
            </div>

            <nav v-if="mobileOpen" class="clean-mobile">
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
            <section id="top" class="clean-hero">
                <div class="clean-hero-glow" aria-hidden="true" />
                <div class="clean-star clean-star-left" aria-hidden="true" />
                <div class="clean-star clean-star-right" aria-hidden="true" />
                <div class="clean-orb" aria-hidden="true" />
                <div class="clean-container">
                    <div class="clean-hero-copy">
                        <div class="clean-eyebrow">
                            <Sparkles :size="13" />
                            {{
                                lt(
                                    'landing.hero.badge',
                                    'The next generation MIS starter',
                                )
                            }}
                        </div>
                        <h1>
                            {{
                                lt('landing.hero.line1', 'Take control of your')
                            }}
                            <span>{{
                                lt('landing.hero.line2', 'digital growth.')
                            }}</span>
                        </h1>
                        <p>
                            {{
                                lt(
                                    'landing.hero.lead',
                                    'Launch secure, intelligent management systems faster with a complete Laravel and Vue foundation built for ambitious teams.',
                                )
                            }}
                        </p>
                        <div class="clean-hero-actions">
                            <Link
                                v-if="isAuthenticated"
                                :href="dashboard()"
                                class="clean-button clean-button-primary"
                            >
                                {{ t('Open dashboard') }}
                                <ArrowRight :size="16" />
                            </Link>
                            <template v-else>
                                <Link
                                    :href="register()"
                                    class="clean-button clean-button-primary"
                                >
                                    {{
                                        lt(
                                            'landing.hero.cta',
                                            'Start building today',
                                        )
                                    }}
                                    <ArrowRight :size="16" />
                                </Link>
                                <a
                                    href="#platform"
                                    class="clean-secondary-link"
                                >
                                    {{
                                        lt(
                                            'landing.hero.explore',
                                            'Explore the platform',
                                        )
                                    }}
                                </a>
                            </template>
                        </div>
                    </div>

                    <div class="clean-product">
                        <div class="clean-browser">
                            <div class="clean-browser-bar">
                                <div><i /><i /><i /></div>
                                <span>{{ appName }}</span>
                                <div class="clean-browser-user">YK</div>
                            </div>
                            <div class="clean-app">
                                <aside class="clean-sidebar">
                                    <div class="clean-sidebar-logo">
                                        <Boxes :size="18" />
                                    </div>
                                    <span class="active"
                                        ><LayoutDashboard :size="16"
                                    /></span>
                                    <span><Users :size="16" /></span>
                                    <span><BarChart3 :size="16" /></span>
                                    <span><FileText :size="16" /></span>
                                </aside>

                                <div class="clean-dashboard">
                                    <header>
                                        <div>
                                            <small>{{
                                                lt(
                                                    'landing.preview.eyebrow',
                                                    'Workspace',
                                                )
                                            }}</small>
                                            <h2>
                                                {{
                                                    lt(
                                                        'landing.preview.title',
                                                        'Operations overview',
                                                    )
                                                }}
                                            </h2>
                                        </div>
                                        <button type="button">
                                            <Sparkles :size="13" />
                                            {{
                                                lt(
                                                    'landing.preview.ai',
                                                    'Ask AI',
                                                )
                                            }}
                                        </button>
                                    </header>

                                    <div class="clean-metrics">
                                        <article>
                                            <small>{{
                                                lt(
                                                    'landing.preview.users',
                                                    'Active users',
                                                )
                                            }}</small>
                                            <strong>2,438</strong>
                                            <em>+12.4%</em>
                                        </article>
                                        <article>
                                            <small>{{
                                                lt(
                                                    'landing.preview.records',
                                                    'Records synced',
                                                )
                                            }}</small>
                                            <strong>18,204</strong>
                                            <em>+8.1%</em>
                                        </article>
                                        <article>
                                            <small>{{
                                                lt(
                                                    'landing.preview.uptime',
                                                    'System health',
                                                )
                                            }}</small>
                                            <strong>99.9%</strong>
                                            <em>{{
                                                lt(
                                                    'landing.preview.live',
                                                    'Healthy',
                                                )
                                            }}</em>
                                        </article>
                                    </div>

                                    <div class="clean-dashboard-bottom">
                                        <article class="clean-chart">
                                            <div>
                                                <strong>{{
                                                    lt(
                                                        'landing.preview.activity',
                                                        'Activity',
                                                    )
                                                }}</strong>
                                                <small>{{
                                                    lt(
                                                        'landing.preview.days',
                                                        'Last 30 days',
                                                    )
                                                }}</small>
                                            </div>
                                            <svg
                                                viewBox="0 0 500 150"
                                                aria-hidden="true"
                                            >
                                                <defs>
                                                    <linearGradient
                                                        id="cleanArea"
                                                        x1="0"
                                                        y1="0"
                                                        x2="0"
                                                        y2="1"
                                                    >
                                                        <stop
                                                            offset="0%"
                                                            stop-color="#f26b21"
                                                            stop-opacity=".18"
                                                        />
                                                        <stop
                                                            offset="100%"
                                                            stop-color="#0d9488"
                                                            stop-opacity="0"
                                                        />
                                                    </linearGradient>
                                                </defs>
                                                <path
                                                    class="area"
                                                    d="M0 130 C60 126 80 90 130 100 S205 126 245 72 S325 62 360 78 S430 34 500 24 V150 H0Z"
                                                />
                                                <path
                                                    class="line"
                                                    d="M0 130 C60 126 80 90 130 100 S205 126 245 72 S325 62 360 78 S430 34 500 24"
                                                />
                                            </svg>
                                        </article>

                                        <article class="clean-activity">
                                            <div>
                                                <strong>{{
                                                    lt(
                                                        'landing.preview.events',
                                                        'Recent activity',
                                                    )
                                                }}</strong>
                                            </div>
                                            <ul>
                                                <li>
                                                    <span
                                                        ><ShieldCheck
                                                            :size="13"
                                                    /></span>
                                                    <div>
                                                        <strong>{{
                                                            lt(
                                                                'landing.preview.login',
                                                                'Secure login',
                                                            )
                                                        }}</strong
                                                        ><small>2m</small>
                                                    </div>
                                                </li>
                                                <li>
                                                    <span
                                                        ><Zap :size="13"
                                                    /></span>
                                                    <div>
                                                        <strong>{{
                                                            lt(
                                                                'landing.preview.sync',
                                                                'Records synced',
                                                            )
                                                        }}</strong
                                                        ><small>8m</small>
                                                    </div>
                                                </li>
                                                <li>
                                                    <span
                                                        ><Bot :size="13"
                                                    /></span>
                                                    <div>
                                                        <strong>{{
                                                            lt(
                                                                'landing.preview.report',
                                                                'AI report ready',
                                                            )
                                                        }}</strong
                                                        ><small>14m</small>
                                                    </div>
                                                </li>
                                            </ul>
                                        </article>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="clean-floating-note">
                            <span><Sparkles :size="14" /></span>
                            <div>
                                <small>{{
                                    lt('landing.float.ai', 'AI assistant')
                                }}</small>
                                <strong>{{
                                    lt(
                                        'landing.float.ready',
                                        'Your report is ready',
                                    )
                                }}</strong>
                            </div>
                        </div>
                    </div>

                    <div class="clean-built-with">
                        <span>{{
                            lt('landing.builtwith', 'Built on a modern stack')
                        }}</span>
                        <strong>Laravel</strong>
                        <strong>Vue</strong>
                        <strong>Inertia</strong>
                        <strong>TypeScript</strong>
                        <strong>Tailwind</strong>
                    </div>
                </div>
            </section>

            <section class="clean-benefits">
                <div class="clean-container clean-benefit-grid">
                    <article v-for="item in benefits" :key="item.title">
                        <component :is="item.icon" :size="20" />
                        <h2>{{ item.title }}</h2>
                        <p>{{ item.body }}</p>
                    </article>
                </div>
            </section>

            <section id="platform" class="clean-platform">
                <div class="clean-container">
                    <div class="clean-section-heading">
                        <span>{{
                            lt('landing.platform.kicker', 'Platform')
                        }}</span>
                        <h2>
                            {{
                                lt(
                                    'landing.platform.title',
                                    'Everything your product needs underneath.',
                                )
                            }}
                        </h2>
                        <p>
                            {{
                                lt(
                                    'landing.platform.lead',
                                    'A focused foundation that handles security, administration and operations while your team builds the workflows users actually need.',
                                )
                            }}
                        </p>
                    </div>

                    <div class="clean-feature-shell">
                        <nav aria-label="Feature categories">
                            <button
                                v-for="(feature, index) in features"
                                :key="feature.title"
                                type="button"
                                :class="{ active: activeFeature === index }"
                                @click="activeFeature = index"
                            >
                                <component :is="feature.icon" :size="18" />
                                <span>{{ feature.title }}</span>
                                <ArrowRight :size="15" />
                            </button>
                        </nav>

                        <div class="clean-feature-panel">
                            <div class="clean-feature-title">
                                <span>
                                    <component
                                        :is="features[activeFeature].icon"
                                        :size="22"
                                    />
                                </span>
                                <div>
                                    <small>{{
                                        lt(
                                            'landing.platform.included',
                                            'Included',
                                        )
                                    }}</small>
                                    <h3>{{ features[activeFeature].title }}</h3>
                                    <p>{{ features[activeFeature].lead }}</p>
                                </div>
                            </div>

                            <div class="clean-feature-items">
                                <article
                                    v-for="item in features[activeFeature]
                                        .items"
                                    :key="item.title"
                                >
                                    <Check :size="14" />
                                    <div>
                                        <h4>{{ item.title }}</h4>
                                        <p>{{ item.body }}</p>
                                    </div>
                                </article>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section id="ai" class="clean-ai">
                <div class="clean-container clean-ai-grid">
                    <div class="clean-ai-copy">
                        <span class="clean-kicker">{{
                            lt('landing.ai.kicker', 'Built-in AI')
                        }}</span>
                        <h2>
                            {{
                                lt(
                                    'landing.ai.title',
                                    'Useful intelligence, exactly where work happens.',
                                )
                            }}
                        </h2>
                        <p>
                            {{
                                lt(
                                    'landing.ai.lead',
                                    'Help people write, complete forms, manage records and explore data without leaving the system.',
                                )
                            }}
                        </p>

                        <ul>
                            <li v-for="item in aiItems" :key="item.title">
                                <component :is="item.icon" :size="17" />
                                <div>
                                    <strong>{{ item.title }}</strong>
                                    <span>{{ item.body }}</span>
                                </div>
                            </li>
                        </ul>
                    </div>

                    <div class="clean-ai-demo">
                        <header>
                            <span
                                ><Sparkles :size="14" />
                                {{
                                    lt('landing.ai.command', 'AI assistant')
                                }}</span
                            >
                            <kbd>⌘ K</kbd>
                        </header>
                        <div class="clean-ai-prompt">
                            <small>{{ lt('landing.ai.you', 'You') }}</small>
                            <p>
                                {{
                                    lt(
                                        'landing.ai.prompt',
                                        'Summarize this report and create the follow-up tasks.',
                                    )
                                }}
                            </p>
                        </div>
                        <div class="clean-ai-answer">
                            <small><Sparkles :size="12" /> {{ appName }}</small>
                            <p>
                                {{
                                    lt(
                                        'landing.ai.answer',
                                        'The report is summarized. I found three follow-up actions and prepared them for your review.',
                                    )
                                }}
                            </p>
                            <div>
                                <span
                                    ><Check :size="12" />
                                    {{
                                        lt(
                                            'landing.ai.task1',
                                            'Verify submitted records',
                                        )
                                    }}</span
                                >
                                <span
                                    ><Check :size="12" />
                                    {{
                                        lt(
                                            'landing.ai.task2',
                                            'Assign the open case',
                                        )
                                    }}</span
                                >
                                <span
                                    ><Check :size="12" />
                                    {{
                                        lt(
                                            'landing.ai.task3',
                                            'Schedule a follow-up',
                                        )
                                    }}</span
                                >
                            </div>
                        </div>
                        <footer>
                            <span>{{
                                lt('landing.ai.refine', 'Ask a follow-up...')
                            }}</span>
                            <button type="button">
                                <ArrowRight :size="14" />
                            </button>
                        </footer>
                    </div>
                </div>
            </section>

            <section class="clean-workflow">
                <div class="clean-container">
                    <div
                        class="clean-section-heading clean-section-heading-centered"
                    >
                        <span>{{
                            lt('landing.workflow.kicker', 'Simple workflow')
                        }}</span>
                        <h2>
                            {{
                                lt(
                                    'landing.workflow.title',
                                    'From clone to first module in three steps.',
                                )
                            }}
                        </h2>
                    </div>

                    <div class="clean-steps">
                        <article>
                            <span>01</span>
                            <h3>{{ lt('landing.step1.title', 'Install') }}</h3>
                            <p>
                                {{
                                    lt(
                                        'landing.step1.body',
                                        'Run the guided installer and configure the environment.',
                                    )
                                }}
                            </p>
                            <code
                                ><Terminal :size="13" /> php artisan
                                kit:install</code
                            >
                        </article>
                        <article>
                            <span>02</span>
                            <h3>
                                {{ lt('landing.step2.title', 'Configure') }}
                            </h3>
                            <p>
                                {{
                                    lt(
                                        'landing.step2.body',
                                        'Apply your brand, locales, shell and feature settings.',
                                    )
                                }}
                            </p>
                            <code
                                ><Globe2 :size="13" /> name · colors ·
                                locale</code
                            >
                        </article>
                        <article>
                            <span>03</span>
                            <h3>{{ lt('landing.step3.title', 'Build') }}</h3>
                            <p>
                                {{
                                    lt(
                                        'landing.step3.body',
                                        'Scaffold your resources and ship the domain workflows.',
                                    )
                                }}
                            </p>
                            <code
                                ><Code2 :size="13" /> kit:scaffold
                                Resource</code
                            >
                        </article>
                    </div>
                </div>
            </section>

            <section id="pricing" class="clean-pricing">
                <div class="clean-container">
                    <div
                        class="clean-section-heading clean-section-heading-centered"
                    >
                        <span>{{
                            lt('landing.pricing.kicker', 'Pricing')
                        }}</span>
                        <h2>
                            {{
                                lt(
                                    'landing.pricing.title',
                                    'Start simple. Add more when you need it.',
                                )
                            }}
                        </h2>
                        <p>
                            {{
                                lt(
                                    'landing.pricing.lead',
                                    'One clear foundation for builders, teams and organizations.',
                                )
                            }}
                        </p>

                        <div class="clean-billing">
                            <button
                                type="button"
                                :class="{ active: billing === 'once' }"
                                @click="billing = 'once'"
                            >
                                {{ lt('landing.billing.once', 'One-time') }}
                            </button>
                            <button
                                type="button"
                                :class="{ active: billing === 'annual' }"
                                @click="billing = 'annual'"
                            >
                                {{ lt('landing.billing.annual', 'Annual') }}
                            </button>
                        </div>
                    </div>

                    <div class="clean-plan-grid">
                        <article
                            v-for="plan in plans"
                            :key="plan.name"
                            :class="{ featured: plan.featured }"
                        >
                            <div class="clean-plan-top">
                                <h3>{{ plan.name }}</h3>
                                <span v-if="plan.featured">
                                    {{
                                        lt('landing.pricing.popular', 'Popular')
                                    }}
                                </span>
                            </div>
                            <p>{{ plan.description }}</p>
                            <div class="clean-price">
                                <strong>{{ plan.price }}</strong>
                                <small v-if="plan.period">{{
                                    plan.period
                                }}</small>
                            </div>
                            <Link :href="plan.href">
                                {{ plan.cta }}
                                <ArrowRight :size="15" />
                            </Link>
                            <ul>
                                <li v-for="item in plan.items" :key="item">
                                    <Check :size="14" />
                                    {{ item }}
                                </li>
                            </ul>
                        </article>
                    </div>
                </div>
            </section>

            <section id="faq" class="clean-faq-section">
                <div class="clean-container clean-faq-grid">
                    <div>
                        <span class="clean-kicker">{{
                            lt('landing.faq.kicker', 'FAQ')
                        }}</span>
                        <h2>
                            {{ lt('landing.faq.title', 'Common questions.') }}
                        </h2>
                    </div>
                    <div class="clean-faq">
                        <article
                            v-for="(item, index) in faqItems"
                            :key="item.question"
                            :class="{ open: openFaq === index }"
                        >
                            <button
                                type="button"
                                :aria-expanded="openFaq === index"
                                @click="
                                    openFaq = openFaq === index ? -1 : index
                                "
                            >
                                {{ item.question }}
                                <ChevronDown :size="17" />
                            </button>
                            <p v-show="openFaq === index">{{ item.answer }}</p>
                        </article>
                    </div>
                </div>
            </section>

            <section class="clean-final">
                <div class="clean-container clean-final-inner">
                    <div>
                        <span>{{
                            lt('landing.final.kicker', 'Ready when you are')
                        }}</span>
                        <h2>
                            {{
                                lt(
                                    'landing.final.title',
                                    'Build what matters. The foundation is ready.',
                                )
                            }}
                        </h2>
                    </div>
                    <Link
                        v-if="isAuthenticated"
                        :href="dashboard()"
                        class="clean-button clean-button-light"
                    >
                        {{ t('Open dashboard') }}
                        <ArrowRight :size="16" />
                    </Link>
                    <Link
                        v-else
                        :href="register()"
                        class="clean-button clean-button-light"
                    >
                        {{ lt('landing.final.cta', 'Start building free') }}
                        <ArrowRight :size="16" />
                    </Link>
                </div>
            </section>
        </main>

        <footer class="clean-footer">
            <div class="clean-container">
                <a href="#top" class="clean-brand">
                    <span><Boxes :size="17" /></span>
                    {{ appName }}
                </a>
                <p>
                    {{
                        lt(
                            'landing.footer',
                            'Production-ready MIS infrastructure for modern teams.',
                        )
                    }}
                </p>
                <nav>
                    <a href="#platform">{{
                        lt('landing.nav.platform', 'Platform')
                    }}</a>
                    <a href="#pricing">{{
                        lt('landing.nav.pricing', 'Pricing')
                    }}</a>
                    <Link :href="login()">{{ t('Log in') }}</Link>
                </nav>
            </div>
        </footer>
    </div>
</template>
