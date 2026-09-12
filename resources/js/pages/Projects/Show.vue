<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    Banknote,
    Building2,
    CircleDollarSign,
    HandCoins,
    Info,
    Paperclip,
    Pencil,
    Receipt,
    Trash2,
    UserRound,
    Warehouse,
} from '@lucide/vue';
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import Can from '@/components/Can.vue';
import ConfirmActionDialog from '@/components/ConfirmActionDialog.vue';
import EmployeeAddDialog from '@/components/EmployeeAddDialog.vue';
import ExpenseRecordDialog from '@/components/ExpenseRecordDialog.vue';
import {
    MisAlert,
    MisSearchInput,
    MisTabs,
} from '@/components/mis';
import ShareholderAddDialog from '@/components/ShareholderAddDialog.vue';
import ShareholderContributionDialog from '@/components/ShareholderContributionDialog.vue';
import ShareholderPayoutDialog from '@/components/ShareholderPayoutDialog.vue';
import SortableTh from '@/components/SortableTh.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import TableFrame from '@/components/TableFrame.vue';
import TableIndexTd from '@/components/TableIndexTd.vue';
import TableIndexTh from '@/components/TableIndexTh.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    V2DetailHero,
    V2ListPage,
    V2StatCard,
    V2StatGrid,
} from '@/components/v2';
import { indexTableColumn } from '@/composables/useTableColumns';
import { useCalendar } from '@/composables/useCalendar';
import { useCurrency } from '@/composables/useCurrency';
import { provideTableSort } from '@/composables/useTableSort';
import { useTranslations } from '@/composables/useTranslations';
import { formatNumber, money, moneyByCurrency } from '@/lib/utils';
import {
    consumePendingTrashUndo,
    expectTrashUndo,
} from '@/lib/trashUndoToast';
import ProjectShowAttachmentsTab from './partials/ProjectShowAttachmentsTab.vue';
import ProjectShowEmployeesTab from './partials/ProjectShowEmployeesTab.vue';
import ProjectShowExpensesTab from './partials/ProjectShowExpensesTab.vue';
import ProjectShowInfoTab from './partials/ProjectShowInfoTab.vue';
import ProjectShowLoansTab from './partials/ProjectShowLoansTab.vue';
import ProjectShowMoneyInTab from './partials/ProjectShowMoneyInTab.vue';
import ProjectShowPurchasesTab, {
    type PurchaseMaterial,
} from './partials/ProjectShowPurchasesTab.vue';
import ProjectShowSalesTab from './partials/ProjectShowSalesTab.vue';
import ProjectShowShareholdersTab from './partials/ProjectShowShareholdersTab.vue';
import ProjectShowStockTab from './partials/ProjectShowStockTab.vue';

