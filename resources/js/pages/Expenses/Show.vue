<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Pencil, Printer, Trash2 } from '@lucide/vue';
import { computed, ref } from 'vue';
import Can from '@/components/Can.vue';
import ConfirmActionDialog from '@/components/ConfirmActionDialog.vue';
import RecordAttachments from '@/components/RecordAttachments.vue';
import { ExpenseVoucherPrintSheet, MisDetailGrid } from '@/components/mis';
import { Button } from '@/components/ui/button';
import { V2DetailHero, V2ListPage, V2Panel } from '@/components/v2';
import { useA4Print } from '@/composables/useA4Print';
import { useCalendar } from '@/composables/useCalendar';
import { useTranslations } from '@/composables/useTranslations';
import { money } from '@/lib/utils';
import {
    consumePendingTrashUndo,
    expectTrashUndo,
} from '@/lib/trashUndoToast';

const props = defineProps<{ item: Record<string, any> }>();
const { t } = useTranslations();
const calendar = useCalendar();
const formatDate = (value: string | null | undefined) =>
    calendar.format(value) || '—';
const { printA4 } = useA4Print();

const deleteOpen = ref(false);
const deleting = ref(false);

const canEditExpense = computed(
    () => !props.item.purchase_id && !props.item.project_id,
);

const hasExtras = computed(
    () =>
        Boolean(
            props.item.purchase_id ||
                props.item.expense_unit ||
                props.item.item_amount != null ||
                props.item.per_price != null,
        ),
);

const extraItems = computed(() =>
    [
        {
            label: t('Purchase'),
            value: props.item.purchase?.purchase_no || null,
        },
        {
            label: t('Unit'),
            value: props.item.expense_unit?.unit_name || null,
        },
        {
            label: t('Item amount'),
            value:
                props.item.item_amount != null
                    ? String(props.item.item_amount)
                    : null,
        },
        {
            label: t('Per price'),
            value:
                props.item.per_price != null
                    ? money(props.item.per_price, props.item.currency)
                    : null,
        },
    ].filter((row) => row.value),
);

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
        <V2DetailHero
            compact
            class="print:hidden"
            image="/images/dashboard-v2-building.png"
        >
            <template #eyebrow>{{ t('Expense voucher') }}</template>
            <template #title>{{ item.expense_no }}</template>
            <template #description>
                {{ money(item.amount, item.currency) }}
                ·
                {{ item.paid_to || t('Company-wide') }}
                ·
                {{ formatDate(item.expense_date) }}
            </template>
            <template #actions>
                <Button variant="outline" @click="printA4()">
                    <Printer class="size-4" /> {{ t('Print') }}
                </Button>
                <Can permission="expenses.edit">
                    <Button v-if="canEditExpense" as-child>
                        <Link :href="`/expenses/${item.id}/edit`">
                            <Pencil class="size-4" /> {{ t('Edit') }}
                        </Link>
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

        <ExpenseVoucherPrintSheet
            class="expense-voucher-preview"
            :expense="item"
            :project-name="item.project?.name"
            :project-code="item.project?.code"
        />

        <V2Panel
            v-if="hasExtras"
            class="print:hidden"
            :title="t('Additional details')"
        >
            <template v-if="item.purchase_id" #actions>
                <Button as-child variant="outline" size="sm">
                    <Link :href="`/purchases/${item.purchase_id}`">
                        {{ t('View purchase') }}
                    </Link>
                </Button>
            </template>
            <MisDetailGrid :columns="2" :items="extraItems" />
        </V2Panel>

        <RecordAttachments
            class="print:hidden"
            boxed
            attachable="expenses"
            :attachable-id="item.id"
            :attachments="item.attachments"
            :label="t('Receipt files')"
            accept="application/pdf,image/jpeg,image/png,image/webp"
        />

        <ConfirmActionDialog
            v-model:open="deleteOpen"
            :title="t('Move to trash')"
            :description="
                t('This item will move to trash. You can restore it later.')
            "
            :confirm-label="t('Move to trash')"
            :processing="deleting"
            @confirm="confirmDelete"
        />
    </V2ListPage>
</template>
