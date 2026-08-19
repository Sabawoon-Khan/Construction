import { computed } from 'vue';
import { useAppNavigation } from '@/composables/useAppNavigation';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { useSettingsNavigation } from '@/composables/useSettingsNavigation';
import { toUrl } from '@/lib/utils';
import type { NavItem } from '@/types';

export function navItemPath(href: NavItem['href']): string {
    const raw = toUrl(href).split('?')[0] ?? '';

    if (!raw.startsWith('http')) {
        return raw || '/';
    }

    try {
        return new URL(raw).pathname;
    } catch {
        return raw;
    }
}

function matchScore(href: NavItem['href'], currentPath: string): number {
    const path = navItemPath(href);

    if (currentPath === path) {
        return path.length + 1000;
    }

    if (path !== '/' && currentPath.startsWith(`${path}/`)) {
        return path.length;
    }

    return -1;
}

export function matchNavItem(items: NavItem[], currentPath: string): NavItem | null {
    let best: NavItem | null = null;
    let bestScore = -1;

    for (const item of items) {
        const score = matchScore(item.href, currentPath);

        if (score > bestScore) {
            best = item;
            bestScore = score;
        }
    }

    return best;
}

export function useModuleTabs() {
    const { tabGroups } = useAppNavigation();
    const { navItems: settingsItems } = useSettingsNavigation();
    const { currentUrl } = useCurrentUrl();

    const isSettingsArea = computed(() => {
        const path = currentUrl.value;

        return (
            path.startsWith('/settings')
            || path === '/partners'
            || path.startsWith('/partners/')
        );
    });

    const tabItems = computed<NavItem[]>(() => {
        if (isSettingsArea.value) {
            return settingsItems.value;
        }

        const path = currentUrl.value;
        let bestGroup = tabGroups.value[0];
        let bestScore = -1;

        for (const group of tabGroups.value) {
            const match = matchNavItem(group.items, path);

            if (!match) {
                continue;
            }

            const score = matchScore(match.href, path);

            if (score > bestScore) {
                bestGroup = group;
                bestScore = score;
            }
        }

        return bestGroup?.items ?? [];
    });

    const activeItem = computed(() => matchNavItem(tabItems.value, currentUrl.value));

    const activeId = computed(() => {
        const item = activeItem.value;

        return item ? navItemPath(item.href) : '';
    });

    return {
        tabItems,
        activeItem,
        activeId,
        isSettingsArea,
    };
}
