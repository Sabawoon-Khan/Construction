<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import {
    ChevronLeft,
    ChevronRight,
    GripVertical,
    Printer,
    Trash2,
} from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import AttachmentLinks from '@/components/AttachmentLinks.vue';
import Can from '@/components/Can.vue';
import ConfirmActionDialog from '@/components/ConfirmActionDialog.vue';
import EmptyState from '@/components/EmptyState.vue';
import SortableTh from '@/components/SortableTh.vue';
import TableFrame from '@/components/TableFrame.vue';
import TableIndexTd from '@/components/TableIndexTd.vue';
import TableIndexTh from '@/components/TableIndexTh.vue';
import { indexTableColumn } from '@/composables/useTableColumns';
import {
    ExpenseListPrintSheet,
    ExpenseVoucherPrintSheet,
    MisSearchInput,
    MisTableActions,
    PrintRecordsDialog,
} from '@/components/mis';
import type { ExportCell } from '@/composables/useTableExport';
import type { MisSelectOption } from '@/components/mis/MisSelectField.vue';
import { Button } from '@/components/ui/button';
import { CreateButton } from '@/components/ui/create-button';
import { V2Panel } from '@/components/v2';
import { useA4Print } from '@/composables/useA4Print';
import { useCalendar } from '@/composables/useCalendar';
import { useCurrency } from '@/composables/useCurrency';
import { usePermissions } from '@/composables/usePermissions';
import { provideTableSort } from '@/composables/useTableSort';
import { useTranslations } from '@/composables/useTranslations';
import type { PrintRecordOption } from '@/lib/printRecords';
import { formatNumber, money, resolveCurrency } from '@/lib/utils';

type ExpenseRow = {
    id: number;
    expense_no: string;
    expense_date: string;
    paid_to?: string;
    description: string;
    amount: number;
    currency?: string;
    expense_category_id?: number | null;
    category?: { id: number; name: string } | null;
    expense_unit?: { id: number; unit_name: string } | null;
    purchase_id?: number | null;
    reference_no?: string | null;
    payment_method?: string | null;
    sort_order?: number;
    attachments?: Array<{
        id: number;
        original_filename?: string;
        title?: string;
        download_url?: string;
    }>;
};

const props = defineProps<{
    item: Record<string, any>;
    expenses: ExpenseRow[];
}>();

const emit = defineEmits<{
    'open-expense': [expense: Record<string, any> | null, edit?: boolean];
}>();

const { t } = useTranslations();
const { can } = usePermissions();
const tableColumns = computed(() => [
    { key: 'drag', label: '', locked: true },
    indexTableColumn(),
    { key: 'expense_date', label: t('Date') },
    { key: 'reference_no', label: t('Bill number') },
    { key: 'paid_to', label: t('Paid to') },
    { key: 'category.name', label: t('Category') },
    { key: 'description', label: t('Description') },
    { key: 'expense_unit.unit_name', label: t('Unit') },
    { key: 'payment_method', label: t('Paying type') },
    { key: 'amount', label: t('Amount') },
    { key: 'attachments', label: t('Attachments') },
    { key: 'actions', label: t('Actions'), locked: true },
]);
const { baseCurrency } = useCurrency();
const calendar = useCalendar();
const formatDate = (value: string | null | undefined) =>
    calendar.format(value) || '—';

const expenseSearch = ref('');
const categoryFilter = ref<string>('all');
const currentPage = ref(1);
const perPage = 12;
const deletingId = ref<number | null>(null);
const movingId = ref<number | null>(null);
const dragExpenseId = ref<number | null>(null);
const dropTargetId = ref<number | null>(null);
const draftOrderIds = ref<number[] | null>(null);
const pendingDelete = ref<ExpenseRow | null>(null);
const confirmDeleteOpen = ref(false);
const printSingle = ref<ExpenseRow | null>(null);
const printListRows = ref<ExpenseRow[]>([]);
const printPickerOpen = ref(false);
const selectedPrintKeys = ref<string[]>([]);
const { printA4 } = useA4Print();
const canReorder = computed(() => can('expenses.edit'));

