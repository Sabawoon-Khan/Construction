<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import { Settings2 } from '@lucide/vue';
import { computed, onMounted, onUnmounted, ref } from 'vue';
import SettingsContentSkeleton from '@/components/settings/SettingsContentSkeleton.vue';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { useLocale } from '@/composables/useLocale';
import { useSettingsNavigation } from '@/composables/useSettingsNavigation';
import { useTranslations } from '@/composables/useTranslations';
import { toUrl } from '@/lib/utils';
const { t } = useTranslations();
const { dir } = useLocale();
const { isCurrentOrParentUrl } = useCurrentUrl();
const { navItems, navGroups } = useSettingsNavigation();
const page = usePage();

const navigating = ref(false);
const skeletonVariant = ref<'form' | 'list'>('list');

let removeStart: (() => void) | undefined;
let removeFinish: (() => void) | undefined;
let removeError: (() => void) | undefined;
let removeCancel: (() => void) | undefined;

function isSettingsManagementPath(pathname: string): boolean {
    if (!pathname.startsWith('/settings')) {
        return false;
    }

    const account = [
        '/settings/profile',
        '/settings/security',
        '/settings/appearance',
        '/settings/password',
    ];

    return !account.some(
        (path) => pathname === path || pathname.startsWith(`${path}/`),
    );
}

function skeletonVariantForPath(pathname: string): 'form' | 'list' {
    if (
        pathname.includes('/create') ||
        pathname.endsWith('/general') ||
        pathname.endsWith('/company') ||
        pathname.includes('/translations')
    ) {
        return 'form';
    }

    return 'list';
}

onMounted(() => {
    removeStart = router.on('start', (event) => {
        const visit = event.detail.visit;

        if (visit.method.toLowerCase() !== 'get') {
            return;
        }

        const pathname = visit.url.pathname;

        if (!isSettingsManagementPath(pathname)) {
            return;
        }

        skeletonVariant.value = skeletonVariantForPath(pathname);
        navigating.value = true;
    });

    removeFinish = router.on('finish', () => {
        navigating.value = false;
    });

    removeError = router.on('error', () => {
        navigating.value = false;
    });

    removeCancel = router.on('cancel', () => {
        navigating.value = false;
    });
});

onUnmounted(() => {
    removeStart?.();
    removeFinish?.();
    removeError?.();
    removeCancel?.();
});

const activeItem = computed(
    () =>
        navItems.value.find((item) => isCurrentOrParentUrl(item.href)) ?? null,
);

const pageTitle = computed(() => {
    const component = String(page.component ?? '');
    const path = String(page.url ?? '').split('?')[0] ?? '';

    if (component.includes('Appearance')) return t('Appearance');
    if (component.includes('Profile')) return t('Profile');
    if (path.startsWith('/settings/security') || component.includes('Security')) {
        return t('Security');
    }
    if (component.includes('Users')) return t('Users');
    if (component.includes('Roles')) return t('Roles');
    if (component.includes('GoogleAuth')) return t('Google Auth');
    if (component.includes('Lookups')) return t('Lookups');
    if (
        component.includes('ExpenseCategories') ||
        path.startsWith('/expense-categories')
    ) {
        return t('Expense categories');
    }
    if (component.includes('ExpenseUnits') || path.startsWith('/expense-units')) {
        return t('Expense units');
    }
    if (component.includes('Backups')) return t('Backups');
    if (component.includes('FileManager')) return t('File Manager');
    if (component.includes('Trash')) return t('Trash');
    if (component.includes('Email')) return t('Email');
    if (
        component.includes('settings/Ai') ||
        component.includes('settings/AiTabs') ||
        path.startsWith('/settings/ai')
    ) {
        return t('AI');
    }
    if (component.includes('Offline')) return t('Offline');
    if (component.includes('SystemControl')) return t('System Control');
    if (component.includes('AuthenticationLogs')) return t('Login logs');
    if (component.includes('SystemLog')) return t('System logs');
    if (component.includes('Translations')) return t('Translations');
    if (component.includes('Company')) return t('Company');

    return activeItem.value?.title ?? t('Settings');
});

