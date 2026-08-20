<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, Printer } from '@lucide/vue';
import DetailGrid from '@/components/DetailGrid.vue';
import RecordAttachments from '@/components/RecordAttachments.vue';
import Heading from '@/components/Heading.vue';
import PrintableVoucher from '@/components/PrintableVoucher.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import { useCalendar } from '@/composables/useCalendar';
import { useTranslations } from '@/composables/useTranslations';
import { money } from '@/lib/utils';
import { printPage } from '@/lib/voucher';

defineProps<{ item: Record<string, any> }>();
const { t } = useTranslations();
const calendar = useCalendar();
const formatDate = (value: string | null | undefined) => calendar.format(value) || '—';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Payments', href: '/payments' },
            { title: 'Details', href: '#' },
        ],
    },
});
</script>

<template>
    <Head :title="item.payment_no" />
    <div class="voucher-print-page flex h-full flex-1 flex-col gap-4 print:gap-0 print:p-0">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between print:hidden">
            <div class="space-y-3">
                <Button as-child variant="ghost" size="sm" class="-ml-2 w-fit">
                    <Link href="/payments"><ArrowLeft class="size-4" /> {{ t('Back') }}</Link>
                </Button>
                <Heading :title="item.payment_no" :description="item.supplier?.name || item.customer?.name || t('Payment details')" />
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <StatusBadge :status="item.status" />
                <Button variant="outline" @click="printPage">
                    <Printer class="size-4" /> {{ t('Print receipt') }}
                </Button>
            </div>
        </div>

        <div class="print:hidden">
            <div class="rounded-xl border bg-card p-5 shadow-sm sm:max-w-sm">
                <div class="text-sm text-muted-foreground">{{ t('Amount') }}</div>
                <div class="mt-1 text-3xl font-semibold tabular-nums text-emerald-700">
                    {{ money(item.amount, item.currency) }}
                </div>
            </div>
        </div>

        <div class="print:hidden">
            <DetailGrid
                :title="t('Payment details')"
                :items="[
                    { label: t('Date'), value: formatDate(item.paid_at) },
                    { label: t('Type'), value: item.type },
                    { label: t('Customer'), value: item.customer?.name },
                    { label: t('Supplier'), value: item.supplier?.name },
                    { label: t('Project'), value: item.project?.name },
                    { label: t('Sale'), value: item.sale?.sale_no },
                    { label: t('Purchase'), value: item.purchase?.purchase_no },
                    { label: t('Installment #'), value: item.installment?.installment_no },
                    { label: t('Payment method'), value: item.payment_method },
                    { label: t('Reference'), value: item.reference_no },
                    { label: t('Currency'), value: item.currency },
                    { label: t('Notes'), value: item.notes, span: true },
                ]"
            >
                <template v-if="item.sale_id">
                    <Button as-child variant="outline" size="sm">
                        <Link :href="`/sales/${item.sale_id}`">{{ t('View related sale') }}</Link>
                    </Button>
                </template>
                <template v-else-if="item.purchase_id">
                    <Button as-child variant="outline" size="sm">
                        <Link :href="`/purchases/${item.purchase_id}`">{{ t('View related purchase') }}</Link>
                    </Button>
                </template>
            </DetailGrid>
        </div>

        <RecordAttachments
            attachable="payments"
            :attachable-id="item.id"
            :attachments="item.attachments"
            boxed
        />

        <PrintableVoucher
                :kind="item.type === 'disbursement' ? 'payment' : 'receipt'"
                :title="item.type === 'disbursement' ? t('Supplier Payment Voucher') : t('Customer Payment Receipt')"
                :document-no="item.payment_no"
                :date="item.paid_at"
                :party-label="item.type === 'disbursement' ? t('Paid to') : t('Received from')"
                :party-name="item.supplier?.name || item.customer?.name"
                :amount="item.amount"
                :currency="item.currency"
                :payment-method="item.payment_method"
                :reference-no="item.reference_no"
                :project-name="item.project?.name"
                :description="item.notes"
                :status="item.status"
                :fields="[
                    { label: t('Sale'), value: item.sale?.sale_no },
                    { label: t('Purchase'), value: item.purchase?.purchase_no },
                    { label: t('Installment'), value: item.installment?.installment_no },
                    { label: t('Voucher type'), value: item.type },
                ]"
            />
    </div>
</template>
