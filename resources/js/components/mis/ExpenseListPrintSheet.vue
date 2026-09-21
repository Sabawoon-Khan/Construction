<script setup lang="ts">
import { computed } from 'vue';
import MisPrintFooter from '@/components/mis/MisPrintFooter.vue';
import MisPrintLetterhead from '@/components/mis/MisPrintLetterhead.vue';
import { useCalendar } from '@/composables/useCalendar';
import { useTranslations } from '@/composables/useTranslations';
import { formatNumber, money, moneyByCurrency, resolveCurrency } from '@/lib/utils';

export type ExpensePrintRow = {
    id: number;
    expense_date: string;
    reference_no?: string | null;
    paid_to?: string | null;
    description?: string | null;
    amount: number;
    currency?: string | null;
    payment_method?: string | null;
    category?: { id?: number; name?: string } | null;
    expense_unit?: { id?: number; unit_name?: string } | null;
};

const props = defineProps<{
    projectName: string;
    projectCode?: string | null;
    categoryLabel?: string | null;
    rows: ExpensePrintRow[];
    baseCurrency: string;
}>();

const { t } = useTranslations();
const calendar = useCalendar();
const formatDate = (value: string | null | undefined) =>
    calendar.format(value) || '—';

const totalsByCurrency = computed(() => {
    const byCurrency: Record<string, number> = {};
    for (const row of props.rows) {
        const code = resolveCurrency(row.currency || props.baseCurrency);
        byCurrency[code] = (byCurrency[code] ?? 0) + Number(row.amount ?? 0);
    }
    return byCurrency;
});

const subtitle = computed(() =>
    [
        props.projectName,
        props.projectCode,
        props.categoryLabel,
    ]
        .filter(Boolean)
        .join(' · '),
);
</script>

<template>
    <article class="expense-a4-sheet a4-print-sheet print-target">
        <MisPrintLetterhead
            :title="t('Project expenses')"
            :subtitle="subtitle"
        />

        <div class="a4-summary">
            <div>
                <span>{{ t('Records') }}</span>
                <strong>{{ formatNumber(rows.length, 0) }}</strong>
            </div>
            <div>
                <span>{{ t('Total') }}</span>
                <strong>{{ moneyByCurrency(totalsByCurrency) }}</strong>
            </div>
        </div>

        <table class="a4-table">
            <thead>
                <tr>
                    <th class="col-num">#</th>
                    <th>{{ t('Date') }}</th>
                    <th>{{ t('Bill number') }}</th>
                    <th>{{ t('Paid to') }}</th>
                    <th>{{ t('Category') }}</th>
                    <th>{{ t('Description') }}</th>
                    <th>{{ t('Unit') }}</th>
                    <th>{{ t('Paying type') }}</th>
                    <th class="col-amt">{{ t('Amount') }}</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="(row, index) in rows" :key="row.id">
                    <td class="col-num">{{ index + 1 }}</td>
                    <td class="nowrap">{{ formatDate(row.expense_date) }}</td>
                    <td>{{ row.reference_no || '—' }}</td>
                    <td>{{ row.paid_to || '—' }}</td>
                    <td>{{ row.category?.name || t('General') }}</td>
                    <td>{{ row.description || '—' }}</td>
                    <td>{{ row.expense_unit?.unit_name || '—' }}</td>
                    <td>
                        {{
                            row.payment_method === 'bank' ? t('Bank') : t('Cash')
                        }}
                    </td>
                    <td class="col-amt">
                        {{ money(row.amount, row.currency || baseCurrency) }}
                    </td>
                </tr>
                <tr v-if="!rows.length">
                    <td colspan="9" class="empty">{{ t('No expenses yet') }}</td>
                </tr>
            </tbody>
        </table>

        <MisPrintFooter />
    </article>
</template>
