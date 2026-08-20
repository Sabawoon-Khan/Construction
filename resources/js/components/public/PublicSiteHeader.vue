<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { Building2, Menu, X } from '@lucide/vue';
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import LanguageSwitcher from '@/components/LanguageSwitcher.vue';
import { useTranslations } from '@/composables/useTranslations';
import { dashboard, login } from '@/routes';

const props = withDefaults(
    defineProps<{
        /** When true, nav uses in-page anchors; otherwise prefixes with home. */
        onHome?: boolean;
    }>(),
    { onHome: false },
);

type PublicCompany = {
    name?: string | null;
    name_primary?: string | null;
    name_en?: string | null;
};

const page = usePage();
const { t } = useTranslations();
const publicCompany = computed(
    () => (page.props.publicCompany as PublicCompany | null) ?? null,
);
const locale = computed(() => (page.props.locale as string) || 'en');
const isEnglish = computed(() => locale.value === 'en');
const brandEn = computed(
    () =>
        publicCompany.value?.name_en?.trim() ||
        (isEnglish.value ? (page.props.name as string | undefined) : null) ||
        'Shahin Samim Barakzai',
);
const brandFa = computed(
    () =>
        publicCompany.value?.name_primary?.trim() ||
        (!isEnglish.value ? (page.props.name as string | undefined) : null) ||
        'شاهین صمیم بارکزی',
);
/** English-only on `en`; Dari primary (+ English secondary) otherwise. */
const brandPrimary = computed(() =>
    isEnglish.value ? brandEn.value : brandFa.value,
);
const brandSecondary = computed(() =>
    isEnglish.value ? null : brandEn.value,
);
const isAuthenticated = computed(() => !!page.props.auth.user);
const mobileOpen = ref(false);

function lt(key: string, fallback: string): string {
    const value = t(key);
    return value === key ? fallback : value;
}

function href(hash: string): string {
    return props.onHome ? hash : `/${hash}`;
}

const navItems = computed(() => [
    { href: href('#about'), label: lt('home.nav.about', 'About') },
    { href: href('#services'), label: lt('home.nav.services', 'Services') },
    {
        href: props.onHome ? '#projects' : '/work',
        label: lt('home.nav.projects', 'Projects'),
    },
    { href: href('#why'), label: lt('home.nav.why', 'Why us') },
    { href: href('#contact'), label: lt('home.nav.contact', 'Contact') },
]);

function closeMobile() {
    mobileOpen.value = false;
}

function onKeydown(event: KeyboardEvent) {
    if (event.key === 'Escape' && mobileOpen.value) {
        closeMobile();
    }
}

watch(mobileOpen, (open) => {
    if (typeof document === 'undefined') {
        return;
    }
    document.body.style.overflow = open ? 'hidden' : '';
});

onMounted(() => window.addEventListener('keydown', onKeydown));
onUnmounted(() => {
    window.removeEventListener('keydown', onKeydown);
    if (typeof document !== 'undefined') {
        document.body.style.overflow = '';
    }
});
</script>

<template>
    <header class="construct-nav">
        <div class="construct-island">
            <a
                v-if="onHome"
                href="#top"
                class="construct-brand"
                @click="closeMobile"
            >
                <span><Building2 :size="15" /></span>
                <span class="construct-brand-stack">
                    <strong>{{ brandPrimary }}</strong>
                    <em v-if="brandSecondary">{{ brandSecondary }}</em>
                </span>
            </a>
            <Link
                v-else
                href="/"
                class="construct-brand"
                @click="closeMobile"
            >
                <span><Building2 :size="15" /></span>
                <span class="construct-brand-stack">
                    <strong>{{ brandPrimary }}</strong>
                    <em v-if="brandSecondary">{{ brandSecondary }}</em>
                </span>
            </Link>

            <nav
                class="construct-links"
                :aria-label="lt('home.nav.primary', 'Primary navigation')"
            >
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
                    :aria-expanded="mobileOpen"
                    :aria-label="
                        mobileOpen
                            ? lt('home.nav.closeMenu', 'Close menu')
                            : lt('home.nav.openMenu', 'Open menu')
                    "
                    @click="mobileOpen = !mobileOpen"
                >
                    <X v-if="mobileOpen" :size="18" />
                    <Menu v-else :size="18" />
                </button>
            </div>
        </div>

        <nav
            v-if="mobileOpen"
            class="construct-mobile"
            :aria-label="lt('home.nav.mobile', 'Mobile navigation')"
        >
            <a
                v-for="item in navItems"
                :key="item.href"
                :href="item.href"
                @click="closeMobile"
            >
                {{ item.label }}
            </a>
            <Link
                v-if="isAuthenticated"
                :href="dashboard()"
                class="construct-mobile-auth"
                @click="closeMobile"
            >
                {{ t('Dashboard') }}
            </Link>
            <Link
                v-else
                :href="login()"
                class="construct-mobile-auth"
                @click="closeMobile"
            >
                {{ t('Log in') }}
            </Link>
        </nav>
    </header>
</template>
