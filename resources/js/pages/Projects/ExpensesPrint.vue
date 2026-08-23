<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { ArrowLeft, Printer } from '@lucide/vue';
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import { useCalendar } from '@/composables/useCalendar';
import { useTranslations } from '@/composables/useTranslations';
import { formatNumber, money, moneyByCurrency } from '@/lib/utils';
import { printPage } from '@/lib/voucher';

const props = defineProps<{
    item: { id: number; name: string; code: string; location?: string | null };
    category?: { id: number | null; name: string } | null;
    expenses: {
        id: number;
        expense_no: string;
        expense_date: string;
        paid_to?: string | null;
        description: string;
        amount: number;
        currency?: string;
        reference_no?: string | null;
        payment_method?: string | null;
        category?: { id: number; name: string } | null;
    }[];
    summary: {
        count: number;
        total: number;
        by_currency: Record<string, number>;
    };
}>();

const page = usePage();
const { t } = useTranslations();
const calendar = useCalendar();
const formatDate = (value: string | null | undefined) =>
    calendar.format(value) || '—';

const company = computed(
    () =>
        page.props.company as
            | { name?: string; legal_name?: string; address?: string; phone?: string }
            | undefined,
);
const companyName = computed(
    () => company.value?.legal_name || company.value?.name || page.props.name || t('Company'),
);
const printedAt = computed(() => calendar.formatDateTime(new Date()) || '—');

const title = computed(() =>
    props.category
        ? t('Expenses — :category', { category: props.category.name })
        : t('Project expenses'),
);

const groupedExpenses = computed(() => {
    if (props.category) {
        return [
            {
                key: String(props.category.id ?? 'uncategorized'),
                name: props.category.name,
                rows: props.expenses,
            },
        ];
    }

    const groups = new Map<
        string,
        { key: string; name: string; rows: typeof props.expenses }
    >();

    for (const expense of props.expenses) {
        const key = expense.category?.id
            ? String(expense.category.id)
            : 'uncategorized';
        const name = expense.category?.name || t('General');

        if (!groups.has(key)) {
            groups.set(key, { key, name, rows: [] });
        }

        groups.get(key)!.rows.push(expense);
    }

    return [...groups.values()].sort((a, b) => a.name.localeCompare(b.name));
});

function groupTotal(rows: typeof props.expenses) {
    const byCurrency: Record<string, number> = {};

    for (const row of rows) {
        const code = row.currency || 'USD';
        byCurrency[code] = (byCurrency[code] ?? 0) + Number(row.amount ?? 0);
    }

    return byCurrency;
}

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Projects', href: '/projects' },
            { title: 'Expenses', href: '#' },
        ],
    },
});
</script>

