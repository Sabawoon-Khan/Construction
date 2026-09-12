<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Printer } from '@lucide/vue';
import { MisDetailGrid } from '@/components/mis';
import PrintableVoucher from '@/components/PrintableVoucher.vue';
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
    <V2ListPage>
        <div class="voucher-print-page flex flex-col gap-4 print:gap-0">
            <V2DetailHero class="print:hidden" image="/images/dashboard-v2-building.png">
                <template #eyebrow>{{ t('Projects') }}</template>
                <template #title>{{ documentNo }}</template>
                <template #description>{{ t('Shareholder contribution') }} · {{ project.name }}</template>
                <template #actions>
                    <Button as-child variant="outline">
                        <Link :href="`/projects/${project.id}`">{{ t('Back') }}</Link>
                    </Button>
                    <Button variant="outline" @click="printPage">
                        <Printer class="size-4" /> {{ t('Print voucher') }}
                    </Button>
                </template>
            </V2DetailHero>

            <div class="print:hidden">
                <V2AmountCard :label="t('Amount')" tone="emerald">
                    {{ money(item.amount, item.currency) }}
                </V2AmountCard>
            </div>

            <V2Panel class="print:hidden" :title="t('Contribution details')">
                <MisDetailGrid
                    :columns="2"
                    :items="[
                        { label: t('Date'), value: formatDate(item.invested_at) },
                        { label: t('Shareholder'), value: item.partner?.name },
                        { label: t('Project'), value: project.name },
                        { label: t('Amount'), value: money(item.amount, item.currency) },
                    ]"
                />
            </V2Panel>

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
    </V2ListPage>
</template>
