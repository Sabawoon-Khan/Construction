<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ChevronLeft, ChevronRight, Printer } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import EmptyState from '@/components/EmptyState.vue';
import SortableTh from '@/components/SortableTh.vue';
import TableFrame from '@/components/TableFrame.vue';
import TableIndexTd from '@/components/TableIndexTd.vue';
import TableIndexTh from '@/components/TableIndexTh.vue';
import { indexTableColumn } from '@/composables/useTableColumns';
import { MisTableActions, MisSearchInput } from '@/components/mis';
import type { ExportCell } from '@/composables/useTableExport';
import { Button } from '@/components/ui/button';
import { CreateButton } from '@/components/ui/create-button';
import { V2Panel } from '@/components/v2';
import { useCalendar } from '@/composables/useCalendar';
import { useCurrency } from '@/composables/useCurrency';
import { provideTableSort } from '@/composables/useTableSort';
import { useTranslations } from '@/composables/useTranslations';
import { formatNumber, money } from '@/lib/utils';

const props = defineProps<{
    item: Record<string, any>;
    contributions: {
        id: number;
        partner_id: number;
        amount: number;
        currency?: string;
        invested_at: string;
        created_at?: string;
        partner?: { id: number; name: string };
    }[];
    hasShareholders?: boolean;
}>();

const emit = defineEmits<{
    'record-contribution': [];
    'add-shareholder': [];
}>();

const { t } = useTranslations();
const { baseCurrency } = useCurrency();
const calendar = useCalendar();
const formatDate = (value: string | null | undefined) =>
    calendar.format(value) || '—';

const search = ref('');
const currentPage = ref(1);
const perPage = 12;

const tableColumns = computed(() => [
    indexTableColumn(),
    { key: 'invested_at', label: t('Date') },
    { key: 'partner.name', label: t('Shareholder') },
    { key: 'amount', label: t('Amount') },
    { key: 'created_at', label: t('Created at') },
    { key: 'actions', label: t('Actions'), locked: true },
]);

const totals = computed(() => ({
    count: props.contributions.length,
}));

const headerSummary = computed(() => {
    return `${formatNumber(totals.value.count, 0)} ${t('records')}`;
});

const filteredRows = computed(() => {
    const q = search.value.trim().toLowerCase();
    if (!q) return props.contributions;
    return props.contributions.filter((row) => {
        const haystack = [
            row.partner?.name,
            row.amount,
            row.currency,
            row.invested_at,
            formatDate(row.invested_at),
            formatDate(row.created_at),
        ]
            .filter((v) => v != null && v !== '')
            .join(' ')
            .toLowerCase();
        return haystack.includes(q);
    });
});

const { sortedRows } = provideTableSort(filteredRows);

const totalPages = computed(() =>
    Math.max(1, Math.ceil(filteredRows.value.length / perPage)),
);

const paginatedRows = computed(() => {
    const start = (currentPage.value - 1) * perPage;
    return sortedRows.value.slice(start, start + perPage);
});

const paginationFrom = computed(() =>
    filteredRows.value.length ? (currentPage.value - 1) * perPage + 1 : 0,
);

const paginationTo = computed(() =>
    Math.min(currentPage.value * perPage, filteredRows.value.length),
);

watch(search, () => {
    currentPage.value = 1;
});

watch(totalPages, (pages) => {
    if (currentPage.value > pages) currentPage.value = pages;
});

function onRecordClick() {
    if (props.hasShareholders) {
        emit('record-contribution');
        return;
    }
    emit('add-shareholder');
}

const moneyInExportHeaders = computed(() => [
    t('Date'),
    t('Shareholder'),
    t('Amount'),
    t('Created at'),
]);

function mapMoneyInExportRow(
    row: (typeof props.contributions)[number],
): ExportCell[] {
    return [
        formatDate(row.invested_at),
        row.partner?.name || null,
        Number(row.amount),
        formatDate(row.created_at),
    ];
}

const moneyInExportRows = computed(() =>
    props.contributions.map(mapMoneyInExportRow),
);
const moneyInFilteredExportRows = computed(() =>
    sortedRows.value.map(mapMoneyInExportRow),
);
</script>

