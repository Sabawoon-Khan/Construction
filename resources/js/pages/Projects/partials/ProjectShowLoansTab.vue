<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import Can from '@/components/Can.vue';
import ConfirmActionDialog from '@/components/ConfirmActionDialog.vue';
import CurrencySelect from '@/components/CurrencySelect.vue';
import EmptyState from '@/components/EmptyState.vue';
import InputError from '@/components/InputError.vue';
import { MisTableActions } from '@/components/mis';
import type { ExportCell } from '@/composables/useTableExport';
import SortableTh from '@/components/SortableTh.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import TableFrame from '@/components/TableFrame.vue';
import TableIndexTd from '@/components/TableIndexTd.vue';
import TableIndexTh from '@/components/TableIndexTh.vue';
import { indexTableColumn } from '@/composables/useTableColumns';
import { Button } from '@/components/ui/button';
import { CreateButton } from '@/components/ui/create-button';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { V2Panel } from '@/components/v2';
import { useCalendar } from '@/composables/useCalendar';
import { useCurrency } from '@/composables/useCurrency';
import { useTableSort } from '@/composables/useTableSort';
import { useTranslations } from '@/composables/useTranslations';
import { money } from '@/lib/utils';

type LoanPayment = {
    id: number;
    amount: number;
    currency?: string;
    paid_at?: string | null;
    payment_method?: string | null;
    reference_no?: string | null;
};

type LoanRow = {
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
    payments?: LoanPayment[];
};

const props = defineProps<{
    item: Record<string, any>;
    loans?: LoanRow[];
}>();

const { t } = useTranslations();
const { baseCurrency } = useCurrency();
const calendar = useCalendar();
const formatDate = (value: string | null | undefined) =>
    calendar.format(value) || '—';

const tableColumns = computed(() => [
    indexTableColumn(),
    { key: 'loan_no', label: t('Loan') },
    { key: 'lender_name', label: t('Lender') },
    { key: 'loan_date', label: t('Date') },
    { key: 'principal', label: t('Principal') },
    { key: 'paid_amount', label: t('Paid') },
    { key: 'remaining_amount', label: t('Remaining') },
    { key: 'status', label: t('Status') },
    { key: 'payout', label: t('Payout'), locked: true },
]);

const loanSort = useTableSort(() => props.loans ?? []);
const expandedId = ref<number | null>(null);

function remainingOf(loan: LoanRow) {
    return (
        loan.remaining_amount ??
        Math.max(0, Number(loan.principal) - Number(loan.paid_amount))
    );
}

function togglePayments(loanId: number) {
    expandedId.value = expandedId.value === loanId ? null : loanId;
}

const createOpen = ref(false);
const createForm = useForm({
    lender_name: '',
    loan_date: new Date().toISOString().slice(0, 10),
    due_date: '',
    principal: '',
    currency: baseCurrency.value,
    reference_no: '',
    notes: '',
});

function openCreate() {
    createForm.reset();
    createForm.loan_date = new Date().toISOString().slice(0, 10);
    createForm.currency = baseCurrency.value;
    createOpen.value = true;
}

function submitCreate() {
    createForm.post(`/projects/${props.item.id}/loans`, {
        preserveScroll: true,
        onSuccess: () => {
            createOpen.value = false;
            createForm.reset();
        },
    });
}

type PayDraft = {
    loanId: number;
    loanNo: string;
    amount: number;
    currency: string;
    payment_method: string;
    reference_no: string;
    paid_at: string;
};

const payConfirmOpen = ref(false);
const payProcessing = ref(false);
const payDraft = ref<PayDraft | null>(null);

