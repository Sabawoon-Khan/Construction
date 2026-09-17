<script setup lang="ts">
import { computed } from 'vue';
import MisPrintFooter from '@/components/mis/MisPrintFooter.vue';
import MisPrintLetterhead from '@/components/mis/MisPrintLetterhead.vue';
import { useCalendar } from '@/composables/useCalendar';
import { useTranslations } from '@/composables/useTranslations';
import { amountInWords } from '@/lib/voucher';
import { money } from '@/lib/utils';

const props = defineProps<{
    expense: {
        id: number;
        expense_no?: string | null;
        expense_date?: string | null;
        paid_to?: string | null;
        description?: string | null;
        amount: number;
        currency?: string | null;
        payment_method?: string | null;
        reference_no?: string | null;
        category?: { name?: string } | null;
        status?: string | null;
    };
    projectName?: string | null;
    projectCode?: string | null;
}>();

const { t } = useTranslations();
const calendar = useCalendar();

const displayDate = computed(
    () => calendar.format(props.expense.expense_date) || '—',
);
const words = computed(() =>
    amountInWords(props.expense.amount, props.expense.currency || 'USD'),
);
const methodLabel = computed(() =>
    props.expense.payment_method === 'bank' ? t('Bank') : t('Cash'),
);
</script>

<template>
    <article class="expense-a4-sheet a4-print-sheet expense-voucher-sheet">
        <MisPrintLetterhead
            :title="t('Expense voucher')"
            :subtitle="
                [projectName, projectCode, expense.expense_no]
                    .filter(Boolean)
                    .join(' · ')
            "
        />

        <div class="voucher-hero">
            <div>
                <p class="voucher-label">{{ t('Paid to') }}</p>
                <p class="voucher-party">{{ expense.paid_to || '—' }}</p>
            </div>
            <div class="voucher-amount-block">
                <p class="voucher-label">{{ t('Amount') }}</p>
                <p class="voucher-amount">
                    {{ money(expense.amount, expense.currency) }}
                </p>
            </div>
        </div>

        <dl class="voucher-grid">
            <div>
                <dt>{{ t('Date') }}</dt>
                <dd>{{ displayDate }}</dd>
            </div>
            <div>
                <dt>{{ t('No') }}</dt>
                <dd>{{ expense.expense_no || '—' }}</dd>
            </div>
            <div>
                <dt>{{ t('Bill number') }}</dt>
                <dd>{{ expense.reference_no || '—' }}</dd>
            </div>
            <div>
                <dt>{{ t('Category') }}</dt>
                <dd>{{ expense.category?.name || t('General') }}</dd>
            </div>
            <div>
                <dt>{{ t('Paying type') }}</dt>
                <dd>{{ methodLabel }}</dd>
            </div>
            <div v-if="projectName">
                <dt>{{ t('Project') }}</dt>
                <dd>{{ projectName }}</dd>
            </div>
            <div class="span-2">
                <dt>{{ t('Description') }}</dt>
                <dd>{{ expense.description || '—' }}</dd>
            </div>
        </dl>

        <p class="voucher-words">{{ words }}</p>

        <div class="voucher-signs">
            <div>
                <div class="sign-line" />
                <p>{{ t('Prepared by') }}</p>
            </div>
            <div>
                <div class="sign-line" />
                <p>{{ t('Checked by') }}</p>
            </div>
            <div>
                <div class="sign-line" />
                <p>{{ t('Received by') }}</p>
            </div>
        </div>

        <MisPrintFooter />
    </article>
</template>
