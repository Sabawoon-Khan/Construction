<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { ChevronLeft, ChevronRight, Printer, Trash2 } from '@lucide/vue';
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
} from '@/components/mis';
import type { ExportCell } from '@/composables/useTableExport';
import type { MisSelectOption } from '@/components/mis/MisSelectField.vue';
import { Button } from '@/components/ui/button';
import { CreateButton } from '@/components/ui/create-button';
import { V2Panel } from '@/components/v2';
import { useA4Print } from '@/composables/useA4Print';
import { useCalendar } from '@/composables/useCalendar';
import { useCurrency } from '@/composables/useCurrency';
import { provideTableSort } from '@/composables/useTableSort';
import { useTranslations } from '@/composables/useTranslations';
import { formatNumber, money, resolveCurrency } from '@/lib/utils';

const props = defineProps<{
    item: Record<string, any>;
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
}>();

const emit = defineEmits<{
    'open-expense': [expense: Record<string, any> | null, edit?: boolean];
}>();

const { t } = useTranslations();
const tableColumns = computed(() => [
    indexTableColumn(),
    { key: 'expense_date', label: t('Date') },
    { key: 'reference_no', label: t('Bill number') },
    { key: 'paid_to', label: t('Paid to') },
    { key: 'category.name', label: t('Category') },
    { key: 'description', label: t('Description') },
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
const pendingDelete = ref<(typeof props.expenses)[number] | null>(null);
const confirmDeleteOpen = ref(false);
const printSingle = ref<(typeof props.expenses)[number] | null>(null);
const { printA4 } = useA4Print();

function printExpenseList() {
    void printA4(() => {
        printSingle.value = null;
    });
}

function printExpenseOne(expense: (typeof props.expenses)[number]) {
    void printA4(() => {
        printSingle.value = expense;
    });
}

function requestDeleteExpense(expense: (typeof props.expenses)[number]) {
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
    let rows = props.expenses;

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

const { sortedRows } = provideTableSort(filteredExpenses);

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
    t('Paying type'),
    t('Amount'),
]);

function mapExpenseExportRow(
    expense: (typeof props.expenses)[number],
): ExportCell[] {
    return [
        formatDate(expense.expense_date),
        expense.reference_no || null,
        expense.paid_to || null,
        expense.category?.name || t('General'),
        expense.description || null,
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
                :on-print="printExpenseList"
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
                        <TableIndexTh />
                        <SortableTh column="expense_date">{{ t('Date') }}</SortableTh>
                        <SortableTh column="reference_no">{{ t('Bill number') }}</SortableTh>
                        <SortableTh column="paid_to">{{ t('Paid to') }}</SortableTh>
                        <SortableTh column="category.name">{{ t('Category') }}</SortableTh>
                        <SortableTh column="description">{{ t('Description') }}</SortableTh>
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
                        @click="emit('open-expense', expense)"
                    >
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
                        :colspan="10"
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

        <!-- A4 print sheets (screen-hidden; isolated while printing) -->
        <div class="a4-print-host">
            <ExpenseVoucherPrintSheet
                v-if="printSingle"
                :expense="printSingle"
                :project-name="item.name"
                :project-code="item.code"
            />
            <ExpenseListPrintSheet
                v-else
                :project-name="item.name"
                :project-code="item.code"
                :category-label="printCategoryLabel"
                :rows="sortedRows"
                :base-currency="baseCurrency"
            />
        </div>
    </V2Panel>

    <ConfirmActionDialog
        v-model:open="confirmDeleteOpen"
        :title="t('Delete expense?')"
        :description="t('This cannot be undone.')"
        :confirm-label="t('Delete')"
        :processing="deletingId !== null"
        @confirm="deleteExpense"
    />
</template>
