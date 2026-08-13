import {
    Boxes,
    Building2,
    ChartColumn,
    CircleDollarSign,
    ClipboardList,
    DatabaseBackup,
    FolderKanban,
    Handshake,
    LayoutGrid,
    Package,
    Receipt,
    Settings,
    Shield,
    Truck,
    Users,
    WalletCards,
} from '@lucide/vue';
import { computed } from 'vue';
import { usePermissions } from '@/composables/usePermissions';
import { useTranslations } from '@/composables/useTranslations';
import { dashboard } from '@/routes';
import type { NavItem } from '@/types';

export function useAppNavigation() {
    const { t } = useTranslations();
    const { can, canAny } = usePermissions();

    const mainNavItems = computed(() => {
        const items: NavItem[] = [
            {
                title: t('Dashboard'),
                href: dashboard(),
                icon: LayoutGrid,
            },
        ];

        if (can('field_reports.view')) {
            items.push({
                title: t('Field reports'),
                href: '/field-reports',
                icon: ClipboardList,
            });
        }

        if (can('ai.analytics')) {
            items.push({
                title: t('AI Analytics'),
                href: '/ai/analytics',
                icon: ChartColumn,
            });
        }

        return items;
    });

    const constructionNavItems = computed(() => {
        const items: NavItem[] = [];

        if (can('projects.view')) {
            items.push({
                title: t('Projects'),
                href: '/projects',
                icon: FolderKanban,
            });
        }

        if (can('units.view')) {
            items.push({
                title: t('Units'),
                href: '/units',
                icon: Building2,
            });
        }

        if (can('sales.view')) {
            items.push({
                title: t('Sales'),
                href: '/sales',
                icon: CircleDollarSign,
            });
        }

        if (can('expenses.view')) {
            items.push({
                title: t('Expenses'),
                href: '/expenses',
                icon: Receipt,
            });
        }

        if (can('incomes.view')) {
            items.push({
                title: t('Incomes'),
                href: '/incomes',
                icon: WalletCards,
            });
        }

        if (can('customers.view')) {
            items.push({
                title: t('Customers'),
                href: '/customers',
                icon: Users,
            });
        }

        if (can('partners.view')) {
            items.push({
                title: t('Shareholders'),
                href: '/partners',
                icon: Handshake,
            });
        }

        if (can('suppliers.view')) {
            items.push({
                title: t('Suppliers'),
                href: '/suppliers',
                icon: Package,
            });
        }

        if (can('contractors.view')) {
            items.push({
                title: t('Contractors'),
                href: '/contractors',
                icon: Truck,
            });
        }

        if (can('materials.view')) {
            items.push({
                title: t('Materials'),
                href: '/materials',
                icon: Boxes,
            });
        }

        if (can('bank_accounts.view')) {
            items.push({
                title: t('Bank Accounts'),
                href: '/bank-accounts',
                icon: Building2,
            });
        }

        if (can('dashboard.view')) {
            items.push({
                title: t('Reports'),
                href: '/reports',
                icon: ChartColumn,
            });
        }

        if (can('expense_categories.view')) {
            items.push({
                title: t('Expense Categories'),
                href: '/expense-categories',
                icon: Receipt,
            });
        }

        if (can('income_categories.view')) {
            items.push({
                title: t('Income Categories'),
                href: '/income-categories',
                icon: WalletCards,
            });
        }

        return items;
    });

    const adminNavItems = computed(() => {
        const items: NavItem[] = [];

        if (
            can('settings.manage') ||
            can('users.view') ||
            can('roles.view') ||
            can('backups.view') ||
            can('storage.view')
        ) {
            items.push({
                title: t('Settings'),
                href: can('settings.manage')
                    ? '/settings/appearance'
                    : can('users.view')
                      ? '/settings/users'
                      : can('roles.view')
                        ? '/settings/roles'
                        : can('backups.view')
                          ? '/settings/backups'
                          : '/settings/file-manager',
                icon: Settings,
            });
        }

        if (can('users.view')) {
            items.push({
                title: t('Users'),
                href: '/settings/users',
                icon: Users,
            });
        }

        if (can('roles.view')) {
            items.push({
                title: t('Roles'),
                href: '/settings/roles',
                icon: Shield,
            });
        }

        if (
            can('backups.view') ||
            canAny(['backups.run', 'backups.download'])
        ) {
            items.push({
                title: t('Backups'),
                href: '/settings/backups',
                icon: DatabaseBackup,
            });
        }

        return items;
    });

    const allNavItems = computed(() => [
        ...mainNavItems.value,
        ...constructionNavItems.value,
        ...adminNavItems.value,
    ]);

    return {
        mainNavItems,
        constructionNavItems,
        adminNavItems,
        allNavItems,
    };
}