function openPrintPicker() {
    selectedPrintKeys.value = printRecordOptions.value.map((record) => record.key);
    printPickerOpen.value = true;
}

function confirmPrintSelection(keys: string[]) {
    const selected = new Set(keys.map(String));
    const rows = sortedRows.value.filter((expense) =>
        selected.has(String(expense.id)),
    );

    if (!rows.length) {
        return;
    }

    printPickerOpen.value = false;
    void printA4(() => {
        if (rows.length === 1) {
            printSingle.value = rows[0];
            printListRows.value = [];
            return;
        }

        printSingle.value = null;
        printListRows.value = rows;
    });
}

function printExpenseOne(expense: ExpenseRow) {
    void printA4(() => {
        printSingle.value = expense;
        printListRows.value = [];
    });
}

function requestDeleteExpense(expense: ExpenseRow) {
    pendingDelete.value = expense;
    confirmDeleteOpen.value = true;
}

function deleteExpense() {
    const expense = pendingDelete.value;
    if (!expense) {
        return;
    }

    deletingId.value = expense.id;
    const href = expense.purchase_id
        ? `/purchases/${expense.purchase_id}`
        : `/expenses/${expense.id}`;

    router.delete(href, {
        preserveScroll: true,
        onFinish: () => {
            deletingId.value = null;
        },
        onSuccess: () => {
            confirmDeleteOpen.value = false;
            pendingDelete.value = null;
        },
    });
}

function orderedSourceExpenses(): ExpenseRow[] {
    if (draftOrderIds.value) {
        const byId = new Map(props.expenses.map((expense) => [expense.id, expense]));

        return draftOrderIds.value
            .map((id) => byId.get(id))
            .filter((expense): expense is ExpenseRow => Boolean(expense));
    }

    return [...props.expenses].sort((a, b) => {
        const orderDiff = (a.sort_order ?? 0) - (b.sort_order ?? 0);

        return orderDiff !== 0 ? orderDiff : a.id - b.id;
    });
}

function onDragStart(event: DragEvent, expense: ExpenseRow) {
    if (!canReorder.value || movingId.value) {
        event.preventDefault();
        return;
    }

    dragExpenseId.value = expense.id;
    event.dataTransfer?.setData('text/plain', String(expense.id));
    if (event.dataTransfer) {
        event.dataTransfer.effectAllowed = 'move';
    }
}

function onDragOver(event: DragEvent, expense: ExpenseRow) {
    if (!canReorder.value || dragExpenseId.value == null) {
        return;
    }

    event.preventDefault();
    if (event.dataTransfer) {
        event.dataTransfer.dropEffect = 'move';
    }
    dropTargetId.value = expense.id;
}

function onDragLeave(expense: ExpenseRow) {
    if (dropTargetId.value === expense.id) {
        dropTargetId.value = null;
    }
}

function onDragEnd() {
    dragExpenseId.value = null;
    dropTargetId.value = null;
}

function onDrop(event: DragEvent, target: ExpenseRow) {
    event.preventDefault();
    event.stopPropagation();

    const sourceId = dragExpenseId.value;
    dragExpenseId.value = null;
    dropTargetId.value = null;

    if (!canReorder.value || sourceId == null || sourceId === target.id || movingId.value) {
        return;
    }

    const order = orderedSourceExpenses();
    const from = order.findIndex((expense) => expense.id === sourceId);
    const to = order.findIndex((expense) => expense.id === target.id);

    if (from < 0 || to < 0) {
        return;
    }

    const next = [...order];
    const [moved] = next.splice(from, 1);
    next.splice(to, 0, moved);

    draftOrderIds.value = next.map((expense) => expense.id);

    const newIndex = next.findIndex((expense) => expense.id === sourceId);
    const beforeId = next[newIndex + 1]?.id ?? null;
    const payload =
        beforeId == null ? { position: 'last' as const } : { before_id: beforeId };

    movingId.value = sourceId;
    router.patch(`/expenses/${sourceId}/move`, payload, {
        preserveScroll: true,
        onFinish: () => {
            movingId.value = null;
        },
        onError: () => {
            draftOrderIds.value = null;
        },
    });
}

