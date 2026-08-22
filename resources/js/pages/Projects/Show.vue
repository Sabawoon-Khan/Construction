<script setup lang="ts">
import { Form, Head, Link, router } from '@inertiajs/vue3';
import {
    ArrowLeft,
    Banknote,
    Handshake,
    Info,
    Package,
    Paperclip,
    Pencil,
    Receipt,
    Trash2,
    UserRound,
    Warehouse,
} from '@lucide/vue';
import { onMounted, onUnmounted, computed, ref, watch } from 'vue';
import Can from '@/components/Can.vue';
import EmployeeAddDialog from '@/components/EmployeeAddDialog.vue';
import ExpenseRecordDialog from '@/components/ExpenseRecordDialog.vue';
import Heading from '@/components/Heading.vue';
import { MisSearchInput, MisTabs } from '@/components/mis';
import ShareholderAddDialog from '@/components/ShareholderAddDialog.vue';
import ShareholderContributionDialog from '@/components/ShareholderContributionDialog.vue';
import ShareholderPayoutDialog from '@/components/ShareholderPayoutDialog.vue';
import SortableTh from '@/components/SortableTh.vue';
import TableFrame from '@/components/TableFrame.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { useCalendar } from '@/composables/useCalendar';
import { useCurrency } from '@/composables/useCurrency';
import { provideTableSort } from '@/composables/useTableSort';
import { useTranslations } from '@/composables/useTranslations';
import { formatNumber, money, moneyByCurrency } from '@/lib/utils';
import ProjectShowAttachmentsTab from './partials/ProjectShowAttachmentsTab.vue';
import ProjectShowEmployeesTab from './partials/ProjectShowEmployeesTab.vue';
import ProjectShowExpensesTab from './partials/ProjectShowExpensesTab.vue';
import ProjectShowInfoTab from './partials/ProjectShowInfoTab.vue';
import ProjectShowPurchasesTab, {
    type PurchaseMaterial,
} from './partials/ProjectShowPurchasesTab.vue';
import ProjectShowSalesTab from './partials/ProjectShowSalesTab.vue';
import ProjectShowShareholdersTab from './partials/ProjectShowShareholdersTab.vue';
import ProjectShowStockTab from './partials/ProjectShowStockTab.vue';

defineProps<{
    item: Record<string, any>;
    stats: Record<string, any>;
    shareholders: {
        id: number;
        name: string;
        ownership_percentage: number;
        contributed_total: number;
        entitled_share: number;
        paid_total: number;
        balance_owed: number;
    }[];
    available_shareholders: { id: number; name: string; phone?: string }[];
    employees: {
        id: number;
        employee_no?: string;
        name: string;
        department?: string;
        designation?: string;
        phone?: string;
        basic_salary?: number | null;
        currency?: string;
        status?: string;
        started_at?: string | null;
        role_on_project?: string | null;
        working_hours?: string | null;
        notes?: string | null;
    }[];
    available_employees: {
        id: number;
        name: string;
        employee_no?: string;
        department?: string;
        designation?: string;
        working_hours?: string;
        phone?: string;
    }[];
    contributions: {
        id: number;
        partner_id: number;
        amount: number;
        currency?: string;
        invested_at: string;
        partner?: { id: number; name: string };
    }[];
    expenses: {
        id: number;
        expense_no: string;
        expense_date: string;
        paid_to?: string;
        description: string;
        amount: number;
        currency?: string;
        expense_category_id?: number | null;
        category?: { id: number; name: string } | null;
        purchase_id?: number | null;
        reference_no?: string | null;
        payment_method?: string | null;
        attachments?: Array<{
            id: number;
            original_filename?: string;
            title?: string;
            download_url?: string;
        }>;
    }[];
    expense_categories?: { id: number; name: string; code: string }[];
    purchases?: {
        id: number;
        purchase_no: string;
        purchase_date: string;
        total_amount: number;
        paid_amount: number;
        remaining_amount?: number;
        currency?: string;
        status: string;
        supplier?: { id: number; name: string };
    }[];
    purchase_materials?: PurchaseMaterial[];
    stock?: {
        material_id: number;
        purchased: number;
        issued: number;
        on_hand: number;
        last_issue_at?: string | null;
    }[];
    stock_materials?: {
        id: number;
        name: string;
        code: string;
        unit?: { unit_name: string } | null;
    }[];
    stock_issues?: {
        id: number;
        material_id: number;
        quantity: number;
        transaction_date?: string | null;
        material?: { id: number; name: string } | null;
    }[];
}>();

