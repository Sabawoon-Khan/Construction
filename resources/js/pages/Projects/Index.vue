<script setup lang="ts">
import EmptyState from '@/components/EmptyState.vue';
import { MisTableActions, TabPrintHost } from '@/components/mis';
import { useTabPrint } from '@/composables/useTabPrint';
import SortableTh from '@/components/SortableTh.vue';
import TableIndexTd from '@/components/TableIndexTd.vue';
import TableIndexTh from '@/components/TableIndexTh.vue';
import TableToolbar from '@/components/TableToolbar.vue';
import RowActionsMenu from '@/components/RowActionsMenu.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { CreateButton } from '@/components/ui/create-button';
import {
    V2FilterBar,
    V2Hero,
    V2IndicatorCard,
    V2ListPage,
    V2Pager,
    V2SelectFilter,
    V2StatCard,
    V2StatGrid,
    V2TablePanel,
} from '@/components/v2';
import { indexTableColumn } from '@/composables/useTableColumns';
import { translateStatus } from '@/lib/translateStatus';
import { useMisFilters } from '@/composables/useMisFilters';
import { useMisPage } from '@/composables/useMisPage';
import { provideTableSort } from '@/composables/useTableSort';
import { useTranslations } from '@/composables/useTranslations';
import type { RowActionItem } from '@/lib/row-actions';
import { buildMoneyBars, compactMoneyByCurrency } from '@/lib/v2Money';
import { formatNumber, moneyByCurrency } from '@/lib/utils';
import {
    Building2,
    Layers3,
    Users,
    Wallet,
} from '@lucide/vue';
import { computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';

type Item = Record<string, any>;

const props = defineProps<{
    items: { data: Item[]; links: any[]; meta?: any };
    printItems?: Item[];
    filters: { status?: string };
    totals: {
        count: number;
        active: number;
        units: number;
        units_available?: number;
        units_reserved?: number;
        units_sold?: number;
        sales_total: number;
        sales_total_base?: number;
        sales_by_currency?: Record<string, number>;
    };
    moneyChart?: Array<{
        key: string;
        label: string;
        by_currency: { AFN: number; USD: number };
    }>;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Projects', href: '/projects' },
        ],
    },
});

const onlyKeys = ['items', 'filters', 'totals', 'moneyChart'];

const { filters, pending, apply } = useMisFilters(
    '/projects',
    { status: props.filters.status ?? '' },
    { status: '' },
    { only: onlyKeys },
);
const { sortedRows } = provideTableSort(() => props.items.data);
const { t } = useTranslations();
const tableColumns = computed(() => [
    indexTableColumn(),
    { key: 'code', label: t('Code') },
    { key: 'name', label: t('Name') },
    { key: 'location', label: t('Location') },
    { key: 'status', label: t('Status') },
    { key: 'progress_percent', label: t('Progress') },
    { key: 'units_count', label: t('Units') },
    { key: 'creator.name', label: t('Added by') },
    { key: 'created_at', label: t('Created at') },
    { key: 'actions', label: t('Actions'), locked: true },
]);

const { viewAction, editAction, deleteAction, can, formatDate } = useMisPage();

const inventory = computed(() => {
    const available = Number(props.totals.units_available ?? 0);
    const reserved = Number(props.totals.units_reserved ?? 0);
    const sold = Number(props.totals.units_sold ?? 0);
    const total = Math.max(
        available + reserved + sold,
        Number(props.totals.units ?? 0),
        0,
    );
    const soldShare = total > 0 ? Math.round((sold / total) * 100) : 0;

    return {
        available,
        reserved,
        sold,
        total,
        soldShare,
        segments: [
            {
                key: 'available',
                label: t('Available'),
                value: available,
                color: 'var(--brand-accent)',
                width:
                    total > 0
                        ? Math.max(available > 0 ? 6 : 0, (available / total) * 100)
                        : 0,
            },
            {
                key: 'reserved',
                label: t('Reserved'),
                value: reserved,
                color: 'var(--school-gold)',
                width:
                    total > 0
                        ? Math.max(reserved > 0 ? 6 : 0, (reserved / total) * 100)
                        : 0,
            },
            {
                key: 'sold',
                label: t('Sold'),
                value: sold,
                color: 'var(--school-navy)',
                width:
                    total > 0
                        ? Math.max(sold > 0 ? 6 : 0, (sold / total) * 100)
                        : 0,
            },
        ],
    };
});

const statusTone: Record<string, string> = {
    planning: 'var(--school-navy)',
    under_construction: 'var(--school-gold)',
    ready_for_sale: 'var(--brand-accent)',
    sold: 'var(--muted-foreground)',
    completed: 'var(--brand-accent)',
    on_hold: 'var(--destructive, #e51f56)',
};

const moneyBars = computed(() => buildMoneyBars(props.moneyChart));

const projectStatuses = [
    'planning',
    'under_construction',
    'ready_for_sale',
    'sold',
    'completed',
    'on_hold',
];

function statusLabel(status: string): string {
    return translateStatus(status, t);
}

