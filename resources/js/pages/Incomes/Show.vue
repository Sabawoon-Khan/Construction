<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, Pencil, Printer } from '@lucide/vue';
import DetailGrid from '@/components/DetailGrid.vue';
import Heading from '@/components/Heading.vue';
import PrintableVoucher from '@/components/PrintableVoucher.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import { money } from '@/lib/utils';
import { printPage } from '@/lib/voucher';

defineProps<{ item: Record<string, any> }>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Incomes', href: '/incomes' },
            { title: 'Details', href: '#' },
        ],
    },
});
</script>

<template>
    <Head :title="item.income_no" />
    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6 print:gap-0 print:p-0">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between print:hidden">
            <div class="space-y-3">
                <Button as-child variant="ghost" size="sm" class="-ml-2 w-fit">
                    <Link href="/incomes"><ArrowLeft class="size-4" /> Back</Link>
                </Button>
                <Heading :title="item.income_no" :description="item.category?.name || 'Income details'" />
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <StatusBadge :status="item.status" />
                <Button variant="outline" @click="printPage">
                    <Printer class="size-4" /> Print receipt
                </Button>
                <Button as-child>
                    <Link :href="`/incomes/${item.id}/edit`"><Pencil class="size-4" /> Edit</Link>
                </Button>
            </div>
        </div>

        <div class="print:hidden">
            <div class="rounded-xl border bg-card p-5 shadow-sm sm:max-w-sm">
                <div class="text-sm text-muted-foreground">Amount</div>
                <div class="mt-1 text-3xl font-semibold tabular-nums text-emerald-700">
                    {{ money(item.amount, item.currency || 'USD') }}
                </div>
            </div>
        </div>

        <div class="print:hidden">
            <DetailGrid
                title="Income details"
                :items="[
                    { label: 'Date', value: item.income_date },
                    { label: 'Category', value: item.category?.name },
                    { label: 'Project', value: item.project?.name },
                    { label: 'Received from', value: item.received_from || item.customer?.name },
                    { label: 'Customer', value: item.customer?.name },
                    { label: 'Bank account', value: item.bank_account?.name },
                    { label: 'Payment method', value: item.payment_method },
                    { label: 'Reference', value: item.reference_no },
                    { label: 'Currency', value: item.currency },
                    { label: 'Description', value: item.description, span: true },
                ]"
            />
        </div>

        <div class="space-y-3">
            <div class="flex items-center justify-between print:hidden">
                <h3 class="text-sm font-semibold tracking-tight">Income receipt voucher preview</h3>
                <Button size="sm" @click="printPage"><Printer class="size-4" /> Print</Button>
            </div>
            <PrintableVoucher
                kind="receipt"
                title="Income Receipt Voucher"
                :document-no="item.income_no"
                :date="item.income_date"
                party-label="Received from"
                :party-name="item.received_from || item.customer?.name"
                :amount="item.amount"
                :currency="item.currency || 'USD'"
                :payment-method="item.payment_method"
                :reference-no="item.reference_no"
                :project-name="item.project?.name"
                :account-name="item.bank_account?.name"
                :description="item.description"
                :status="item.status"
                :fields="[
                    { label: 'Category', value: item.category?.name },
                    { label: 'Customer', value: item.customer?.name },
                ]"
            />
        </div>
    </div>
</template>
