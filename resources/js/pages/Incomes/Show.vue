<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, Pencil, Printer, Trash2 } from '@lucide/vue';
import Can from '@/components/Can.vue';
import DetailGrid from '@/components/DetailGrid.vue';
import Heading from '@/components/Heading.vue';
import PrintableVoucher from '@/components/PrintableVoucher.vue';
import { Button } from '@/components/ui/button';
import { useCalendar } from '@/composables/useCalendar';
import { useTranslations } from '@/composables/useTranslations';
import { money } from '@/lib/utils';
import { printPage } from '@/lib/voucher';

defineProps<{ item: Record<string, any> }>();
const { t } = useTranslations();
const calendar = useCalendar();
const formatDate = (value: string | null | undefined) => calendar.format(value) || '—';

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
    <div class="voucher-print-page flex h-full flex-1 flex-col gap-4 print:gap-0 print:p-0">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between print:hidden">
            <div class="space-y-3">
                <Button as-child variant="ghost" size="sm" class="-ml-2 w-fit">
                    <Link href="/incomes"><ArrowLeft class="size-4" /> {{ t('Back') }}</Link>
                </Button>
                <Heading :title="item.income_no" :description="t('General income')" />
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <Button variant="outline" @click="printPage">
                    <Printer class="size-4" /> {{ t('Print voucher') }}
                </Button>
                <Can permission="incomes.edit">
                    <Button as-child>
                        <Link :href="`/incomes/${item.id}/edit`"><Pencil class="size-4" /> {{ t('Edit') }}</Link>
                    </Button>
                </Can>
                <Can permission="incomes.delete">
                    <Form :action="`/incomes/${item.id}`" method="delete" v-slot="{ processing }">
                        <Button type="submit" variant="destructive" :disabled="processing">
                            <Trash2 class="size-4" /> {{ processing ? t('Deleting…') : t('Delete') }}
                        </Button>
                    </Form>
                </Can>
            </div>
        </div>

        <div class="print:hidden">
            <div class="rounded-xl border bg-card p-5 shadow-sm sm:max-w-sm">
                <div class="text-sm text-muted-foreground">{{ t('Total amount') }}</div>
                <div class="mt-1 text-3xl font-semibold tabular-nums text-emerald-700">
                    {{ money(item.amount, item.currency || 'USD') }}
                </div>
            </div>
        </div>

        <div class="print:hidden">
            <DetailGrid
                :title="t('Income details')"
                :items="[
                    { label: t('Date'), value: formatDate(item.income_date) },
                    { label: t('Description'), value: item.description, span: true },
                ]"
            />
        </div>

        <PrintableVoucher
            kind="receipt"
            :title="t('Income Receipt Voucher')"
            :document-no="item.income_no"
            :date="item.income_date"
            :party-label="t('Received for')"
            :party-name="t('Company-wide')"
            :amount="item.amount"
            :currency="item.currency || 'USD'"
            :payment-method="item.payment_method"
            :description="item.description"
            :status="item.status"
        />
    </div>
</template>
