<script setup lang="ts">
import EmptyState from '@/components/EmptyState.vue';
import IssueStockDialog from '@/components/IssueStockDialog.vue';
import { MisTableActions, MisSearchInput  } from '@/components/mis';
import RowActionsMenu from '@/components/RowActionsMenu.vue';
import SortableTh from '@/components/SortableTh.vue';
import TableIndexTd from '@/components/TableIndexTd.vue';
import TableIndexTh from '@/components/TableIndexTh.vue';
import TableToolbar from '@/components/TableToolbar.vue';
import { CreateButton } from '@/components/ui/create-button';
import {
    V2FilterBar,
    V2Hero,
    V2IndicatorCard,
    V2ListPage,
    V2SelectFilter,
    V2StatCard,
    V2StatGrid,
    V2TablePanel,
} from '@/components/v2';
import { useCalendar } from '@/composables/useCalendar';
import { useMisFilters } from '@/composables/useMisFilters';
import { useMisPage } from '@/composables/useMisPage';
import { provideTableSort } from '@/composables/useTableSort';
import { useTranslations } from '@/composables/useTranslations';
import type { RowActionItem } from '@/lib/row-actions';
import { compactMoney } from '@/lib/v2Money';
import { formatNumber } from '@/lib/utils';
import {
    AlertTriangle,
    Package,
    PackageCheck,
    PackageMinus,
    Pencil,
    Trash2,
    Wallet,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';

type LatestIssue = {
    id: number;
    quantity: number;
    transaction_date?: string | null;
};

type StockRow = {
    project_id: number;
    project_name?: string;
    material_id: number;
    material_name?: string;
    material_code?: string;
    unit_name?: string;
    purchased: number;
    issued: number;
    on_hand: number;
    minimum_stock: number;
    stock_value?: number;
    latest_issue?: LatestIssue | null;
};

type IssueRow = {
    id: number;
    project_id: number;
    project_name?: string;
    material_id: number;
    material_name?: string;
    material_code?: string;
    unit_name?: string;
    quantity: number;
    transaction_date?: string | null;
};

const props = defineProps<{
    items: StockRow[];
    issues: IssueRow[];
    projects: { id: number; name: string }[];
    materials: {
        id: number;
        name: string;
        code: string;
        unit?: { unit_name: string } | null;
    }[];
    filters: { project_id?: number | null; search?: string };
    totals: {
        rows: number;
        on_hand_rows: number;
        low_stock_rows: number;
        empty_rows?: number;
        purchased_qty?: number;
        issued_qty?: number;
        on_hand_qty?: number;
        stock_value?: number;
        purchase_total?: number;
        issues_count?: number;
    };
    volumeChart?: Array<{ key: string; label: string; value: number }>;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Stock', href: '/inventory' },
        ],
    },
});

const onlyKeys = [
    'items',
    'issues',
    'filters',
    'totals',
    'projects',
    'materials',
    'volumeChart',
];

const { t } = useTranslations();
const { can } = useMisPage();
const calendar = useCalendar();
const formatDate = (value: string | null | undefined) => calendar.format(value) || '—';

const { filters, pending, apply, clear } = useMisFilters(
    '/inventory',
    {
        search: props.filters.search ?? '',
        project_id: props.filters.project_id
            ? String(props.filters.project_id)
            : '',
    },
    { search: '', project_id: '' },
    { only: onlyKeys, liveKeys: ['search'] },
);

const showActions = computed(
    () =>
        can('inventory.create') ||
        can('inventory.edit') ||
        can('inventory.delete'),
);

const tableColumns = computed(() => {
    const columns: { key: string; label: string; locked?: boolean }[] = [
        { key: 'project_name', label: t('Project') },
        { key: 'material_name', label: t('Material') },
        { key: 'unit_name', label: t('Unit') },
        { key: 'purchased', label: t('Purchased') },
        { key: 'issued', label: t('Issued') },
        { key: 'on_hand', label: t('On hand') },
    ];
    if (showActions.value) {
        columns.push({ key: 'actions', label: t('Actions'), locked: true });
    }
    return columns;
});

