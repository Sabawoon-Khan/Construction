<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, CalendarClock, FileText, Info, Pencil, Printer } from '@lucide/vue';
import { computed, onMounted, ref } from 'vue';
import Can from '@/components/Can.vue';
import DetailGrid from '@/components/DetailGrid.vue';
import FileUploadField, { type ExistingUploadFile } from '@/components/FileUploadField.vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { MisTabs } from '@/components/mis';
import PrintableVoucher from '@/components/PrintableVoucher.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useCalendar } from '@/composables/useCalendar';
import { useTranslations } from '@/composables/useTranslations';
import { money } from '@/lib/utils';
import { printPage } from '@/lib/voucher';

const props = defineProps<{
    sale: any;
    paid_amount: number;
    remaining_amount: number;
    total_price: number;
}>();
const { t } = useTranslations();
const calendar = useCalendar();
const formatDate = (value: string | null | undefined) => calendar.format(value) || '—';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Sales', href: '/sales' },
            { title: 'Details', href: '#' },
        ],
    },
});

const tab = ref(window.location.hash === '#receive-payment' ? 'schedule' : 'info');
const tabs = [
    { id: 'info', label: t('Sale info'), icon: Info },
    { id: 'schedule', label: t('Payment schedule'), icon: CalendarClock },
    { id: 'documents', label: t('Contract documents'), icon: FileText },
];

const existingDocuments = computed<ExistingUploadFile[]>(() =>
    (props.sale.attachments ?? []).map((file: any) => ({
        id: file.id,
        name: file.original_filename || file.title || t('Document'),
        size: file.file_size,
        mime: file.mime_type,
        url: file.download_url,
    })),
);

const firstUnpaidId = computed(() => {
    const row = (props.sale.installments ?? []).find((i: any) => installmentRemaining(i) > 0);
    return row?.id ?? props.sale.installments?.[0]?.id ?? null;
});

const payForm = useForm({
    amount: '',
    payment_method: 'cash',
    reference_no: '',
    notes: '',
    installment_id: null as number | null,
});

const statementLines = computed(() =>
    (props.sale.installments ?? []).map((i: any) => ({
        description: `${t('Installment')} #${i.installment_no}`,
        meta: `${t('Due')} ${formatDate(i.due_date)} · ${t(String(i.status || '').replaceAll('_', ' '))}`,
        amount: Number(i.amount ?? 0),
    })),
);

onMounted(() => {
    if (window.location.hash === '#receive-payment') {
        tab.value = 'schedule';
    }
});

function installmentRemaining(installment: any) {
    return Math.max(0, Number(installment.amount ?? 0) - Number(installment.paid_amount ?? 0));
}

function fillInstallment(installment: any) {
    payForm.installment_id = installment.id;
    payForm.amount = String(installmentRemaining(installment) || '');
    tab.value = 'schedule';
}

function submitPayment() {
    const installmentId = payForm.installment_id ?? firstUnpaidId.value;
    if (!installmentId) {
        return;
    }

    payForm.post(`/sales/${props.sale.id}/installments/${installmentId}/pay`, {
        preserveScroll: true,
        onSuccess: () => {
            payForm.reset('amount', 'reference_no', 'notes');
            payForm.installment_id = null;
        },
    });
}
</script>

