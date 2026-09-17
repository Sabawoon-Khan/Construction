import {
    Bot,
    Building2,
    CloudOff,
    DatabaseBackup,
    FolderOpen,
    Globe,
    History,
    KeyRound,
    Languages,
    ListTree,
    Lock,
    Mail,
    Palette,
    Receipt,
    Ruler,
    ScrollText,
    Shield,
    SlidersHorizontal,
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

export type SettingsNavGroup =
    | 'account'
    | 'company'
    | 'access'
    | 'data'
    | 'system';

export type SettingsNavItem = NavItem & {
    group: SettingsNavGroup;
};

export const SETTINGS_NAV_GROUP_ORDER: SettingsNavGroup[] = [
    'account',
    'company',
    'access',
    'data',
    'system',
];

export function useSettingsNavigation() {
    const { t } = useTranslations();
    const { can } = usePermissions();
    const page = usePage();

    const groupLabels = computed<Record<SettingsNavGroup, string>>(() => ({
        account: t('Account'),
        company: t('Company'),
        access: t('Access'),
        data: t('Data'),
        system: t('System'),
    }));

    const navItems = computed<SettingsNavItem[]>(() => {
        const items: SettingsNavItem[] = [
            {
                title: t('Profile'),
                href: editProfile(),
                icon: UserRound,
                group: 'account',
            },
            {
                title: t('Security'),
                href: '/settings/security',
                icon: Lock,
                group: 'account',
            },
            {
                title: t('Appearance'),
                href: editAppearance(),
                icon: Palette,
                group: 'account',
            },
        ];

        if (can('companies.view') || can('companies.edit')) {
            items.push({
                title: t('Company'),
                href: '/settings/company',
                icon: Building2,
                group: 'company',
            });
        }

        if (can('settings.manage')) {
            const features = page.props.kitFeatures as
                { translations_ui?: boolean } | undefined;

            if (features?.translations_ui !== false) {
                items.push(
                    {
                        title: t('Translations'),
                        href: '/settings/translations',
                        icon: Languages,
                        group: 'company',
                    },
                    {
                        title: t('Website copy'),
                        href: '/settings/translations?search=home.',
                        icon: Globe,
                        group: 'company',
                    },
                );
            }
        }

        if (can('users.view')) {
            items.push({
                title: t('Users'),
                href: '/settings/users',
                icon: Users,
                group: 'access',
                createHref: '/settings/users/create',
                createPermission: 'users.create',
            });
        }

        if (can('roles.view')) {
            items.push({
                title: t('Roles'),
                href: '/settings/roles',
                icon: Shield,
                group: 'access',
            });
        }

        if (can('settings.manage')) {
            items.push({
                title: t('Google Auth'),
                href: '/settings/google-auth',
                icon: KeyRound,
                group: 'access',
            });
        }

        if (can('settings.manage')) {
            items.push({
                title: t('Lookups'),
                href: '/settings/lookups',
                icon: ListTree,
                group: 'data',
            });
        }

        if (can('expenses.view')) {
            items.push(
                {
                    title: t('Expense categories'),
                    href: '/expense-categories',
                    icon: Receipt,
                    group: 'data',
                },
                {
                    title: t('Expense units'),
                    href: '/expense-units',
                    icon: Ruler,
                    group: 'data',
                },
            );
        }

        if (can('storage.view')) {
            items.push({
                title: t('File Manager'),
                href: '/settings/file-manager',
                icon: FolderOpen,
                group: 'data',
            });
        }

        if (can('trash.view')) {
            items.push({
                title: t('Trash'),
                href: '/settings/trash',
                icon: Trash2,
                group: 'data',
            });
        }

        if (can('backups.view')) {
            items.push({
                title: t('Backups'),
                href: '/settings/backups',
                icon: DatabaseBackup,
                group: 'data',
            });
        }

        if (can('settings.manage')) {
            items.push(
                {
                    title: t('Email'),
                    href: '/settings/email',
                    icon: Mail,
                    group: 'system',
                },
                {
                    title: t('AI'),
                    href: '/settings/ai',
                    icon: Bot,
                    group: 'system',
                },
                {
                    title: t('Offline'),
                    href: '/settings/offline',
                    icon: CloudOff,
                    group: 'system',
                },
                {
                    title: t('System Control'),
                    href: '/settings/system-control',
                    icon: SlidersHorizontal,
                    group: 'system',
                },
            );
        }

        if (can('audit.view') || can('settings.manage')) {
            items.push(
                {
                    title: t('Login logs'),
                    href: '/settings/login-logs',
                    icon: History,
                    group: 'system',
                },
                {
                    title: t('System logs'),
                    href: '/settings/system-logs',
                    icon: ScrollText,
                    group: 'system',
                },
            );
        }

        return items;
    });

    const navGroups = computed(() =>
        SETTINGS_NAV_GROUP_ORDER.map((group) => ({
            key: group,
            label: groupLabels.value[group],
            items: navItems.value.filter((item) => item.group === group),
        })).filter((group) => group.items.length > 0),
    );

    const accountItems = computed(() =>
        navItems.value.filter((item) => item.group === 'account'),
    );
    const adminItems = computed(() =>
        navItems.value.filter((item) => item.group !== 'account'),
    );

    return {
        navItems,
        navGroups,
        groupLabels,
        accountItems,
        adminItems,
    };
}