function progressColor(status: string): string {
    return statusTone[status] ?? 'var(--school-gold)';
}

function onStatusChange(value: string) {
    apply({ status: value });
}

function creatorInitial(name?: string | null): string {
    return String(name || '?').charAt(0).toUpperCase();
}

function openProject(item: Item) {
    router.visit(`/projects/${item.id}`);
}

const printSource = computed(() =>
    (props.printItems?.length ? props.printItems : sortedRows.value) as Item[],
);

const projectsPrint = useTabPrint({
    title: () => t('Projects'),
    columns: () => [
        t('Code'),
        t('Name'),
        t('Location'),
        t('Status'),
        t('Progress'),
        t('Units'),
        t('Added by'),
        t('Created at'),
    ],
    source: printSource,
    emptyLabel: () => t('No projects yet'),
    option: (item) => ({
        key: String(item.id),
        title: item.name || t('Project'),
        subtitle: [
            item.code,
            item.location,
            statusLabel(item.status),
            item.units_count != null
                ? `${formatNumber(item.units_count, 0)} ${t('Units')}`
                : null,
        ]
            .filter((value) => value != null && String(value).trim() !== '')
            .join(' · '),
    }),
    cells: (item) => [
        item.code || '—',
        item.name || '—',
        item.location || '—',
        statusLabel(item.status),
        `${formatNumber(item.progress_percent, 0)}%`,
        formatNumber(item.units_count, 0),
        item.creator_name || item.creator?.name || '—',
        formatDate(item.created_at),
    ],
});

function rowActions(item: Item): RowActionItem[] {
    return [
        viewAction(`/projects/${item.id}`),
        editAction(`/projects/${item.id}/edit`, 'projects.edit'),
        ...(can('projects.edit')
            ? [
                  { separator: true, isLabel: true, label: t('Change status') },
                  ...projectStatuses.map((status) => ({
                      label: statusLabel(status),
                      method: 'patch' as const,
                      href: `/projects/${item.id}/status`,
                      data: { status },
                      disabled: item.status === status,
                  })),
              ]
            : []),
        deleteAction(
            {
                href: `/projects/${item.id}`,
                title: t('Delete project'),
                description: t(
                    'Are you sure you want to delete this project? This action cannot be undone.',
                ),
                trashType: 'projects',
            },
            'projects.delete',
        ),
    ];
}
</script>

