<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, Printer } from '@lucide/vue';
import { computed } from 'vue';
import DetailGrid from '@/components/DetailGrid.vue';
import Heading from '@/components/Heading.vue';
import PrintableVoucher from '@/components/PrintableVoucher.vue';
import { Button } from '@/components/ui/button';
import { money } from '@/lib/utils';
import { printPage } from '@/lib/voucher';

const props = defineProps<{ item: Record<string, any> }>();

const isPurchase = computed(() => props.item.type === 'purchase');

const documentNo = computed(() => {
    if (props.item.expense?.expense_no) {
        return props.item.expense.expense_no;
    }

    const prefix = isPurchase.value ? 'PUR' : 'ISS';

    return `${prefix}-${String(props.item.id).padStart(6, '0')}`;
});

const title = computed(() => (isPurchase.value ? 'Material Purchase Voucher' : 'Material Issue Voucher'));

const partyLabel = computed(() => (isPurchase.value ? 'Supplier' : 'Project'));

const partyName = computed(() => {
    if (isPurchase.value) {
        return props.item.supplier?.name || '—';
    }

    return props.item.project?.name || '—';
});

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Purchases', href: '/purchases' },
            { title: 'Voucher', href: '#' },
        ],
    },
});
</script>

<template>
    <Head :title="documentNo" />
    <div class="voucher-print-page flex h-full flex-1 flex-col gap-6 p-4 md:p-6 print:gap-0 print:p-0">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between print:hidden">
            <div class="space-y-3">
                <Button as-child variant="ghost" size="sm" class="-ml-2 w-fit">
                    <Link href="/purchases"><ArrowLeft class="size-4" /> Back</Link>
                </Button>
                <Heading :title="documentNo" :description="title" />
            </div>
            <Button variant="outline" @click="printPage">
                <Printer class="size-4" /> Print voucher
            </Button>
        </div>

        <div class="print:hidden">
            <div class="rounded-xl border bg-card p-5 shadow-sm sm:max-w-sm">
                <div class="text-sm text-muted-foreground">{{ isPurchase ? 'Total paid' : 'Stock value' }}</div>
                <div class="mt-1 text-3xl font-semibold tabular-nums" :class="isPurchase ? 'text-rose-700' : 'text-slate-900'">
                    {{ money(item.total_cost, item.currency || 'USD') }}
                </div>
            </div>
        </div>

        <div class="print:hidden">
            <DetailGrid
                title="Movement details"
                :items="[
                    { label: 'Type', value: isPurchase ? 'Purchase' : 'Issue' },
                    { label: 'Date', value: item.transaction_date?.slice?.(0, 10) ?? item.transaction_date },
                    { label: 'Material', value: item.material?.name },
                    { label: 'Code', value: item.material?.code },
                    { label: 'Quantity', value: `${item.quantity} ${item.material?.unit || ''}`.trim() },
                    { label: 'Unit cost', value: money(item.unit_cost, item.currency || 'USD') },
                    { label: 'Supplier', value: item.supplier?.name },
                    { label: 'Project', value: item.project?.name },
                    { label: 'Linked expense', value: item.expense?.expense_no },
                    { label: 'Notes', value: item.notes, span: true },
                ]"
            />
        </div>

        <PrintableVoucher
            :kind="isPurchase ? 'payment' : 'statement'"
            :title="title"
            :document-no="documentNo"
            :date="item.transaction_date"
            :party-label="partyLabel"
            :party-name="partyName"
            :amount="item.total_cost"
            :currency="item.currency || 'USD'"
            :payment-method="item.expense?.payment_method"
            :reference-no="item.expense?.expense_no || item.reference_no"
            :project-name="item.project?.name"
            :description="item.notes"
            :status="item.expense?.status"
            :lines="[
                {
                    description: item.material?.name || 'Material',
                    meta: `${item.quantity} ${item.material?.unit || ''} × ${money(item.unit_cost, item.currency || 'USD')}`.trim(),
                    amount: item.total_cost,
                },
            ]"
            :fields="[
                { label: 'Material code', value: item.material?.code },
                { label: 'Movement', value: isPurchase ? 'Stock in' : 'Stock out' },
            ]"
        />
    </div>
</template>