<template>
    <Head :title="`${t('Sale')} ${sale.sale_no}`" />
    <div class="voucher-print-page flex h-full flex-1 flex-col gap-6 p-4 md:p-6 print:gap-0 print:p-0">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between print:hidden">
            <div class="space-y-3">
                <Button as-child variant="ghost" size="sm" class="-ml-2 w-fit">
                    <Link href="/sales"><ArrowLeft class="size-4" /> {{ t('Back') }}</Link>
                </Button>
                <Heading
                    :title="sale.sale_no"
                    :description="`${sale.customer?.name || '—'} · ${sale.project?.name || '—'} · ${t('Unit')} ${sale.unit?.unit_no || '—'}`"
                />
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <StatusBadge :status="sale.status" />
                <Button variant="outline" @click="printPage">
                    <Printer class="size-4" /> {{ t('Print statement') }}
                </Button>
                <Can permission="sales.edit">
                    <Button as-child variant="outline">
                        <Link :href="`/sales/${sale.id}/edit`"><Pencil class="size-4" /> {{ t('Edit') }}</Link>
                    </Button>
                </Can>
            </div>
        </div>

        <MisTabs v-model="tab" :tabs="tabs" class="print:hidden" />

        <div v-show="tab === 'info'" class="grid gap-4 md:grid-cols-3 print:hidden">
            <div class="rounded-xl border border-sidebar-border/70 bg-card p-4 shadow-sm">
                <div class="text-sm text-muted-foreground">{{ t('Total due') }}</div>
                <div class="mt-1 text-2xl font-semibold tabular-nums">{{ money(total_price, sale.currency || 'USD') }}</div>
            </div>
            <div class="rounded-xl border border-sidebar-border/70 bg-card p-4 shadow-sm">
                <div class="text-sm text-muted-foreground">{{ t('Paid') }}</div>
                <div class="mt-1 text-2xl font-semibold tabular-nums text-emerald-700">{{ money(paid_amount) }}</div>
            </div>
            <div class="rounded-xl border border-sidebar-border/70 bg-card p-4 shadow-sm">
                <div class="text-sm text-muted-foreground">{{ t('Remaining') }}</div>
                <div class="mt-1 text-2xl font-semibold tabular-nums text-amber-700">{{ money(remaining_amount) }}</div>
            </div>
        </div>

        <div v-show="tab === 'info'" class="print:hidden">
            <DetailGrid
                :title="t('Contract details')"
                :items="[
                    { label: t('Customer'), value: sale.customer?.name },
                    { label: t('Project'), value: sale.project?.name },
                    { label: t('Unit'), value: sale.unit?.unit_no },
                    { label: t('Sale price'), value: money(sale.unit?.list_price, sale.currency || 'USD') },
                    { label: t('Discount'), value: money(sale.discount, sale.currency || 'USD') },
                    { label: t('Currency'), value: sale.currency || 'USD' },
                    { label: t('Contract date'), value: formatDate(sale.contract_date) },
                    { label: t('Terms'), value: sale.terms, span: true },
                    { label: t('Notes'), value: sale.notes, span: true },
                ]"
            />
        </div>

        <div v-show="tab === 'schedule'" class="space-y-4 print:hidden">
        <div id="receive-payment" v-if="remaining_amount > 0" class="space-y-4 rounded-xl border border-sidebar-border/70 bg-card p-5 shadow-sm">
            <div>
                <h3 class="text-sm font-semibold">{{ t('Receive payment') }}</h3>
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
                    <Input v-model="payForm.reference_no" :placeholder="t('Receipt / slip no')" />
                </div>
                <div class="grid gap-2 sm:col-span-2">
                    <Label>{{ t('Notes') }}</Label>
                    <Input v-model="payForm.notes" :placeholder="t('Optional')" />
                </div>
                <div class="sm:col-span-2">
                    <Button type="submit" :disabled="payForm.processing">{{ t('Record receipt') }}</Button>
                </div>
            </form>
        </div>

        <div class="overflow-hidden rounded-xl border border-sidebar-border/70 bg-card shadow-sm">
            <div class="border-b px-5 py-3">
                <h3 class="text-sm font-semibold">{{ t('Installments') }}</h3>
            </div>
            <table class="min-w-full text-sm">
                <thead class="bg-muted/40 text-left text-muted-foreground">
                    <tr>
                        <th class="px-4 py-3 font-medium">#</th>
                        <th class="px-4 py-3 font-medium">{{ t('Due') }}</th>
                        <th class="px-4 py-3 font-medium">{{ t('Amount') }}</th>
                        <th class="px-4 py-3 font-medium">{{ t('Paid') }}</th>
                        <th class="px-4 py-3 font-medium">{{ t('Status') }}</th>
                        <th class="px-4 py-3 font-medium text-right">{{ t('Action') }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="i in sale.installments" :key="i.id" class="border-t hover:bg-muted/30">
                        <td class="px-4 py-3">{{ i.installment_no }}</td>
                        <td class="px-4 py-3">{{ formatDate(i.due_date) }}</td>
                        <td class="px-4 py-3 tabular-nums">{{ money(i.amount) }}</td>
                        <td class="px-4 py-3 tabular-nums">{{ money(i.paid_amount) }}</td>
                        <td class="px-4 py-3"><StatusBadge :status="i.status" /></td>
                        <td class="px-4 py-3 text-right">
                            <Button
                                v-if="installmentRemaining(i) > 0"
                                size="sm"
                                variant="outline"
                                @click="fillInstallment(i)"
                            >
                                {{ t('Receive') }} {{ money(installmentRemaining(i), sale.currency || 'USD') }}
                            </Button>
                        </td>
                    </tr>
                    <tr v-if="!sale.installments?.length">
                        <td colspan="6" class="px-4 py-10 text-center text-muted-foreground">{{ t('No installments') }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="overflow-hidden rounded-xl border border-sidebar-border/70 bg-card shadow-sm">
            <div class="border-b px-5 py-3">
                <h3 class="text-sm font-semibold">{{ t('Receipts') }}</h3>
            </div>
            <table class="min-w-full text-sm">
                <thead class="bg-muted/40 text-left text-muted-foreground">
                    <tr>
                        <th class="px-4 py-3 font-medium">{{ t('No') }}</th>
                        <th class="px-4 py-3 font-medium">{{ t('Date') }}</th>
                        <th class="px-4 py-3 font-medium">{{ t('Amount') }}</th>
                        <th class="px-4 py-3 font-medium text-right">{{ t('Action') }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="p in sale.payments" :key="p.id" class="border-t hover:bg-muted/30">
                        <td class="px-4 py-3 font-medium">{{ p.payment_no }}</td>
                        <td class="px-4 py-3">{{ formatDate(p.paid_at) }}</td>
                        <td class="px-4 py-3 tabular-nums">{{ money(p.amount, sale.currency || 'USD') }}</td>
                        <td class="px-4 py-3 text-right">
                            <Button as-child size="sm" variant="outline">
                                <Link :href="`/payments/${p.id}`"><Printer class="size-4" /> {{ t('Print') }}</Link>
                            </Button>
                        </td>
                    </tr>
                    <tr v-if="!sale.payments?.length">
                        <td colspan="4" class="px-4 py-10 text-center text-muted-foreground">{{ t('No receipts yet') }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
        </div>

        <div v-show="tab === 'documents'" class="space-y-3 rounded-xl border border-sidebar-border/70 bg-card p-5 shadow-sm print:hidden">
            <h3 class="text-sm font-semibold">{{ t('Contract documents') }}</h3>
            <FileUploadField
                mode="chunked"
                destination="attachment"
                attachable="sales"
                :attachable-id="sale.id"
                multiple
                :max-files="10"
                accept="application/pdf,image/jpeg,image/png,image/webp,.doc,.docx"
                :existing="existingDocuments"
            />
        </div>

        <PrintableVoucher
            kind="statement"
            :title="t('Customer Sale Statement')"
            :document-no="sale.sale_no"
            :date="sale.contract_date"
            :party-label="t('Customer')"
            :party-name="sale.customer?.name"
            :amount="remaining_amount"
            :currency="sale.currency || 'USD'"
            :project-name="sale.project?.name"
            :description="sale.notes || sale.terms"
            :status="sale.status"
            :fields="[
                { label: t('Unit'), value: sale.unit?.unit_no },
                { label: t('Sale price'), value: money(sale.unit?.list_price, sale.currency || 'USD') },
                { label: t('Discount'), value: money(sale.discount, sale.currency || 'USD') },
            ]"
            :lines="statementLines"
            :totals="[
                { label: t('Total due'), value: money(total_price, sale.currency || 'USD') },
                { label: t('Paid to date'), value: money(paid_amount, sale.currency || 'USD') },
                { label: t('Balance due'), value: money(remaining_amount, sale.currency || 'USD') },
            ]"
        />
    </div>
</template>