watch(
    () => props.expenses,
    () => {
        draftOrderIds.value = null;
    },
);

const categorySummaries = computed(() => {
    const groups = new Map<
        string,
        { key: string; name: string; count: number; byCurrency: Record<string, number> }
    >();

    for (const expense of props.expenses) {
        const key = expense.category?.id
            ? String(expense.category.id)
            : 'uncategorized';
        const name = expense.category?.name || t('General');
        const code = resolveCurrency(expense.currency || baseCurrency.value);

        if (!groups.has(key)) {
            groups.set(key, { key, name, count: 0, byCurrency: {} });
        }

        const group = groups.get(key)!;
        group.count += 1;
        group.byCurrency[code] =
            (group.byCurrency[code] ?? 0) + Number(expense.amount ?? 0);
    }

    return [...groups.values()].sort((a, b) => a.name.localeCompare(b.name));
});

const expenseTotals = computed(() => ({
    count: props.expenses.length,
}));

const headerSummary = computed(() => {
    const parts = [
        `${formatNumber(expenseTotals.value.count, 0)} ${t('records')}`,
    ];

    if (categorySummaries.value.length) {
        parts.push(
            `${formatNumber(categorySummaries.value.length, 0)} ${t('categories')}`,
        );
    }

    return parts.join(' · ');
});

const filteredExpenses = computed(() => {
    let rows = orderedSourceExpenses();

    if (categoryFilter.value !== 'all') {
        rows = rows.filter((expense) => {
            const key = expense.category?.id
                ? String(expense.category.id)
                : 'uncategorized';

            return key === categoryFilter.value;
        });
    }

    const q = expenseSearch.value.trim().toLowerCase();
    if (!q) {
        return rows;
    }

    return rows.filter((expense) => {
        const method =
            expense.payment_method === 'bank' ? 'bank payment' : 'cash';
        const haystack = [
            expense.expense_no,
            expense.expense_date,
            formatDate(expense.expense_date),
            expense.reference_no,
            expense.paid_to,
            expense.category?.name,
            expense.description,
            expense.expense_unit?.unit_name,
            expense.payment_method,
            method,
            expense.amount,
            expense.currency,
        ]
            .filter((v) => v != null && v !== '')
            .join(' ')
            .toLowerCase();

        return haystack.includes(q);
    });
});

const { sortedRows, sortKey, sortDir } = provideTableSort(filteredExpenses, {
    defaultKey: 'sort_order',
    defaultDir: 'asc',
});

const dragEnabled = computed(
    () =>
        canReorder.value &&
        sortKey.value === 'sort_order' &&
        sortDir.value === 'asc' &&
        !expenseSearch.value.trim() &&
        categoryFilter.value === 'all',
);

const printRecordOptions = computed<PrintRecordOption[]>(() =>
    sortedRows.value.map((expense) => ({
        key: String(expense.id),
        title: expense.paid_to || t('Expense'),
        subtitle: [
            formatDate(expense.expense_date),
            expense.reference_no || expense.expense_no || '—',
            expense.category?.name || t('General'),
        ].join(' · '),
        amount: money(expense.amount, expense.currency || baseCurrency.value),
    })),
);

const categoryOptions = computed<MisSelectOption[]>(() => [
    { value: 'all', label: t('All categories') },
    ...categorySummaries.value.map((group) => ({
        value: group.key,
        label: `${group.name} (${formatNumber(group.count, 0)})`,
    })),
]);

const totalPages = computed(() =>
    Math.max(1, Math.ceil(filteredExpenses.value.length / perPage)),
);

