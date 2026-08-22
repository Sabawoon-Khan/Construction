<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Printer } from '@lucide/vue';
import Can from '@/components/Can.vue';
import { computed } from 'vue';
import SortableTh from '@/components/SortableTh.vue';
import TableFrame from '@/components/TableFrame.vue';
import { Button } from '@/components/ui/button';
import { useCalendar } from '@/composables/useCalendar';
import { useCurrency } from '@/composables/useCurrency';
import { useTableSort } from '@/composables/useTableSort';
import { useTranslations } from '@/composables/useTranslations';
import { formatNumber, money } from '@/lib/utils';

const props = defineProps<{
    item: Record<string, any>;
    shareholders: {
        id: number;
        name: string;
        ownership_percentage: number;
        contributed_total: number;
        entitled_share: number;
        paid_total: number;
        balance_owed: number;
    }[];
    contributions: {
        id: number;
        partner_id: number;
        amount: number;
        currency?: string;
        invested_at: string;
        partner?: { id: number; name: string };
    }[];
}>();

const emit = defineEmits<{
    'add-shareholder': [];
    'record-contribution': [];
    'record-payout': [];
}>();

const { t } = useTranslations();
const tableColumnsShareholders = computed(() => [
    { key: 'name', label: t('Name') },
    { key: 'ownership_percentage', label: t('Share') },
    { key: 'contributed_total', label: t('Money in') },
    { key: 'entitled_share', label: t('Profit share') },
    { key: 'paid_total', label: t('Paid out') },
    { key: 'balance_owed', label: t('Still owed') },
]);
const tableColumnsContributions = computed(() => [
    { key: 'invested_at', label: t('Date') },
    { key: 'partner.name', label: t('Shareholder') },
    { key: 'amount', label: t('Amount') },
    { key: 'actions', label: t('Actions'), locked: true },
]);
const shareholderSort = useTableSort(() => props.shareholders ?? []);
const contributionSort = useTableSort(() => props.contributions ?? []);
const { baseCurrency } = useCurrency();
const calendar = useCalendar();
const formatDate = (value: string | null | undefined) =>
    calendar.format(value) || '—';
</script>

