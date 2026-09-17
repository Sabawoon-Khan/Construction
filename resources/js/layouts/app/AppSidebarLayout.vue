<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    LogOut,
    Menu,
    PanelLeftClose,
    PanelLeftOpen,
    X,
} from '@lucide/vue';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import AiHeaderButton from '@/components/AiHeaderButton.vue';
import GlobalSearch from '@/components/GlobalSearch.vue';
import LanguageSwitcher from '@/components/LanguageSwitcher.vue';
import NotificationBell from '@/components/NotificationBell.vue';
import OfflineBanner from '@/components/OfflineBanner.vue';
import OfflineConflictPanel from '@/components/OfflineConflictPanel.vue';
import UserMenuContent from '@/components/UserMenuContent.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Toaster } from '@/components/ui/sonner';
import AppShell from '@/components/AppShell.vue';
import {
    isNavItemActive,
    useAppNavigation,
} from '@/composables/useAppNavigation';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { useInitials } from '@/composables/useInitials';
import { useLocale } from '@/composables/useLocale';
import { useModuleTabs, navItemPath } from '@/composables/useModuleTabs';
import { useProductTour } from '@/composables/useProductTour';
import { useTranslations } from '@/composables/useTranslations';
import { toUrl } from '@/lib/utils';
import { logout } from '@/routes';
import type { BreadcrumbItem, NavItem } from '@/types';

type Props = {
    breadcrumbs?: BreadcrumbItem[];
};

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});

const page = usePage();
const offlineEnabled = computed(() =>
    Boolean((page.props as { offlineEnabled?: boolean }).offlineEnabled),
);
const { dir } = useLocale();
const { t } = useTranslations();
const { startAdminShellTour } = useProductTour();
const { navGroups } = useAppNavigation();
const { currentUrl } = useCurrentUrl();
const { tabItems, isSettingsArea } = useModuleTabs();
const { getInitials } = useInitials();

const SIDEBAR_KEY = 'app-v2-sidebar-open';
// Collapsed until mount so SSR and client first paint match.
const sidebarOpen = ref(false);
const mobileNavOpen = ref(false);
const sidebarHydrated = ref(false);
/** Defer body Teleports/Toaster until client mount to avoid SSR hydration mismatches. */
const mounted = ref(false);

watch(sidebarOpen, (open) => {
    if (!sidebarHydrated.value || typeof localStorage === 'undefined') return;
    localStorage.setItem(SIDEBAR_KEY, open ? '1' : '0');
});

watch(currentUrl, () => {
    mobileNavOpen.value = false;
    hideTip();
});

watch(mobileNavOpen, (open) => {
    if (typeof document === 'undefined') return;
    document.documentElement.style.overflow = open ? 'hidden' : '';
    document.body.style.overflow = open ? 'hidden' : '';
});

function toggleSidebar() {
    sidebarOpen.value = !sidebarOpen.value;
    hideTip();
}

function restoreSidebarOpen(): void {
    try {
        sidebarOpen.value = localStorage.getItem(SIDEBAR_KEY) === '1';
    } catch {
        // ignore quota / private mode
    } finally {
        sidebarHydrated.value = true;
    }
}

const tip = ref<{ show: boolean; text: string; x: number; y: number }>({
    show: false,
    text: '',
    x: 0,
    y: 0,
});

const sidebarItems = computed(() =>
    navGroups.value.flatMap((group) => group.items),
);

const moduleTabs = computed(() => tabItems.value);

const showTopnav = computed(
    () => !isSettingsArea.value && moduleTabs.value.length > 1,
);

const companyName = computed(
    () =>
        (page.props.company as { name?: string } | null)?.name ??
        (page.props.name as string | undefined) ??
        'Structo',
);

const user = computed(() => page.props.auth.user);

const showAvatar = computed(
    () => Boolean(user.value?.avatar && user.value.avatar !== ''),
);

const toasterPosition = computed(() =>
    dir.value === 'rtl' ? 'top-left' : 'top-right',
);

function showTip(event: MouseEvent, text: string) {
    if (sidebarOpen.value) return;
    const el = event.currentTarget as HTMLElement | null;
    if (!el) return;
    const rect = el.getBoundingClientRect();
    const isRtl = dir.value === 'rtl';
    tip.value = {
        show: true,
        text,
        x: isRtl ? rect.left - 12 : rect.right + 12,
        y: rect.top + rect.height / 2,
    };
}

function moveTip(event: MouseEvent) {
    if (!tip.value.show || sidebarOpen.value) return;
    showTip(event, tip.value.text);
}

function hideTip() {
    tip.value.show = false;
}

function tabHref(item: NavItem): string {
    return navItemPath(item.href);
}

function tabActive(item: NavItem): boolean {
    return isNavItemActive(item, currentUrl.value);
}

function navActive(item: NavItem): boolean {
    return isNavItemActive(item, currentUrl.value);
}

onMounted(() => {
    mounted.value = true;
    restoreSidebarOpen();
    window.setTimeout(() => startAdminShellTour(), 600);
});

