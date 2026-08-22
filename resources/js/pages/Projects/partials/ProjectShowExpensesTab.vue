<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import { Trash2 } from '@lucide/vue';
import { computed, ref } from 'vue';
import AttachmentLinks from '@/components/AttachmentLinks.vue';
import Can from '@/components/Can.vue';
import SortableTh from '@/components/SortableTh.vue';
import TableFrame from '@/components/TableFrame.vue';
import { MisSearchInput } from '@/components/mis';
import { Button } from '@/components/ui/button';
import { useCalendar } from '@/composables/useCalendar';
import { useCurrency } from '@/composables/useCurrency';
import { provideTableSort } from '@/composables/useTableSort';
import { useTranslations } from '@/composables/useTranslations';
import { money } from '@/lib/utils';

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
    { key: 'expense_date', label: t('Date') },
    { key: 'reference_no', label: t('Bill number') },
    { key: 'paid_to', label: t('Paid to') },
    { key: 'category.name', label: t('Category') },
    { key: 'description', label: t('Description') },
    { key: 'payment_method', label: t('Paying type') },
    { key: 'amount', label: t('Amount') },
    { key: 'attachments', label: t('Attachments') },
    { key: 'created_at', label: t('Created at') },
    { key: 'actions', label: t('Actions'), locked: true },
]);
const { baseCurrency } = useCurrency();
const calendar = useCalendar();
const formatDate = (value: string | null | undefined) =>
    calendar.format(value) || '—';

const expenseSearch = ref('');
const deletingId = ref<number | null>(null);

function deleteExpense(expense: (typeof props.expenses)[number]) {
    deletingId.value = expense.id;
    const href = expense.purchase_id
        ? `/purchases/${expense.purchase_id}`
        : `/expenses/${expense.id}`;

    router.delete(href, {
        preserveScroll: true,
        onFinish: () => {
            deletingId.value = null;
        },
    });
}

const filteredExpenses = computed(() => {
    const q = expenseSearch.value.trim().toLowerCase();
    if (!q) {
        return props.expenses;
    }

    return props.expenses.filter((expense) => {
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
</script>

<template>
    <div class="overflow-hidden rounded-xl border bg-card shadow-sm">
        <div
            class="flex flex-wrap items-center justify-between gap-3 border-b px-5 py-4"
        >
            <h3 class="text-sm font-semibold">
                {{ t('Project expenses') }}
            </h3>
            <div class="flex flex-wrap gap-2">
                <Can permission="purchases.create">
                    <Button as-child size="sm" variant="outline">
                        <Link
                            :href="`/purchases/create?project_id=${item.id}`"
                        >{{ t('New purchase') }}</Link>
                    </Button>
                </Can>
                <Can permission="expenses.create">
                    <Button size="sm" @click="emit('open-expense', null)">{{
                        t('Add expense')
                    }}</Button>
                </Can>
            </div>
        </div>

        <div class="border-b px-5 py-3">
            <MisSearchInput
                v-model="expenseSearch"
                class="max-w-sm"
                :placeholder="t('Search expenses...')"
            />
        </div>

        <TableFrame table-id="project-expenses" :columns="tableColumns">
            <table class="min-w-full text-sm">
                <thead class="bg-muted/40 text-start text-muted-foreground">
                    <tr>
                        <SortableTh column="expense_date">{{ t('Date') }}</SortableTh>
                        <SortableTh column="reference_no">{{ t('Bill number') }}</SortableTh>
                        <SortableTh column="paid_to">{{ t('Paid to') }}</SortableTh>
                        <SortableTh column="category.name">{{ t('Category') }}</SortableTh>
                        <SortableTh column="description">{{ t('Description') }}</SortableTh>
                        <SortableTh column="payment_method">{{ t('Paying type') }}</SortableTh>
                        <SortableTh column="amount">{{ t('Amount') }}</SortableTh>
                        <SortableTh column="attachments">{{ t('Attachments') }}</SortableTh>
                        <SortableTh column="created_at">{{ t('Created at') }}</SortableTh>
                        <th class="px-4 py-3 text-end font-medium">{{ t('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="expense in sortedRows"
                        :key="expense.id"
                        class="cursor-pointer border-t hover:bg-muted/30"
                        @click="emit('open-expense', expense)"
                    >
                        <td class="px-4 py-3 whitespace-nowrap">
                            {{ formatDate(expense.expense_date) }}
                        </td>
                        <td class="px-4 py-3 font-medium tabular-nums">
                            {{ expense.reference_no || '—' }}
                        </td>
                        <td class="px-4 py-3">
                            {{ expense.paid_to || '—' }}
                        </td>
                        <td class="px-4 py-3">
                            {{ expense.category?.name || t('General') }}
                        </td>
                        <td class="max-w-[16rem] truncate px-4 py-3">
                            {{ expense.description || '—' }}
                            <span
                                v-if="expense.purchase_id"
                                class="ms-1 text-xs text-muted-foreground"
                            >{{ t('(from purchase)') }}</span>
                        </td>
                        <td class="px-4 py-3">
                            {{
                                expense.payment_method === 'bank'
                                    ? t('Bank payment')
                                    : t('Cash')
                            }}
                        </td>
                        <td class="px-4 py-3 tabular-nums whitespace-nowrap">
                            {{
                                money(
                                    expense.amount,
                                    expense.currency || baseCurrency,
                                )
                            }}
                        </td>
                        <td class="px-4 py-3" @click.stop>
                            <AttachmentLinks :files="expense.attachments" />
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-muted-foreground">{{ formatDate(expense.created_at) }}</td>
                        <td class="px-4 py-3" @click.stop>
                            <div class="flex flex-wrap justify-end gap-1">
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="sm"
                                    @click="emit('open-expense', expense, false)"
                                >
                                    {{ t('Open') }}
                                </Button>
                                <Can permission="expenses.edit">
                                    <Button
                                        v-if="!expense.purchase_id"
                                        type="button"
                                        variant="ghost"
                                        size="sm"
                                        @click="emit('open-expense', expense, true)"
                                    >
                                        {{ t('Edit') }}
                                    </Button>
                                </Can>
                                <Can permission="expenses.delete">
                                    <Button
                                        v-if="!expense.purchase_id"
                                        type="button"
                                        variant="ghost"
                                        size="sm"
                                        class="text-destructive hover:text-destructive"
                                        :disabled="deletingId === expense.id"
                                        @click.stop="deleteExpense(expense)"
                                    >
                                        <Trash2 class="size-4" />
                                    </Button>
                                </Can>
                                <Can permission="purchases.delete">
                                    <Button
                                        v-if="expense.purchase_id"
                                        type="button"
                                        variant="ghost"
                                        size="sm"
                                        class="text-destructive hover:text-destructive"
                                        :disabled="deletingId === expense.id"
                                        @click.stop="deleteExpense(expense)"
                                    >
                                        <Trash2 class="size-4" />
                                    </Button>
                                </Can>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="!filteredExpenses.length">
                        <td
                            colspan="10"
                            class="px-4 py-10 text-center text-muted-foreground"
                        >
                            {{
                                expenseSearch.trim()
                                    ? t('No matching expenses')
                                    : t('No expenses yet')
                            }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </TableFrame>
    </div>
</template>