<template>
    <div class="space-y-4">
        <div class="overflow-hidden rounded-xl border bg-card shadow-sm">
            <div
                class="flex flex-wrap items-center justify-between gap-3 border-b px-5 py-4"
            >
                <div>
                    <h3 class="text-sm font-semibold">
                        {{ t('Shareholders') }}
                    </h3>
                    <p class="mt-0.5 text-sm text-muted-foreground">
                        {{ t('Ownership, contributions and payouts') }}
                    </p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <Can permission="partners.create">
                        <Button
                            v-if="shareholders.length"
                            size="sm"
                            variant="outline"
                            @click="emit('record-contribution')"
                        >
                            {{ t('Record money in') }}
                        </Button>
                        <Button
                            v-if="shareholders.length"
                            size="sm"
                            variant="outline"
                            @click="emit('record-payout')"
                        >
                            {{ t('Record payout') }}
                        </Button>
                        <Button
                            size="sm"
                            @click="emit('add-shareholder')"
                        >
                            {{ t('Add shareholder') }}
                        </Button>
                    </Can>
                </div>
            </div>

            <TableFrame table-id="project-shareholders" :columns="tableColumnsShareholders">
                <table class="min-w-full text-sm">
                    <thead class="bg-muted/40 text-start text-muted-foreground">
                        <tr>
                            <SortableTh
                                column="name"
                                :sort-key="shareholderSort.sortKey"
                                :sort-dir="shareholderSort.sortDir"
                                @sort="shareholderSort.sortBy"
                            >
                                {{ t('Name') }}
                            </SortableTh>
                            <SortableTh
                                column="ownership_percentage"
                                :sort-key="shareholderSort.sortKey"
                                :sort-dir="shareholderSort.sortDir"
                                @sort="shareholderSort.sortBy"
                            >
                                {{ t('Share') }}
                            </SortableTh>
                            <SortableTh
                                column="contributed_total"
                                :sort-key="shareholderSort.sortKey"
                                :sort-dir="shareholderSort.sortDir"
                                @sort="shareholderSort.sortBy"
                            >
                                {{ t('Money in') }}
                            </SortableTh>
                            <SortableTh
                                column="entitled_share"
                                :sort-key="shareholderSort.sortKey"
                                :sort-dir="shareholderSort.sortDir"
                                @sort="shareholderSort.sortBy"
                            >
                                {{ t('Profit share') }}
                            </SortableTh>
                            <SortableTh
                                column="paid_total"
                                :sort-key="shareholderSort.sortKey"
                                :sort-dir="shareholderSort.sortDir"
                                @sort="shareholderSort.sortBy"
                            >
                                {{ t('Paid out') }}
                            </SortableTh>
                            <SortableTh
                                column="balance_owed"
                                :sort-key="shareholderSort.sortKey"
                                :sort-dir="shareholderSort.sortDir"
                                @sort="shareholderSort.sortBy"
                            >
                                {{ t('Still owed') }}
                            </SortableTh>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="s in shareholderSort.sortedRows"
                            :key="s.id"
                            class="border-t hover:bg-muted/30"
                        >
                            <td class="px-4 py-3 font-medium">
                                {{ s.name }}
                            </td>
                            <td class="px-4 py-3 tabular-nums">
                                {{ formatNumber(s.ownership_percentage) }}%
                            </td>
                            <td class="px-4 py-3 tabular-nums">
                                {{
                                    money(
                                        s.contributed_total,
                                        baseCurrency,
                                    )
                                }}
                            </td>
                            <td class="px-4 py-3 tabular-nums">
                                {{
                                    money(s.entitled_share, baseCurrency)
                                }}
                            </td>
                            <td
                                class="px-4 py-3 text-emerald-700 tabular-nums"
                            >
                                {{ money(s.paid_total, baseCurrency) }}
                            </td>
                            <td
                                class="px-4 py-3 text-amber-700 tabular-nums"
                            >
                                {{ money(s.balance_owed, baseCurrency) }}
                            </td>
                        </tr>
                        <tr v-if="!shareholders.length">
                            <td
                                colspan="6"
                                class="px-4 py-10 text-center text-muted-foreground"
                            >
                                {{ t('No shareholders yet') }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </TableFrame>
        </div>

        <div
            v-if="contributions.length"
            class="overflow-hidden rounded-xl border bg-card shadow-sm"
        >
            <div class="border-b px-5 py-4">
                <h3 class="text-sm font-semibold">
                    {{ t('Contribution history') }}
                </h3>
            </div>
            <TableFrame table-id="project-contributions" :columns="tableColumnsContributions">
                <table class="min-w-full text-sm">
                    <thead
                        class="bg-muted/40 text-start text-muted-foreground"
                    >
                        <tr>
                            <SortableTh
                                column="invested_at"
                                :sort-key="contributionSort.sortKey"
                                :sort-dir="contributionSort.sortDir"
                                @sort="contributionSort.sortBy"
                            >
                                {{ t('Date') }}
                            </SortableTh>
                            <SortableTh
                                column="partner.name"
                                :sort-key="contributionSort.sortKey"
                                :sort-dir="contributionSort.sortDir"
                                @sort="contributionSort.sortBy"
                            >
                                {{ t('Shareholder') }}
                            </SortableTh>
                            <SortableTh
                                column="amount"
                                :sort-key="contributionSort.sortKey"
                                :sort-dir="contributionSort.sortDir"
                                @sort="contributionSort.sortBy"
                            >
                                {{ t('Amount') }}
                            </SortableTh>
                            <th class="px-4 py-3 text-end font-medium">
                                {{ t('Actions') }}
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="c in contributionSort.sortedRows"
                            :key="c.id"
                            class="border-t hover:bg-muted/30"
                        >
                            <td class="px-4 py-3">
                                {{ formatDate(c.invested_at) }}
                            </td>
                            <td class="px-4 py-3 font-medium">
                                {{ c.partner?.name || '—' }}
                            </td>
                            <td class="px-4 py-3 tabular-nums">
                                {{
                                    money(
                                        c.amount,
                                        c.currency || baseCurrency,
                                    )
                                }}
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex justify-end">
                                    <Button
                                        as-child
                                        size="sm"
                                        variant="outline"
                                    >
                                        <Link
                                            :href="`/projects/${item.id}/contributions/${c.id}`"
                                        >
                                            <Printer class="size-4" />
                                            {{ t('Voucher') }}
                                        </Link>
                                    </Button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </TableFrame>
        </div>
    </div>
</template>