<template>
    <Head :title="`${item.name} — ${title}`" />

    <div
        class="print-document mx-auto flex h-full w-full max-w-4xl flex-1 flex-col gap-5 p-4 md:p-6 print:gap-0 print:p-0"
    >
        <div
            class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between print:hidden"
        >
            <div class="space-y-2">
                <Button as-child variant="ghost" size="sm" class="-ml-2 w-fit">
                    <Link :href="`/projects/${item.id}#expenses`">
                        <ArrowLeft class="size-4" /> {{ t('Back') }}
                    </Link>
                </Button>
                <div>
                    <h1 class="text-xl font-semibold tracking-tight">{{ title }}</h1>
                    <p class="text-sm text-muted-foreground">
                        {{ item.name }} · {{ item.code }}
                    </p>
                </div>
            </div>
            <Button variant="outline" @click="printPage">
                <Printer class="size-4" /> {{ t('Print') }}
            </Button>
        </div>

        <article
            class="voucher-sheet overflow-hidden rounded-xl border bg-white text-neutral-900 shadow-sm print:rounded-none print:border-0 print:shadow-none"
        >
            <header class="border-b border-neutral-200 px-8 py-6 text-center print:px-0 print:py-5">
                <p class="text-[11px] font-medium tracking-[0.14em] text-neutral-400 uppercase">
                    {{ t('Expense report') }}
                </p>
                <h2 class="mt-2 text-xl font-semibold tracking-tight">{{ companyName }}</h2>
                <p
                    v-if="company?.address || company?.phone"
                    class="mt-1.5 text-sm text-neutral-500"
                >
                    <span v-if="company?.address">{{ company.address }}</span>
                    <template v-if="company?.address && company?.phone"> · </template>
                    <span v-if="company?.phone">{{ company.phone }}</span>
                </p>

                <div class="mt-5 flex flex-wrap items-center justify-center gap-x-4 gap-y-1 text-sm text-neutral-600">
                    <span class="font-medium text-neutral-800">{{ item.name }}</span>
                    <span class="hidden h-3 w-px bg-neutral-200 sm:block" />
                    <span>{{ item.code }}</span>
                    <template v-if="item.location">
                        <span class="hidden h-3 w-px bg-neutral-200 sm:block" />
                        <span>{{ item.location }}</span>
                    </template>
                    <template v-if="category">
                        <span class="hidden h-3 w-px bg-neutral-200 sm:block" />
                        <span class="font-medium text-neutral-800">{{ category.name }}</span>
                    </template>
                </div>

                <div
                    class="mt-4 inline-flex flex-wrap items-center justify-center gap-x-5 gap-y-1 rounded-full border border-neutral-200 px-4 py-2 text-sm"
                >
                    <span>
                        <span class="text-neutral-400">{{ t('Records') }}</span>
                        {{ formatNumber(summary.count, 0) }}
                    </span>
                    <span class="h-3 w-px bg-neutral-200" />
                    <span class="font-semibold tabular-nums">
                        {{ moneyByCurrency(summary.by_currency, summary.total) }}
                    </span>
                </div>
            </header>

            <div class="space-y-7 px-8 py-6 print:space-y-5 print:px-0 print:py-4">
                <section v-for="group in groupedExpenses" :key="group.key">
                    <div
                        v-if="!category"
                        class="mb-3 flex items-baseline justify-between gap-3"
                    >
                        <h3 class="text-sm font-semibold text-neutral-800">
                            {{ group.name }}
                        </h3>
                        <p class="text-xs tabular-nums text-neutral-500">
                            {{ formatNumber(group.rows.length, 0) }} ·
                            {{ moneyByCurrency(groupTotal(group.rows)) }}
                        </p>
                    </div>

                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-neutral-300 text-neutral-500">
                                <th class="pb-2 text-start text-xs font-medium">
                                    {{ t('Date') }}
                                </th>
                                <th class="pb-2 text-start text-xs font-medium">
                                    {{ t('Bill number') }}
                                </th>
                                <th class="pb-2 text-start text-xs font-medium">
                                    {{ t('Paid to') }}
                                </th>
                                <th
                                    v-if="!category"
                                    class="pb-2 text-start text-xs font-medium"
                                >
                                    {{ t('Category') }}
                                </th>
                                <th class="pb-2 text-start text-xs font-medium">
                                    {{ t('Description') }}
                                </th>
                                <th class="pb-2 text-end text-xs font-medium">
                                    {{ t('Amount') }}
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="expense in group.rows"
                                :key="expense.id"
                                class="border-b border-neutral-100"
                            >
                                <td class="py-2.5 whitespace-nowrap">
                                    {{ formatDate(expense.expense_date) }}
                                </td>
                                <td class="py-2.5 tabular-nums">
                                    {{ expense.reference_no || expense.expense_no || '—' }}
                                </td>
                                <td class="py-2.5">{{ expense.paid_to || '—' }}</td>
                                <td v-if="!category" class="py-2.5 text-neutral-600">
                                    {{ expense.category?.name || t('General') }}
                                </td>
                                <td class="max-w-[12rem] py-2.5 text-neutral-700">
                                    {{ expense.description || '—' }}
                                </td>
                                <td class="py-2.5 text-end tabular-nums font-medium">
                                    {{ money(expense.amount, expense.currency) }}
                                </td>
                            </tr>
                        </tbody>
                        <tfoot v-if="group.rows.length > 1">
                            <tr>
                                <td
                                    :colspan="category ? 4 : 5"
                                    class="pt-3 text-end text-xs font-medium text-neutral-500"
                                >
                                    {{ t('Subtotal') }}
                                </td>
                                <td class="pt-3 text-end text-sm font-semibold tabular-nums">
                                    {{ moneyByCurrency(groupTotal(group.rows)) }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </section>

                <p
                    v-if="!expenses.length"
                    class="py-10 text-center text-sm text-neutral-500"
                >
                    {{ t('No expenses yet') }}
                </p>
            </div>

            <footer
                v-if="expenses.length"
                class="border-t border-neutral-200 px-8 py-4 print:px-0"
            >
                <div class="flex items-center justify-between gap-4 text-sm">
                    <span class="text-neutral-500">{{ t('Grand total') }}</span>
                    <span class="text-lg font-semibold tabular-nums">
                        {{ moneyByCurrency(summary.by_currency, summary.total) }}
                    </span>
                </div>
            </footer>

            <div
                class="border-t border-neutral-100 px-8 py-3 text-center text-[11px] text-neutral-400 print:px-0"
            >
                {{ companyName }} · {{ t('Printed') }} {{ printedAt }}
            </div>
        </article>
    </div>
</template>