const paginatedRows = computed(() => {
    const start = (currentPage.value - 1) * perPage;

    return sortedRows.value.slice(start, start + perPage);
});

const paginationFrom = computed(() =>
    filteredExpenses.value.length ? (currentPage.value - 1) * perPage + 1 : 0,
);

const paginationTo = computed(() =>
    Math.min(currentPage.value * perPage, filteredExpenses.value.length),
);

watch([expenseSearch, categoryFilter], () => {
    currentPage.value = 1;
});

watch(totalPages, (pages) => {
    if (currentPage.value > pages) {
        currentPage.value = pages;
    }
});

const expenseExportHeaders = computed(() => [
    t('Date'),
    t('Bill number'),
    t('Paid to'),
    t('Category'),
    t('Description'),
    t('Unit'),
    t('Paying type'),
    t('Amount'),
]);

function mapExpenseExportRow(expense: ExpenseRow): ExportCell[] {
    return [
        formatDate(expense.expense_date),
        expense.reference_no || null,
        expense.paid_to || null,
        expense.category?.name || t('General'),
        expense.description || null,
        expense.expense_unit?.unit_name || null,
        expense.payment_method === 'bank' ? t('Bank') : t('Cash'),
        money(expense.amount, expense.currency || baseCurrency.value),
    ];
}

const expenseExportRows = computed(() =>
    props.expenses.map(mapExpenseExportRow),
);
const expenseFilteredExportRows = computed(() =>
    sortedRows.value.map(mapExpenseExportRow),
);

const printCategoryLabel = computed(() => {
    if (categoryFilter.value === 'all') {
        return null;
    }

    return (
        categoryOptions.value.find((o) => o.value === categoryFilter.value)
            ?.label ?? null
    );
});
</script>