const issueTableColumns = computed(() => {
    const columns: { key: string; label: string; locked?: boolean }[] = [
        { key: 'project_name', label: t('Project') },
        { key: 'material_name', label: t('Material') },
        { key: 'quantity', label: t('Issued qty') },
        { key: 'transaction_date', label: t('Issue date') },
    ];
    if (can('inventory.edit') || can('inventory.delete')) {
        columns.push({ key: 'actions', label: t('Actions'), locked: true });
    }
    return columns;
});

const { sortedRows } = provideTableSort(() => props.items);
const { sortedRows: sortedIssues } = provideTableSort(() => props.issues);
const dialogOpen = ref(false);
const dialogPrefill = ref<{ project_id?: number; material_id?: number } | null>(
    null,
);
const editingIssue = ref<
    (LatestIssue & { project_id: number; material_id: number }) | null
>(null);

const hasIssueableStock = computed(() =>
    props.items.some((row) => row.on_hand > 0),
);

const stockMix = computed(() => {
    const onHand = Number(props.totals.on_hand_rows ?? 0);
    const low = Number(props.totals.low_stock_rows ?? 0);
    const empty = Number(props.totals.empty_rows ?? 0);
    const total = Math.max(
        onHand + empty,
        Number(props.totals.rows ?? 0),
        0,
    );
    const healthy = Math.max(0, onHand - low);
    const lowShare = total > 0 ? Math.round((low / total) * 100) : 0;

    const segments = [
        { key: 'ok', label: t('On hand'), value: healthy, color: 'var(--brand-accent)' },
        { key: 'low', label: t('Low stock'), value: low, color: 'var(--school-gold)' },
        { key: 'empty', label: t('Empty'), value: empty, color: 'var(--muted-foreground)' },
    ].map((seg) => ({
        ...seg,
        width:
            total > 0
                ? Math.max(seg.value > 0 ? 6 : 0, (seg.value / total) * 100)
                : 0,
    }));

    return { onHand, lowShare, segments };
});

const volumeBars = computed(() => {
    const rows = props.volumeChart ?? [];
    const source =
        rows.length > 0
            ? rows
            : Array.from({ length: 6 }, (_, index) => ({
                  key: `m-${index}`,
                  label: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'][index],
                  value: 0,
              }));
    const max = Math.max(...source.map((row) => Number(row.value) || 0), 1);

    return source.map((row) => {
        const value = Number(row.value) || 0;
        return {
            key: row.key,
            label: row.label,
            value,
            height: Math.max(value > 0 ? 6 : 3, Math.round((value / max) * 44)),
            peak: value === max && value > 0,
        };
    });
});

function onProjectChange(value: string) {
    apply({ project_id: value });
}

function openIssueDialog(row?: StockRow) {
    editingIssue.value = null;
    dialogPrefill.value = row
        ? { project_id: row.project_id, material_id: row.material_id }
        : null;
    dialogOpen.value = true;
}

function openEditDialog(
    issue: {
        id: number;
        quantity: number;
        transaction_date?: string | null;
        project_id?: number;
        material_id?: number;
    },
    row?: Pick<StockRow, 'project_id' | 'material_id'>,
) {
    editingIssue.value = {
        id: issue.id,
        project_id: row?.project_id ?? issue.project_id ?? 0,
        material_id: row?.material_id ?? issue.material_id ?? 0,
        quantity: issue.quantity,
        transaction_date: issue.transaction_date,
    };
    dialogPrefill.value = {
        project_id: editingIssue.value.project_id,
        material_id: editingIssue.value.material_id,
    };
    dialogOpen.value = true;
}

function isLowStock(row: StockRow) {
    return (
        row.minimum_stock > 0 &&
        row.on_hand > 0 &&
        row.on_hand <= row.minimum_stock
    );
}