function openPayConfirm(loan: LoanRow, formEl: HTMLFormElement | null) {
    if (!formEl) {
        return;
    }

    const data = new FormData(formEl);
    const amount = Number(data.get('amount') || 0);
    if (!amount || amount <= 0) {
        return;
    }

    payDraft.value = {
        loanId: loan.id,
        loanNo: loan.loan_no,
        amount,
        currency: loan.currency || baseCurrency.value,
        payment_method: String(data.get('payment_method') || 'cash'),
        reference_no: String(data.get('reference_no') || ''),
        paid_at: String(data.get('paid_at') || new Date().toISOString().slice(0, 10)),
    };
    payConfirmOpen.value = true;
}

function confirmPay() {
    if (!payDraft.value) {
        return;
    }

    const draft = payDraft.value;
    payProcessing.value = true;
    router.post(
        `/projects/${props.item.id}/loans/${draft.loanId}/pay`,
        {
            amount: draft.amount,
            payment_method: draft.payment_method,
            reference_no: draft.reference_no || undefined,
            paid_at: draft.paid_at,
        },
        {
            preserveScroll: true,
            onFinish: () => {
                payProcessing.value = false;
                payConfirmOpen.value = false;
                payDraft.value = null;
            },
        },
    );
}

const loanExportHeaders = computed(() => [
    t('Loan'),
    t('Lender'),
    t('Date'),
    t('Principal'),
    t('Paid'),
    t('Remaining'),
    t('Status'),
]);

function mapLoanExportRow(loan: LoanRow): ExportCell[] {
    return [
        loan.loan_no,
        loan.lender_name,
        formatDate(loan.loan_date),
        Number(loan.principal),
        Number(loan.paid_amount),
        remainingOf(loan),
        loan.status,
    ];
}

const loanExportRows = computed(() => (props.loans ?? []).map(mapLoanExportRow));
const loanFilteredExportRows = computed(() =>
    loanSort.sortedRows.map(mapLoanExportRow),
);
</script>

