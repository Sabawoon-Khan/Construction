<script setup lang="ts">
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { useCalendar } from '@/composables/useCalendar';
import { useTranslations } from '@/composables/useTranslations';
import { amountInWords } from '@/lib/voucher';
import { formatNumber, money } from '@/lib/utils';

type PublicCompany = {
    name?: string | null;
    name_primary?: string | null;
    name_en?: string | null;
    phone?: string | null;
    email?: string | null;
    address?: string | null;
};

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
        item_amount?: number | string | null;
        per_price?: number | string | null;
        category?: { name?: string } | null;
        status?: string | null;
    };
    projectName?: string | null;
    projectCode?: string | null;
}>();

const page = usePage();
const { t } = useTranslations();
const calendar = useCalendar();

const publicCompany = computed(
    () => (page.props.publicCompany as PublicCompany | null) ?? null,
);
const locale = computed(() => (page.props.locale as string) || 'en');
const isEnglish = computed(() => locale.value === 'en');

const brandPrimary = computed(() => {
    if (isEnglish.value) {
        return (
            publicCompany.value?.name_en?.trim() ||
            (page.props.name as string | undefined) ||
            ''
        );
    }

    return (
        publicCompany.value?.name_primary?.trim() ||
        (page.props.name as string | undefined) ||
        ''
    );
});

const brandSecondary = computed(() => {
    if (isEnglish.value) {
        return null;
    }

    return publicCompany.value?.name_en?.trim() || null;
});

const logo = computed(
    () => (page.props.logo as string | null) ?? '/favicon.png',
);

const contactParts = computed(() =>
    [
        publicCompany.value?.phone?.trim(),
        publicCompany.value?.address?.trim(),
        publicCompany.value?.email?.trim(),
    ].filter(Boolean),
);

const dateRaw = computed(() => {
    const raw = props.expense.expense_date;
    if (!raw) {
        return null;
    }

    return typeof raw === 'string' ? raw.slice(0, 10) : raw;
});

const displayDate = computed(() => calendar.formatLong(dateRaw.value) || '—');

const words = computed(() =>
    amountInWords(props.expense.amount, props.expense.currency || 'USD'),
);

const methodLabel = computed(() =>
    props.expense.payment_method === 'bank' ? t('Bank') : t('Cash'),
);

const amountLabel = computed(() =>
    money(props.expense.amount, props.expense.currency),
);

const hasQtyPrice = computed(() => {
    const qty = Number(props.expense.item_amount);
    const price = Number(props.expense.per_price);

    return Number.isFinite(qty) && qty > 0 && Number.isFinite(price) && price > 0;
});

const qtyLabel = computed(() =>
    hasQtyPrice.value
        ? formatNumber(Number(props.expense.item_amount), 0)
        : '—',
);

const priceLabel = computed(() =>
    hasQtyPrice.value
        ? money(Number(props.expense.per_price), props.expense.currency)
        : '—',
);

const categoryLabel = computed(
    () => props.expense.category?.name?.trim() || t('General'),
);

const projectLabel = computed(() =>
    [props.projectName, props.projectCode].filter(Boolean).join(' · ') || null,
);

const itemTitle = computed(
    () => props.expense.description?.trim() || t('Expense'),
);
</script>

<template>
    <article class="ev-sheet a4-print-sheet expense-voucher-sheet expense-a4-sheet">
        <header class="ev-header">
            <div class="ev-brand-block">
                <p class="ev-doc-label">{{ t('Expense voucher') }}</p>
                <div class="ev-brand">
                    <img
                        v-if="logo"
                        :src="logo"
                        alt=""
                        class="ev-logo"
                    />
                    <div>
                        <p class="ev-brand-name">{{ brandPrimary }}</p>
                        <p v-if="brandSecondary" class="ev-brand-sub">
                            {{ brandSecondary }}
                        </p>
                    </div>
                </div>
                <p v-if="contactParts.length" class="ev-company-contact">
                    {{ contactParts.join(' · ') }}
                </p>
            </div>

            <div class="ev-meta-card">
                <div>
                    <span>{{ t('No') }}</span>
                    <strong>{{ expense.expense_no || '—' }}</strong>
                </div>
                <div>
                    <span>{{ t('Date') }}</span>
                    <strong>{{ displayDate }}</strong>
                </div>
            </div>
        </header>

        <section class="ev-parties">
            <div>
                <p class="ev-kicker">{{ t('Paid to') }}</p>
                <p class="ev-party-name">{{ expense.paid_to || '—' }}</p>
                <p v-if="projectLabel" class="ev-party-sub">
                    {{ t('Project') }}: {{ projectLabel }}
                </p>
                <p v-if="expense.reference_no" class="ev-party-sub">
                    {{ t('Bill number') }}: {{ expense.reference_no }}
                </p>
            </div>
            <div>
                <p class="ev-kicker">{{ t('Paying type') }}</p>
                <p class="ev-party-name">{{ methodLabel }}</p>
                <p class="ev-party-sub">
                    {{ t('Category') }}: {{ categoryLabel }}
                </p>
            </div>
        </section>

        <table class="ev-table">
            <thead>
                <tr>
                    <th class="ev-col-num">#</th>
                    <th>{{ t('Description') }}</th>
                    <th class="ev-col-cat">{{ t('Category') }}</th>
                    <th v-if="hasQtyPrice" class="ev-col-numish">{{ t('Qty') }}</th>
                    <th v-if="hasQtyPrice" class="ev-col-numish">
                        {{ t('Unit cost') }}
                    </th>
                    <th class="ev-col-amt">{{ t('Amount') }}</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="ev-col-num">1</td>
                    <td>{{ itemTitle }}</td>
                    <td class="ev-col-cat">{{ categoryLabel }}</td>
                    <td v-if="hasQtyPrice" class="ev-col-numish">
                        {{ qtyLabel }}
                    </td>
                    <td v-if="hasQtyPrice" class="ev-col-numish">
                        {{ priceLabel }}
                    </td>
                    <td class="ev-col-amt">{{ amountLabel }}</td>
                </tr>
            </tbody>
        </table>

        <section class="ev-summary">
            <div class="ev-words-block">
                <p class="ev-kicker">{{ t('Amount in words') }}</p>
                <p class="ev-words">{{ words }}</p>
            </div>
            <dl class="ev-totals">
                <div class="ev-total-row">
                    <dt>{{ t('Total') }}</dt>
                    <dd>{{ amountLabel }}</dd>
                </div>
            </dl>
        </section>

        <section class="ev-signs">
            <div>
                <div class="ev-sign-line" />
                <p>{{ t('Prepared by') }}</p>
            </div>
            <div>
                <div class="ev-sign-line" />
                <p>{{ t('Checked by') }}</p>
            </div>
            <div>
                <div class="ev-sign-line" />
                <p>{{ t('Received by') }}</p>
            </div>
        </section>

        <footer v-if="contactParts.length" class="ev-footer">
            {{ contactParts.join('  |  ') }}
        </footer>
    </article>
</template>