const pageLead = computed(() => {
    const group = navGroups.value.find((g) =>
        g.items.some((item) => isCurrentOrParentUrl(item.href)),
    );
    return group?.label ?? t('Settings');
});

const pageDescription = computed(() => {
    const title = pageTitle.value;
    const map: Record<string, string> = {
        [t('Profile')]: t('Update your name, email, and account preferences.'),
        [t('Appearance')]: t('Theme, branding, and interface style.'),
        [t('Security')]: t('Password and signed-in devices.'),
        [t('Company')]: t('Company profile and exchange rates.'),
        [t('Users')]: t('Manage who can access this workspace.'),
        [t('Roles')]: t('Permissions and access levels.'),
        [t('Email')]: t('Outgoing mail and delivery settings.'),
        [t('AI')]: t('Models, usage, and assistant controls.'),
        [t('Backups')]: t('Create and restore system backups.'),
        [t('Trash')]: t('Recover or permanently remove deleted items.'),
        [t('File Manager')]: t('Browse and manage stored files.'),
        [t('Translations')]: t('Edit interface language strings.'),
        [t('Google Auth')]: t('Sign-in with Google for your team.'),
        [t('Offline')]: t('Offline sync and conflict handling.'),
        [t('System Control')]: t('Maintenance and system switches.'),
        [t('Login logs')]: t('Recent authentication activity.'),
        [t('System logs')]: t('Application and error logs.'),
        [t('Lookups')]: t('Shared lists used across modules.'),
        [t('Expense categories')]: t('Categories for project expenses.'),
        [t('Expense units')]: t('Units used when recording expenses.'),
    };

    return map[title] ?? t('Configure this area of your workspace.');
});
</script>

<template>
    <div class="settings-v2" :dir="dir" data-tour="settings-layout">
        <aside
            class="settings-aside"
            data-tour="settings-nav"
            :aria-label="t('Settings')"
        >
            <div class="settings-mobile-nav">
                <div class="settings-mobile-track">
                    <Link
                        v-for="item in navItems"
                        :key="toUrl(item.href)"
                        :href="item.href"
                        :title="item.title"
                        class="settings-chip"
                        :class="{ active: isCurrentOrParentUrl(item.href) }"
                    >
                        <component
                            :is="item.icon"
                            class="chip-icon"
                            :stroke-width="1.75"
                        />
                        <span>{{ item.title }}</span>
                    </Link>
                </div>
            </div>

            <div class="settings-nav-card">
                <div class="settings-nav-head">
                    <div class="settings-nav-badge">
                        <Settings2 :stroke-width="1.75" />
                    </div>
                    <div class="settings-nav-copy">
                        <p>{{ t('Settings') }}</p>
                        <small>{{
                            activeItem?.title ?? t('Pick a category')
                        }}</small>
                    </div>
                </div>

                <nav class="settings-nav-groups">
                    <div
                        v-for="group in navGroups"
                        :key="group.key"
                        class="settings-nav-group"
                    >
                        <p class="settings-nav-label">{{ group.label }}</p>
                        <Link
                            v-for="item in group.items"
                            :key="toUrl(item.href)"
                            :href="item.href"
                            class="settings-nav-link"
                            :class="{
                                active: isCurrentOrParentUrl(item.href),
                            }"
                        >
                            <span class="settings-nav-icon">
                                <component
                                    :is="item.icon"
                                    :stroke-width="1.75"
                                />
                            </span>
                            <span class="settings-nav-title">{{
                                item.title
                            }}</span>
                        </Link>
                    </div>
                </nav>
            </div>
        </aside>

        <section class="settings-main">
            <header class="settings-page-head">
                <div class="settings-page-copy">
                    <p class="settings-eyebrow">{{ pageLead }}</p>
                    <h1>{{ pageTitle }}</h1>
                    <p class="settings-desc">{{ pageDescription }}</p>
                </div>
                <div v-if="$slots.actions" class="settings-page-actions">
                    <slot name="actions" />
                </div>
            </header>

            <div class="settings-content">
                <SettingsContentSkeleton
                    v-if="navigating"
                    :variant="skeletonVariant"
                />
                <div v-show="!navigating" class="settings-content-body">
                    <slot />
                </div>
            </div>
        </section>
    </div>
