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
const printedAt = computed(() =>
    calendar.formatDateTime(new Date()) ||
    new Date().toLocaleString('en-GB', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    }),
);
const displayDate = computed(() => calendar.format(props.date) || '—');

const kindLabel = computed(() => {
    if (props.kind === 'receipt') return t('Receipt Voucher');
    if (props.kind === 'payment') return t('Payment Voucher');
    return t('Account Statement');
});

const accent = computed(() => {
    if (props.kind === 'payment') return 'border-rose-700 text-rose-800';
    if (props.kind === 'receipt') return 'border-emerald-700 text-emerald-800';
    return 'border-slate-700 text-slate-800';
});

const amountClass = computed(() => {
    if (props.kind === 'payment') return 'text-rose-800';
    if (props.kind === 'receipt') return 'text-emerald-800';
    return 'text-slate-900';
});

const words = computed(() => amountInWords(props.amount, props.currency));
</script>

<template>
    <article
        class="voucher-sheet mx-auto w-full max-w-3xl overflow-hidden rounded-xl border border-slate-300 bg-white text-slate-900 shadow-sm print:max-w-none print:rounded-none print:border-black print:shadow-none"
    >
        <div class="relative border-b border-slate-300 px-6 py-5 print:border-black print:px-0 print:py-4">
            <div class="absolute inset-y-0 left-0 w-1.5 bg-slate-900 print:w-1" />
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between print:gap-3">
                <div class="space-y-1 pl-3 print:pl-4">
                    <p class="text-xs font-semibold tracking-[0.2em] text-slate-500 uppercase">
                        {{ kindLabel }}
                    </p>
                    <h1 class="text-2xl font-semibold tracking-tight">{{ companyName }}</h1>
                    <p v-if="company?.address" class="max-w-md text-sm text-slate-600">{{ company.address }}</p>
                    <p class="text-sm text-slate-600">
                        <span v-if="company?.phone">{{ t('Tel') }}: {{ company.phone }}</span>
                        <span v-if="company?.phone && company?.email"> · </span>
                        <span v-if="company?.email">{{ company.email }}</span>
                    </p>
                    <p v-if="company?.registration_no" class="text-xs text-slate-500">
                        {{ t('Reg. No') }}: {{ company.registration_no }}
                    </p>
                </div>
                <div class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-sm sm:min-w-[200px] print:border-black print:bg-white">
                    <div class="flex justify-between gap-6">
                        <span class="text-slate-500">{{ t('Voucher No') }}</span>
                        <span class="font-semibold">{{ documentNo }}</span>
                    </div>
                    <div class="mt-2 flex justify-between gap-6">
                        <span class="text-slate-500">{{ t('Date') }}</span>
                        <span class="font-medium">{{ displayDate }}</span>
                    </div>
                    <div v-if="status" class="mt-2 flex justify-between gap-6">
                        <span class="text-slate-500">{{ t('Status') }}</span>
                        <span class="font-medium">{{ translateStatus(status, t) }}</span>
                    </div>
                </div>
            </div>
            <div class="mt-4 flex items-center justify-between gap-3 border-t border-dashed border-slate-300 pt-3">
                <h2 class="text-lg font-semibold">{{ title }}</h2>
                <span
                    class="rounded-full border px-3 py-1 text-xs font-semibold tracking-wide uppercase"
                    :class="accent"
                >
                    {{ kind }}
                </span>
            </div>
        </div>

        <div class="space-y-5 px-6 py-5 print:space-y-4 print:px-0 print:py-4">
            <div class="grid gap-3 sm:grid-cols-2 print:gap-2">
                <div class="rounded-lg border border-slate-200 p-4 print:border-black print:p-3">
                    <p class="text-xs font-medium tracking-wide text-slate-500 uppercase">{{ partyLabel }}</p>
                    <p class="mt-1 text-base font-semibold">{{ partyName || '—' }}</p>
                </div>
                <div class="rounded-lg border border-slate-200 p-4 print:border-black print:p-3">
                    <p class="text-xs font-medium tracking-wide text-slate-500 uppercase">
                        {{ kind === 'statement' ? t('Balance due') : t('Amount') }}
                    </p>
                    <p class="mt-1 text-2xl font-semibold tabular-nums" :class="amountClass">
                        {{ money(amount, currency) }}
                    </p>
                </div>
            </div>

            <dl class="grid gap-x-6 gap-y-3 rounded-lg border border-slate-200 p-4 text-sm sm:grid-cols-2 print:border-black print:p-3">
                <div v-if="projectName">
                    <dt class="text-xs text-slate-500 uppercase">{{ t('Project') }}</dt>
                    <dd class="mt-0.5 font-medium">{{ projectName }}</dd>
                </div>
                <div v-if="accountName">
                    <dt class="text-xs text-slate-500 uppercase">{{ t('Account') }}</dt>
                    <dd class="mt-0.5 font-medium">{{ accountName }}</dd>
                </div>
                <div v-if="paymentMethod">
                    <dt class="text-xs text-slate-500 uppercase">{{ t('Payment method') }}</dt>
                    <dd class="mt-0.5 font-medium capitalize">{{ paymentMethod }}</dd>
                </div>
                <div v-if="referenceNo">
                    <dt class="text-xs text-slate-500 uppercase">{{ t('Reference') }}</dt>
                    <dd class="mt-0.5 font-medium">{{ referenceNo }}</dd>
                </div>
                <div v-for="field in fields" :key="field.label">
                    <dt class="text-xs text-slate-500 uppercase">{{ field.label }}</dt>
                    <dd class="mt-0.5 font-medium">{{ field.value || '—' }}</dd>
                </div>
                <div v-if="description" class="sm:col-span-2">
                    <dt class="text-xs text-slate-500 uppercase">{{ t('Description / Notes') }}</dt>
                    <dd class="mt-0.5 font-medium whitespace-pre-wrap">{{ description }}</dd>
                </div>
            </dl>

            <div v-if="lines?.length" class="overflow-hidden rounded-lg border border-slate-200 print:border-black">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 text-start text-slate-600 print:bg-white">
                        <tr>
                            <th class="px-4 py-2.5 font-medium">{{ t('Description') }}</th>
                            <th class="px-4 py-2.5 font-medium">{{ t('Details') }}</th>
                            <th class="px-4 py-2.5 text-end font-medium">{{ t('Amount') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(line, index) in lines" :key="index" class="border-t border-slate-200">
                            <td class="px-4 py-2.5">{{ line.description }}</td>
                            <td class="px-4 py-2.5 text-slate-600">{{ line.meta || '—' }}</td>
                            <td class="px-4 py-2.5 text-end tabular-nums">
                                {{ line.amount == null ? '—' : money(line.amount, currency) }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="totals?.length" class="grid gap-2 sm:ml-auto sm:max-w-sm">
                <div
                    v-for="total in totals"
                    :key="total.label"
                    class="flex items-center justify-between border-b border-dashed border-slate-200 py-1.5 text-sm"
                >
                    <span class="text-slate-600">{{ total.label }}</span>
                    <span class="font-semibold tabular-nums">{{ total.value }}</span>
                </div>
            </div>

            <div class="rounded-lg bg-slate-50 px-4 py-3 text-sm print:border print:border-black print:bg-white print:py-2">
                <span class="text-slate-500">{{ t('Amount in words') }}:</span>
                <span class="ml-2 font-medium italic">{{ words }}</span>
            </div>

            <div class="grid gap-8 pt-4 sm:grid-cols-3 print:gap-6 print:pt-2">
                <div class="space-y-8">
                    <div class="border-b border-slate-400" />
                    <p class="text-center text-xs tracking-wide text-slate-500 uppercase">{{ t('Prepared by') }}</p>
                </div>
                <div class="space-y-8">
                    <div class="border-b border-slate-400" />
                    <p class="text-center text-xs tracking-wide text-slate-500 uppercase">{{ t('Checked by') }}</p>
                </div>
                <div class="space-y-8">
                    <div class="border-b border-slate-400" />
                    <p class="text-center text-xs tracking-wide text-slate-500 uppercase">
                        {{ kind === 'payment' ? t('Received by') : t('Authorized by') }}
                    </p>
                </div>
            </div>
        </div>

        <div class="flex items-center justify-between border-t border-slate-200 bg-slate-50 px-6 py-3 text-xs text-slate-500 print:border-black print:bg-white print:px-0 print:py-2">
            <span>{{ t('Computer-generated voucher') }} · {{ companyName }}</span>
            <span>{{ t('Printed') }} {{ printedAt }}</span>
        </div>
    </article>
</template>