onBeforeUnmount(() => {
    if (typeof document === 'undefined') return;
    document.documentElement.style.overflow = '';
    document.body.style.overflow = '';
});
</script>

<template>
    <AppShell variant="header">
        <a
            href="#main-content"
            class="sr-only focus:not-sr-only focus:absolute focus:start-4 focus:top-4 focus:z-[100] focus:rounded-md focus:bg-background focus:px-3 focus:py-2 focus:text-sm focus:shadow-lg"
        >
            {{ t('Skip to main content') }}
        </a>

        <div
            class="app-v2-page print:bg-white"
            :class="{ 'settings-shell': isSettingsArea }"
            :dir="dir"
        >
            <div
                class="app-v2-shell"
                :class="{
                    'sidebar-open': sidebarOpen,
                    'no-topbar': isSettingsArea,
                }"
            >
                <header
                    v-if="!isSettingsArea"
                    class="app-v2-topbar print:hidden"
                >
                    <div class="app-v2-brand">
                        <button
                            type="button"
                            class="app-v2-mobile-toggle"
                            :aria-label="t('Menu')"
                            :aria-expanded="mobileNavOpen"
                            @click="mobileNavOpen = !mobileNavOpen"
                        >
                            <X v-if="mobileNavOpen" />
                            <Menu v-else />
                        </button>
                        <svg viewBox="0 0 32 38" aria-hidden="true">
                            <path d="M16 1 30 9 16 17 2 9Z" />
                            <path d="m2 14 14 8 14-8v7l-14 8L2 21Z" />
                            <path d="m2 26 14 8 14-8v5l-14 7L2 31Z" />
                        </svg>
                        <span class="app-v2-brand-name">{{ companyName }}</span>
                    </div>

                    <nav v-if="showTopnav" class="app-v2-topnav">
                        <Link
                            v-for="item in moduleTabs"
                            :key="tabHref(item)"
                            :href="tabHref(item)"
                            :class="{ active: tabActive(item) }"
                        >
                            <component :is="item.icon" />
                            {{ item.title }}
                        </Link>
                    </nav>
                    <div v-else class="app-v2-topnav-spacer" />

                    <div class="app-v2-top-actions">
                        <div class="app-v2-action-slot app-v2-search">
                            <GlobalSearch />
                        </div>
                        <div
                            class="app-v2-action-slot lang-wrap app-v2-desktop-only"
                            :title="t('Language')"
                        >
                            <LanguageSwitcher />
                        </div>
                        <div class="app-v2-action-slot app-v2-ai app-v2-desktop-only">
                            <AiHeaderButton />
                        </div>
                        <div class="app-v2-action-slot app-v2-bell">
                            <NotificationBell />
                        </div>

                        <DropdownMenu v-if="user">
                            <DropdownMenuTrigger as-child>
                                <button type="button" class="app-v2-avatar-btn">
                                    <Avatar class="app-v2-avatar">
                                        <AvatarImage
                                            v-if="showAvatar"
                                            :src="user.avatar!"
                                            :alt="user.name"
                                        />
                                        <AvatarFallback class="app-v2-avatar-fallback">
                                            {{ getInitials(user.name) }}
                                        </AvatarFallback>
                                    </Avatar>
                                </button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent
                                align="end"
                                class="min-w-56 rounded-2xl border-border/60 p-1.5 shadow-[0_20px_60px_-20px_rgba(15,23,42,0.25)] backdrop-blur-md"
                            >
                                <UserMenuContent :user="user" />
                            </DropdownMenuContent>
                        </DropdownMenu>
                    </div>
                </header>

                <aside class="app-v2-sidebar print:hidden">
                    <div class="app-v2-sidebar-dock">
                        <div class="app-v2-sidebar-top">
                            <Link
                                v-for="item in sidebarItems"
                                :key="`${item.title}-${toUrl(item.href)}`"
                                :href="toUrl(item.href)"
                                :class="{ active: navActive(item) }"
                                class="app-v2-nav-link"
                                data-tour="sidebar-nav"
                                @mouseenter="showTip($event, item.title)"
                                @mousemove="moveTip"
                                @mouseleave="hideTip"
                            >
                                <component :is="item.icon" />
                                <span v-if="sidebarOpen" class="app-v2-nav-label">{{
                                    item.title
                                }}</span>
                            </Link>
                        </div>
                        <div class="app-v2-sidebar-bottom">
                            <button
                                type="button"
                                class="app-v2-nav-link"
                                data-tour="sidebar-collapse"
                                :title="
                                    sidebarOpen
                                        ? t('Collapse sidebar')
                                        : t('Expand sidebar')
                                "
                                @click="toggleSidebar"
                                @mouseenter="
                                    showTip(
                                        $event,
                                        sidebarOpen
                                            ? t('Collapse sidebar')
                                            : t('Expand sidebar'),
                                    )
                                "
                                @mousemove="moveTip"
                                @mouseleave="hideTip"
                            >
                                <PanelLeftClose v-if="sidebarOpen" />
                                <PanelLeftOpen v-else />
                                <span v-if="sidebarOpen" class="app-v2-nav-label">{{
                                    t('Collapse sidebar')
                                }}</span>
                            </button>
                            <Link
                                :href="logout()"
                                as="button"
                                class="app-v2-nav-link"
                                @mouseenter="showTip($event, t('Log out'))"
                                @mousemove="moveTip"
                                @mouseleave="hideTip"
                            >
                                <LogOut />
                                <span v-if="sidebarOpen" class="app-v2-nav-label">{{
                                    t('Log out')
                                }}</span>
                            </Link>
                        </div>
                    </div>
                </aside>

                <Teleport v-if="mounted" to="body">
                    <div
                        v-if="tip.show && !sidebarOpen"
                        class="app-v2-nav-tip"
                        :class="{ rtl: dir === 'rtl' }"
                        :style="{
                            left: `${tip.x}px`,
                            top: `${tip.y}px`,
                        }"
                    >
                        {{ tip.text }}
                    </div>
                </Teleport>

                <Teleport v-if="mounted" to="body">
                    <div
                        v-if="mobileNavOpen"
                        class="app-v2-mobile-scrim print:hidden"
                        @click="mobileNavOpen = false"
                    />
                    <nav
                        v-if="mobileNavOpen"
                        class="app-v2-mobile-drawer print:hidden"
                        :dir="dir"
                        aria-label="Mobile navigation"
                    >
                        <div class="app-v2-mobile-drawer-head">
                            <div class="app-v2-mobile-brand">
                                <div class="app-v2-mobile-brand-mark" aria-hidden="true">
                                    <svg viewBox="0 0 32 38">
                                        <path d="M16 1 30 9 16 17 2 9Z" />
                                        <path d="m2 14 14 8 14-8v7l-14 8L2 21Z" />
                                        <path d="m2 26 14 8 14-8v5l-14 7L2 31Z" />
                                    </svg>
                                </div>
                                <div>
                                    <strong>{{ companyName }}</strong>
                                    <small v-if="user">{{ user.name }}</small>
                                </div>
                            </div>
                            <button
                                type="button"
                                :aria-label="t('Close')"
                                @click="mobileNavOpen = false"
                            >
                                <X />
                            </button>
                        </div>

                        <div class="app-v2-mobile-drawer-body">
                            <section
                                v-for="group in navGroups.filter((g) => g.items.length)"
                                :key="group.label"
                                class="app-v2-mobile-group"
                            >
                                <p class="app-v2-mobile-group-label">
                                    {{ group.label }}
                                </p>
                                <Link
                                    v-for="item in group.items"
                                    :key="`m-${item.title}-${toUrl(item.href)}`"
                                    :href="toUrl(item.href)"
                                    :class="{ active: navActive(item) }"
                                    class="app-v2-mobile-link"
                                >
                                    <span class="app-v2-mobile-link-icon">
                                        <component :is="item.icon" />
                                    </span>
                                    <span>{{ item.title }}</span>
                                </Link>
                            </section>

                            <section
                                v-if="!isSettingsArea && moduleTabs.length > 1"
                                class="app-v2-mobile-group"
                            >
                                <p class="app-v2-mobile-group-label">
                                    {{ t('Modules') }}
                                </p>
                                <div class="app-v2-mobile-tabs">
                                    <Link
                                        v-for="item in moduleTabs"
                                        :key="`mt-${tabHref(item)}`"
                                        :href="tabHref(item)"
                                        :class="{ active: tabActive(item) }"
                                        class="app-v2-mobile-tab"
                                    >
                                        <component :is="item.icon" />
                                        {{ item.title }}
                                    </Link>
                                </div>
                            </section>
                        </div>

                        <div class="app-v2-mobile-drawer-foot">
                            <div class="app-v2-mobile-foot-row">
                                <span>{{ t('Language') }}</span>
                                <LanguageSwitcher />
                            </div>
                            <div class="app-v2-mobile-foot-actions">
                                <AiHeaderButton />
                                <Link
                                    :href="logout()"
                                    as="button"
                                    class="app-v2-mobile-logout"
                                >
                                    <LogOut />
                                    {{ t('Log out') }}
                                </Link>
                            </div>
                        </div>
                    </nav>
                </Teleport>

                <main class="app-v2-content">
                    <OfflineBanner
                        class="print:hidden"
                        :enabled="offlineEnabled"
                    />
                    <div
                        id="main-content"
                        class="app-v2-main"
                        tabindex="-1"
                    >
                        <OfflineConflictPanel v-if="offlineEnabled" />
                        <slot />
                    </div>
                </main>
            </div>
        </div>

        <Toaster
            v-if="mounted"
            :position="toasterPosition"
            :duration="5000"
            close-button
            close-button-position="top-right"
        />
    </AppShell>
