<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Printer } from '@lucide/vue';
import { computed, toValue } from 'vue';
import { MisTableActions, TabPrintHost } from '@/components/mis';
import type { ExportCell } from '@/composables/useTableExport';
import { useTabPrint } from '@/composables/useTabPrint';
import Can from '@/components/Can.vue';
import EmptyState from '@/components/EmptyState.vue';
import SortableTh from '@/components/SortableTh.vue';
import TableFrame from '@/components/TableFrame.vue';
import TableIndexTd from '@/components/TableIndexTd.vue';
import TableIndexTh from '@/components/TableIndexTh.vue';
import { indexTableColumn } from '@/composables/useTableColumns';
import { Button } from '@/components/ui/button';
import { CreateButton } from '@/components/ui/create-button';
import { V2Panel } from '@/components/v2';
import { useCalendar } from '@/composables/useCalendar';
import { useCurrency } from '@/composables/useCurrency';
import { useTableSort } from '@/composables/useTableSort';
import { useTranslations } from '@/composables/useTranslations';
import { formatNumber, money } from '@/lib/utils';

const props = withDefaults(
    defineProps<{
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
            created_at?: string;
            partner?: { id: number; name: string };
        }[];
        showOwnership?: boolean;
        showContributions?: boolean;
    }>(),
    {
        showOwnership: true,
        showContributions: true,
    },
);

const emit = defineEmits<{
    'add-shareholder': [];
    'record-contribution': [];
    'record-payout': [];
}>();

const { t } = useTranslations();
const tableColumnsShareholders = computed(() => [
    indexTableColumn(),
    { key: 'name', label: t('Name') },
    { key: 'ownership_percentage', label: t('Share') },
    { key: 'contributed_total', label: t('Money in') },
    { key: 'entitled_share', label: t('Profit share') },
    { key: 'paid_total', label: t('Paid out') },
    { key: 'balance_owed', label: t('Still owed') },
]);
const tableColumnsContributions = computed(() => [
    indexTableColumn(),
    { key: 'invested_at', label: t('Date') },
    { key: 'partner.name', label: t('Shareholder') },
    { key: 'amount', label: t('Amount') },
    { key: 'created_at', label: t('Created at') },
    { key: 'actions', label: t('Actions'), locked: true },
]);
const shareholderSort = useTableSort(() => props.shareholders ?? []);
const contributionSort = useTableSort(() => props.contributions ?? []);
const { baseCurrency } = useCurrency();
const calendar = useCalendar();
const formatDate = (value: string | null | undefined) =>
    calendar.format(value) || '—';

const shareholderExportHeaders = computed(() => [
    t('Name'),
    t('Share'),
    t('Money in'),
    t('Profit share'),
    t('Paid out'),
    t('Still owed'),
]);

function mapShareholderExportRow(
    s: (typeof props.shareholders)[number],
): ExportCell[] {
    return [
        s.name,
        s.ownership_percentage,
        s.contributed_total,
        s.entitled_share,
        s.paid_total,
        s.balance_owed,
    ];
}

const shareholderExportRows = computed(() =>
    props.shareholders.map(mapShareholderExportRow),
);
const shareholderFilteredExportRows = computed(() =>
    shareholderSort.sortedRows.map(mapShareholderExportRow),
);

const contributionExportHeaders = computed(() => [
    t('Date'),
    t('Shareholder'),
    t('Amount'),
    t('Created at'),
]);

function mapContributionExportRow(
    c: (typeof props.contributions)[number],
): ExportCell[] {
    return [
        formatDate(c.invested_at),
        c.partner?.name || null,
        Number(c.amount),
        formatDate(c.created_at),
    ];
}

const contributionExportRows = computed(() =>
    props.contributions.map(mapContributionExportRow),
);
const contributionFilteredExportRows = computed(() =>
    contributionSort.sortedRows.map(mapContributionExportRow),
);

const projectSubtitle = () =>
    [props.item.name, props.item.code].filter(Boolean).join(' · ');

const shareholdersPrint = useTabPrint({
    title: () => t('Shareholders'),
    subtitle: projectSubtitle,
    columns: () => [
        t('Name'),
        t('Share'),
        t('Money in'),
        t('Profit share'),
        t('Paid out'),
        t('Still owed'),
    ],
    source: () => toValue(shareholderSort.sortedRows),
    emptyLabel: () => t('No shareholders yet'),
    option: (row) => ({
        key: String(row.id),
        title: row.name,
        subtitle: `${formatNumber(row.ownership_percentage, 2)}%`,
        amount: money(row.contributed_total, baseCurrency.value),
    }),
    cells: (row) => [
        row.name,
        `${formatNumber(row.ownership_percentage, 2)}%`,
        money(row.contributed_total, baseCurrency.value),
        money(row.entitled_share, baseCurrency.value),
        money(row.paid_total, baseCurrency.value),
        money(row.balance_owed, baseCurrency.value),
    ],
});

