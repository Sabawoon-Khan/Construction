import { createInertiaApp, router } from '@inertiajs/vue3';
import { initializeTheme } from '@/composables/useAppearance';
import { initializeLocale } from '@/composables/useLocale';
import AppLayout from '@/layouts/AppLayout.vue';
import AuthLayout from '@/layouts/AuthLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { initializeFlashToast } from '@/lib/flashToast';
import {
    applyThemeColors,
    cacheThemeColors,
    initializeThemeColors,
    normalizeThemeColors
    
} from '@/lib/theme-colors';
import type {ThemeColorSettings} from '@/lib/theme-colors';
import { initializeOffline, initializeOfflineForUser } from '@/offline/register';
import type { OfflineClientSettings } from '@/offline/settings';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

type PageAuthUser = {
    id: number;
    org_id?: number | null;
};

type OfflinePageProps = {
    auth?: { user?: PageAuthUser | null };
    offlineEnabled?: boolean;
    offlineSettings?: Partial<OfflineClientSettings>;
    themeColors?: ThemeColorSettings;
};

function syncOfflineFromPage(props: OfflinePageProps): void {
    const pageTheme = props.themeColors;

    if (pageTheme) {
        const normalized = normalizeThemeColors(pageTheme);
        cacheThemeColors(normalized);
        applyThemeColors(normalized);
    }

    const user = props.auth?.user
        ? { id: props.auth.user.id, org_id: props.auth.user.org_id ?? null }
        : null;

    void initializeOfflineForUser(user, {
        offlineEnabled: props.offlineEnabled,
        offlineSettings: props.offlineSettings,
    });
}

function readInitialPageProps(): OfflinePageProps | null {
    if (typeof document === 'undefined') {
        return null;
    }

    const el = document.getElementById('app');
    const raw = el?.dataset.page;

    if (! raw) {
        return null;
    }

    try {
        const page = JSON.parse(raw) as { props?: OfflinePageProps };

        return page.props ?? null;
    } catch {
        return null;
    }
}

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    layout: (name) => {
        switch (true) {
            case name === 'Welcome':
            case name.startsWith('PublicPortfolio/'):
            case name.startsWith('Public/'):
                return null;
            case name.startsWith('auth/'):
                return AuthLayout;
            case name.startsWith('settings/'):
            case name.startsWith('Partners/'):
                return [AppLayout, SettingsLayout];
            default:
                return AppLayout;
        }
    },
    progress: {
        color: '#0d9488',
    },
});

initializeTheme();
initializeThemeColors();
initializeLocale();
initializeFlashToast();
initializeOffline();

const initialProps = readInitialPageProps();

if (initialProps) {
    syncOfflineFromPage(initialProps);
}

router.on('navigate', (event) => {
    syncOfflineFromPage(event.detail.page.props as OfflinePageProps);
});