<template>
    <V2Panel
        :padded="false"
        :title="t('Loans')"
        :description="t('Project loans and payouts')"
    >
        <template #actions>
            
            <MisTableActions
                :filename="`project-${item.id}-loans`"
                :headers="loanExportHeaders"
                :rows="loanExportRows"
                :filtered-rows="loanFilteredExportRows"
            >
                <CreateButton permission="loans.create" @click="openCreate">
                {{ t('Add loan') }}
            </CreateButton>
            </MisTableActions>
        
        </template>

        <TableFrame table-id="project-loans" :columns="tableColumns">
            <table class="min-w-full text-sm">
                <thead class="table-chrome-head text-start text-muted-foreground">
                    <tr>
                        <TableIndexTh />
                        <SortableTh
                            column="loan_no"
                            :sort-key="loanSort.sortKey"
                            :sort-dir="loanSort.sortDir"
                            @sort="loanSort.sortBy"
                        >
                            {{ t('Loan') }}
                        </SortableTh>
                        <SortableTh
                            column="lender_name"
                            :sort-key="loanSort.sortKey"
                            :sort-dir="loanSort.sortDir"
                            @sort="loanSort.sortBy"
                        >
                            {{ t('Lender') }}
                        </SortableTh>
                        <SortableTh
                            column="loan_date"
                            :sort-key="loanSort.sortKey"
                            :sort-dir="loanSort.sortDir"
                            @sort="loanSort.sortBy"
                        >
                            {{ t('Date') }}
                        </SortableTh>
                        <SortableTh
                            column="principal"
                            :sort-key="loanSort.sortKey"
                            :sort-dir="loanSort.sortDir"
                            @sort="loanSort.sortBy"
                        >
                            {{ t('Principal') }}
                        </SortableTh>
                        <SortableTh
                            column="paid_amount"
                            :sort-key="loanSort.sortKey"
                            :sort-dir="loanSort.sortDir"
                            @sort="loanSort.sortBy"
                        >
                            {{ t('Paid') }}
                        </SortableTh>
                        <SortableTh
                            column="remaining_amount"
                            :sort-key="loanSort.sortKey"
                            :sort-dir="loanSort.sortDir"
                            @sort="loanSort.sortBy"
                        >
                            {{ t('Remaining') }}
                        </SortableTh>
                        <SortableTh
                            column="status"
                            :sort-key="loanSort.sortKey"
                            :sort-dir="loanSort.sortDir"
                            @sort="loanSort.sortBy"
                        >
                            {{ t('Status') }}
                        </SortableTh>
                        <th class="px-4 py-3 font-medium text-end">
                            {{ t('Payout') }}
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <template
                        v-for="(loan, index) in loanSort.sortedRows"
                        :key="loan.id"
                    >
                        <tr class="border-t hover:bg-muted/30">
                            <TableIndexTd :index="index" />
                            <td class="px-4 py-3">
                                <button
                                    type="button"
                                    class="font-medium text-primary hover:underline"
                                    @click="togglePayments(loan.id)"
                                >
                                    {{ loan.loan_no }}
                                </button>
                            </td>
                            <td class="px-4 py-3">{{ loan.lender_name }}</td>
                            <td class="px-4 py-3">
                                {{ formatDate(loan.loan_date) }}
                            </td>
                            <td class="px-4 py-3 tabular-nums">
                                {{
                                    money(
                                        loan.principal,
                                        loan.currency || baseCurrency,
                                    )
                                }}
                            </td>
                            <td class="px-4 py-3 tabular-nums">
                                {{
                                    money(
                                        loan.paid_amount,
                                        loan.currency || baseCurrency,
                                    )
                                }}
                            </td>
                            <td class="px-4 py-3 text-amber-700 tabular-nums">
                                {{
                                    money(
                                        remainingOf(loan),
                                        loan.currency || baseCurrency,
                                    )
                                }}
                            </td>
                            <td class="px-4 py-3">
                                <StatusBadge :status="loan.status" />
                            </td>
                            <td class="px-4 py-3 text-end">
                                <Can permission="loans.edit">
                                    <form
                                        v-if="remainingOf(loan) > 0"
                                        class="flex flex-wrap items-center justify-end gap-2"
                                        @submit.prevent="
                                            openPayConfirm(
                                                loan,
                                                $event.target as HTMLFormElement,
                                            )
                                        "
                                    >
                                        <Input
                                            name="amount"
                                            type="number"
                                            step="0.01"
                                            min="0.01"
                                            class="h-8 w-[100px] text-xs"
                                            :placeholder="t('Amount')"
                                            :default-value="remainingOf(loan)"
                                        />
                                        <select
                                            name="payment_method"
                                            class="h-8 rounded-md border border-input bg-transparent px-2 text-xs"
                                        >
                                            <option value="cash">
                                                {{ t('Cash') }}
                                            </option>
                                            <option value="bank">
                                                {{ t('Bank') }}
                                            </option>
                                            <option value="transfer">
                                                {{ t('Transfer') }}
                                            </option>
                                        </select>
                                        <Input
                                            name="reference_no"
                                            type="text"
                                            class="h-8 w-[100px] text-xs"
                                            :placeholder="t('Ref')"
                                        />
                                        <Input
                                            name="paid_at"
                                            type="date"
                                            class="h-8 w-[130px] text-xs"
                                            :default-value="
                                                new Date()
                                                    .toISOString()
                                                    .slice(0, 10)
                                            "
                                        />
                                        <Button type="submit" size="sm">
                                            {{ t('Payout') }}
                                        </Button>
                                    </form>
                                    <span
                                        v-else
                                        class="text-xs text-muted-foreground"
                                        >—</span
                                    >
                                </Can>
                            </td>
                        </tr>
                        <tr
                            v-if="expandedId === loan.id"
                            class="border-t bg-muted/20"
                        >
                            <td colspan="9" class="px-4 py-3">
                                <div class="text-xs font-medium text-muted-foreground">
                                    {{ t('Payout history') }}
                                </div>
                                <ul
                                    v-if="(loan.payments || []).length"
                                    class="mt-2 space-y-1 text-sm"
                                >
                                    <li
                                        v-for="payment in loan.payments"
                                        :key="payment.id"
                                        class="flex flex-wrap gap-x-3 gap-y-1"
                                    >
                                        <span>{{ formatDate(payment.paid_at) }}</span>
                                        <span class="tabular-nums font-medium">
                                            {{
                                                money(
                                                    payment.amount,
                                                    payment.currency ||
                                                        loan.currency ||
                                                        baseCurrency,
                                                )
                                            }}
                                        </span>
                                        <span class="text-muted-foreground">
                                            {{ payment.payment_method || '—' }}
                                        </span>
                                        <span
                                            v-if="payment.reference_no"
                                            class="text-muted-foreground"
                                        >
                                            {{ payment.reference_no }}
                                        </span>
                                    </li>
                                </ul>
                                <p
                                    v-else
                                    class="mt-2 text-sm text-muted-foreground"
                                >
                                    {{ t('No payouts yet') }}
                                </p>
                            </td>
                        </tr>
                    </template>
                    <EmptyState
                        v-if="!(loans || []).length"
                        :colspan="9"
                        :title="t('No loans yet')"
                    >
                        </EmptyState>
                </tbody>
            </table>
        </TableFrame>

        <Dialog :open="createOpen" @update:open="createOpen = $event">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>{{ t('Add loan') }}</DialogTitle>
                </DialogHeader>
                <form class="space-y-3" @submit.prevent="submitCreate">
                    <div class="space-y-1.5">
                        <Label for="loan-lender">{{ t('Lender') }}</Label>
                        <Input
                            id="loan-lender"
                            v-model="createForm.lender_name"
                            required
                        />
                        <InputError :message="createForm.errors.lender_name" />
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div class="space-y-1.5">
                            <Label for="loan-date">{{ t('Date') }}</Label>
                            <Input
                                id="loan-date"
                                v-model="createForm.loan_date"
                                type="date"
                                required
                            />
                            <InputError :message="createForm.errors.loan_date" />
                        </div>
                        <div class="space-y-1.5">
                            <Label for="loan-due">{{ t('Due date') }}</Label>
                            <Input
                                id="loan-due"
                                v-model="createForm.due_date"
                                type="date"
                            />
                            <InputError :message="createForm.errors.due_date" />
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div class="space-y-1.5">
                            <Label for="loan-principal">{{ t('Principal') }}</Label>
                            <Input
                                id="loan-principal"
                                v-model="createForm.principal"
                                type="number"
                                step="0.01"
                                min="0.01"
                                required
                            />
                            <InputError :message="createForm.errors.principal" />
                        </div>
                        <div class="space-y-1.5">
                            <Label>{{ t('Currency') }}</Label>
                            <CurrencySelect v-model="createForm.currency" />
                            <InputError :message="createForm.errors.currency" />
                        </div>
                    </div>
                    <div class="space-y-1.5">
                        <Label for="loan-ref">{{ t('Reference') }}</Label>
                        <Input
                            id="loan-ref"
                            v-model="createForm.reference_no"
                        />
                        <InputError :message="createForm.errors.reference_no" />
                    </div>
                    <div class="space-y-1.5">
                        <Label for="loan-notes">{{ t('Notes') }}</Label>
                        <Input id="loan-notes" v-model="createForm.notes" />
                        <InputError :message="createForm.errors.notes" />
                    </div>
                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            @click="createOpen = false"
                        >
                            {{ t('Cancel') }}
                        </Button>
                        <Button type="submit" :disabled="createForm.processing">
                            {{ t('Save') }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <ConfirmActionDialog
            v-model:open="payConfirmOpen"
            :title="t('Record payout')"
            :description="
                payDraft
                    ? `${payDraft.loanNo} · ${money(payDraft.amount, payDraft.currency)}`
                    : undefined
            "
            :confirm-label="t('Payout')"
            variant="default"
            :processing="payProcessing"
            @confirm="confirmPay"
        />
    </V2Panel>
</template>