const contributionsPrint = useTabPrint({
    title: () => t('Money in'),
    subtitle: projectSubtitle,
    columns: () => [t('Date'), t('Shareholder'), t('Amount'), t('Created at')],
    source: () => toValue(contributionSort.sortedRows),
    emptyLabel: () => t('No contributions yet'),
    option: (row) => ({
        key: String(row.id),
        title: row.partner?.name || t('Money in'),
        subtitle: formatDate(row.invested_at),
        amount: money(row.amount, row.currency || baseCurrency.value),
    }),
    cells: (row) => [
        formatDate(row.invested_at),
        row.partner?.name || '—',
        money(row.amount, row.currency || baseCurrency.value),
        formatDate(row.created_at),
    ],
});
</script>

<template>
    <div class="space-y-4">
        <V2Panel
            v-if="showOwnership"
            :padded="false"
            :title="t('Shareholders')"
            :description="t('Ownership, contributions and payouts')"
        >
            <template #actions>
                
            <MisTableActions
                    :filename="`project-${item.id}-shareholders`"
                    :headers="shareholderExportHeaders"
                    :rows="shareholderExportRows"
                    :filtered-rows="shareholderFilteredExportRows"
                    :on-print="shareholdersPrint.openPicker"
                >
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
                    <CreateButton
                        permission="partners.create"
                        @click="emit('add-shareholder')"
                    >
                        {{ t('Add shareholder') }}
                    </CreateButton>
                </Can>
            </MisTableActions>
        
            </template>

            <TableFrame
                table-id="project-shareholders"
                :columns="tableColumnsShareholders"
            >
                <table class="min-w-full text-sm">
                    <thead class="table-chrome-head text-start text-muted-foreground">
                        <tr>
                            <TableIndexTh />
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
                            v-for="(s, index) in shareholderSort.sortedRows"
                            :key="s.id"
                            class="border-t hover:bg-muted/30"
                        >
                            <TableIndexTd :index="index" />
                            <td class="px-4 py-3 font-medium">
                                {{ s.name }}
                            </td>
                            <td class="px-4 py-3 tabular-nums">
                                {{ formatNumber(s.ownership_percentage) }}%
                            </td>
                            <td class="px-4 py-3 tabular-nums">
                                {{
                                    money(s.contributed_total, baseCurrency)
                                }}
                            </td>
                            <td class="px-4 py-3 tabular-nums">
                                {{ money(s.entitled_share, baseCurrency) }}
                            </td>
                            <td class="px-4 py-3 text-emerald-700 tabular-nums">
                                {{ money(s.paid_total, baseCurrency) }}
                            </td>
                            <td class="px-4 py-3 text-amber-700 tabular-nums">
                                {{ money(s.balance_owed, baseCurrency) }}
                            </td>
                        </tr>
                        <EmptyState
                            v-if="!shareholders.length"
                            :colspan="7"
                            :title="t('No shareholders yet')"
                        >
                        </EmptyState>
                    </tbody>
                </table>
            </TableFrame>
        </V2Panel>

        <V2Panel
            v-if="showContributions"
            :padded="false"
            :title="t('Contribution history')"
        >
            <template #actions>
                
            <MisTableActions
                    :filename="`project-${item.id}-contributions`"
                    :headers="contributionExportHeaders"
                    :rows="contributionExportRows"
                    :filtered-rows="contributionFilteredExportRows"
                    :on-print="contributionsPrint.openPicker"
                >
                <Can permission="partners.create">
                    <Button
                        v-if="shareholders.length"
                        size="sm"
                        variant="outline"
                        @click="emit('record-contribution')"
                    >
                        {{ t('Record money in') }}
                    </Button>
                </Can>
            </MisTableActions>
        
            </template>
            <TableFrame
                table-id="project-contributions"
                :columns="tableColumnsContributions"
            >
                <table class="min-w-full text-sm">
                    <thead class="table-chrome-head text-start text-muted-foreground">
                        <tr>
                            <TableIndexTh />
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
                            <SortableTh
                                column="created_at"
                                :sort-key="contributionSort.sortKey"
                                :sort-dir="contributionSort.sortDir"
                                @sort="contributionSort.sortBy"
                            >
                                {{ t('Created at') }}
                            </SortableTh>
                            <th class="px-4 py-3 text-end font-medium">
                                {{ t('Actions') }}
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="(c, index) in contributionSort.sortedRows"
                            :key="c.id"
                            class="border-t hover:bg-muted/30"
                        >
                            <TableIndexTd :index="index" />
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
                            <td
                                class="px-4 py-3 whitespace-nowrap text-muted-foreground"
                            >
                                {{ formatDate(c.created_at) }}
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
                        <EmptyState
                            v-if="!contributions.length"
                            :colspan="6"
                            :title="t('No contributions yet')"
                        >
                        </EmptyState>
                    </tbody>
                </table>
            </TableFrame>
        </V2Panel>

        <TabPrintHost :state="shareholdersPrint" />
        <TabPrintHost :state="contributionsPrint" />
    </div>
</template>