<template>
    <V2Panel :padded="false" class="project-money-panel">
        <template #header>
            <div>
                <h2>{{ t('Money in') }}</h2>
                <p>{{ headerSummary }}</p>
            </div>
        </template>
        <template #actions>
            
            <MisTableActions
                :filename="`project-${item.id}-money-in`"
                :headers="moneyInExportHeaders"
                :rows="moneyInExportRows"
                :filtered-rows="moneyInFilteredExportRows"
                :filters="{ search: search }"
            >
                <CreateButton
                permission="partners.create"
                @click="onRecordClick"
            >
                {{
                    hasShareholders
                        ? t('Record money in')
                        : t('Add shareholder')
                }}
            </CreateButton>
            </MisTableActions>
        
        </template>

        <div class="flex flex-col gap-2 border-b border-border/50 bg-muted/15 px-3 py-2.5 sm:flex-row sm:items-center sm:px-5">
            <MisSearchInput
                v-model="search"
                class="h-9 min-w-0 w-full flex-1 sm:min-w-[12rem] sm:max-w-sm"
                :placeholder="t('Search money in...')"
            />
        </div>

        <TableFrame table-id="project-money-in" :columns="tableColumns">
            <table class="min-w-full text-sm">
                <thead class="table-chrome-head text-start text-muted-foreground">
                    <tr>
                        <TableIndexTh />
                        <SortableTh column="invested_at">{{ t('Date') }}</SortableTh>
                        <SortableTh column="partner.name">{{ t('Shareholder') }}</SortableTh>
                        <SortableTh column="amount">{{ t('Amount') }}</SortableTh>
                        <SortableTh column="created_at">{{ t('Created at') }}</SortableTh>
                        <th class="px-4 py-3 text-end font-medium">{{ t('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="(row, index) in paginatedRows"
                        :key="row.id"
                        class="border-t hover:bg-muted/30"
                    >
                        <TableIndexTd :index="index" :from="paginationFrom" />
                        <td class="px-4 py-3 whitespace-nowrap">
                            {{ formatDate(row.invested_at) }}
                        </td>
                        <td class="px-4 py-3 font-medium">
                            {{ row.partner?.name || '—' }}
                        </td>
                        <td class="px-4 py-3 font-semibold tabular-nums whitespace-nowrap text-emerald-700">
                            {{ money(row.amount, row.currency || baseCurrency) }}
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-muted-foreground">
                            {{ formatDate(row.created_at) }}
                        </td>
                        <td class="px-4 py-3" @click.stop>
                            <div class="flex justify-end">
                                <Button
                                    as-child
                                    size="sm"
                                    variant="outline"
                                    class="h-8 gap-1.5"
                                >
                                    <Link
                                        :href="`/projects/${item.id}/contributions/${row.id}`"
                                    >
                                        <Printer class="size-4" />
                                        {{ t('Print') }}
                                    </Link>
                                </Button>
                            </div>
                        </td>
                    </tr>
                    <EmptyState
                        v-if="!filteredRows.length"
                        :colspan="6"
                        :title="
                            search.trim()
                                ? t('No matching money in')
                                : t('No money in yet')
                        "
                    >
                        </EmptyState>
                </tbody>
            </table>
        </TableFrame>

        <div
            v-if="filteredRows.length > perPage"
            class="flex flex-wrap items-center justify-between gap-3 border-t px-5 py-3"
        >
            <p class="text-xs text-muted-foreground">
                {{
                    t('Showing :from–:to of :total', {
                        from: formatNumber(paginationFrom, 0),
                        to: formatNumber(paginationTo, 0),
                        total: formatNumber(filteredRows.length, 0),
                    })
                }}
            </p>
            <div class="flex items-center gap-1">
                <Button
                    type="button"
                    variant="outline"
                    size="icon"
                    class="size-8"
                    :disabled="currentPage <= 1"
                    @click="currentPage -= 1"
                >
                    <ChevronLeft class="size-4 rtl:rotate-180" />
                </Button>
                <span class="min-w-[4rem] text-center text-xs tabular-nums text-muted-foreground">
                    {{ currentPage }} / {{ totalPages }}
                </span>
                <Button
                    type="button"
                    variant="outline"
                    size="icon"
                    class="size-8"
                    :disabled="currentPage >= totalPages"
                    @click="currentPage += 1"
                >
                    <ChevronRight class="size-4 rtl:rotate-180" />
                </Button>
            </div>
        </div>
    </V2Panel>
</template>