</template>

<style scoped>
.app-v2-page {
    --app-v2-sidebar-width: 88px;
    --app-v2-topbar-height: 72px;
    --app-v2-surface: color-mix(in srgb, var(--card, #ffffff) 72%, transparent);
    --app-v2-surface-strong: color-mix(
        in srgb,
        var(--card, #ffffff) 88%,
        transparent
    );
    --app-v2-ink: var(--foreground, #111);
    --app-v2-muted: var(--muted-foreground, #535b60);
    --app-v2-active-bg: var(--school-navy, #162238);
    --app-v2-active-fg: #ffffff;
    --app-v2-panel-radius: calc(var(--radius, 0.875rem) * 2);
    width: 100%;
    height: 100dvh;
    min-height: 100dvh;
    overflow: hidden;
    background: var(--background, #eef0ee);
    color: var(--app-v2-ink);
    font-family:
        var(--font-sans), 'Plus Jakarta Sans', 'Helvetica Neue', sans-serif;
}

.app-v2-shell {
    display: grid;
    grid-template-columns: var(--app-v2-sidebar-width) minmax(0, 1fr);
    grid-template-rows: var(--app-v2-topbar-height) minmax(0, 1fr);
    width: 100%;
    height: 100%;
    min-height: 0;
    overflow: hidden;
    transition: grid-template-columns 0.28s ease;
    background:
        radial-gradient(
            ellipse 48% 42% at 12% 8%,
            color-mix(in srgb, var(--school-gold, #d4a853) 22%, transparent) 0%,
            transparent 70%
        ),
        radial-gradient(
            ellipse 42% 38% at 92% 6%,
            color-mix(in srgb, var(--brand-accent, var(--primary)) 18%, transparent)
                0%,
            transparent 68%
        ),
        linear-gradient(
            120deg,
            var(--app-shell, var(--background)) 0%,
            color-mix(
                    in srgb,
                    var(--background) 94%,
                    var(--brand-accent, var(--primary))
                )
                48%,
            color-mix(in srgb, var(--background) 90%, var(--school-navy, #162238))
                100%
        );
}

.app-v2-shell.sidebar-open {
    --app-v2-sidebar-width: 220px;
}

.app-v2-shell.no-topbar {
    --app-v2-topbar-height: 0px;
    grid-template-rows: minmax(0, 1fr);
}

.app-v2-shell.no-topbar .app-v2-sidebar,
.app-v2-shell.no-topbar .app-v2-content {
    grid-row: 1;
}

.app-v2-page.settings-shell {
    --app-v2-topbar-height: 0px;
}

.app-v2-topbar {
    z-index: 20;
    grid-column: 1 / -1;
    display: grid;
    grid-template-columns: 240px 1fr auto;
    align-items: center;
    padding: 0 28px;
    animation: app-v2-fade-down 0.45s ease both;
}

.app-v2-brand {
    display: flex;
    align-items: center;
    gap: 7px;
    font-size: 18px;
    font-weight: 650;
    letter-spacing: -0.7px;
    color: var(--app-v2-ink);
}

.app-v2-brand svg {
    width: 23px;
    color: var(--school-gold, var(--brand-accent, #f08b3d));
    fill: none;
    stroke: currentcolor;
    stroke-width: 1.6;
}

.app-v2-mobile-toggle {
    display: none;
    place-items: center;
    width: 39px;
    height: 39px;
    margin-inline-end: 4px;
    border: 0;
    border-radius: 50%;
    background: var(--app-v2-surface);
    color: var(--app-v2-muted);
    cursor: pointer;
}

.app-v2-topnav {
    justify-self: center;
    display: flex;
    align-items: center;
    max-width: 100%;
    padding: 4px;
    overflow-x: auto;
    border-radius: 999px;
    border: 1px solid color-mix(in srgb, var(--card, #fff) 55%, transparent);
    background: var(--app-v2-surface);
    box-shadow: 0 10px 28px
        color-mix(in srgb, var(--school-navy, #162238) 8%, transparent);
    backdrop-filter: blur(18px);
    scrollbar-width: none;
}

.app-v2-topnav::-webkit-scrollbar {
    display: none;
}

.app-v2-topnav-spacer {
    justify-self: center;
}

.app-v2-topnav a {
    display: flex;
    align-items: center;
    gap: 7px;
    height: 38px;
    padding: 0 16px;
    border-radius: calc(var(--radius, 0.875rem) * 1.75);
    color: var(--app-v2-ink);
    font-size: 11px;
    font-weight: 500;
    white-space: nowrap;
    text-decoration: none;
    transition:
        background 0.15s ease,
        color 0.15s ease,
        box-shadow 0.15s ease;
}

.app-v2-topnav a svg {
    width: 14px;
    height: 14px;
    opacity: 0.75;
    flex-shrink: 0;
}

.app-v2-topnav a:hover:not(.active) {
    background: var(--app-v2-surface-strong);
}

.app-v2-topnav .active {
    background: var(--app-v2-active-bg);
    color: var(--app-v2-active-fg);
    box-shadow: 0 8px 18px
        color-mix(in srgb, var(--app-v2-active-bg) 28%, transparent);
}

.app-v2-topnav .active svg {
    opacity: 1;
}

.app-v2-top-actions {
    display: flex;
    align-items: center;
    gap: 11px;
}

.app-v2-action-slot {
    display: grid;
    place-items: center;
    width: 39px;
    height: 39px;
    border-radius: 50%;
    background: var(--app-v2-surface);
    color: var(--app-v2-muted);
    transition:
        transform 0.15s ease,
        background 0.15s ease;
}

.app-v2-action-slot:hover {
    background: var(--app-v2-surface-strong);
    transform: translateY(-1px);
}

.app-v2-action-slot:not(.app-v2-search):not(.app-v2-ai) :deep(button),
.app-v2-action-slot:not(.app-v2-search):not(.app-v2-ai) :deep(a) {
    width: 39px;
    height: 39px;
    border-radius: 50%;
    color: var(--app-v2-muted);
    background: transparent !important;
    box-shadow: none !important;
}

.app-v2-action-slot :deep(svg) {
    width: 17px;
    height: 17px;
}

.app-v2-search {
    width: auto;
    min-width: 39px;
    padding: 0;
    background: transparent;
}

.app-v2-search:hover {
    background: transparent;
    transform: none;
}

.app-v2-search :deep(button[data-search-variant='icon']) {
    width: 39px;
    height: 39px;
    border-radius: 50%;
    background: var(--app-v2-surface) !important;
}

.app-v2-search :deep(button[data-search-variant='icon']:hover) {
    background: var(--app-v2-surface-strong) !important;
}

.app-v2-search :deep(button[data-search-variant='pill']) {
    width: auto;
    height: 39px;
    padding-inline: 12px 14px;
    border-radius: 999px;
    background: var(--app-v2-surface) !important;
    color: var(--app-v2-muted) !important;
    box-shadow: 0 10px 28px
        color-mix(in srgb, var(--school-navy, #162238) 8%, transparent);
}

.app-v2-search :deep(button[data-search-variant='pill']:hover) {
    background: var(--app-v2-surface-strong) !important;
    transform: translateY(-1px);
}

.app-v2-ai {
    width: auto;
    background: transparent;
}

.app-v2-ai:hover {
    background: transparent;
    transform: none;
}

.app-v2-ai :deep(button) {
    width: auto;
    min-width: 39px;
    height: 39px;
    padding-inline: 10px;
    border-radius: 999px;
    background: var(--app-v2-surface) !important;
    color: var(--app-v2-muted) !important;
}

.app-v2-ai :deep(button:hover) {
    background: var(--app-v2-surface-strong) !important;
}

.app-v2-ai :deep(span) {
    font-size: 11px;
    font-weight: 550;
}

.app-v2-bell :deep([data-slot='dropdown-menu-trigger']),
.app-v2-bell :deep(button) {
    position: relative;
}

.app-v2-avatar-btn {
    padding: 0;
    border: 0;
    background: transparent;
    cursor: pointer;
}

.app-v2-avatar {
    width: 39px;
    height: 39px;
    border: 3px solid color-mix(in srgb, var(--card, #fff) 70%, transparent);
    border-radius: 50%;
}

.app-v2-avatar-fallback {
    background: linear-gradient(
        145deg,
        color-mix(in srgb, var(--brand-accent, var(--primary)) 28%, var(--card)),
        var(--school-navy, #6c7b85)
    );
    color: white;
    font-size: 14px;
    font-weight: 700;
}

.app-v2-sidebar {
    grid-column: 1;
    grid-row: 2;
    position: relative;
    z-index: 80;
    inset: auto;
    top: auto;
    bottom: auto;
    width: 100%;
    height: 100%;
    min-height: 0;
    display: flex;
    flex-direction: column;
    justify-content: flex-end;
    align-items: stretch;
    padding: 0 12px 28px;
    transition: width 0.28s ease;
    animation: app-v2-fade-up 0.5s 0.05s ease both;
    pointer-events: none;
    transform: none;
}

.app-v2-sidebar > * {
    pointer-events: auto;
}

.app-v2-sidebar-dock {
    display: flex;
    flex-direction: column;
    justify-content: flex-end;
    gap: 10px;
    max-height: calc(100dvh - 110px);
}

.app-v2-shell.no-topbar .app-v2-sidebar {
    padding-top: 28px;
}

.app-v2-shell.no-topbar .app-v2-sidebar-dock {
    max-height: calc(100dvh - 56px);
}

.app-v2-shell.no-topbar .app-v2-content {
    padding-top: 20px;
}

.app-v2-page[dir='rtl'] .app-v2-sidebar-bottom .app-v2-nav-link:first-child svg {
    transform: scaleX(-1);
}

.app-v2-sidebar-top,
.app-v2-sidebar-bottom {
    display: flex;
    flex-direction: column;
    gap: 4px;
    padding: 8px;
    border-radius: var(--app-v2-panel-radius);
    background: var(--app-v2-surface);
    box-shadow: 0 14px 30px
        color-mix(in srgb, var(--school-navy, #162238) 10%, transparent);
    backdrop-filter: blur(18px);
}

.app-v2-sidebar-top {
    overflow-y: auto;
    overscroll-behavior: contain;
    scrollbar-width: none;
}

.app-v2-sidebar-top::-webkit-scrollbar {
    display: none;
}

.app-v2-nav-link {
    position: relative;
    display: flex;
    align-items: center;
    gap: 10px;
    width: 100%;
    min-height: 38px;
    padding: 0 10px;
    border: 0;
    border-radius: 999px;
    background: transparent;
    color: var(--app-v2-muted);
    font-size: 12px;
    font-weight: 500;
    text-decoration: none;
    cursor: pointer;
    transition:
        background 0.15s ease,
        color 0.15s ease,
        transform 0.15s ease;
}

.app-v2-shell:not(.sidebar-open) .app-v2-nav-link {
    justify-content: center;
    width: 38px;
    padding: 0;
    margin-inline: auto;
}

.app-v2-nav-link:hover {
    background: var(--app-v2-surface-strong);
}

.app-v2-nav-link.active {
    background: var(--app-v2-active-bg);
    color: var(--app-v2-active-fg);
}

.app-v2-nav-link.active:hover {
    background: var(--app-v2-active-bg);
    color: var(--app-v2-active-fg);
}

.app-v2-nav-link svg {
    width: 17px;
    height: 17px;
    flex-shrink: 0;
}

.app-v2-nav-label {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.app-v2-content {
    grid-column: 2;
    grid-row: 2;
    display: flex;
    flex-direction: column;
    gap: 16px;
    min-width: 0;
    min-height: 0;
    overflow-x: hidden;
    overflow-y: auto;
    overscroll-behavior: contain;
    padding: 4px 28px 28px 8px;
}

.app-v2-shell > .app-v2-sidebar {
    align-self: stretch;
}

.app-v2-main {
    display: flex;
    flex: 1;
    flex-direction: column;
    gap: 16px;
    min-width: 0;
}

.app-v2-content:has(.dashboard-v2) {
    padding: 8px 16px 16px 4px;
    overflow: auto;
}

.app-v2-main:has(.dashboard-v2) {
    gap: 0;
    min-height: 0;
    height: auto;
}

.app-v2-mobile-scrim {
    position: fixed;
    inset: 0;
    z-index: 90;
    background: color-mix(in srgb, var(--school-navy, #0f172a) 48%, transparent);
    backdrop-filter: blur(6px);
    animation: app-v2-fade-up 0.2s ease both;
}

.app-v2-mobile-drawer {
    position: fixed;
    inset-block: 0;
    inset-inline-start: 0;
    z-index: 95;
    display: flex;
    flex-direction: column;
    width: min(328px, 86vw);
    padding: env(safe-area-inset-top, 0) 0 env(safe-area-inset-bottom, 0);
    border-radius: 0 22px 22px 0;
    border: 1px solid color-mix(in srgb, var(--card, #fff) 70%, transparent);
    border-inline-start: 0;
    background:
        linear-gradient(
            180deg,
            color-mix(in srgb, var(--card, #fff) 96%, transparent) 0%,
            color-mix(in srgb, var(--background) 94%, var(--brand-accent, var(--primary)))
                100%
        );
    box-shadow: 18px 0 48px
        color-mix(in srgb, var(--school-navy, #0f172a) 24%, transparent);
    color: var(--app-v2-ink);
    animation: app-v2-drawer-in 0.28s cubic-bezier(0.22, 1, 0.36, 1) both;
}

.app-v2-mobile-drawer[dir='rtl'] {
    border-radius: 22px 0 0 22px;
    border-inline-start: 1px solid
        color-mix(in srgb, var(--card, #fff) 70%, transparent);
    border-inline-end: 0;
}

.app-v2-mobile-drawer-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 20px 16px 16px;
}

.app-v2-mobile-brand {
    display: flex;
    align-items: center;
    gap: 12px;
    min-width: 0;
}

.app-v2-mobile-brand-mark {
    display: grid;
    place-items: center;
    width: 44px;
    height: 44px;
    flex-shrink: 0;
    border-radius: 14px;
    background: color-mix(
        in srgb,
        var(--school-gold, var(--brand-accent, #f08b3d)) 16%,
        var(--card, #fff)
    );
    box-shadow: inset 0 1px color-mix(in srgb, #fff 70%, transparent);
}

.app-v2-mobile-brand-mark svg {
    width: 22px;
    color: var(--school-gold, var(--brand-accent, #f08b3d));
    fill: none;
    stroke: currentcolor;
    stroke-width: 1.6;
}

.app-v2-mobile-brand strong {
    display: block;
    overflow: hidden;
    font-size: 16px;
    font-weight: 650;
    letter-spacing: -0.35px;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.app-v2-mobile-brand small {
    display: block;
    margin-top: 3px;
    color: var(--app-v2-muted);
    font-size: 11px;
    font-weight: 500;
}

.app-v2-mobile-drawer-head button {
    display: grid;
    place-items: center;
    width: 36px;
    height: 36px;
    flex-shrink: 0;
    border: 0;
    border-radius: 50%;
    background: var(--app-v2-surface-strong);
    color: var(--app-v2-muted);
    cursor: pointer;
}

.app-v2-mobile-drawer-head > button svg {
    width: 18px;
    height: 18px;
}

.app-v2-mobile-drawer-body {
    display: flex;
    flex: 1;
    flex-direction: column;
    gap: 18px;
    padding: 4px 12px 18px;
    overflow-y: auto;
    overscroll-behavior: contain;
    -webkit-overflow-scrolling: touch;
}

.app-v2-mobile-group {
    display: flex;
    flex-direction: column;
    gap: 4px;
    padding: 10px;
    border-radius: 18px;
    background: color-mix(in srgb, var(--card, #fff) 58%, transparent);
    box-shadow: inset 0 1px color-mix(in srgb, #fff 75%, transparent);
}

.app-v2-mobile-group-label {
    margin: 0 0 4px;
    padding: 0 8px;
    color: var(--app-v2-muted);
    font-size: 10px;
    font-weight: 650;
    letter-spacing: 0.07em;
    text-transform: uppercase;
}

.app-v2-mobile-link {
    display: flex;
    align-items: center;
    gap: 12px;
    min-height: 46px;
    padding: 0 10px;
    border-radius: 14px;
    color: var(--app-v2-ink);
    font-size: 13px;
    font-weight: 550;
    text-decoration: none;
    transition:
        background 0.15s ease,
        color 0.15s ease,
        transform 0.15s ease;
}

.app-v2-mobile-link:active {
    transform: scale(0.985);
}

.app-v2-mobile-link-icon {
    display: grid;
    place-items: center;
    width: 34px;
    height: 34px;
    flex-shrink: 0;
    border-radius: 11px;
    background: color-mix(in srgb, var(--school-navy, #162238) 6%, transparent);
    color: var(--app-v2-muted);
}

.app-v2-mobile-link-icon svg,
.app-v2-mobile-link > svg {
    width: 17px;
    height: 17px;
    flex-shrink: 0;
}

.app-v2-mobile-link.active {
    background: var(--app-v2-active-bg);
    color: var(--app-v2-active-fg);
    box-shadow: 0 10px 22px
        color-mix(in srgb, var(--app-v2-active-bg) 28%, transparent);
}

.app-v2-mobile-link.active .app-v2-mobile-link-icon {
    background: color-mix(in srgb, #fff 16%, transparent);
    color: var(--app-v2-active-fg);
}

.app-v2-mobile-tabs {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    padding: 2px 2px 2px;
}

.app-v2-mobile-tab {
    display: flex;
    align-items: center;
    gap: 6px;
    height: 36px;
    padding: 0 12px;
    border-radius: 999px;
    border: 1px solid color-mix(in srgb, var(--border, #e5e7eb) 80%, transparent);
    background: color-mix(in srgb, var(--card, #fff) 80%, transparent);
    color: var(--app-v2-ink);
    font-size: 11px;
    font-weight: 550;
    white-space: nowrap;
    text-decoration: none;
}

.app-v2-mobile-tab.active {
    border-color: transparent;
    background: var(--app-v2-active-bg);
    color: var(--app-v2-active-fg);
}

.app-v2-mobile-tab svg {
    width: 14px;
    height: 14px;
}

.app-v2-mobile-drawer-foot {
    display: flex;
    flex-direction: column;
    gap: 10px;
    padding: 12px 14px calc(16px + env(safe-area-inset-bottom, 0));
    border-top: 1px solid
        color-mix(in srgb, var(--border, #e5e7eb) 70%, transparent);
    background: color-mix(in srgb, var(--card, #fff) 72%, transparent);
}

.app-v2-mobile-foot-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    min-height: 44px;
    padding: 0 10px;
    border-radius: 14px;
    background: color-mix(in srgb, var(--background) 55%, transparent);
    color: var(--app-v2-muted);
    font-size: 12px;
    font-weight: 550;
}

.app-v2-mobile-foot-actions {
    display: flex;
    align-items: center;
    gap: 8px;
}

.app-v2-mobile-foot-actions :deep(button) {
    flex: 1;
    justify-content: center;
    height: 42px;
    border-radius: 14px;
    background: var(--app-v2-surface) !important;
}

.app-v2-mobile-foot-actions :deep(span) {
    display: inline !important;
}

.app-v2-mobile-logout {
    display: inline-flex;
    flex: 1;
    align-items: center;
    justify-content: center;
    gap: 8px;
    height: 42px;
    border: 0;
    border-radius: 14px;
    background: color-mix(in srgb, #df315b 12%, var(--card, #fff));
    color: #df315b;
    font-size: 12px;
    font-weight: 600;
    text-decoration: none;
    cursor: pointer;
}

.app-v2-mobile-logout svg {
    width: 16px;
    height: 16px;
}

@keyframes app-v2-fade-up {
    from {
        opacity: 0;
    }
    to {
        opacity: 1;
    }
}

@keyframes app-v2-fade-down {
    from {
        opacity: 0;
    }
    to {
        opacity: 1;
    }
}

@keyframes app-v2-drawer-in {
    from {
        opacity: 0.6;
        transform: translateX(-12px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

.app-v2-mobile-drawer[dir='rtl'] {
    animation-name: app-v2-drawer-in-rtl;
}

@keyframes app-v2-drawer-in-rtl {
    from {
        opacity: 0.6;
        transform: translateX(12px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

@media (max-width: 1100px) {
    .app-v2-topbar {
        grid-template-columns: 180px 1fr auto;
        padding: 0 16px;
    }

    .app-v2-content {
        padding: 4px 16px 20px 4px;
    }
}

@media (max-width: 820px) {
    .app-v2-page {
        display: flex;
        flex: 1 1 auto;
        flex-direction: column;
        height: 100%;
        max-height: 100%;
        min-height: 0;
        overflow: hidden;
        --app-v2-sidebar-width: 0px;
        --app-v2-topbar-height: 56px;
    }

    .app-v2-shell {
        display: flex;
        flex: 1 1 auto;
        flex-direction: column;
        height: 100%;
        max-height: 100%;
        min-height: 0;
        overflow: hidden;
    }

    .app-v2-topbar {
        position: relative;
        z-index: 30;
        display: flex;
        flex-shrink: 0;
        justify-content: space-between;
        align-items: center;
        gap: 8px;
        height: var(--app-v2-topbar-height);
        min-height: var(--app-v2-topbar-height);
        padding: 0 12px;
        background: color-mix(
            in srgb,
            var(--app-shell, var(--background)) 92%,
            transparent
        );
        backdrop-filter: blur(16px);
        border-bottom: 1px solid
            color-mix(in srgb, var(--card, #fff) 45%, transparent);
    }

    .app-v2-brand {
        min-width: 0;
        gap: 6px;
    }

    .app-v2-brand-name {
        display: none;
    }

    .app-v2-brand svg {
        width: 20px;
    }

    .app-v2-mobile-toggle {
        display: grid;
        width: 36px;
        height: 36px;
        margin-inline-end: 2px;
    }

    .app-v2-top-actions {
        gap: 6px;
        flex-shrink: 0;
    }

    .app-v2-action-slot,
    .app-v2-avatar {
        width: 36px;
        height: 36px;
    }

    .app-v2-action-slot:not(.app-v2-search):not(.app-v2-ai) :deep(button),
    .app-v2-action-slot:not(.app-v2-search):not(.app-v2-ai) :deep(a) {
        width: 36px;
        height: 36px;
    }

    .app-v2-search :deep(button[data-search-variant='icon']) {
        width: 36px;
        height: 36px;
    }

    .app-v2-desktop-only {
        display: none !important;
    }

    .app-v2-topnav,
    .app-v2-topnav-spacer,
    .app-v2-sidebar {
        display: none;
    }

    .app-v2-content {
        flex: 1 1 auto;
        min-height: 0;
        height: auto;
        overflow-x: hidden;
        overflow-y: auto;
        overscroll-behavior: contain;
        -webkit-overflow-scrolling: touch;
        padding: 12px 12px 20px;
    }

    .app-v2-content:has(.dashboard-v2) {
        padding: 8px 10px 16px;
        overflow-x: hidden;
        overflow-y: auto;
    }

    .app-v2-ai :deep(span) {
        display: none;
    }
}

@media print {
    .app-v2-topbar,
    .app-v2-sidebar,
    .app-v2-mobile-scrim,
    .app-v2-mobile-drawer {
        display: none !important;
    }

    .app-v2-page {
        height: auto !important;
        min-height: 0 !important;
        overflow: visible !important;
        background: #fff !important;
    }

    .app-v2-shell {
        display: block !important;
        height: auto !important;
        overflow: visible !important;
        background: #fff !important;
    }

    .app-v2-content {
        display: block !important;
        overflow: visible !important;
        padding: 0 !important;
    }
}

@media (prefers-reduced-motion: reduce) {
    .app-v2-topbar,
    .app-v2-sidebar,
    .app-v2-mobile-drawer,
    .app-v2-mobile-scrim {
        animation: none !important;
        transition: none !important;
    }
}
</style>


<style>
.app-v2-nav-tip {
    position: fixed;
    z-index: 99999;
    padding: 7px 11px;
    border-radius: calc(var(--radius, 0.875rem) * 0.75);
    background: var(--school-navy, #111);
    color: #ffffff;
    font-size: 11px;
    font-weight: 550;
    font-family:
        var(--font-sans), 'Plus Jakarta Sans', 'Helvetica Neue', sans-serif;
    white-space: nowrap;
    pointer-events: none;
    transform: translateY(-50%);
    box-shadow: 0 12px 28px
        color-mix(in srgb, var(--school-navy, #000) 35%, transparent);
}

.app-v2-nav-tip.rtl {
    transform: translate(-100%, -50%);
}

.app-v2-nav-tip::before {
    content: '';
    position: absolute;
    top: 50%;
    inset-inline-start: -4px;
    width: 8px;
    height: 8px;
    background: var(--school-navy, #111);
    transform: translateY(-50%) rotate(45deg);
}

.app-v2-nav-tip.rtl::before {
    inset-inline-start: auto;
    inset-inline-end: -4px;
}
</style>
