<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, Printer } from '@lucide/vue';
import DetailGrid from '@/components/DetailGrid.vue';
import Heading from '@/components/Heading.vue';
import PrintableVoucher from '@/components/PrintableVoucher.vue';
import { Button } from '@/components/ui/button';
import { useCalendar } from '@/composables/useCalendar';
import { useTranslations } from '@/composables/useTranslations';
import { money } from '@/lib/utils';
import { printPage } from '@/lib/voucher';

const props = defineProps<{
    item: Record<string, any>;
    project: { id: number; name: string; code?: string };
}>();

const { t } = useTranslations();
const calendar = useCalendar();
const formatDate = (value: string | null | undefined) => calendar.format(value) || '—';
const documentNo = `CONT-${props.item.id}`;

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Projects', href: '/projects' },
            { title: 'Contribution', href: '#' },
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
                    <Link :href="`/projects/${project.id}`">
                        <ArrowLeft class="size-4" /> {{ t('Back') }}
                    </Link>
                </Button>
                <Heading
                    :title="documentNo"
                    :description="`${t('Shareholder contribution')} · ${project.name}`"
                />
            </div>
            <Button variant="outline" @click="printPage">
                <Printer class="size-4" /> {{ t('Print voucher') }}
            </Button>
        </div>

        <div class="print:hidden">
            <div class="rounded-xl border bg-card p-5 shadow-sm sm:max-w-sm">
                <div class="text-sm text-muted-foreground">{{ t('Amount') }}</div>
                <div class="mt-1 text-3xl font-semibold tabular-nums text-emerald-700">
                    {{ money(item.amount, item.currency) }}
                </div>
            </div>
        </div>

        <div class="print:hidden">
            <DetailGrid
                :title="t('Contribution details')"
                :items="[
                    { label: t('Date'), value: formatDate(item.invested_at) },
                    { label: t('Shareholder'), value: item.partner?.name },
                    { label: t('Project'), value: project.name },
                    {
                        label: t('Amount'),
                        value: money(item.amount, item.currency),
                    },
                ]"
            />
        </div>

        <PrintableVoucher
            kind="receipt"
            :title="t('Shareholder Contribution Voucher')"
            :document-no="documentNo"
            :date="item.invested_at"
            :party-label="t('Received from')"
            :party-name="item.partner?.name"
            :amount="item.amount"
            :currency="item.currency"
            :project-name="project.name"
            :status="item.status"
            :fields="[
                { label: t('Project code'), value: project.code },
                { label: t('Type'), value: t('Contribution') },
            ]"
        />
    </div>
</template>
