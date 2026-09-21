<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { translateStatus } from '@/lib/translateStatus';
import { useCalendar } from '@/composables/useCalendar';
import { useTranslations } from '@/composables/useTranslations';
import { amountInWords, type VoucherField, type VoucherKind, type VoucherLine } from '@/lib/voucher';
import { money } from '@/lib/utils';

const props = withDefaults(
    defineProps<{
        kind: VoucherKind;
        title: string;
        documentNo: string;
        date?: string | null;
        partyLabel: string;
        partyName?: string | null;
        amount: number | string;
        currency?: string;
        paymentMethod?: string | null;
        referenceNo?: string | null;
        projectName?: string | null;
        accountName?: string | null;
        description?: string | null;
        status?: string | null;
        fields?: VoucherField[];
        lines?: VoucherLine[];
        totals?: VoucherField[];
    }>(),
    {
        currency: undefined,
        fields: () => [],
        lines: () => [],
        totals: () => [],
    },
);

const page = usePage();
const { t } = useTranslations();
const calendar = useCalendar();
const company = computed(() => page.props.company);
const companyName = computed(
    () => company.value?.legal_name || company.value?.name || page.props.name || t('Company'),
);
const printedAt = computed(() => calendar.formatDateTime(new Date()) || '—');
const displayDate = computed(() => calendar.format(props.date) || '—');

const kindLabel = computed(() => {
    if (props.kind === 'receipt') return t('Receipt Voucher');
    if (props.kind === 'payment') return t('Payment Voucher');
    if (props.kind === 'salary') return t('Salary Voucher');
    return t('Account Statement');
});

const thirdSignLabel = computed(() => {
    if (props.kind === 'payment' || props.kind === 'salary') return t('Received by');
    return t('Authorized by');
});

const words = computed(() => amountInWords(props.amount, props.currency));

const detailItems = computed(() => {
    const items: { label: string; value: string }[] = [];

    if (props.projectName) items.push({ label: t('Project'), value: props.projectName });
    if (props.accountName) items.push({ label: t('Account'), value: props.accountName });
    if (props.paymentMethod) items.push({ label: t('Payment method'), value: props.paymentMethod });
    if (props.referenceNo) items.push({ label: t('Reference'), value: props.referenceNo });

    for (const field of props.fields ?? []) {
        if (field.value) items.push({ label: field.label, value: String(field.value) });
    }

    return items;
});

const showDetailsColumn = computed(() => props.lines?.some((line) => line.meta));
</script>

<template>
    <article class="voucher-sheet print-target mx-auto w-full max-w-2xl bg-white text-neutral-900">
        <!-- Header -->
        <header class="border-b border-neutral-200 px-8 py-7 text-center print:px-0 print:py-5">
            <p class="text-[11px] font-medium tracking-[0.15em] text-neutral-400 uppercase">
                {{ kindLabel }}
            </p>
            <h1 class="mt-2 text-xl font-semibold tracking-tight">{{ companyName }}</h1>
            <p v-if="company?.address || company?.phone || company?.email" class="mt-1.5 text-sm text-neutral-500">
                <span v-if="company?.address">{{ company.address }}</span>
                <template v-if="company?.address && (company?.phone || company?.email)"> · </template>
                <span v-if="company?.phone">{{ company.phone }}</span>
                <template v-if="company?.phone && company?.email"> · </template>
                <span v-if="company?.email">{{ company.email }}</span>
            </p>
            <h2 class="mt-5 text-base font-medium text-neutral-700">{{ title }}</h2>
        </header>

        <!-- Meta -->
        <div class="flex flex-wrap items-center justify-center gap-x-6 gap-y-1 border-b border-neutral-100 px-8 py-3 text-sm text-neutral-500 print:px-0">
            <span><span class="text-neutral-400">{{ t('No') }}</span> {{ documentNo }}</span>
            <span class="hidden h-3 w-px bg-neutral-200 sm:block" />
            <span><span class="text-neutral-400">{{ t('Date') }}</span> {{ displayDate }}</span>
            <template v-if="status">
                <span class="hidden h-3 w-px bg-neutral-200 sm:block" />
                <span>{{ translateStatus(status, t) }}</span>
            </template>
        </div>

        <div class="space-y-7 px-8 py-7 print:space-y-5 print:px-0 print:py-5">
            <!-- Party & amount -->
            <div class="flex items-end justify-between gap-6">
                <div class="min-w-0">
                    <p class="text-xs text-neutral-400">{{ partyLabel }}</p>
                    <p class="mt-1 text-lg font-medium">{{ partyName || '—' }}</p>
                </div>
                <div class="shrink-0 text-end">
                    <p class="text-xs text-neutral-400">
                        {{ kind === 'statement' ? t('Balance due') : t('Amount') }}
                    </p>
                    <p class="mt-1 text-2xl font-semibold tabular-nums tracking-tight">
                        {{ money(amount, currency) }}
                    </p>
                </div>
            </div>

            <!-- Details -->
            <div v-if="detailItems.length" class="grid gap-x-8 gap-y-2.5 text-sm sm:grid-cols-2">
                <div v-for="item in detailItems" :key="item.label" class="flex justify-between gap-4 sm:block">
                    <span class="text-neutral-400">{{ item.label }}</span>
                    <span class="font-medium sm:mt-0.5 sm:block">{{ item.value }}</span>
                </div>
            </div>

            <!-- Line items -->
            <div v-if="lines?.length">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-neutral-200 text-neutral-400">
                            <th class="pb-2 text-start font-normal">{{ t('Description') }}</th>
                            <th v-if="showDetailsColumn" class="pb-2 text-start font-normal">{{ t('Details') }}</th>
                            <th class="pb-2 text-end font-normal">{{ t('Amount') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="(line, index) in lines"
                            :key="index"
                            class="border-b border-neutral-100 last:border-0"
                        >
                            <td class="py-2.5">{{ line.description }}</td>
                            <td v-if="showDetailsColumn" class="py-2.5 text-neutral-500">{{ line.meta || '—' }}</td>
                            <td class="py-2.5 text-end tabular-nums">
                                {{ line.amount == null ? '—' : money(line.amount, currency) }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Totals -->
            <div v-if="totals?.length" class="ms-auto max-w-xs space-y-1.5 text-sm">
                <div
                    v-for="total in totals"
                    :key="total.label"
                    class="flex items-center justify-between gap-4"
                >
                    <span class="text-neutral-500">{{ total.label }}</span>
                    <span class="font-semibold tabular-nums">{{ total.value }}</span>
                </div>
            </div>

            <!-- Notes -->
            <p v-if="description" class="text-sm leading-relaxed text-neutral-600">
                <span class="text-neutral-400">{{ t('Notes') }}:</span>
                {{ description }}
            </p>

            <!-- Amount in words -->
            <p class="border-t border-neutral-100 pt-5 text-sm text-neutral-500 italic">
                {{ words }}
            </p>

            <!-- Signatures -->
            <div class="grid grid-cols-3 gap-6 pt-2 print:gap-4">
                <div v-for="label in [t('Prepared by'), t('Checked by'), thirdSignLabel]" :key="label">
                    <div class="border-b border-neutral-300" />
                    <p class="mt-2 text-center text-[11px] text-neutral-400">{{ label }}</p>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <footer class="border-t border-neutral-100 px-8 py-3 text-center text-[11px] text-neutral-400 print:px-0">
            {{ companyName }} · {{ t('Printed') }} {{ printedAt }}
        </footer>
    </article>
</template>
