<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Printer } from '@lucide/vue';
import PrintableVoucher from '@/components/PrintableVoucher.vue';
import RecordAttachments from '@/components/RecordAttachments.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { MisDetailGrid } from '@/components/mis';
import { Button } from '@/components/ui/button';
import {
    V2AmountCard,
    V2DetailHero,
    V2ListPage,
    V2Panel,
} from '@/components/v2';
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
    <V2ListPage>
        <div class="voucher-print-page flex flex-col gap-4 print:gap-0">
            <V2DetailHero class="print:hidden" image="/images/dashboard-v2-building.png">
                <template #eyebrow>{{ t('Payments') }}</template>
                <template #title>{{ item.payment_no }}</template>
                <template #description>{{ item.supplier?.name || item.customer?.name || t('Payment details') }}</template>
                <template #actions>
                    <StatusBadge :status="item.status" />
                    <Button variant="outline" @click="printPage">
                        <Printer class="size-4" /> {{ t('Print receipt') }}
                    </Button>
                </template>
            </V2DetailHero>

            <div class="print:hidden">
                <V2AmountCard :label="t('Amount')" tone="rose">
                    {{ money(item.amount, item.currency) }}
                </V2AmountCard>
            </div>

            <V2Panel class="print:hidden" :title="t('Payment details')">
                <template v-if="item.sale_id || item.purchase_id" #actions>
                    <Button v-if="item.sale_id" as-child variant="outline" size="sm">
                        <Link :href="`/sales/${item.sale_id}`">{{ t('View related sale') }}</Link>
                    </Button>
                    <Button v-else-if="item.purchase_id" as-child variant="outline" size="sm">
                        <Link :href="`/purchases/${item.purchase_id}`">{{ t('View related purchase') }}</Link>
                    </Button>
                </template>
                <MisDetailGrid
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
                        { label: t('Notes'), value: item.notes, span: 3 },
                    ]"
                />
            </V2Panel>

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
    </V2ListPage>
</template>