const expenseDialogOpen = ref(false);
const selectedExpense = ref<Record<string, any> | null>(null);
const expenseStartEditing = ref(false);
const addShareholderOpen = ref(false);
const addEmployeeOpen = ref(false);
const contributionDialogOpen = ref(false);
const payoutDialogOpen = ref(false);
const materialDialogOpen = ref(false);
const selectedMaterial = ref<PurchaseMaterial | null>(null);
const materialLineQuery = ref('');
const tab = ref('info');
const { t } = useTranslations();
const tableColumns = computed(() => [
    { key: 'purchase_no', label: t('Purchase') },
    { key: 'purchase_date', label: t('Date') },
    { key: 'supplier', label: t('Supplier') },
    { key: 'quantity', label: t('Qty') },
    { key: 'unit_cost', label: t('Unit cost') },
    { key: 'total_cost', label: t('Total') },
]);
const { baseCurrency } = useCurrency();

function openExpense(expense: Record<string, any> | null, edit = false) {
    selectedExpense.value = expense;
    expenseStartEditing.value = edit && !!expense && !expense.purchase_id;
    expenseDialogOpen.value = true;
}

function openMaterial(row: PurchaseMaterial) {
    selectedMaterial.value = row;
    materialLineQuery.value = '';
    materialDialogOpen.value = true;
}

const calendar = useCalendar();
const formatDate = (value: string | null | undefined) =>
    calendar.format(value) || '—';

const filteredMaterialLines = computed(() => {
    const lines = selectedMaterial.value?.lines ?? [];
    const q = materialLineQuery.value.trim().toLowerCase();
    if (!q) {
        return lines;
    }

    return lines.filter((line) =>
        [line.purchase_no, line.supplier]
            .filter(Boolean)
            .join(' ')
            .toLowerCase()
            .includes(q),
    );
});

const { sortedRows } = provideTableSort(filteredMaterialLines);

const tabs = [
    { id: 'info', label: t('Project info'), icon: Info },
    { id: 'attachments', label: t('Attachments'), icon: Paperclip },
    { id: 'shareholders', label: t('Shareholders'), icon: Handshake },
    { id: 'employees', label: t('Employees'), icon: UserRound },
    { id: 'expenses', label: t('Expenses'), icon: Receipt },
    { id: 'purchases', label: t('Purchases'), icon: Package },
    { id: 'stock', label: t('Stock'), icon: Warehouse },
    { id: 'sales', label: t('Sales'), icon: Banknote },
];
const tabIds = new Set(tabs.map((entry) => entry.id));

function syncTabFromHash() {
    const hashTab = window.location.hash.replace('#', '');
    if (hashTab && tabIds.has(hashTab)) {
        tab.value = hashTab;
    }
}

function persistTabHash(value: string) {
    if (!tabIds.has(value) || window.location.hash === `#${value}`) {
        return;
    }
    window.history.replaceState(window.history.state, '', `#${value}`);
}

onMounted(() => {
    syncTabFromHash();
});

watch(tab, persistTabHash);

const stopNavigate = router.on('navigate', () => {
    syncTabFromHash();
    if (tab.value !== 'info') {
        persistTabHash(tab.value);
    }
});

onUnmounted(() => {
    stopNavigate();
});

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Projects', href: '/projects' },
            { title: 'Details', href: '#' },
        ],
    },
});
</script>