</template>

<style scoped>
.settings-v2 {
    display: grid;
    grid-template-columns: 248px minmax(0, 1fr);
    gap: 16px;
    align-items: start;
    min-width: 0;
    color: var(--settings-ink);
    font-family:
        var(--font-sans), 'Plus Jakarta Sans', 'Helvetica Neue', sans-serif;
}

.settings-v2[dir='rtl'] {
    font-family:
        var(--font-rtl), 'Noto Sans Arabic', 'Plus Jakarta Sans', sans-serif;
}

.settings-aside {
    position: sticky;
    top: 16px;
    display: flex;
    flex-direction: column;
    gap: 10px;
    min-width: 0;
    max-height: calc(100dvh - 32px);
}

.settings-nav-card {
    display: flex;
    flex-direction: column;
    min-height: 0;
    overflow: hidden;
    padding: 8px;
    border: 1px solid rgba(255, 255, 255, 0.62);
    border-radius: 24px;
    background:
        linear-gradient(
            165deg,
            rgba(255, 255, 255, 0.72) 0%,
            rgba(255, 255, 255, 0.38) 100%
        );
    box-shadow:
        0 14px 36px rgba(26, 26, 26, 0.07),
        inset 0 1px rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(22px) saturate(140%);
    -webkit-backdrop-filter: blur(22px) saturate(140%);
}

.settings-nav-head {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 8px 8px 10px;
}

.settings-nav-badge {
    display: grid;
    place-items: center;
    width: 34px;
    height: 34px;
    border-radius: 12px;
    background: rgba(255, 138, 58, 0.14);
    color: #ff8a3a;
    flex-shrink: 0;
}

.settings-nav-badge svg {
    width: 15px;
    height: 15px;
}

.settings-nav-copy {
    min-width: 0;
}

.settings-nav-copy p {
    margin: 0;
    font-size: 12.5px;
    font-weight: 650;
    letter-spacing: -0.2px;
}

