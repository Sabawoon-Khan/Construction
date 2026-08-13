import {
    Banknote,
    Building2,
    ChartColumn,
    CircleDollarSign,
    ClipboardList,
    DatabaseBackup,
    FolderKanban,
    Handshake,
    Landmark,
    LayoutGrid,
    Receipt,
    Settings,
    Shield,
    Users,
    WalletCards,
} from '@lucide/vue';
import { computed } from 'vue';
import { usePermissions } from '@/composables/usePermissions';
import { useTranslations } from '@/composables/useTranslations';
import { dashboard } from '@/routes';
import type { NavGroup, NavItem } from '@/types';

function item(title: string, href: NavItem['href'], icon: NavItem['icon']): NavItem {
    return { title, href, icon };
}

export function useAppNavigation() {
    const { t } = useTranslations();
    const { can, canAny } = usePermissions();

    const navGroups = computed(() => {
        const groups: NavGroup[] = [];

        const overview: NavItem[] = [
            item(t('Dashboard'), dashboard(), LayoutGrid),
        ];

        if (can('dashboard.view')) {
            overview.push(item(t('Reports'), '/reports', ChartColumn));
        }

        if (can('field_reports.view')) {
            overview.push(item(t('Field reports'), '/field-reports', ClipboardList));
        }

        if (can('ai.analytics')) {
            overview.push(item(t('AI Analytics'), '/ai/analytics', ChartColumn));
        }

        groups.push({ label: t('Overview'), items: overview });

        const projectsAndSales: NavItem[] = [];

        if (can('projects.view')) {
            projectsAndSales.push(item(t('Projects'), '/projects', FolderKanban));
        }

        if (can('units.view')) {
            projectsAndSales.push(item(t('Units'), '/units', Building2));
        }

        if (can('sales.view')) {
            projectsAndSales.push(item(t('Sales'), '/sales', CircleDollarSign));
        }

        if (can('customers.view')) {
            projectsAndSales.push(item(t('Customers'), '/customers', Users));
        }

        if (projectsAndSales.length) {
            groups.push({ label: t('Projects & Sales'), items: projectsAndSales });
        }

        const finance: NavItem[] = [];

        if (can('expenses.view')) {
            finance.push(item(t('Expenses'), '/expenses', Receipt));
        }

        if (can('incomes.view')) {
            finance.push(item(t('Incomes'), '/incomes', WalletCards));
        }

        if (can('payments.view')) {
            finance.push(item(t('Payments'), '/payments', Banknote));
        }

        if (can('bank_accounts.view')) {
            finance.push(item(t('Bank Accounts'), '/bank-accounts', Landmark));
        }

        if (finance.length) {
            groups.push({ label: t('Finance'), items: finance });
        }

        if (can('partners.view')) {
            groups.push({
                label: t('Shareholders'),
                items: [item(t('Shareholders'), '/partners', Handshake)],
            });
        }

        const administration: NavItem[] = [];

        if (
            can('settings.manage') ||
            can('users.view') ||
            can('roles.view') ||
            can('backups.view') ||
            can('storage.view')
        ) {
            administration.push({
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
            administration.push(item(t('Users'), '/settings/users', Users));
        }

        if (can('roles.view')) {
            administration.push(item(t('Roles'), '/settings/roles', Shield));
        }

        if (can('backups.view') || canAny(['backups.run', 'backups.download'])) {
            administration.push(item(t('Backups'), '/settings/backups', DatabaseBackup));
        }

        if (administration.length) {
            groups.push({ label: t('Administration'), items: administration });
        }

        return groups;
    });

    const allNavItems = computed(() => navGroups.value.flatMap((group) => group.items));

    return {
        navGroups,
        allNavItems,
    };
}
