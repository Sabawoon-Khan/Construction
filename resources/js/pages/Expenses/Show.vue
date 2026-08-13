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

const backHref = computed(() =>
    props.item.project_id ? `/projects/${props.item.project_id}` : '/expenses',
);

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Expenses', href: '/expenses' },
            { title: 'Voucher', href: '#' },
        ],
    },
});
</script>

<template>
    <Head :title="item.expense_no" />
    <div class="voucher-print-page flex h-full flex-1 flex-col gap-6 p-4 md:p-6 print:gap-0 print:p-0">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between print:hidden">
            <div class="space-y-3">
                <Button as-child variant="ghost" size="sm" class="-ml-2 w-fit">
                    <Link :href="backHref"><ArrowLeft class="size-4" /> Back</Link>
                </Button>
                <Heading :title="item.expense_no" :description="item.project?.name || 'General expense'" />
            </div>
            <Button variant="outline" @click="printPage">
                <Printer class="size-4" /> Print voucher
            </Button>
        </div>

        <div class="print:hidden">
            <div class="rounded-xl border bg-card p-5 shadow-sm sm:max-w-sm">
                <div class="text-sm text-muted-foreground">Total cost</div>
                <div class="mt-1 text-3xl font-semibold tabular-nums text-rose-700">
                    {{ money(item.amount, item.currency || 'USD') }}
                </div>
            </div>
        </div>

        <div class="print:hidden">
            <DetailGrid
                title="Expense details"
                :items="[
                    { label: 'Date', value: item.expense_date },
                    { label: 'Project', value: item.project?.name || 'Company-wide' },
                    { label: 'Description', value: item.description, span: true },
                ]"
            />
        </div>

        <PrintableVoucher
            kind="payment"
            title="Expense Payment Voucher"
            :document-no="item.expense_no"
            :date="item.expense_date"
            party-label="Project"
            :party-name="item.project?.name || 'Company-wide'"
            :amount="item.amount"
            :currency="item.currency || 'USD'"
            :payment-method="item.payment_method"
            :project-name="item.project?.name"
            :description="item.description"
            :status="item.status"
        />
    </div>
</template>