.settings-nav-copy small {
    display: block;
    margin-top: 1px;
    overflow: hidden;
    color: var(--settings-soft);
    font-size: 10.5px;
    font-weight: 500;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.settings-nav-groups {
    display: flex;
    flex: 1;
    flex-direction: column;
    gap: 8px;
    min-height: 0;
    overflow: auto;
    padding: 0 0 4px;
    scrollbar-width: thin;
}

.settings-nav-group {
    display: flex;
    flex-direction: column;
    gap: 1px;
}

.settings-nav-group + .settings-nav-group {
    padding-top: 6px;
    border-top: 1px solid rgb(0 0 0 / 4%);
}

.settings-nav-label {
    margin: 0;
    padding: 2px 10px 5px;
    color: var(--settings-soft);
    font-size: 9.5px;
    font-weight: 600;
    letter-spacing: 0.11em;
    text-transform: uppercase;
}

.settings-v2[dir='rtl'] .settings-nav-label {
    letter-spacing: 0.03em;
    text-transform: none;
}

.settings-nav-link {
    display: flex;
    align-items: center;
    gap: 9px;
    min-height: 36px;
    padding: 0 8px;
    border-radius: 999px;
    color: var(--settings-muted, #667085);
    text-decoration: none;
    transition:
        background 0.14s ease,
        color 0.14s ease,
        box-shadow 0.14s ease;
}

.settings-nav-link:hover {
    background: rgba(255, 255, 255, 0.72);
    color: var(--settings-ink, #1a1a1a);
}

.settings-nav-link.active {
    background: #1a1a1a;
    color: white;
    box-shadow: 0 10px 22px rgba(26, 26, 26, 0.22);
}

.settings-nav-icon {
    display: grid;
    place-items: center;
    width: 26px;
    height: 26px;
    border-radius: 999px;
    background: rgba(26, 26, 26, 0.04);
    color: var(--settings-muted, #667085);
    flex-shrink: 0;
    transition:
        background 0.14s ease,
        color 0.14s ease;
}

.settings-nav-link:hover .settings-nav-icon {
    background: rgba(255, 138, 58, 0.14);
    color: #ff8a3a;
}

.settings-nav-link.active .settings-nav-icon {
    background: rgba(255, 255, 255, 0.14);
    color: white;
}

.settings-nav-icon :deep(svg) {
    width: 14px;
    height: 14px;
}

.settings-nav-title {
    flex: 1;
    min-width: 0;
    overflow: hidden;
    font-size: 12px;
    font-weight: 600;
    letter-spacing: -0.1px;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.settings-mobile-nav {
    display: none;
}

.settings-mobile-track {
    display: flex;
    gap: 6px;
    overflow-x: auto;
    padding: 7px;
    border: 1px solid var(--settings-line);
    border-radius: 16px;
    background: var(--settings-panel-strong);
    box-shadow: var(--settings-shadow);
    backdrop-filter: blur(16px);
    scrollbar-width: none;
}

.settings-mobile-track::-webkit-scrollbar {
    display: none;
}

.settings-chip {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    height: 34px;
    padding: 0 11px;
    border-radius: 999px;
    color: var(--settings-muted, #3e454b);
    font-size: 11.5px;
    font-weight: 600;
    text-decoration: none;
    white-space: nowrap;
    transition:
        background 0.14s ease,
        color 0.14s ease,
        box-shadow 0.14s ease;
}

.settings-chip:hover {
    background: color-mix(in srgb, var(--card, #fff) 82%, transparent);
}

.settings-chip.active {
    background: #1a1a1a;
    color: white;
    box-shadow: 0 8px 18px rgba(26, 26, 26, 0.2);
}

.chip-icon {
    width: 13px;
    height: 13px;
    flex-shrink: 0;
}

.settings-main {
    display: flex;
    flex-direction: column;
    gap: 12px;
    min-width: 0;
}

.settings-page-head {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-end;
    justify-content: space-between;
    gap: 12px;
    padding: 16px 18px;
    border: 1px solid rgba(255, 255, 255, 0.62);
    border-radius: 22px;
    background:
        linear-gradient(
            145deg,
            rgba(255, 255, 255, 0.72) 0%,
            rgba(255, 255, 255, 0.4) 100%
        );
    box-shadow:
        0 14px 36px rgba(26, 26, 26, 0.07),
        inset 0 1px rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(22px) saturate(140%);
    -webkit-backdrop-filter: blur(22px) saturate(140%);
}

.settings-page-copy {
    min-width: 0;
    max-width: 560px;
}

.settings-eyebrow {
    margin: 0 0 4px;
    color: var(--settings-soft);
    font-size: 10.5px;
    font-weight: 600;
    letter-spacing: 0.12em;
    text-transform: uppercase;
}

.settings-v2[dir='rtl'] .settings-eyebrow {
    letter-spacing: 0.04em;
    text-transform: none;
}

.settings-page-copy h1 {
    margin: 0;
    color: #1a1a1a;
    font-size: clamp(20px, 2.2vw, 26px);
    font-weight: 650;
    letter-spacing: -0.8px;
    line-height: 1.12;
}

.settings-v2[dir='rtl'] .settings-page-copy h1 {
    letter-spacing: -0.25px;
    line-height: 1.28;
}

.settings-desc {
    margin: 6px 0 0;
    color: var(--settings-muted);
    font-size: 12.5px;
    font-weight: 450;
    line-height: 1.45;
}

.settings-page-actions {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 8px;
}

.settings-content {
    min-height: 240px;
}

@media (max-width: 1024px) {
    .settings-v2 {
        grid-template-columns: 1fr;
        gap: 12px;
    }

    .settings-aside {
        position: static;
        max-height: none;
    }

    .settings-mobile-nav {
        display: block;
    }

    .settings-nav-card {
        display: none;
    }

    .settings-page-head {
        border-radius: 16px;
        padding: 14px 15px;
    }
}

@media (prefers-reduced-motion: reduce) {
    .settings-nav-link,
    .settings-chip {
        transition: none;
    }
}
</style>
