<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Pencil, Printer } from '@lucide/vue';
import Can from '@/components/Can.vue';
import DetailGrid from '@/components/DetailGrid.vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useCalendar } from '@/composables/useCalendar';
import { useCurrency } from '@/composables/useCurrency';
import { useTranslations } from '@/composables/useTranslations';
import { money } from '@/lib/utils';

const props = defineProps<{
    purchase: any;
    paid_amount: number;
    paid_amount_base: number;
    remaining_amount: number;
    remaining_amount_base: number;
    total_amount: number;
    total_amount_base: number;
}>();
const { t } = useTranslations();
const { baseCurrency } = useCurrency();
const calendar = useCalendar();
const formatDate = (value: string | null | undefined) => calendar.format(value) || '—';

const payForm = useForm({
    amount: String(props.remaining_amount || ''),
    payment_method: 'cash',
    reference_no: '',
    notes: '',
});

function submitPayment() {
    payForm.post(`/purchases/${props.purchase.id}/pay`, {
        preserveScroll: true,
        onSuccess: () => payForm.reset('amount', 'reference_no', 'notes'),
    });
}

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Purchases', href: '/purchases' },
            { title: 'Details', href: '#' },
        ],
    },
});
</script>

<template>
    <Head :title="`${t('Purchase')} ${purchase.purchase_no}`" />
    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div class="space-y-3">
                <Button as-child variant="ghost" size="sm" class="-ml-2 w-fit">
                    <Link href="/purchases"><ArrowLeft class="size-4" /> {{ t('Back') }}</Link>
                </Button>
                <Heading
                    :title="purchase.purchase_no"
                    :description="`${purchase.supplier?.name || '—'} · ${purchase.project?.name || '—'}`"
                />
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <StatusBadge :status="purchase.status" />
                <template v-if="purchase.expense?.id">
                    <Button as-child variant="outline">
                        <Link :href="`/expenses/${purchase.expense.id}`"><Printer class="size-4" /> {{ t('Voucher') }}</Link>
                    </Button>
                </template>
                <Can permission="purchases.edit">
                    <Button as-child variant="outline">
                        <Link :href="`/purchases/${purchase.id}/edit`"><Pencil class="size-4" /> {{ t('Edit') }}</Link>
                    </Button>
                </Can>
            </div>
        </div>

        <div class="grid gap-4 md:grid-cols-3">
            <div class="rounded-xl border bg-card p-4 shadow-sm">
                <div class="text-sm text-muted-foreground">{{ t('Purchase total') }}</div>
                <div class="mt-1 text-2xl font-semibold tabular-nums">{{ money(total_amount, purchase.currency || baseCurrency) }}</div>
                <div class="text-xs text-muted-foreground tabular-nums">{{ money(total_amount_base, baseCurrency) }} {{ t('base') }}</div>
            </div>
            <div class="rounded-xl border bg-card p-4 shadow-sm">
                <div class="text-sm text-muted-foreground">{{ t('Paid') }}</div>
                <div class="mt-1 text-2xl font-semibold tabular-nums text-emerald-700">{{ money(paid_amount, purchase.currency || baseCurrency) }}</div>
                <div class="text-xs text-muted-foreground tabular-nums">{{ money(paid_amount_base, baseCurrency) }} {{ t('base') }}</div>
            </div>
            <div class="rounded-xl border bg-card p-4 shadow-sm">
                <div class="text-sm text-muted-foreground">{{ t('Remaining owed') }}</div>
                <div class="mt-1 text-2xl font-semibold tabular-nums text-amber-700">{{ money(remaining_amount, purchase.currency || baseCurrency) }}</div>
                <div class="text-xs text-muted-foreground tabular-nums">{{ money(remaining_amount_base, baseCurrency) }} {{ t('base') }}</div>
            </div>
        </div>

        <DetailGrid
            :title="t('Purchase details')"
            :items="[
                { label: t('Supplier'), value: purchase.supplier?.name },
                { label: t('Project'), value: purchase.project?.name },
                { label: t('Date'), value: formatDate(purchase.purchase_date) },
                { label: t('Reference'), value: purchase.reference_no },
                { label: t('Expense'), value: purchase.expense?.expense_no },
                { label: t('Notes'), value: purchase.notes, span: true },
            ]"
        >
            <template v-if="purchase.expense_id || purchase.expense?.id">
                <Button as-child variant="outline" size="sm">
                    <Link :href="`/expenses/${purchase.expense.id}`">{{ t('View project expense') }}</Link>
                </Button>
            </template>
        </DetailGrid>

        <div class="overflow-hidden rounded-xl border bg-card shadow-sm">
            <div class="border-b px-5 py-3">
                <h3 class="text-sm font-semibold">{{ t('Materials (in stock)') }}</h3>
            </div>
            <table class="min-w-full text-sm">
                <thead class="bg-muted/40 text-left text-muted-foreground">
                    <tr>
                        <th class="px-4 py-3 font-medium">{{ t('Material') }}</th>
                        <th class="px-4 py-3 font-medium">{{ t('Qty') }}</th>
                        <th class="px-4 py-3 font-medium">{{ t('Unit cost') }}</th>
                        <th class="px-4 py-3 font-medium">{{ t('Total') }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="line in purchase.items" :key="line.id" class="border-t">
                        <td class="px-4 py-3">{{ line.material?.name }} {{ line.material?.unit?.unit_name ? `(${line.material.unit.unit_name})` : '' }}</td>
                        <td class="px-4 py-3 tabular-nums">{{ line.quantity }}</td>
                        <td class="px-4 py-3 tabular-nums">{{ money(line.unit_cost, purchase.currency || 'USD') }}</td>
                        <td class="px-4 py-3 tabular-nums">{{ money(line.total_cost, purchase.currency || 'USD') }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div id="pay" v-if="remaining_amount > 0" class="space-y-4 rounded-xl border bg-card p-5 shadow-sm">
            <div>
                <h3 class="text-sm font-semibold">{{ t('Pay supplier') }}</h3>
            </div>
            <form class="grid gap-4 sm:grid-cols-2" @submit.prevent="submitPayment">
                <div class="grid gap-2">
                    <Label>{{ t('Amount') }}</Label>
                    <Input v-model="payForm.amount" type="number" step="0.01" min="0.01" required :placeholder="String(remaining_amount)" />
                    <InputError :message="payForm.errors.amount" />
                </div>
                <div class="grid gap-2">
                    <Label>{{ t('Method') }}</Label>
                    <select v-model="payForm.payment_method" class="h-9 rounded-md border px-3 text-sm">
                        <option value="cash">{{ t('Cash') }}</option>
                        <option value="bank">{{ t('Bank') }}</option>
                        <option value="transfer">{{ t('Transfer') }}</option>
                    </select>
                </div>
                <div class="grid gap-2">
                    <Label>{{ t('Reference') }}</Label>
                    <Input v-model="payForm.reference_no" />
                </div>
                <div class="grid gap-2">
                    <Label>{{ t('Notes') }}</Label>
                    <Input v-model="payForm.notes" />
                </div>
                <div class="sm:col-span-2">
                    <Can permission="purchases.edit">
                        <Button type="submit" :disabled="payForm.processing">{{ t('Record payment') }}</Button>
                    </Can>
                </div>
            </form>
        </div>

        <div class="overflow-hidden rounded-xl border bg-card shadow-sm">
            <div class="border-b px-5 py-3">
                <h3 class="text-sm font-semibold">{{ t('Payments to supplier') }}</h3>
            </div>
            <table class="min-w-full text-sm">
                <thead class="bg-muted/40 text-left text-muted-foreground">
                    <tr>
                        <th class="px-4 py-3 font-medium">{{ t('No') }}</th>
                        <th class="px-4 py-3 font-medium">{{ t('Date') }}</th>
                        <th class="px-4 py-3 font-medium">{{ t('Amount') }}</th>
                        <th class="px-4 py-3 font-medium">{{ t('Method') }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="payment in purchase.payments" :key="payment.id" class="border-t">
                        <td class="px-4 py-3">
                            <Link :href="`/payments/${payment.id}`" class="hover:underline">{{ payment.payment_no }}</Link>
                        </td>
                        <td class="px-4 py-3">{{ formatDate(payment.paid_at) }}</td>
                        <td class="px-4 py-3 tabular-nums">{{ money(payment.amount, payment.currency || baseCurrency) }}</td>
                        <td class="px-4 py-3 capitalize">{{ payment.payment_method }}</td>
                    </tr>
                    <tr v-if="!purchase.payments?.length">
                        <td colspan="4" class="px-4 py-10 text-center text-muted-foreground">{{ t('No supplier payments yet') }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