function stockRowActions(row: StockRow): RowActionItem[] {
    return [
        {
            label: t('Issue from stock'),
            icon: PackageMinus,
            onClick: () => openIssueDialog(row),
            hidden: !can('inventory.create') || row.on_hand <= 0,
        },
        {
            label: t('Edit'),
            icon: Pencil,
            onClick: () => openEditDialog(row.latest_issue!, row),
            hidden: !can('inventory.edit') || !row.latest_issue,
        },
        {
            separator: true,
            isLabel: true,
            label: t('Danger'),
            hidden: !can('inventory.delete') || !row.latest_issue,
        },
        {
            label: t('Delete'),
            icon: Trash2,
            method: 'delete',
            href: row.latest_issue ? `/inventory/${row.latest_issue.id}` : '#',
            variant: 'destructive' as const,
            hidden: !can('inventory.delete') || !row.latest_issue,
            confirm: {
                title: t('Delete stock issue'),
                description: t(
                    'Delete this stock issue and restore the quantity on hand?',
                ),
                confirmLabel: t('Delete'),
            },
        },
    ];
}

function issueRowActions(issue: IssueRow): RowActionItem[] {
    return [
        {
            label: t('Edit'),
            icon: Pencil,
            onClick: () => openEditDialog(issue),
            hidden: !can('inventory.edit'),
        },
        {
            separator: true,
            isLabel: true,
            label: t('Danger'),
            hidden: !can('inventory.delete'),
        },
        {
            label: t('Delete'),
            icon: Trash2,
            method: 'delete',
            href: `/inventory/${issue.id}`,
            variant: 'destructive' as const,
            hidden: !can('inventory.delete'),
            confirm: {
                title: t('Delete stock issue'),
                description: t(
                    'Delete this stock issue and restore the quantity on hand?',
                ),
                confirmLabel: t('Delete'),
            },
        },
    ];
}
</script>

