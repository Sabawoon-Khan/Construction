<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, Printer } from '@lucide/vue';
import DetailGrid from '@/components/DetailGrid.vue';
import Heading from '@/components/Heading.vue';
import PrintableVoucher from '@/components/PrintableVoucher.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import { useTranslations } from '@/composables/useTranslations';
import { money } from '@/lib/utils';
import { printPage } from '@/lib/voucher';

defineProps<{ item: Record<string, any> }>();
const { t } = useTranslations();

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
    <div class="voucher-print-page flex h-full flex-1 flex-col gap-6 p-4 md:p-6 print:gap-0 print:p-0">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between print:hidden">
            <div class="space-y-3">
                <Button as-child variant="ghost" size="sm" class="-ml-2 w-fit">
                    <Link href="/payments"><ArrowLeft class="size-4" /> {{ t('Back') }}</Link>
                </Button>
                <Heading :title="item.payment_no" :description="item.customer?.name || t('Payment details')" />
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
                    {{ money(item.amount, item.currency || 'USD') }}
                </div>
            </div>
        </div>

        <div class="print:hidden">
            <DetailGrid
                :title="t('Payment details')"
                :items="[
                    { label: t('Date'), value: item.paid_at },
                    { label: t('Type'), value: item.type },
                    { label: t('Customer'), value: item.customer?.name },
                    { label: t('Project'), value: item.project?.name },
                    { label: t('Sale'), value: item.sale?.sale_no },
                    { label: t('Installment #'), value: item.installment?.installment_no },
                    { label: t('Bank account'), value: item.bank_account?.name },
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
            </DetailGrid>
        </div>

        <PrintableVoucher
                kind="receipt"
                :title="t('Customer Payment Receipt')"
                :document-no="item.payment_no"
                :date="item.paid_at"
                :party-label="t('Received from')"
                :party-name="item.customer?.name"
                :amount="item.amount"
                :currency="item.currency || 'USD'"
                :payment-method="item.payment_method"
                :reference-no="item.reference_no"
                :project-name="item.project?.name"
                :account-name="item.bank_account?.name"
                :description="item.notes"
                :status="item.status"
                :fields="[
                    { label: t('Sale'), value: item.sale?.sale_no },
                    { label: t('Installment'), value: item.installment?.installment_no },
                    { label: t('Voucher type'), value: item.type },
                ]"
            />
    </div>
</template>
