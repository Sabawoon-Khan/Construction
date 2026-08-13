<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import {
    Building2,
    DatabaseBackup,
    FolderOpen,
    History,
    KeyRound,
    Languages,
    ListTree,
    Mail,
    Palette,
    ScrollText,
    Settings2,
    Shield,
    Sparkles,
    Trash2,
    UserRound,
    Users,
    Wifi,
} from '@lucide/vue';
import { computed, onMounted, onUnmounted, ref } from 'vue';
import SettingsContentSkeleton from '@/components/settings/SettingsContentSkeleton.vue';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { usePermissions } from '@/composables/usePermissions';
import { useTranslations } from '@/composables/useTranslations';
import { toUrl } from '@/lib/utils';
import { edit as editAppearance } from '@/routes/appearance';
import { edit as editProfile } from '@/routes/profile';
import type { NavItem } from '@/types';

type SettingsNavItem = NavItem & {
    icon: typeof Settings2;
    group: 'account' | 'administration';
};

const { t } = useTranslations();
const { can } = usePermissions();
const { isCurrentOrParentUrl } = useCurrentUrl();
const page = usePage();

const navigating = ref(false);
const skeletonVariant = ref<'form' | 'list'>('list');

let removeStart: (() => void) | undefined;
let removeFinish: (() => void) | undefined;
let removeError: (() => void) | undefined;
let removeCancel: (() => void) | undefined;

function isSettingsManagementPath(pathname: string): boolean {
    if (! pathname.startsWith('/settings')) {
        return false;
    }

    // Account pages keep a lighter feel — skeleton is for management surfaces.
    const account = [
        '/settings/profile',
        '/settings/security',
        '/settings/appearance',
        '/settings/password',
    ];

    return ! account.some(
        (path) => pathname === path || pathname.startsWith(`${path}/`),
    );
}

