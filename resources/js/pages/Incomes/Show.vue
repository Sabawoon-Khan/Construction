<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Pencil, Printer, Trash2 } from '@lucide/vue';
import { ref } from 'vue';
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

function confirmDelete() {
    deleting.value = true;
    expectTrashUndo({ type: 'incomes', id: props.item.id });
    router.delete(`/incomes/${props.item.id}`, {
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
            { title: 'Incomes', href: '/incomes' },
            { title: 'Voucher', href: '#' },
        ],
    },
});
</script>

<template>
    <Head :title="item.income_no" />
    <V2ListPage>
        <div class="voucher-print-page flex flex-col gap-4 print:gap-0">
            <V2DetailHero class="print:hidden" image="/images/dashboard-v2-building.png">
                <template #eyebrow>{{ t('Incomes') }}</template>
                <template #title>{{ item.income_no }}</template>
                <template #actions>
                    <Button variant="outline" @click="printPage">
                        <Printer class="size-4" /> {{ t('Print voucher') }}
                    </Button>
                    <Can permission="incomes.edit">
                        <Button as-child>
                            <Link :href="`/incomes/${item.id}/edit`"><Pencil class="size-4" /> {{ t('Edit') }}</Link>
                        </Button>
                    </Can>
                    <Can permission="incomes.delete">
                        <Button type="button" variant="destructive" @click="deleteOpen = true">
                            <Trash2 class="size-4" /> {{ t('Move to trash') }}
                        </Button>
                    </Can>
                </template>
            </V2DetailHero>

            <div class="print:hidden">
                <V2AmountCard :label="t('Total amount')" tone="emerald">
                    {{ money(item.amount, item.currency) }}
                </V2AmountCard>
            </div>

            <V2Panel class="print:hidden" :title="t('Income details')">
                <MisDetailGrid
                    :columns="2"
                    :items="[
                        { label: t('Date'), value: formatDate(item.income_date) },
                        { label: t('Description'), value: item.description, span: 2 },
                    ]"
                />
            </V2Panel>

            <RecordAttachments
                attachable="incomes"
                :attachable-id="item.id"
                :attachments="item.attachments"
                boxed
            />

            <PrintableVoucher
                kind="receipt"
                :title="t('Income Receipt Voucher')"
                :document-no="item.income_no"
                :date="item.income_date"
                :party-label="t('Received for')"
                :party-name="t('Company-wide')"
                :amount="item.amount"
                :currency="item.currency"
                :payment-method="item.payment_method"
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
