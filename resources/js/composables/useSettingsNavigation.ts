import {
    Building2,
    DatabaseBackup,
    FolderOpen,
    Handshake,
    History,
    Languages,
    Palette,
    ScrollText,
    Shield,
    Trash2,
    UserRound,
    Users,
} from '@lucide/vue';
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { usePermissions } from '@/composables/usePermissions';
import { useTranslations } from '@/composables/useTranslations';
import { edit as editAppearance } from '@/routes/appearance';
import { edit as editProfile } from '@/routes/profile';
import type { NavItem } from '@/types';

export type SettingsNavItem = NavItem & {
    group: 'account' | 'administration';
};

export function useSettingsNavigation() {
    const { t } = useTranslations();
    const { can } = usePermissions();
    const page = usePage();

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
                createHref: '/settings/users/create',
                createPermission: 'users.create',
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

        if (can('partners.view')) {
            items.push({
                title: t('Shareholders'),
                href: '/partners',
                icon: Handshake,
                group: 'administration',
                createHref: '/partners/create',
                createPermission: 'partners.create',
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

        if (can('audit.view') || can('settings.manage')) {
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

    return {
        navItems,
        accountItems,
        adminItems,
    };
}