<template>
    <Head :title="t('Projects')" />

    <V2ListPage>
        <V2Hero image="/images/projects-v2-hero-luxury.png">
            <template #eyebrow>{{ t('Work areas') }}</template>
            <template #title>{{ t('Projects') }}</template>
            <template #description>
                {{
                    t(
                        'Track every site from planning to handover — progress, units, and sales in one place.',
                    )
                }}
            </template>

            <template #side>
                <div class="hero-cards">
                    <V2IndicatorCard card-class="inventory-card">
                        <template #head>{{ t('Inventory') }}</template>
                        <template #meta
                            >{{ inventory.soldShare }}% {{ t('Sold') }}</template
                        >

                        <div class="inventory-hero">
                            <div class="inventory-hero-copy">
                                <strong>{{ inventory.available }}</strong>
                                <small>{{ t('Available') }}</small>
                            </div>
                        </div>

                        <div class="inventory-bar" aria-hidden="true">
                            <i
                                v-for="seg in inventory.segments"
                                :key="seg.key"
                                :style="{
                                    width: `${seg.width}%`,
                                    background: seg.color,
                                }"
                            />
                        </div>

                        <ul class="indicator-list compact">
                            <li
                                v-for="seg in inventory.segments"
                                :key="seg.key"
                            >
                                <i :style="{ background: seg.color }" />
                                <span>{{ seg.label }}</span>
                                <b>{{ seg.value }}</b>
                            </li>
                        </ul>
                    </V2IndicatorCard>

                    <V2IndicatorCard card-class="money-card">
                        <template #head>{{ t('Sales by month') }}</template>
                        <template #meta>
                            <span
                                :title="
                                    moneyByCurrency(
                                        totals.sales_by_currency,
                                        totals.sales_total,
                                    )
                                ">{{
                                    compactMoneyByCurrency(
                                        totals.sales_by_currency,
                                        totals.sales_total,
                                    )
                                }}</span
                            >
                        </template>

                        <div class="money-legend" aria-hidden="true">
                            <span><i class="afn" /> AFN</span>
                            <span><i class="usd" /> USD</span>
                        </div>

                        <div class="money-chart">
                            <div
                                v-for="bar in moneyBars"
                                :key="bar.key"
                                class="money-col"
                                :class="{ peak: bar.peak }"
                                :title="`${bar.label}: ${compactMoneyByCurrency(bar.by_currency)}`">
                                <div class="money-pair">
                                    <i
                                        class="afn"
                                        :style="{
                                            height: `${bar.afnHeight}px`,
                                        }"
                                    />
                                    <i
                                        class="usd"
                                        :style="{
                                            height: `${bar.usdHeight}px`,
                                        }"
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
                        :title="t('Projects')"
                        :value="totals.count"
                    >
                        <template #icon><Building2 /></template>
                    </V2StatCard>
                    <V2StatCard
                        :delay="1"
                        icon-tone="warm"
                        :title="t('Active')"
                        :value="totals.active"
                    >
                        <template #icon><Layers3 /></template>
                    </V2StatCard>
                    <V2StatCard
                        :delay="2"
                        icon-tone="teal"
                        :title="t('Units')"
                        :value="totals.units"
                    >
                        <template #icon><Users /></template>
                    </V2StatCard>
                    <V2StatCard
                        :delay="3"
                        accent
                        icon-tone="orange"
                        :title="t('Sales total')">
                        <template #icon><Wallet /></template>
                        <span>{{ t('Sales total') }}</span>
                        <strong
                            :title="
                                moneyByCurrency(
                                    totals.sales_by_currency,
                                    totals.sales_total,
                                )
                            ">{{
                                compactMoneyByCurrency(
                                    totals.sales_by_currency,
                                    totals.sales_total,
                                )
                            }}</strong
                        >
                    </V2StatCard>
                </V2StatGrid>
            </template>
        </V2Hero>

        <V2TablePanel
            table-id="projects"
            :columns="tableColumns"
            :pending="pending && items.data.length > 0"
            :print-title="t('Projects')"
        >
            <template #filters>
                <V2FilterBar>
                    <V2SelectFilter
                        :label="t('Status')"
                        :model-value="filters.status || ''"
                        @change="onStatusChange"
                    >
                        <option value="">{{ t('All') }}</option>
                        <option
                            v-for="status in projectStatuses"
                            :key="status"
                            :value="status"
                        >
                            {{ statusLabel(status) }}
                        </option>
                    </V2SelectFilter>
                    <template #columns>
                        <MisTableActions
                            export-url="/projects/export"
                            :filters="filters"
                            :on-print="projectsPrint.openPicker"
                        >
                            <CreateButton href="/projects/create" permission="projects.create">{{ t('Create') }}</CreateButton>
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
                            <SortableTh column="code">{{
                                t('Code')
                            }}</SortableTh>
                            <SortableTh column="name">{{
                                t('Name')
                            }}</SortableTh>
                            <SortableTh column="location">{{
                                t('Location')
                            }}</SortableTh>
                            <SortableTh column="status">{{
                                t('Status')
                            }}</SortableTh>
                            <SortableTh column="progress_percent">{{
                                t('Progress')
                            }}</SortableTh>
                            <SortableTh column="units_count">{{
                                t('Units')
                            }}</SortableTh>
                            <SortableTh column="creator.name">{{
                                t('Added by')
                            }}</SortableTh>
                            <SortableTh column="created_at">{{
                                t('Created at')
                            }}</SortableTh>
                            <th class="end">{{ t('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="(item, index) in sortedRows"
                            :key="item.id"
                            :data-print-key="String(item.id)"
                            :style="{ '--i': index }"
                            @click="openProject(item)"
                        >
                            <TableIndexTd
                                :index="index"
                                :from="items.meta?.from"
                            />
                            <td>
                                <Link
                                    :href="`/projects/${item.id}`"
                                    class="code-chip"
                                    @click.stop
                                >
                                    {{ item.code || '—' }}
                                </Link>
                            </td>
                            <td>
                                <div class="name-cell">
                                    <b>{{ item.name || '—' }}</b>
                                    <small>{{ item.location || '—' }}</small>
                                </div>
                            </td>
                            <td class="muted loc">
                                {{ item.location || '—' }}
                            </td>
                            <td>
                                <StatusBadge :status="item.status" />
                            </td>
                            <td>
                                <div class="progress-cell">
                                    <div class="progress-track">
                                        <i
                                            :style="{
                                                width: `${Math.min(100, Number(item.progress_percent ?? 0))}%`,
                                                background: progressColor(
                                                    item.status,
                                                ),
                                            }"
                                        />
                                    </div>
                                    <span class="nums">
                                        {{
                                            formatNumber(
                                                item.progress_percent,
                                                0,
                                            )
                                        }}%
                                    </span>
                                </div>
                            </td>
                            <td>
                                <span class="pill">{{ item.units_count }}</span>
                            </td>
                            <td>
                                <div class="creator-cell">
                                    <span class="creator-avatar">{{
                                        creatorInitial(item.creator?.name)
                                    }}</span>
                                    <span>{{
                                        item.creator?.name || '—'
                                    }}</span>
                                </div>
                            </td>
                            <td class="muted nowrap">
                                {{ formatDate(item.created_at) }}
                            </td>
                            <td class="end" @click.stop>
                                <RowActionsMenu :actions="rowActions(item)" />
                            </td>
                        </tr>
                        <EmptyState
                            v-if="!items.data.length"
                            :colspan="visibleColCount"
                            :title="t('No projects yet')">
                        </EmptyState>
                    </tbody>
                </table>
            </template>

            <template #pager>
                <V2Pager :items="items" :only="onlyKeys" />
            </template>
        </V2TablePanel>

        <TabPrintHost :state="projectsPrint" />
    </V2ListPage>
</template>