<template>
    <Head :title="t('Stock')" />

    <V2ListPage>
        <V2Hero image="/images/dashboard-v2-building.png">
            <template #eyebrow>{{ t('Materials') }}</template>
            <template #title>{{ t('Stock') }}</template>
            <template #description>
                {{
                    t(
                        'On-hand quantities, low stock alerts, and issue history by project.',
                    )
                }}
            </template>

            <template #side>
                <div class="hero-cards">
                    <V2IndicatorCard card-class="inventory-card">
                        <template #head>{{ t('Stock health') }}</template>
                        <template #meta
                            >{{ stockMix.lowShare }}% {{ t('Low stock') }}</template
                        >

                        <div class="inventory-hero">
                            <div class="inventory-hero-copy">
                                <strong>{{ stockMix.onHand }}</strong>
                                <small>{{ t('With quantity on hand') }}</small>
                            </div>
                        </div>

                        <div class="inventory-bar" aria-hidden="true">
                            <i
                                v-for="seg in stockMix.segments"
                                :key="seg.key"
                                :style="{
                                    width: `${seg.width}%`,
                                    background: seg.color,
                                }"
                            />
                        </div>

                        <ul class="indicator-list compact">
                            <li
                                v-for="seg in stockMix.segments"
                                :key="seg.key"
                            >
                                <i :style="{ background: seg.color }" />
                                <span>{{ seg.label }}</span>
                                <b>{{ seg.value }}</b>
                            </li>
                        </ul>
                    </V2IndicatorCard>

                    <V2IndicatorCard card-class="money-card">
                        <template #head>{{ t('Issues by month') }}</template>
                        <template #meta
                            >{{ totals.issues_count ?? 0 }} {{ t('Total') }}</template
                        >

                        <div class="money-chart">
                            <div
                                v-for="bar in volumeBars"
                                :key="bar.key"
                                class="money-col"
                                :class="{ peak: bar.peak }"
                                :title="`${bar.label}: ${bar.value}`">
                                <div class="money-pair">
                                    <i
                                        class="usd"
                                        :style="{ height: `${bar.height}px` }"
                                    />
                                </div>
                                <span>{{ bar.label }}</span>
                            </div>
                        </div>
                    </V2IndicatorCard>
                </div>
            </template>

            <template #stats>
                <V2StatGrid>
                    <V2StatCard
                        :delay="0"
                        :title="t('Stock rows')"
                        :value="totals.rows"
                    >
                        <template #icon><Package /></template>
                    </V2StatCard>
                    <V2StatCard
                        :delay="1"
                        icon-tone="teal"
                        :title="t('With quantity on hand')"
                        :value="totals.on_hand_rows"
                    >
                        <template #icon><PackageCheck /></template>
                    </V2StatCard>
                    <V2StatCard
                        :delay="2"
                        icon-tone="orange"
                        :title="t('Low stock')"
                        :value="totals.low_stock_rows"
                    >
                        <template #icon><AlertTriangle /></template>
                    </V2StatCard>
                    <V2StatCard
                        :delay="3"
                        accent
                        icon-tone="warm"
                        :title="t('Stock value')">
                        <template #icon><Wallet /></template>
                        <span>{{ t('Stock value') }}</span>
                        <strong>{{ compactMoney(totals.stock_value) }}</strong>
                    </V2StatCard>
                </V2StatGrid>
            </template>
        </V2Hero>

        <V2TablePanel
            table-id="inventory"
            :columns="tableColumns"
            :pending="pending && items.length > 0"
            :print-title="t('Stock')"
        >
            <template #filters>
                <V2FilterBar>
                    <div class="filter-search">
                        <MisSearchInput
                            v-model="filters.search"
                            :placeholder="t('Search...')"
                            @submit="apply()"
                            @clear="clear"
                        />
                    </div>
                    <V2SelectFilter
                        :label="t('Project')"
                        :model-value="filters.project_id || ''"
                        @change="onProjectChange"
                    >
                        <option value="">{{ t('All projects') }}</option>
                        <option
                            v-for="project in projects"
                            :key="project.id"
                            :value="String(project.id)"
                        >
                            {{ project.name }}
                        </option>
                    </V2SelectFilter>
                    <template #columns>
                        <MisTableActions export-url="/inventory/export" :filters="filters">
                            <CreateButton
                                permission="inventory.create"
                                :disabled="!hasIssueableStock"
                                @click="openIssueDialog()"
                            >
                                {{ t('Issue from stock') }}
                            </CreateButton>
                        </MisTableActions>
                        <TableToolbar />
                    </template>
                </V2FilterBar>
            </template>

            <template #default="{ visibleColCount }">
                <table>
                    <thead>
                        <tr>
                            <TableIndexTh />
                            <SortableTh column="project_name">{{
                                t('Project')
                            }}</SortableTh>
                            <SortableTh column="material_name">{{
                                t('Material')
                            }}</SortableTh>
                            <SortableTh column="unit_name">{{
                                t('Unit')
                            }}</SortableTh>
                            <SortableTh column="purchased">{{
                                t('Purchased')
                            }}</SortableTh>
                            <SortableTh column="issued">{{
                                t('Issued')
                            }}</SortableTh>
                            <SortableTh column="on_hand">{{
                                t('On hand')
                            }}</SortableTh>
                            <th v-if="showActions" class="end">
                                {{ t('Actions') }}
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="(row, index) in sortedRows"
                            :key="`${row.project_id}-${row.material_id}-${index}`"
                            :style="{ '--i': index }"
                            :class="{ 'bg-amber-500/5': isLowStock(row) }"
                        >
                            <TableIndexTd :index="index" />
                            <td>
                                <Link
                                    :href="`/projects/${row.project_id}`"
                                    class="hover:underline"
                                >
                                    {{ row.project_name }}
                                </Link>
                            </td>
                            <td>
                                <div class="name-cell">
                                    <b>{{ row.material_name || '—' }}</b>
                                    <small v-if="row.material_code">{{
                                        row.material_code
                                    }}</small>
                                </div>
                            </td>
                            <td>{{ row.unit_name || '—' }}</td>
                            <td class="nums">
                                {{ formatNumber(row.purchased, 3) }}
                                <span v-if="row.unit_name" class="muted">{{
                                    row.unit_name
                                }}</span>
                            </td>
                            <td class="nums">
                                {{ formatNumber(row.issued, 3) }}
                                <span v-if="row.unit_name" class="muted">{{
                                    row.unit_name
                                }}</span>
                            </td>
                            <td class="nums">
                                <span
                                    :class="{
                                        muted: row.on_hand <= 0,
                                        'text-amber-700 dark:text-amber-400':
                                            isLowStock(row),
                                    }"
                                >
                                    {{ formatNumber(row.on_hand, 3) }}
                                </span>
                                <span v-if="row.unit_name" class="muted">{{
                                    row.unit_name
                                }}</span>
                                <span v-if="isLowStock(row)" class="pill ms-2">{{
                                    t('Low stock')
                                }}</span>
                            </td>
                            <td v-if="showActions" class="end" @click.stop>
                                <RowActionsMenu :actions="stockRowActions(row)" />
                            </td>
                        </tr>
                        <EmptyState
                            v-if="!items.length"
                            :colspan="visibleColCount"
                            :title="t('No stock yet')">
                        </EmptyState>
                    </tbody>
                </table>
            </template>
        </V2TablePanel>

        <V2TablePanel
            table-id="inventory-issues"
            :columns="issueTableColumns"
            :pending="pending && issues.length > 0"
            :print-title="t('Stock')"
        >
            <template #filters>
                <div class="filter-panel">
                    <div class="toolbar">
                        <h3 class="text-sm font-semibold">
                            {{ t('Stock issue dates') }}
                        </h3>
                    </div>
                </div>
            </template>

            <template #default="{ visibleColCount }">
                <table>
                    <thead>
                        <tr>
                            <TableIndexTh />
                            <SortableTh column="project_name">{{
                                t('Project')
                            }}</SortableTh>
                            <SortableTh column="material_name">{{
                                t('Material')
                            }}</SortableTh>
                            <SortableTh column="quantity">{{
                                t('Issued qty')
                            }}</SortableTh>
                            <SortableTh column="transaction_date">{{
                                t('Issue date')
                            }}</SortableTh>
                            <th
                                v-if="
                                    can('inventory.edit') ||
                                    can('inventory.delete')
                                "
                                class="end"
                            >
                                {{ t('Actions') }}
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="(issue, index) in sortedIssues"
                            :key="issue.id"
                            :style="{ '--i': index }"
                        >
                            <TableIndexTd :index="index" />
                            <td>{{ issue.project_name || '—' }}</td>
                            <td>
                                <div class="name-cell">
                                    <b>{{ issue.material_name || '—' }}</b>
                                    <small v-if="issue.material_code">{{
                                        issue.material_code
                                    }}</small>
                                </div>
                            </td>
                            <td class="nums">
                                {{ formatNumber(issue.quantity, 3) }}
                                <span v-if="issue.unit_name" class="muted">{{
                                    issue.unit_name
                                }}</span>
                            </td>
                            <td class="muted nowrap">
                                {{ formatDate(issue.transaction_date) }}
                            </td>
                            <td
                                v-if="
                                    can('inventory.edit') ||
                                    can('inventory.delete')
                                "
                                class="end"
                            >
                                <RowActionsMenu
                                    :actions="issueRowActions(issue)"
                                />
                            </td>
                        </tr>
                        <EmptyState
                            v-if="!issues.length"
                            :colspan="visibleColCount"
                            :title="t('No issue dates yet')"
                        />
                    </tbody>
                </table>
            </template>
        </V2TablePanel>

        <IssueStockDialog
            v-model:open="dialogOpen"
            :projects="projects"
            :materials="materials"
            :stock-rows="items"
            :prefill="dialogPrefill"
            :editing="editingIssue"
        />
    </V2ListPage>
</template>
