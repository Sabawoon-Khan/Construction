<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Pencil, Printer, Trash2 } from '@lucide/vue';
import { computed, ref } from 'vue';
import Can from '@/components/Can.vue';
import ConfirmActionDialog from '@/components/ConfirmActionDialog.vue';
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
import type { VoucherLine } from '@/lib/voucher';
import { printPage } from '@/lib/voucher';
import { money } from '@/lib/utils';

const props = defineProps<{ item: Record<string, any> }>();
const { t } = useTranslations();
const calendar = useCalendar();
const formatDate = (value: string | null | undefined) => calendar.format(value) || '—';

const deleteOpen = ref(false);
const deleting = ref(false);

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

function confirmDelete() {
    deleting.value = true;
    router.delete(`/salaries/${props.item.id}`, {
        onFinish: () => {
            deleting.value = false;
            deleteOpen.value = false;
        },
    });
}

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
    <V2ListPage>
        <div class="voucher-print-page flex flex-col gap-4 print:gap-0">
            <V2DetailHero class="print:hidden" image="/images/dashboard-v2-building.png">
                <template #eyebrow>{{ t('Salaries') }}</template>
                <template #title>{{ item.employee?.name || t('Salary record') }}</template>
                <template #description>{{ periodLabel }}</template>
                <template #actions>
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
                        <Button type="button" variant="destructive" @click="deleteOpen = true">
                            <Trash2 class="size-4" /> {{ t('Delete') }}
                        </Button>
                    </Can>
                </template>
            </V2DetailHero>

            <div class="print:hidden">
                <V2AmountCard :label="t('Net salary')" tone="warm">
                    {{ money(item.net_salary, item.currency) }}
                </V2AmountCard>
            </div>

            <V2Panel class="print:hidden" :title="t('Salary details')">
                <MisDetailGrid
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
                        { label: t('Notes'), value: item.notes, span: 3 },
                    ]"
                />
            </V2Panel>

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

            <ConfirmActionDialog
                v-model:open="deleteOpen"
                :title="t('Delete salary?')"
                :description="t('This cannot be undone.')"
                :confirm-label="t('Delete')"
                :processing="deleting"
                @confirm="confirmDelete"
            />
        </div>
    </V2ListPage>
</template>
