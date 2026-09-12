<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Pencil, Printer, Trash2 } from '@lucide/vue';
import { computed, ref } from 'vue';
import Can from '@/components/Can.vue';
import ConfirmActionDialog from '@/components/ConfirmActionDialog.vue';
import PrintableVoucher from '@/components/PrintableVoucher.vue';
import RecordAttachments from '@/components/RecordAttachments.vue';
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
import {
    consumePendingTrashUndo,
    expectTrashUndo,
} from '@/lib/trashUndoToast';

const props = defineProps<{ item: Record<string, any> }>();
const { t } = useTranslations();
const calendar = useCalendar();
const formatDate = (value: string | null | undefined) => calendar.format(value) || '—';

const deleteOpen = ref(false);
const deleting = ref(false);

const canEditExpense = computed(() => !props.item.purchase_id && !props.item.project_id);

function confirmDelete() {
    deleting.value = true;
    expectTrashUndo({ type: 'expenses', id: props.item.id });
    router.delete(`/expenses/${props.item.id}`, {
        onError: () => consumePendingTrashUndo(),
        onCancel: () => consumePendingTrashUndo(),
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
            { title: 'Expenses', href: '/expenses' },
            { title: 'Voucher', href: '#' },
        ],
    },
});
</script>

<template>
    <Head :title="item.expense_no" />
    <V2ListPage>
        <div class="voucher-print-page flex flex-col gap-4 print:gap-0">
            <V2DetailHero class="print:hidden" image="/images/dashboard-v2-building.png">
                <template #eyebrow>{{ t('Expenses') }}</template>
                <template #title>{{ item.expense_no }}</template>
                <template #description>{{ item.project?.name || t('General expense') }}</template>
                <template #actions>
                    <Button variant="outline" @click="printPage">
                        <Printer class="size-4" /> {{ t('Print voucher') }}
                    </Button>
                    <Can permission="expenses.edit">
                        <Button v-if="canEditExpense" as-child>
                            <Link :href="`/expenses/${item.id}/edit`"><Pencil class="size-4" /> {{ t('Edit') }}</Link>
                        </Button>
                    </Can>
                    <Can permission="expenses.delete">
                        <Button
                            v-if="!item.purchase_id"
                            type="button"
                            variant="destructive"
                            @click="deleteOpen = true"
                        >
                            <Trash2 class="size-4" /> {{ t('Move to trash') }}
                        </Button>
                    </Can>
                </template>
            </V2DetailHero>

            <div class="print:hidden">
                <V2AmountCard :label="t('Total cost')" tone="rose">
                    {{ money(item.amount, item.currency) }}
                </V2AmountCard>
            </div>

            <V2Panel class="print:hidden" :title="t('Expense details')">
                <template v-if="item.purchase_id" #actions>
                    <Button as-child variant="outline" size="sm">
                        <Link :href="`/purchases/${item.purchase_id}`">{{ t('View purchase') }}</Link>
                    </Button>
                </template>
                <MisDetailGrid
                    :items="[
                        { label: t('Date'), value: formatDate(item.expense_date) },
                        { label: t('Paid to'), value: item.paid_to || '—' },
                        { label: t('Project'), value: item.project?.name || t('Company-wide') },
                        { label: t('Purchase'), value: item.purchase?.purchase_no || '—' },
                        { label: t('Unit'), value: item.expense_unit?.unit_name || '—' },
                        { label: t('Item amount'), value: item.item_amount ?? '—' },
                        { label: t('Per price'), value: item.per_price != null ? money(item.per_price, item.currency) : '—' },
                        { label: t('Reference'), value: item.reference_no || '—' },
                        { label: t('Description'), value: item.description, span: 3 },
                    ]"
                />
            </V2Panel>

            <RecordAttachments
                boxed
                attachable="expenses"
                :attachable-id="item.id"
                :attachments="item.attachments"
                :label="t('Receipt files')"
                accept="application/pdf,image/jpeg,image/png,image/webp"
            />

            <PrintableVoucher
                kind="payment"
                :title="t('Expense Payment Voucher')"
                :document-no="item.expense_no"
                :date="item.expense_date?.slice?.(0, 10) ?? item.expense_date"
                :party-label="t('Paid to')"
                :party-name="item.paid_to || item.project?.name || t('Company-wide')"
                :amount="item.amount"
                :currency="item.currency"
                :payment-method="item.payment_method"
                :project-name="item.project?.name"
                :description="item.description"
                :status="item.status"
            />

            <ConfirmActionDialog
                v-model:open="deleteOpen"
                :title="t('Move to trash')"
                :description="t('This item will move to trash. You can restore it later.')"
                :confirm-label="t('Move to trash')"
                :processing="deleting"
                @confirm="confirmDelete"
            />
        </div>
    </V2ListPage>
</template>
