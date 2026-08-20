import {
    Banknote,
    Boxes,
    Building2,
    ChartColumn,
    CircleDollarSign,
    ClipboardList,
    Clock,
    FolderKanban,
    Images,
    Inbox,
    LayoutGrid,
    Package,
    Receipt,
    Scale,
    Settings,
    Truck,
    Users,
    Warehouse,
    WalletCards,
} from '@lucide/vue';
import { computed } from 'vue';
import { usePermissions } from '@/composables/usePermissions';
import { useTranslations } from '@/composables/useTranslations';
import { dashboard } from '@/routes';
import { toUrl } from '@/lib/utils';
import type { NavGroup, NavItem } from '@/types';

function item(
    title: string,
    href: NavItem['href'],
    icon: NavItem['icon'],
    create?: { href: string; permission: string },
): NavItem {
    return {
        title,
        href,
        icon,
        createHref: create?.href,
        createPermission: create?.permission,
    };
}

function pathOf(href: NavItem['href']): string {
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

export function useAppNavigation() {
    const { t } = useTranslations();
    const { can } = usePermissions();

    const tabGroups = computed(() => {
        const groups: NavGroup[] = [];

        const overview: NavItem[] = [
            item(t('Dashboard'), dashboard(), LayoutGrid),
        ];

        groups.push({ label: t('Overview'), items: overview });

        const fieldWork: NavItem[] = [];

        if (can('field_reports.view')) {
            fieldWork.push(
                item(t('Field reports'), '/field-reports', ClipboardList, {
                    href: '/field-reports/create',
                    permission: 'field_reports.create',
                }),
            );
        }

        if (fieldWork.length) {
            groups.push({ label: t('Field work'), items: fieldWork });
        }

        const reports: NavItem[] = [];

        if (can('dashboard.view')) {
            reports.push(item(t('Reports'), '/reports', ChartColumn));
            reports.push(item(t('Balance Sheet'), '/balance-sheet', Scale));
        }

        if (can('ai.analytics')) {
            reports.push(item(t('AI Analytics'), '/ai/analytics', ChartColumn));
        }

        if (reports.length) {
            groups.push({ label: t('Reports'), items: reports });
        }

        const projectsAndSales: NavItem[] = [];

        if (can('projects.view')) {
            projectsAndSales.push(
                item(t('Projects'), '/projects', FolderKanban, {
                    href: '/projects/create',
                    permission: 'projects.create',
                }),
            );
        }

        if (can('units.view')) {
            projectsAndSales.push(
                item(t('Units'), '/units', Building2, {
                    href: '/units/create',
                    permission: 'units.create',
                }),
            );
        }

        if (can('sales.view')) {
            projectsAndSales.push(
                item(t('Sales'), '/sales', CircleDollarSign, {
                    href: '/sales/create',
                    permission: 'sales.create',
                }),
            );
        }

        if (can('customers.view')) {
            projectsAndSales.push(
                item(t('Customers'), '/customers', Users, {
                    href: '/customers/create',
                    permission: 'customers.create',
                }),
            );
        }

        if (projectsAndSales.length) {
            groups.push({ label: t('Projects'), items: projectsAndSales });
        }

        const purchaseAndStock: NavItem[] = [];

        if (can('purchases.view')) {
            purchaseAndStock.push(
                item(t('Purchases'), '/purchases', Package, {
                    href: '/purchases/create',
                    permission: 'purchases.create',
                }),
            );
        }

        if (can('suppliers.view')) {
            purchaseAndStock.push(
                item(t('Suppliers'), '/suppliers', Truck, {
                    href: '/suppliers/create',
                    permission: 'suppliers.create',
                }),
            );
        }

        if (can('materials.view')) {
            purchaseAndStock.push(
                item(t('Materials'), '/materials', Boxes, {
                    href: '/materials/create',
                    permission: 'materials.create',
                }),
            );
        }

        if (can('inventory.view')) {
            purchaseAndStock.push(item(t('Stock'), '/inventory', Warehouse));
        }

        if (purchaseAndStock.length) {
            groups.push({ label: t('Purchases'), items: purchaseAndStock });
        }

        const finance: NavItem[] = [];

        if (can('expenses.view')) {
            finance.push(
                item(t('Expenses'), '/expenses', Receipt, {
                    href: '/expenses/create',
                    permission: 'expenses.create',
                }),
            );
            finance.push(item(t('Expense units'), '/expense-units', Receipt));
        }

        if (can('incomes.view')) {
            finance.push(
                item(t('Incomes'), '/incomes', WalletCards, {
                    href: '/incomes/create',
                    permission: 'incomes.create',
                }),
            );
        }

        if (can('payments.view')) {
            finance.push(item(t('Payments'), '/payments', Banknote));
        }

        if (finance.length) {
            groups.push({ label: t('Finance'), items: finance });
        }

        const hr: NavItem[] = [];

        if (can('employees.view')) {
            hr.push(
                item(t('Employees'), '/employees', Users, {
                    href: '/employees/create',
                    permission: 'employees.create',
                }),
            );
        }

        if (can('attendance.view')) {
            hr.push(
                item(t('Attendance'), '/attendance', Clock, {
                    href: '/attendance/create',
                    permission: 'attendance.create',
                }),
            );
        }

        if (can('salaries.view')) {
            hr.push(
                item(t('Salaries'), '/salaries', Banknote, {
                    href: '/salaries/create',
                    permission: 'salaries.create',
                }),
            );
        }

        if (hr.length) {
            groups.push({ label: t('HR'), items: hr });
        }

        if (can('portfolio.view') || can('contact_messages.view')) {
            const website: NavItem[] = [];

            if (can('portfolio.view')) {
                website.push(
                    item(t('Portfolio'), '/portfolio', Images, {
                        href: '/portfolio/create',
                        permission: 'portfolio.create',
                    }),
                );
            }

            if (can('contact_messages.view')) {
                website.push(
                    item(t('Contact'), '/contact-messages', Inbox),
                );
            }

            groups.push({
                label: t('Website'),
                items: website,
            });
        }

        if (
            can('settings.manage') ||
            can('users.view') ||
            can('roles.view') ||
            can('backups.view') ||
            can('storage.view') ||
            can('companies.view') ||
            can('companies.edit') ||
            can('trash.view') ||
            can('audit.view') ||
            can('partners.view')
        ) {
            groups.push({
                label: t('Administration'),
                items: [
                    {
                        title: t('Settings'),
                        href: can('settings.manage')
                            ? '/settings/appearance'
                            : can('users.view')
                              ? '/settings/users'
                              : can('roles.view')
                                ? '/settings/roles'
                                : can('backups.view')
                                  ? '/settings/backups'
                                  : can('storage.view')
                                    ? '/settings/file-manager'
                                    : can('companies.view') || can('companies.edit')
                                      ? '/settings/company'
                                      : can('trash.view')
                                        ? '/settings/trash'
                                        : can('partners.view')
                                          ? '/partners'
                                          : '/settings/login-logs',
                        icon: Settings,
                        matchPrefixes: ['/settings', '/partners'],
                    },
                ],
            });
        }

        return groups;
    });

    const sidebarGroups = computed(() => {
        const toSection = (group: NavGroup): NavItem | null => {
            const first = group.items[0];

            if (!first) {
                return null;
            }

            return {
                title: group.label,
                href: first.href,
                icon: first.icon,
                matchPrefixes: group.items.map((entry) => pathOf(entry.href)),
            };
        };

        const overview: NavItem[] = [];
        const operations: NavItem[] = [];
        const website: NavItem[] = [];
        const organization: NavItem[] = [];

        for (const group of tabGroups.value) {
            if (group.label === t('Overview')) {
                overview.push(...group.items);
                continue;
            }

            if (group.label === t('Website')) {
                website.push(...group.items);
                continue;
            }

            if (group.label === t('Administration')) {
                organization.push(...group.items);
                continue;
            }

            const section = toSection(group);

            if (!section) {
                continue;
            }

            if (group.label === t('Field work')) {
                overview.push(section);
                continue;
            }

            if (group.label === t('HR')) {
                organization.push(section);
                continue;
            }

            operations.push(section);
        }

        const groups: NavGroup[] = [];

        if (overview.length) {
            groups.push({ label: t('Overview'), items: overview });
        }

        if (operations.length) {
            groups.push({ label: t('Operations'), items: operations });
        }

        if (website.length) {
            groups.push({ label: t('Website'), items: website });
        }

        if (organization.length) {
            groups.push({ label: t('Organization'), items: organization });
        }

        return groups;
    });

    const allNavItems = computed(() => tabGroups.value.flatMap((group) => group.items));

    return {
        navGroups: sidebarGroups,
        tabGroups,
        sidebarGroups,
        allNavItems,
    };
}

export function isNavItemActive(item: NavItem, currentPath: string): boolean {
    if (item.matchPrefixes?.length) {
        return item.matchPrefixes.some(
            (prefix) => currentPath === prefix || currentPath.startsWith(`${prefix}/`),
        );
    }

    const path = pathOf(item.href);

    return currentPath === path || (path !== '/' && currentPath.startsWith(`${path}/`));
}