<template>
    <Head :title="item.name" />
    <div class="flex h-full flex-1 flex-col gap-4">
        <div
            class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between"
        >
            <div class="space-y-3">
                <Button as-child variant="ghost" size="sm" class="-ml-2 w-fit">
                    <Link href="/projects"
                        ><ArrowLeft class="size-4" /> {{ t('Back') }}</Link
                    >
                </Button>
                <Heading
                    :title="item.name"
                    :description="`${item.code} · ${item.location || t('No location')}`"
                />
            </div>
            <div class="flex items-center gap-2">
                <StatusBadge :status="item.status" />
                <Button as-child variant="outline">
                    <Link :href="`/reports?project_id=${item.id}`">{{
                        t('Full report')
                    }}</Link>
                </Button>
                <Can permission="projects.edit">
                    <Button as-child>
                        <Link :href="`/projects/${item.id}/edit`"
                            ><Pencil class="size-4" /> {{ t('Edit') }}</Link
                        >
                    </Button>
                </Can>
                <Can permission="projects.delete">
                    <Form :action="`/projects/${item.id}`" method="delete" v-slot="{ processing }">
                        <Button type="submit" variant="destructive" :disabled="processing">
                            <Trash2 class="size-4" /> {{ processing ? t('Deleting…') : t('Delete') }}
                        </Button>
                    </Form>
                </Can>
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="rounded-xl border bg-card p-4 shadow-sm">
                <div class="text-sm text-muted-foreground">
                    {{ t('Sales') }}
                </div>
                <div class="mt-1 text-2xl font-semibold tabular-nums">
                    {{
                        moneyByCurrency(
                            stats.sales_by_currency,
                            stats.sales_total,
                        )
                    }}
                </div>
            </div>
            <div class="rounded-xl border bg-card p-4 shadow-sm">
                <div class="text-sm text-muted-foreground">
                    {{ t('Project spend') }}
                </div>
                <div
                    class="mt-1 text-2xl font-semibold text-rose-700 tabular-nums"
                >
                    {{
                        moneyByCurrency(
                            stats.expenses_by_currency,
                            stats.expenses_total,
                        )
                    }}
                </div>
            </div>
            <div class="rounded-xl border bg-card p-4 shadow-sm">
                <div class="text-sm text-muted-foreground">
                    {{ t('Owed to suppliers') }}
                </div>
                <div
                    class="mt-1 text-2xl font-semibold text-amber-700 tabular-nums"
                >
                    {{
                        moneyByCurrency(
                            stats.purchases_remaining_by_currency,
                            stats.purchases_remaining ?? 0,
                        )
                    }}
                </div>
            </div>
            <div class="rounded-xl border bg-card p-4 shadow-sm">
                <div class="text-sm text-muted-foreground">
                    {{ t('Net profit') }}
                </div>
                <div
                    class="mt-1 text-2xl font-semibold text-emerald-700 tabular-nums"
                >
                    {{ money(stats.net_profit, baseCurrency) }}
                </div>
            </div>
        </div>

        <MisTabs v-model="tab" :tabs="tabs" />

        <div v-show="tab === 'info'">
            <ProjectShowInfoTab :item="item" />
        </div>

        <div v-show="tab === 'attachments'">
            <ProjectShowAttachmentsTab :item="item" />
        </div>

        <div v-show="tab === 'sales'">
            <ProjectShowSalesTab :item="item" />
        </div>

        <div v-show="tab === 'shareholders'">
            <ProjectShowShareholdersTab
                :item="item"
                :shareholders="shareholders"
                :contributions="contributions"
                @add-shareholder="addShareholderOpen = true"
                @record-contribution="contributionDialogOpen = true"
                @record-payout="payoutDialogOpen = true"
            />
        </div>

        <div v-show="tab === 'employees'">
            <ProjectShowEmployeesTab
                :item="item"
                :employees="employees"
                @add-employee="addEmployeeOpen = true"
            />
        </div>

        <div v-show="tab === 'expenses'">
            <ProjectShowExpensesTab
                :item="item"
                :expenses="expenses"
                @open-expense="openExpense"
            />
        </div>

        <div v-show="tab === 'purchases'">
            <ProjectShowPurchasesTab
                :item="item"
                :purchases="purchases"
                :purchase_materials="purchase_materials"
                @open-material="openMaterial"
            />
        </div>

        <div v-show="tab === 'stock'">
            <ProjectShowStockTab
                :item="item"
                :stock="stock"
                :stock_materials="stock_materials"
                :stock_issues="stock_issues"
            />
        </div>
    </div>

    <Dialog v-model:open="materialDialogOpen">
        <DialogContent
            class="flex max-h-[85vh] flex-col gap-4 overflow-hidden sm:max-w-4xl"
        >
            <DialogHeader>
                <DialogTitle>
                    {{
                        t('Purchases of :name', {
                            name: selectedMaterial?.name || t('Material'),
                        })
                    }}
                </DialogTitle>
                <DialogDescription>
                    {{
                        selectedMaterial
                            ? `${formatNumber(selectedMaterial.purchase_count, 0)} ${t('Purchases')} · ${formatNumber(selectedMaterial.quantity, 3)}${selectedMaterial.unit ? ` ${selectedMaterial.unit}` : ''} · ${moneyByCurrency(selectedMaterial.total_cost_by_currency, selectedMaterial.total_cost_base)}`
                            : ''
                    }}
                </DialogDescription>
            </DialogHeader>
            <MisSearchInput
                v-model="materialLineQuery"
                class="max-w-sm"
                :placeholder="t('Search purchases...')"
            />
            <div class="-mx-6 min-h-0 flex-1 overflow-auto border-t">
                <TableFrame table-id="project-material-lines" :columns="tableColumns">
                <table class="min-w-full text-sm">
                    <thead
                        class="sticky top-0 bg-muted/90 text-start text-muted-foreground backdrop-blur"
                    >
                        <tr>
                            <SortableTh column="purchase_no">
                                {{ t('Purchase') }}
                            </SortableTh>
                            <SortableTh column="purchase_date">
                                {{ t('Date') }}
                            </SortableTh>
                            <SortableTh column="supplier">
                                {{ t('Supplier') }}
                            </SortableTh>
                            <SortableTh column="quantity">
                                {{ t('Qty') }}
                            </SortableTh>
                            <SortableTh column="unit_cost">
                                {{ t('Unit cost') }}
                            </SortableTh>
                            <SortableTh column="total_cost">
                                {{ t('Total') }}
                            </SortableTh>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="line in sortedRows"
                            :key="line.id"
                            class="border-t hover:bg-muted/30"
                        >
                            <td class="px-4 py-3">
                                <Link
                                    :href="`/purchases/${line.purchase_id}`"
                                    class="font-medium text-primary hover:underline"
                                >
                                    {{ line.purchase_no }}
                                </Link>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                {{ formatDate(line.purchase_date) }}
                            </td>
                            <td class="px-4 py-3">
                                {{ line.supplier || '—' }}
                            </td>
                            <td class="px-4 py-3 tabular-nums">
                                {{ formatNumber(line.quantity, 3) }}
                            </td>
                            <td class="px-4 py-3 tabular-nums">
                                {{
                                    money(
                                        line.unit_cost,
                                        line.currency || baseCurrency,
                                    )
                                }}
                            </td>
                            <td class="px-4 py-3 tabular-nums">
                                {{
                                    money(
                                        line.total_cost,
                                        line.currency || baseCurrency,
                                    )
                                }}
                            </td>
                        </tr>
                        <tr v-if="!filteredMaterialLines.length">
                            <td
                                colspan="6"
                                class="px-4 py-10 text-center text-muted-foreground"
                            >
                                {{
                                    materialLineQuery.trim()
                                        ? t('No matching purchases')
                                        : t('No purchases yet')
                                }}
                            </td>
                        </tr>
                    </tbody>
                </table>
                </TableFrame>
            </div>
        </DialogContent>
    </Dialog>

    <ExpenseRecordDialog
        v-model:open="expenseDialogOpen"
        :item="selectedExpense"
        :project-id="item.id"
        :start-editing="expenseStartEditing"
        :categories="expense_categories"
    />

    <ShareholderAddDialog
        v-model:open="addShareholderOpen"
        :project-id="item.id"
        :available-shareholders="available_shareholders"
    />

    <EmployeeAddDialog
        v-model:open="addEmployeeOpen"
        :project-id="item.id"
        :available-employees="available_employees"
    />

    <ShareholderContributionDialog
        v-model:open="contributionDialogOpen"
        :project-id="item.id"
        :shareholders="shareholders"
    />

    <ShareholderPayoutDialog
        v-model:open="payoutDialogOpen"
        :project-id="item.id"
        :shareholders="shareholders"
    />
</template>