<template>
    <V2Panel :padded="false" class="project-money-panel project-expenses-panel">
        <template #header>
            <div>
                <h2>{{ t('Expenses') }}</h2>
                <p>{{ headerSummary }}</p>
            </div>
        </template>
        <template #actions>
            <MisTableActions
                :filename="`project-${item.id}-expenses`"
                :headers="expenseExportHeaders"
                :rows="expenseExportRows"
                :filtered-rows="expenseFilteredExportRows"
                :filters="{
                    search: expenseSearch,
                    category:
                        categoryFilter !== 'all' ? categoryFilter : '',
                }"
                :on-print="openPrintPicker"
            >
                <CreateButton
                    :href="`/purchases/create?project_id=${item.id}`"
                    permission="purchases.create"
                    variant="outline"
                >
                    {{ t('Purchase') }}
                </CreateButton>
                <CreateButton
                    permission="expenses.create"
                    @click="emit('open-expense', null)"
                >
                    {{ t('Add expense') }}
                </CreateButton>
            </MisTableActions>
        </template>

        <div class="project-expenses-filters">
            <MisSearchInput
                v-model="expenseSearch"
                class="h-8 min-w-0 w-full flex-1 sm:min-w-[12rem] sm:max-w-sm"
                :placeholder="t('Search expenses...')"
            />
            <select
                v-if="categorySummaries.length"
                v-model="categoryFilter"
                class="h-8 w-full rounded-lg border border-input bg-background px-2.5 text-sm outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] sm:max-w-[14rem] sm:w-auto"
            >
                <option
                    v-for="option in categoryOptions"
                    :key="option.value"
                    :value="option.value"
                >
                    {{ option.label }}
                </option>
            </select>
        </div>

        <TableFrame table-id="project-expenses" :columns="tableColumns">
            <table class="project-expenses-table min-w-full text-sm">
                <thead class="table-chrome-head text-start text-muted-foreground">
                    <tr>
                        <th class="w-8 px-1 font-medium" aria-label="Reorder" />
                        <TableIndexTh />
                        <SortableTh column="expense_date">{{ t('Date') }}</SortableTh>
                        <SortableTh column="reference_no">{{ t('Bill number') }}</SortableTh>
                        <SortableTh column="paid_to">{{ t('Paid to') }}</SortableTh>
                        <SortableTh column="category.name">{{ t('Category') }}</SortableTh>
                        <SortableTh column="description">{{ t('Description') }}</SortableTh>
                        <SortableTh column="expense_unit.unit_name">{{ t('Unit') }}</SortableTh>
                        <SortableTh column="payment_method">{{ t('Paying type') }}</SortableTh>
                        <SortableTh column="amount">{{ t('Amount') }}</SortableTh>
                        <SortableTh column="attachments">{{ t('Attachments') }}</SortableTh>
                        <th class="text-end font-medium">{{ t('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="(expense, index) in paginatedRows"
                        :key="expense.id"
                        class="cursor-pointer border-t hover:bg-muted/30"
                        :class="{
                            'opacity-50': dragExpenseId === expense.id,
                            'bg-muted/50 ring-1 ring-inset ring-primary/40':
                                dropTargetId === expense.id &&
                                dragExpenseId !== expense.id,
                        }"
                        @click="emit('open-expense', expense)"
                        @dragover="onDragOver($event, expense)"
                        @dragleave="onDragLeave(expense)"
                        @drop="onDrop($event, expense)"
                    >
                        <td class="w-8 px-1" @click.stop>
                            <button
                                v-if="dragEnabled"
                                type="button"
                                class="inline-flex size-7 cursor-grab items-center justify-center rounded-md text-muted-foreground hover:bg-muted hover:text-foreground active:cursor-grabbing"
                                :title="t('Drag to reorder')"
                                :disabled="movingId === expense.id"
                                draggable="true"
                                @dragstart="onDragStart($event, expense)"
                                @dragend="onDragEnd"
                            >
                                <GripVertical class="size-3.5" />
                            </button>
                            <span
                                v-else
                                class="inline-flex size-7 items-center justify-center text-muted-foreground/40"
                                :title="
                                    canReorder
                                        ? t('Clear filters and sort by order to drag')
                                        : undefined
                                "
                            >
                                <GripVertical class="size-3.5" />
                            </span>
                        </td>
                        <TableIndexTd :index="index" :from="paginationFrom" />
                        <td class="whitespace-nowrap">
                            {{ formatDate(expense.expense_date) }}
                        </td>
                        <td class="font-medium tabular-nums">
                            {{ expense.reference_no || '—' }}
                        </td>
                        <td>{{ expense.paid_to || '—' }}</td>
                        <td>
                            <span
                                class="inline-flex rounded-full bg-muted px-1.5 py-0.5 text-[11px] font-medium text-muted-foreground"
                            >
                                {{ expense.category?.name || t('General') }}
                            </span>
                        </td>
                        <td class="max-w-[14rem] truncate">
                            {{ expense.description || '—' }}
                        </td>
                        <td class="whitespace-nowrap">
                            {{ expense.expense_unit?.unit_name || '—' }}
                        </td>
                        <td class="whitespace-nowrap capitalize">
                            {{
                                expense.payment_method === 'bank'
                                    ? t('Bank')
                                    : t('Cash')
                            }}
                        </td>
                        <td class="tabular-nums font-semibold whitespace-nowrap text-rose-700">
                            {{
                                money(
                                    expense.amount,
                                    expense.currency || baseCurrency,
                                )
                            }}
                        </td>
                        <td @click.stop>
                            <AttachmentLinks :files="expense.attachments" />
                        </td>
                        <td @click.stop>
                            <div class="flex justify-end gap-1">
                                <Button
                                    type="button"
                                    size="sm"
                                    variant="outline"
                                    class="h-7 gap-1 px-2"
                                    @click="printExpenseOne(expense)"
                                >
                                    <Printer class="size-3.5" />
                                    {{ t('Print') }}
                                </Button>
                                <Can permission="expenses.edit">
                                    <Button
                                        v-if="!expense.purchase_id"
                                        type="button"
                                        variant="outline"
                                        size="sm"
                                        class="h-7 px-2"
                                        @click="emit('open-expense', expense, true)"
                                    >
                                        {{ t('Edit') }}
                                    </Button>
                                </Can>
                                <Can permission="expenses.delete">
                                    <Button
                                        v-if="!expense.purchase_id"
                                        type="button"
                                        variant="outline"
                                        size="icon"
                                        class="size-7 text-destructive hover:text-destructive"
                                        :disabled="deletingId === expense.id"
                                        @click.stop="requestDeleteExpense(expense)"
                                    >
                                        <Trash2 class="size-3.5" />
                                    </Button>
                                </Can>
                                <Can permission="purchases.delete">
                                    <Button
                                        v-if="expense.purchase_id"
                                        type="button"
                                        variant="outline"
                                        size="icon"
                                        class="size-7 text-destructive hover:text-destructive"
                                        :disabled="deletingId === expense.id"
                                        @click.stop="requestDeleteExpense(expense)"
                                    >
                                        <Trash2 class="size-3.5" />
                                    </Button>
                                </Can>
                            </div>
                        </td>
                    </tr>
                    <EmptyState
                        v-if="!filteredExpenses.length"
                        :colspan="12"
                        :title="
                            expenseSearch.trim() || categoryFilter !== 'all'
                                ? t('No matching expenses')
                                : t('No expenses yet')
                        "
                    />
                </tbody>
            </table>
        </TableFrame>

        <div
            v-if="filteredExpenses.length > perPage"
            class="flex flex-wrap items-center justify-between gap-2 border-t px-4 py-2"
        >
            <p class="text-xs text-muted-foreground">
                {{
                    t('Showing :from–:to of :total', {
                        from: formatNumber(paginationFrom, 0),
                        to: formatNumber(paginationTo, 0),
                        total: formatNumber(filteredExpenses.length, 0),
                    })
                }}
            </p>
            <div class="flex items-center gap-1">
                <Button
                    type="button"
                    variant="outline"
                    size="icon"
                    class="size-7"
                    :disabled="currentPage <= 1"
                    @click="currentPage -= 1"
                >
                    <ChevronLeft class="size-3.5 rtl:rotate-180" />
                </Button>
                <span class="min-w-[3.5rem] text-center text-xs tabular-nums text-muted-foreground">
                    {{ currentPage }} / {{ totalPages }}
                </span>
                <Button
                    type="button"
                    variant="outline"
                    size="icon"
                    class="size-7"
                    :disabled="currentPage >= totalPages"
                    @click="currentPage += 1"
                >
                    <ChevronRight class="size-3.5 rtl:rotate-180" />
                </Button>
            </div>
        </div>

        <Teleport to="body">
            <div class="a4-print-host">
                <ExpenseVoucherPrintSheet
                    v-if="printSingle"
                    :expense="printSingle"
                    :project-name="item.name"
                    :project-code="item.code"
                />
                <ExpenseListPrintSheet
                    v-else-if="printListRows.length"
                    :project-name="item.name"
                    :project-code="item.code"
                    :category-label="printCategoryLabel"
                    :rows="printListRows"
                    :base-currency="baseCurrency"
                />
            </div>
        </Teleport>
    </V2Panel>

    <ConfirmActionDialog
        v-model:open="confirmDeleteOpen"
        :title="t('Delete expense?')"
        :description="t('This cannot be undone.')"
        :confirm-label="t('Delete')"
        :processing="deletingId !== null"
        @confirm="deleteExpense"
    />

    <PrintRecordsDialog
        v-model:open="printPickerOpen"
        v-model:selected-keys="selectedPrintKeys"
        :records="printRecordOptions"
        :title="t('Print expenses')"
        :empty-label="t('No expenses yet')"
        @confirm="confirmPrintSelection"
    />
</template>