const props = defineProps<{
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
    loans?: {
        id: number;
        loan_no: string;
        lender_name: string;
        loan_date: string;
        due_date?: string | null;
        principal: number;
        paid_amount: number;
        remaining_amount?: number;
        currency?: string;
        status: string;
        reference_no?: string | null;
        notes?: string | null;
        payments?: {
            id: number;
            amount: number;
            currency?: string;
            paid_at?: string | null;
            payment_method?: string | null;
            reference_no?: string | null;
        }[];
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
const deleteOpen = ref(false);
const deleting = ref(false);
const tab = ref('overview');
const { t } = useTranslations();
const tableColumns = computed(() => [
    indexTableColumn(),
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

function deleteProject() {
    deleting.value = true;
    expectTrashUndo({ type: 'projects', id: props.item.id });
    router.delete(`/projects/${props.item.id}`, {
        onError: () => consumePendingTrashUndo(),
        onCancel: () => consumePendingTrashUndo(),
        onFinish: () => {
            deleting.value = false;
            deleteOpen.value = false;
        },
    });
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

const tabs = computed(() => [
    { id: 'overview', label: t('Overview'), icon: Info },
    { id: 'sell', label: t('Sell'), icon: Banknote },
    { id: 'expenses', label: t('Expenses'), icon: Receipt },
    { id: 'money-in', label: t('Money in'), icon: CircleDollarSign },
    { id: 'loans', label: t('Loans'), icon: HandCoins },
    { id: 'stock', label: t('Stock'), icon: Warehouse },
    { id: 'people', label: t('People'), icon: UserRound },
    { id: 'files', label: t('Files'), icon: Paperclip },
]);

const legacyTabMap: Record<string, string> = {
    info: 'overview',
    overview: 'overview',
    sales: 'sell',
    sell: 'sell',
    expenses: 'expenses',
    money: 'expenses',
    'money-in': 'money-in',
    contributions: 'money-in',
    loans: 'loans',
    purchases: 'stock',
    stock: 'stock',
    shareholders: 'people',
    employees: 'people',
    people: 'people',
    attachments: 'files',
    files: 'files',
};

const tabIds = computed(() => new Set(tabs.value.map((item) => item.id)));

const attentionItems = computed(() => {
    const items: { tone: 'warning' | 'danger'; text: string }[] = [];
    const doneStatuses = new Set(['completed', 'sold']);
    const expected = props.item.expected_completion_date;
    if (
        expected &&
        !doneStatuses.has(String(props.item.status || '')) &&
        new Date(`${expected}T23:59:59`) < new Date()
    ) {
        items.push({
            tone: 'warning',
            text: `${t('Expected completion passed')}: ${formatDate(expected)}`,
        });
    }

    const owedSuppliers = Number(props.stats.purchases_remaining ?? 0);
    if (owedSuppliers > 0) {
        items.push({
            tone: 'warning',
            text: `${t('Supplier balance outstanding')}: ${moneyByCurrency(
                props.stats.purchases_remaining_by_currency,
                owedSuppliers,
            )}`,
        });
    }

    const shareholderOwed = props.shareholders.reduce(
        (sum, row) => sum + Number(row.balance_owed ?? 0),
        0,
    );
    if (shareholderOwed > 0) {
        items.push({
            tone: 'warning',
            text: `${t('Owed to shareholders')}: ${money(shareholderOwed, baseCurrency.value)}`,
        });
    }

    const loanRemaining = (props.loans ?? []).reduce(
        (sum, loan) =>
            sum +
            Number(
                loan.remaining_amount ??
                    Math.max(
                        0,
                        Number(loan.principal) - Number(loan.paid_amount),
                    ),
            ),
        0,
    );
    if (loanRemaining > 0) {
        items.push({
            tone: 'warning',
            text: `${t('Loan balance outstanding')}: ${money(loanRemaining, baseCurrency.value)}`,
        });
    }

    return items;
});

function syncTabFromHash() {
    const hashTab = window.location.hash.replace('#', '');
    const mapped = legacyTabMap[hashTab];
    if (mapped && tabIds.value.has(mapped)) {
        tab.value = mapped;
    }
}

function persistTabHash(value: string) {
    if (!tabIds.value.has(value) || window.location.hash === `#${value}`) {
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
    if (tab.value !== 'overview') {
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
    <V2ListPage>
        <V2DetailHero image="/images/projects-v2-hero-luxury.png">
            <template #eyebrow>{{ t('Projects') }}</template>
            <template #title>{{ item.name }}</template>
            <template #description>
                {{ item.code }} · {{ item.location || t('No location') }}
            </template>
            <template #actions>
                <StatusBadge :status="item.status" />
                <Button as-child variant="outline" size="sm">
                    <Link :href="`/reports?project_id=${item.id}`">{{
                        t('Full report')
                    }}</Link>
                </Button>
                <Can permission="projects.edit">
                    <Button as-child size="sm">
                        <Link :href="`/projects/${item.id}/edit`">
                            <Pencil class="size-4" /> {{ t('Edit') }}
                        </Link>
                    </Button>
                </Can>
                <Can permission="projects.delete">
                    <Button
                        type="button"
                        variant="destructive"
                        size="sm"
                        @click="deleteOpen = true"
                    >
                        <Trash2 class="size-4" /> {{ t('Move to trash') }}
                    </Button>
                </Can>
            </template>
            <template #stats>
                <V2StatGrid class="detail-stat-grid">
                    <V2StatCard
                        :delay="0"
                        :title="t('Sales')"
                        :value="
                            moneyByCurrency(
                                stats.sales_by_currency,
                                stats.sales_total,
                            )
                        "
                    >
                        <template #icon><Banknote /></template>
                    </V2StatCard>
                    <V2StatCard
                        :delay="1"
                        icon-tone="warm"
                        :title="t('Expenses')"
                        :value="
                            moneyByCurrency(
                                stats.expenses_by_currency,
                                stats.expenses_total,
                            )
                        "
                    >
                        <template #icon><Receipt /></template>
                    </V2StatCard>
                    <V2StatCard
                        :delay="2"
                        icon-tone="teal"
                        :title="t('Net profit')"
                        :value="
                            moneyByCurrency(
                                stats.net_profit_by_currency,
                                stats.net_profit,
                            )
                        "
                    >
                        <template #icon><CircleDollarSign /></template>
                    </V2StatCard>
                    <V2StatCard
                        :delay="3"
                        icon-tone="orange"
                        :title="t('Units')"
                        :value="formatNumber(stats.units_count, 0)"
                    >
                        <template #icon><Building2 /></template>
                    </V2StatCard>
                </V2StatGrid>
            </template>
        </V2DetailHero>

        <MisAlert
            v-if="attentionItems.length"
            tone="warning"
            :title="t('Attention')"
        >
            <ul class="list-disc space-y-1 ps-4">
                <li v-for="(alert, index) in attentionItems" :key="index">
                    {{ alert.text }}
                </li>
            </ul>
        </MisAlert>

        <MisTabs v-model="tab" nowrap :tabs="tabs" />

        <div v-show="tab === 'overview'">
            <ProjectShowInfoTab
                :item="item"
                :stats="stats"
                :shareholders="shareholders"
            />
        </div>

        <div v-show="tab === 'sell'" class="space-y-4">
            <ProjectShowSalesTab :item="item" />
        </div>

        <div v-show="tab === 'expenses'" class="space-y-4">
            <ProjectShowExpensesTab
                :item="item"
                :expenses="expenses"
                @open-expense="openExpense"
            />
        </div>

        <div v-show="tab === 'money-in'" class="space-y-4">
            <ProjectShowMoneyInTab
                :item="item"
                :contributions="contributions"
                :has-shareholders="shareholders.length > 0"
                @record-contribution="contributionDialogOpen = true"
                @add-shareholder="addShareholderOpen = true"
            />
        </div>

        <div v-show="tab === 'loans'" class="space-y-4">
            <ProjectShowLoansTab :item="item" :loans="loans" />
        </div>

        <div v-show="tab === 'stock'" class="space-y-4">
            <ProjectShowPurchasesTab
                :item="item"
                :purchases="purchases"
                :purchase_materials="purchase_materials"
                @open-material="openMaterial"
            />
            <ProjectShowStockTab
                :item="item"
                :stock="stock"
                :stock_materials="stock_materials"
                :stock_issues="stock_issues"
            />
        </div>

        <div v-show="tab === 'people'" class="space-y-4">
            <ProjectShowEmployeesTab
                :item="item"
                :employees="employees"
                @add-employee="addEmployeeOpen = true"
            />
            <ProjectShowShareholdersTab
                :item="item"
                :shareholders="shareholders"
                :contributions="contributions"
                :show-ownership="true"
                :show-contributions="false"
                @add-shareholder="addShareholderOpen = true"
                @record-contribution="contributionDialogOpen = true"
                @record-payout="payoutDialogOpen = true"
            />
        </div>

        <div v-show="tab === 'files'">
            <ProjectShowAttachmentsTab :item="item" />
        </div>
    </V2ListPage>

    <ConfirmActionDialog
        v-model:open="deleteOpen"
        :title="t('Move project to trash?')"
        :description="
            t(
                'Are you sure you want to delete this project? This action cannot be undone.',
            )
        "
        :confirm-label="t('Move to trash')"
        :processing="deleting"
        @confirm="deleteProject"
    />

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
                <TableFrame
                    table-id="project-material-lines"
                    :columns="tableColumns"
                >
                    <table class="min-w-full text-sm">
                        <thead
                            class="sticky top-0 bg-muted/90 text-start text-muted-foreground backdrop-blur"
                        >
                            <tr>
                                <TableIndexTh />
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
                                v-for="(line, index) in sortedRows"
                                :key="line.id"
                                class="border-t hover:bg-muted/30"
                            >
                                <TableIndexTd :index="index" />
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
                                    colspan="7"
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
