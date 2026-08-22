<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, Pencil, Printer, Trash2 } from '@lucide/vue';
import { computed } from 'vue';
import Can from '@/components/Can.vue';
import DetailGrid from '@/components/DetailGrid.vue';
import Heading from '@/components/Heading.vue';
import PrintableVoucher from '@/components/PrintableVoucher.vue';
import RecordAttachments from '@/components/RecordAttachments.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import { useCalendar } from '@/composables/useCalendar';
import { useTranslations } from '@/composables/useTranslations';
import type { VoucherLine } from '@/lib/voucher';
import { printPage } from '@/lib/voucher';
import { money } from '@/lib/utils';

const props = defineProps<{ item: Record<string, any> }>();
const { t } = useTranslations();
const calendar = useCalendar();
const formatDate = (value: string | null | undefined) => calendar.format(value) || '—';

const periodLabel = computed(() => {
    const monthIndex = Number(props.item.month) - 1;
    const monthLabel = calendar.months()[monthIndex] ?? String(props.item.month);
    return `${monthLabel} ${calendar.digits(props.item.year)}`;
});

const documentNo = computed(() => {
    const employeeNo = props.item.employee?.employee_no || 'EMP';
    const month = String(props.item.month).padStart(2, '0');
    return `${employeeNo}-${props.item.year}-${month}`;
});

const salaryLines = computed<VoucherLine[]>(() => {
    const lines: VoucherLine[] = [
        {
            description: t('Basic Salary'),
            amount: props.item.basic_salary,
        },
    ];

    if (Number(props.item.bonus) > 0) {
        lines.push({
            description: t('Bonus'),
            amount: props.item.bonus,
        });
    }

    if (Number(props.item.deduction) > 0) {
        lines.push({
            description: t('Deduction'),
            amount: props.item.deduction,
        });
    }

    return lines;
});

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Salaries', href: '/salaries' },
            { title: 'Voucher', href: '#' },
        ],
    },
});
</script>

<template>
    <Head :title="documentNo" />
    <div class="voucher-print-page flex h-full flex-1 flex-col gap-4 print:gap-0 print:p-0">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between print:hidden">
            <div class="space-y-3">
                <Button as-child variant="ghost" size="sm" class="-ml-2 w-fit">
                    <Link href="/salaries"><ArrowLeft class="size-4" /> {{ t('Back') }}</Link>
                </Button>
                <Heading
                    :title="item.employee?.name || t('Salary record')"
                    :description="periodLabel"
                />
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <StatusBadge :status="item.status" />
                <Button variant="outline" @click="printPage">
                    <Printer class="size-4" /> {{ t('Print salary slip') }}
                </Button>
                <Can permission="salaries.edit">
                    <Button as-child>
                        <Link :href="`/salaries/${item.id}/edit`"><Pencil class="size-4" /> {{ t('Edit') }}</Link>
                    </Button>
                </Can>
                <Can permission="salaries.delete">
                    <Form :action="`/salaries/${item.id}`" method="delete" v-slot="{ processing }">
                        <Button type="submit" variant="destructive" :disabled="processing">
                            <Trash2 class="size-4" /> {{ processing ? t('Deleting…') : t('Delete') }}
                        </Button>
                    </Form>
                </Can>
            </div>
        </div>

        <div class="print:hidden">
            <div class="rounded-xl border bg-card p-5 shadow-sm sm:max-w-sm">
                <div class="text-sm text-muted-foreground">{{ t('Net salary') }}</div>
                <div class="mt-1 text-3xl font-semibold tabular-nums text-indigo-700">
                    {{ money(item.net_salary, item.currency) }}
                </div>
            </div>
        </div>

        <div class="print:hidden">
            <DetailGrid
                :title="t('Salary details')"
                :items="[
                    { label: t('Employee'), value: item.employee?.name },
                    { label: t('Period'), value: periodLabel },
                    { label: t('Payment Date'), value: formatDate(item.payment_date) },
                    { label: t('Basic Salary'), value: money(item.basic_salary, item.currency) },
                    { label: t('Bonus'), value: Number(item.bonus) > 0 ? money(item.bonus, item.currency) : '—' },
                    { label: t('Deduction'), value: Number(item.deduction) > 0 ? money(item.deduction, item.currency) : '—' },
                    { label: t('Designation'), value: item.employee?.designation },
                    { label: t('Department'), value: item.employee?.department },
                    { label: t('Salary payment method'), value: item.employee?.salary_payment_method },
                    { label: t('Notes'), value: item.notes, span: true },
                ]"
            />
        </div>

        <RecordAttachments
            attachable="salaries"
            :attachable-id="item.id"
            :attachments="item.attachments"
            boxed
        />

        <PrintableVoucher
            kind="salary"
            :title="t('Salary Payment Voucher')"
            :document-no="documentNo"
            :date="item.payment_date"
            :party-label="t('Paid to')"
            :party-name="item.employee?.name"
            :amount="item.net_salary"
            :currency="item.currency"
            :payment-method="item.employee?.salary_payment_method"
            :description="item.notes"
            :status="item.status"
            :fields="[
                { label: t('Period'), value: periodLabel },
                { label: t('Employee No'), value: item.employee?.employee_no },
                { label: t('Designation'), value: item.employee?.designation },
                { label: t('Department'), value: item.employee?.department },
            ]"
            :lines="salaryLines"
            :totals="[
                { label: t('Net salary'), value: money(item.net_salary, item.currency) },
            ]"
        />
    </div>
</template>