function skeletonVariantForPath(pathname: string): 'form' | 'list' {
    if (
        pathname.includes('/create') ||
        pathname.endsWith('/offline') ||
        pathname.endsWith('/google-auth') ||
        pathname.endsWith('/system-control') ||
        pathname.endsWith('/general') ||
        pathname.endsWith('/company') ||
        pathname.includes('/ai') ||
        pathname.includes('/email') ||
        pathname.includes('/translations') ||
        pathname.includes('/lookups')
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

        if (! isSettingsManagementPath(pathname)) {
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

const navItems = computed<SettingsNavItem[]>(() => {
    const items: SettingsNavItem[] = [
        {
            title: t('Profile'),
            href: editProfile(),
            icon: UserRound,
            group: 'account',
        },
        {
            title: t('Appearance'),
            href: editAppearance(),
            icon: Palette,
            group: 'account',
        },
    ];

    if (can('settings.manage')) {
        items.push(
            {
                title: t('System Control'),
                href: '/settings/system-control',
                icon: Settings2,
                group: 'administration',
            },
            {
                title: t('Offline & PWA'),
                href: '/settings/offline',
                icon: Wifi,
                group: 'administration',
            },
            {
                title: t('Google Auth'),
                href: '/settings/google-auth',
                icon: KeyRound,
                group: 'administration',
            },
            {
                title: t('Lookups'),
                href: '/settings/lookups',
                icon: ListTree,
                group: 'administration',
            },
        );

        const features = page.props.kitFeatures as
            { translations_ui?: boolean } | undefined;

        if (features?.translations_ui !== false) {
            items.push({
                title: t('Translations'),
                href: '/settings/translations',
                icon: Languages,
                group: 'administration',
            });
        }
    }

    if (can('companies.view') || can('companies.edit')) {
        items.push({
            title: t('Company'),
            href: '/settings/company',
            icon: Building2,
            group: 'administration',
        });
    }

    if (can('users.view')) {
        items.push({
            title: t('Users'),
            href: '/settings/users',
            icon: Users,
            group: 'administration',
        });
    }

    if (can('roles.view')) {
        items.push({
            title: t('Roles'),
            href: '/settings/roles',
            icon: Shield,
            group: 'administration',
        });
    }

    if (can('backups.view')) {
        items.push({
            title: t('Backups'),
            href: '/settings/backups',
            icon: DatabaseBackup,
            group: 'administration',
        });
    }

    if (can('storage.view')) {
        items.push({
            title: t('File Manager'),
            href: '/settings/file-manager',
            icon: FolderOpen,
            group: 'administration',
        });
    }

    if (can('trash.view')) {
        items.push({
            title: t('Trash'),
            href: '/settings/trash',
            icon: Trash2,
            group: 'administration',
        });
    }

    if (can('settings.manage') || can('audit.view')) {
        items.push(
            {
                title: t('AI'),
                href: can('settings.manage')
                    ? '/settings/ai'
                    : '/settings/ai/logs',
                icon: Sparkles,
                group: 'administration',
            },
            {
                title: t('Email'),
                href: can('settings.manage')
                    ? '/settings/email'
                    : '/settings/email/logs',
                icon: Mail,
                group: 'administration',
            },
        );
    }

    if (can('audit.view')) {
        items.push(
            {
                title: t('Login logs'),
                href: '/settings/login-logs',
                icon: History,
                group: 'administration',
            },
            {
                title: t('System logs'),
                href: '/settings/system-logs',
                icon: ScrollText,
                group: 'administration',
            },
        );
    }

    return items;
});

const accountItems = computed(() =>
    navItems.value.filter((item) => item.group === 'account'),
);
const adminItems = computed(() =>
    navItems.value.filter((item) => item.group === 'administration'),
);

const activeItem = computed(
    () =>
        navItems.value.find((item) => isCurrentOrParentUrl(item.href)) ?? null,
);

const pageTitle = computed(() => {
    const component = String(page.component ?? '');

    if (component.includes('Appearance')) {
        return t('Appearance');
    }

    if (component.includes('Profile')) {
        return t('Profile');
    }

    if (component.includes('Offline')) {
        return t('Offline & PWA');
    }

    if (component.includes('GoogleAuth')) {
        return t('Google Auth');
    }

    if (component.includes('/Ai') || component.endsWith('Ai')) {
        return t('AI');
    }

    if (component.includes('Users')) {
        return t('Users');
    }

    if (component.includes('Roles')) {
        return t('Roles');
    }

    if (component.includes('Backups')) {
        return t('Backups');
    }

    if (component.includes('FileManager')) {
        return t('File Manager');
    }

    if (component.includes('Trash')) {
        return t('Trash');
    }

    if (component.includes('Email')) {
        return t('Email');
    }

    if (component.includes('AuthenticationLogs')) {
        return t('Login logs');
    }

    if (component.includes('SystemLog')) {
        return t('System logs');
    }

    if (component.includes('Translations')) {
        return t('Translations');
    }

    if (component.includes('Lookups')) {
        return t('Lookups');
    }

    return activeItem.value?.title ?? t('Settings');
});
</script>

<template>
    <div class="px-1 py-2 md:px-0" data-tour="settings-layout">
        <div class="flex flex-col gap-6 lg:flex-row lg:items-start lg:gap-8">
            <aside
                class="w-full shrink-0 lg:sticky lg:top-[88px] lg:w-[268px]"
                data-tour="settings-nav"
                :aria-label="t('Settings')"
            >
                <!-- Mobile horizontal chips -->
                <div class="mb-2 lg:hidden">
                    <div
                        class="no-scrollbar flex gap-1 overflow-x-auto rounded-2xl border border-border/80 bg-card p-2 shadow-soft"
                    >
                        <Link
                            v-for="item in navItems"
                            :key="toUrl(item.href)"
                            :href="item.href"
                            :title="item.title"
                            :class="[
                                'flex shrink-0 items-center gap-2 rounded-xl px-3 py-2 text-xs font-semibold transition',
                                isCurrentOrParentUrl(item.href)
                                    ? 'bg-[var(--school-navy)] text-white'
                                    : 'text-foreground/80 hover:bg-muted',
                            ]"
                        >
                            <component
                                :is="item.icon"
                                class="size-4 shrink-0"
                                :stroke-width="1.75"
                            />
                            <span class="max-w-[8rem] truncate">{{
                                item.title
                            }}</span>
                        </Link>
                    </div>
                </div>

                <div
                    class="hidden rounded-2xl border border-border/80 bg-card p-2 shadow-soft lg:block"
                >
                    <div class="flex items-center gap-2 px-3 pt-2.5 pb-2">
                        <div
                            class="flex size-7 items-center justify-center rounded-lg bg-[var(--school-navy)]/10 text-[var(--school-navy)]"
                        >
                            <Settings2 class="size-3.5" :stroke-width="1.75" />
                        </div>
                        <div class="min-w-0 flex-1">
                            <p
                                class="text-[12.5px] font-semibold tracking-tight text-foreground"
                            >
                                {{ t('Settings') }}
                            </p>
                            <p class="text-[10.5px] text-muted-foreground">
                                {{ activeItem?.title ?? t('Pick a category') }}
                            </p>
                        </div>
                    </div>

                    <div class="mx-2 my-1 h-px bg-border" aria-hidden="true" />

                    <nav class="space-y-3 p-1">
                        <div class="space-y-0.5">
                            <p
                                class="px-3 py-1.5 text-[10px] font-semibold tracking-[0.14em] text-muted-foreground uppercase"
                            >
                                {{ t('Account') }}
                            </p>
                            <Link
                                v-for="item in accountItems"
                                :key="toUrl(item.href)"
                                :href="item.href"
                                :class="[
                                    'group flex items-center gap-3 rounded-xl px-3 py-2.5 text-[13px] font-medium tracking-tight transition-colors duration-150',
                                    isCurrentOrParentUrl(item.href)
                                        ? 'bg-[var(--school-navy)] text-white shadow-[0_10px_18px_-14px_rgba(15,23,42,0.55)]'
                                        : 'text-foreground/80 hover:bg-muted',
                                ]"
                            >
                                <span
                                    class="flex size-8 shrink-0 items-center justify-center rounded-lg transition-colors"
                                    :class="
                                        isCurrentOrParentUrl(item.href)
                                            ? 'bg-white/15 text-white'
                                            : 'bg-muted text-muted-foreground group-hover:bg-background group-hover:text-[var(--school-navy)]'
                                    "
                                >
                                    <component
                                        :is="item.icon"
                                        class="size-4"
                                        :stroke-width="1.75"
                                    />
                                </span>
                                <span
                                    class="min-w-0 flex-1 truncate text-[12.5px] leading-tight font-semibold"
                                >
                                    {{ item.title }}
                                </span>
                                <span
                                    aria-hidden="true"
                                    class="size-1.5 shrink-0 rounded-full transition-colors"
                                    :class="
                                        isCurrentOrParentUrl(item.href)
                                            ? 'bg-white'
                                            : 'bg-transparent group-hover:bg-border'
                                    "
                                />
                            </Link>
                        </div>

                        <div v-if="adminItems.length" class="space-y-0.5">
                            <div
                                class="mx-2 my-1 h-px bg-border"
                                aria-hidden="true"
                            />
                            <p
                                class="px-3 py-1.5 text-[10px] font-semibold tracking-[0.14em] text-muted-foreground uppercase"
                            >
                                {{ t('Administration') }}
                            </p>
                            <Link
                                v-for="item in adminItems"
                                :key="toUrl(item.href)"
                                :href="item.href"
                                :class="[
                                    'group flex items-center gap-3 rounded-xl px-3 py-2.5 text-[13px] font-medium tracking-tight transition-colors duration-150',
                                    isCurrentOrParentUrl(item.href)
                                        ? 'bg-[var(--school-navy)] text-white shadow-[0_10px_18px_-14px_rgba(15,23,42,0.55)]'
                                        : 'text-foreground/80 hover:bg-muted',
                                ]"
                            >
                                <span
                                    class="flex size-8 shrink-0 items-center justify-center rounded-lg transition-colors"
                                    :class="
                                        isCurrentOrParentUrl(item.href)
                                            ? 'bg-white/15 text-white'
                                            : 'bg-muted text-muted-foreground group-hover:bg-background group-hover:text-[var(--school-navy)]'
                                    "
                                >
                                    <component
                                        :is="item.icon"
                                        class="size-4"
                                        :stroke-width="1.75"
                                    />
                                </span>
                                <span
                                    class="min-w-0 flex-1 truncate text-[12.5px] leading-tight font-semibold"
                                >
                                    {{ item.title }}
                                </span>
                                <span
                                    aria-hidden="true"
                                    class="size-1.5 shrink-0 rounded-full transition-colors"
                                    :class="
                                        isCurrentOrParentUrl(item.href)
                                            ? 'bg-white'
                                            : 'bg-transparent group-hover:bg-border'
                                    "
                                />
                            </Link>
                        </div>
                    </nav>
                </div>
            </aside>

            <section class="min-w-0 flex-1">
                <div
                    class="mb-6 flex flex-wrap items-end justify-between gap-3 border-b border-border/70 pb-4"
                >
                    <div class="min-w-0">
                        <p
                            class="text-[11px] font-semibold tracking-[0.16em] text-muted-foreground uppercase"
                        >
                            {{ t('Settings') }}
                        </p>
                        <h1
                            class="mt-1 text-[20px] font-semibold tracking-tight text-foreground sm:text-[22px]"
                        >
                            {{ pageTitle }}
                        </h1>
                    </div>
                    <div
                        v-if="$slots.actions"
                        class="flex flex-wrap items-center gap-2"
                    >
                        <slot name="actions" />
                    </div>
                </div>

                <div class="w-full space-y-6">
                    <SettingsContentSkeleton
                        v-if="navigating"
                        :variant="skeletonVariant"
                    />
                    <div v-show="!navigating" class="w-full space-y-6">
                        <slot />
                    </div>
                </div>
            </section>
        </div>
    </div>
</template>
