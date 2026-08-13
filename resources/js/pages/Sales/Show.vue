<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowLeft, Pencil, Printer } from '@lucide/vue';
import { computed } from 'vue';
import DetailGrid from '@/components/DetailGrid.vue';
import Heading from '@/components/Heading.vue';
import PrintableVoucher from '@/components/PrintableVoucher.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import { money } from '@/lib/utils';
import { printPage } from '@/lib/voucher';

const props = defineProps<{ sale: any; paid_amount: number; remaining_amount: number; total_price: number }>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Sales', href: '/sales' },
            { title: 'Details', href: '#' },
        ],
    },
});

const statementLines = computed(() =>
    (props.sale.installments ?? []).map((i: any) => ({
        description: `Installment #${i.installment_no}`,
        meta: `Due ${i.due_date || '—'} · ${String(i.status || '').replaceAll('_', ' ')}`,
        amount: Number(i.amount ?? 0),
    })),
);

function installmentRemaining(installment: any) {
    return Math.max(0, Number(installment.amount ?? 0) - Number(installment.paid_amount ?? 0));
}

function payInstallment(installment: any) {
    const remaining = installmentRemaining(installment);
    if (remaining <= 0) {
        return;
    }

    if (!confirm(`Record payment of ${money(remaining, props.sale.currency || 'USD')} for installment #${installment.installment_no}?`)) {
        return;
    }

    router.post(`/sales/${props.sale.id}/installments/${installment.id}/pay`, { amount: remaining });
}

function payAllRemaining() {
    const remaining = props.remaining_amount;
    if (remaining <= 0) {
        return;
    }

    const firstUnpaid = props.sale.installments?.find((i: any) => installmentRemaining(i) > 0);
    if (!firstUnpaid) {
        return;
    }

    if (!confirm(`Record full payment of ${money(remaining, props.sale.currency || 'USD')}?`)) {
        return;
    }

    router.post(`/sales/${props.sale.id}/installments/${firstUnpaid.id}/pay`, { amount: remaining });
}
</script>

<template>
    <Head :title="`Sale ${sale.sale_no}`" />
    <div class="voucher-print-page flex h-full flex-1 flex-col gap-6 p-4 md:p-6 print:gap-0 print:p-0">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between print:hidden">
            <div class="space-y-3">
                <Button as-child variant="ghost" size="sm" class="-ml-2 w-fit">
                    <Link href="/sales"><ArrowLeft class="size-4" /> Back</Link>
                </Button>
                <Heading
                    :title="sale.sale_no"
                    :description="`${sale.customer?.name || '—'} · ${sale.project?.name || '—'} · Unit ${sale.unit?.unit_no || '—'}`"
                />
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <StatusBadge :status="sale.status" />
                <Button variant="outline" @click="printPage">
                    <Printer class="size-4" /> Print statement
                </Button>
                <Button as-child variant="outline">
                    <Link :href="`/sales/${sale.id}/edit`"><Pencil class="size-4" /> Edit</Link>
                </Button>
                <Button v-if="remaining_amount > 0 && sale.installments?.length" @click="payAllRemaining">
                    Pay all {{ money(remaining_amount, sale.currency || 'USD') }}
                </Button>
            </div>
        </div>

        <div class="grid gap-4 md:grid-cols-3 print:hidden">
            <div class="rounded-xl border border-sidebar-border/70 bg-card p-4 shadow-sm">
                <div class="text-sm text-muted-foreground">Total due</div>
                <div class="mt-1 text-2xl font-semibold tabular-nums">{{ money(total_price, sale.currency || 'USD') }}</div>
            </div>
            <div class="rounded-xl border border-sidebar-border/70 bg-card p-4 shadow-sm">
                <div class="text-sm text-muted-foreground">Paid</div>
                <div class="mt-1 text-2xl font-semibold tabular-nums text-emerald-700">{{ money(paid_amount) }}</div>
            </div>
            <div class="rounded-xl border border-sidebar-border/70 bg-card p-4 shadow-sm">
                <div class="text-sm text-muted-foreground">Remaining</div>
                <div class="mt-1 text-2xl font-semibold tabular-nums text-amber-700">{{ money(remaining_amount) }}</div>
            </div>
        </div>

        <div class="print:hidden">
            <DetailGrid
                title="Contract details"
                :items="[
                    { label: 'Customer', value: sale.customer?.name },
                    { label: 'Project', value: sale.project?.name },
                    { label: 'Unit', value: sale.unit?.unit_no },
                    { label: 'Sale price', value: money(sale.unit?.list_price, sale.currency || 'USD') },
                    { label: 'Discount', value: money(sale.discount, sale.currency || 'USD') },
                    { label: 'Currency', value: sale.currency || 'USD' },
                    { label: 'Contract date', value: sale.contract_date },
                    { label: 'Terms', value: sale.terms, span: true },
                    { label: 'Notes', value: sale.notes, span: true },
                ]"
            />
        </div>

        <div class="overflow-hidden rounded-xl border border-sidebar-border/70 bg-card shadow-sm print:hidden">
            <div class="border-b px-5 py-3">
                <h3 class="text-sm font-semibold">Installments</h3>
            </div>
            <table class="min-w-full text-sm">
                <thead class="bg-muted/40 text-left text-muted-foreground">
                    <tr>
                        <th class="px-4 py-3 font-medium">#</th>
                        <th class="px-4 py-3 font-medium">Due</th>
                        <th class="px-4 py-3 font-medium">Amount</th>
                        <th class="px-4 py-3 font-medium">Paid</th>
                        <th class="px-4 py-3 font-medium">Status</th>
                        <th class="px-4 py-3 font-medium text-right">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="i in sale.installments" :key="i.id" class="border-t hover:bg-muted/30">
                        <td class="px-4 py-3">{{ i.installment_no }}</td>
                        <td class="px-4 py-3">{{ i.due_date }}</td>
                        <td class="px-4 py-3 tabular-nums">{{ money(i.amount) }}</td>
                        <td class="px-4 py-3 tabular-nums">{{ money(i.paid_amount) }}</td>
                        <td class="px-4 py-3"><StatusBadge :status="i.status" /></td>
                        <td class="px-4 py-3 text-right">
                            <Button
                                v-if="installmentRemaining(i) > 0"
                                size="sm"
                                variant="outline"
                                @click="payInstallment(i)"
                            >
                                Pay {{ money(installmentRemaining(i), sale.currency || 'USD') }}
                            </Button>
                        </td>
                    </tr>
                    <tr v-if="!sale.installments?.length">
                        <td colspan="6" class="px-4 py-10 text-center text-muted-foreground">No installments</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <PrintableVoucher
            kind="statement"
            title="Customer Sale Statement"
            :document-no="sale.sale_no"
            :date="sale.contract_date"
            party-label="Customer"
            :party-name="sale.customer?.name"
            :amount="remaining_amount"
            :currency="sale.currency || 'USD'"
            :project-name="sale.project?.name"
            :description="sale.notes || sale.terms"
            :status="sale.status"
            :fields="[
                { label: 'Unit', value: sale.unit?.unit_no },
                { label: 'Sale price', value: money(sale.unit?.list_price, sale.currency || 'USD') },
                { label: 'Discount', value: money(sale.discount, sale.currency || 'USD') },
            ]"
            :lines="statementLines"
            :totals="[
                { label: 'Total due', value: money(total_price, sale.currency || 'USD') },
                { label: 'Paid to date', value: money(paid_amount, sale.currency || 'USD') },
                { label: 'Balance due', value: money(remaining_amount, sale.currency || 'USD') },
            ]"
        />
    </div>
</template>
