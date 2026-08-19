<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, Pencil, Printer, Trash2 } from '@lucide/vue';
import { computed } from 'vue';
import Can from '@/components/Can.vue';
import DetailGrid from '@/components/DetailGrid.vue';
import FileUploadField, { type ExistingUploadFile } from '@/components/FileUploadField.vue';
import Heading from '@/components/Heading.vue';
import PrintableVoucher from '@/components/PrintableVoucher.vue';
import { Button } from '@/components/ui/button';
import { useCalendar } from '@/composables/useCalendar';
import { useTranslations } from '@/composables/useTranslations';
import { money } from '@/lib/utils';
import { printPage } from '@/lib/voucher';

const props = defineProps<{ item: Record<string, any> }>();
const { t } = useTranslations();
const calendar = useCalendar();
const formatDate = (value: string | null | undefined) => calendar.format(value) || '—';

const backHref = computed(() =>
    props.item.project_id ? `/projects/${props.item.project_id}` : '/expenses',
);
const existingReceipts = computed<ExistingUploadFile[]>(() =>
    (props.item.attachments ?? []).map((file: any) => ({
        id: file.id,
        name: file.original_filename || file.title || t('Receipt'),
        size: file.file_size,
        mime: file.mime_type,
        url: file.download_url,
    })),
);
const canEditExpense = computed(() => !props.item.purchase_id && !props.item.project_id);

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
                    <Link :href="backHref"><ArrowLeft class="size-4" /> {{ t('Back') }}</Link>
                </Button>
                <Heading :title="item.expense_no" :description="item.project?.name || t('General expense')" />
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <Button variant="outline" @click="printPage">
                    <Printer class="size-4" /> {{ t('Print voucher') }}
                </Button>
                <Can permission="expenses.edit">
                    <Button v-if="canEditExpense" as-child>
                        <Link :href="`/expenses/${item.id}/edit`"><Pencil class="size-4" /> {{ t('Edit') }}</Link>
                    </Button>
                </Can>
                <Can permission="expenses.delete">
                    <Form v-if="!item.purchase_id" :action="`/expenses/${item.id}`" method="delete" v-slot="{ processing }">
                        <Button type="submit" variant="destructive" :disabled="processing">
                            <Trash2 class="size-4" /> {{ processing ? t('Deleting…') : t('Delete') }}
                        </Button>
                    </Form>
                </Can>
            </div>
        </div>

        <div class="print:hidden">
            <div class="rounded-xl border bg-card p-5 shadow-sm sm:max-w-sm">
                <div class="text-sm text-muted-foreground">{{ t('Total cost') }}</div>
                <div class="mt-1 text-3xl font-semibold tabular-nums text-rose-700">
                    {{ money(item.amount, item.currency || 'USD') }}
                </div>
            </div>
        </div>

        <div class="print:hidden">
            <DetailGrid
                :title="t('Expense details')"
                :items="[
                    { label: t('Date'), value: formatDate(item.expense_date) },
                    { label: t('Paid to'), value: item.paid_to || '—' },
                    { label: t('Project'), value: item.project?.name || t('Company-wide') },
                    { label: t('Purchase'), value: item.purchase?.purchase_no || '—' },
                    { label: t('Unit'), value: item.expense_unit?.unit_name || '—' },
                    { label: t('Item amount'), value: item.item_amount ?? '—' },
                    { label: t('Per price'), value: item.per_price != null ? money(item.per_price, item.currency || 'USD') : '—' },
                    { label: t('Reference'), value: item.reference_no || '—' },
                    { label: t('Description'), value: item.description, span: true },
                ]"
            >
                <template v-if="item.purchase_id">
                    <Button as-child variant="outline" size="sm">
                        <Link :href="`/purchases/${item.purchase_id}`">{{ t('View purchase') }}</Link>
                    </Button>
                </template>
            </DetailGrid>
        </div>

        <div class="space-y-3 rounded-xl border bg-card p-5 shadow-sm print:hidden sm:max-w-xl">
            <h3 class="text-sm font-semibold">{{ t('Receipt files') }}</h3>
            <FileUploadField
                mode="chunked"
                destination="attachment"
                attachable="expenses"
                :attachable-id="item.id"
                multiple
                :max-files="10"
                accept="application/pdf,image/jpeg,image/png,image/webp"
                :existing="existingReceipts"
            />
        </div>

        <PrintableVoucher
            kind="payment"
            :title="t('Expense Payment Voucher')"
            :document-no="item.expense_no"
            :date="item.expense_date?.slice?.(0, 10) ?? item.expense_date"
            :party-label="t('Paid to')"
            :party-name="item.paid_to || item.project?.name || t('Company-wide')"
            :amount="item.amount"
            :currency="item.currency || 'USD'"
            :payment-method="item.payment_method"
            :project-name="item.project?.name"
            :description="item.description"
            :status="item.status"
        />
    </div>
</template>
