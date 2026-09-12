<script setup lang="ts">
import EmptyState from '@/components/EmptyState.vue';
import MisSearchInput from '@/components/mis/MisSearchInput.vue';
import SortableTh from '@/components/SortableTh.vue';
import TableIndexTd from '@/components/TableIndexTd.vue';
import TableIndexTh from '@/components/TableIndexTh.vue';
import TableToolbar from '@/components/TableToolbar.vue';
import RowActionsMenu from '@/components/RowActionsMenu.vue';
import StatusBadge from '@/components/StatusBadge.vue';
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
import { useMisFilters } from '@/composables/useMisFilters';
import { useMisPage } from '@/composables/useMisPage';
import { provideTableSort } from '@/composables/useTableSort';
import { useTranslations } from '@/composables/useTranslations';
import type { RowActionItem } from '@/lib/row-actions';
import { buildMoneyBars, compactMoneyByCurrency } from '@/lib/v2Money';
import { money, moneyByCurrency } from '@/lib/utils';
import { Building2, CheckCircle2, Clock3, Home, Plus, Trash2 } from '@lucide/vue';
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps<{
    items: { data: any[]; links: any[]; meta?: any };
    filters: any;
    projects: any[];
    totals: {
        count: number;
        available: number;
        reserved: number;
        sold: number;
        units_available?: number;
        units_reserved?: number;
        units_sold?: number;
        list_price_total?: number;
        list_price_by_currency?: Record<string, number>;
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
            { title: 'Units', href: '/units' },
        ],
    },
});

const onlyKeys = ['items', 'filters', 'totals', 'projects', 'moneyChart'];

const { filters, pending, apply } = useMisFilters(
    '/units',
    {
        search: props.filters.search ?? '',
        project_id: props.filters.project_id ? String(props.filters.project_id) : '',
        status: props.filters.status ?? '',
    },
    { search: '', project_id: '', status: '' },
    { only: onlyKeys, liveKeys: ['search'] },
);
const { sortedRows } = provideTableSort(() => props.items.data);
const { t } = useTranslations();
const tableColumns = computed(() => [
    indexTableColumn(),
    { key: 'unit_no', label: t('Unit') },
    { key: 'project.name', label: t('Project') },
    { key: 'floor_number', label: t('Floor') },
    { key: 'type', label: t('Type') },
    { key: 'area', label: t('Area') },
    { key: 'bedrooms', label: t('Beds') },
    { key: 'cost_price', label: t('Unit cost') },
    { key: 'list_price', label: t('Sale price') },
    { key: 'sales.0.customer.name', label: t('Buyer') },
    { key: 'status', label: t('Status') },
    { key: 'creator.name', label: t('Added by') },
    { key: 'created_at', label: t('Created at') },
    { key: 'actions', label: t('Actions'), locked: true },
]);
const { viewAction, editAction, can, formatDate } = useMisPage();

const inventory = computed(() => {
    const available = Number(props.totals.available ?? props.totals.units_available ?? 0);
    const reserved = Number(props.totals.reserved ?? props.totals.units_reserved ?? 0);
    const sold = Number(props.totals.sold ?? props.totals.units_sold ?? 0);
    const total = Math.max(available + reserved + sold, Number(props.totals.count ?? 0), 0);
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

const moneyBars = computed(() => buildMoneyBars(props.moneyChart));

const unitStatuses = ['available', 'reserved', 'sold'];

function onProjectChange(value: string) {
    apply({ project_id: value });
}

function onStatusChange(value: string) {
    apply({ status: value });
}

function rowActions(item: any): RowActionItem[] {
    return [
        viewAction(`/units/${item.id}`),
        editAction(`/units/${item.id}/edit`, 'units.edit'),
        ...(can('units.edit')
            ? [
                  { separator: true, isLabel: true, label: t('Change status') },
                  ...unitStatuses.map((status) => ({
                      label: t(status.charAt(0).toUpperCase() + status.slice(1)),
                      method: 'patch' as const,
                      href: `/units/${item.id}/status`,
                      data: { status },
                      disabled: item.status === status,
                  })),
              ]
            : []),
        { separator: true, isLabel: true, label: t('Danger') },
        {
            label: t('Delete'),
            icon: Trash2,
            method: 'delete',
            href: `/units/${item.id}`,
            variant: 'destructive' as const,
            hidden: !can('units.delete'),
            confirm: {
                title: t('Delete unit'),
                description: t(
                    'Are you sure you want to delete this unit? This action cannot be undone.',
                ),
                confirmLabel: t('Delete'),
            },
        },
    ];
}
</script>

<template>
    <Head :title="t('Units & Homes')" />

    <V2ListPage>
        <V2Hero image="/images/projects-v2-hero-luxury.png">
            <template #eyebrow>{{ t('Inventory') }}</template>
            <template #title>{{ t('Units & Homes') }}</template>
            <template #description>
                {{
                    t(
                        'Browse every home by project and status — availability, pricing, and buyers in one list.',
                    )
                }}
            </template>

            <template #side>
                <Link
                    v-if="can('units.create')"
                    href="/units/create"
                    class="create-btn"
                >
                    <Plus />
                    {{ t('Create') }}
                </Link>

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
                        <template #head>{{ t('Sold by month') }}</template>
                        <template #meta>
                            <span
                                :title="
                                    moneyByCurrency(
                                        totals.list_price_by_currency,
                                        totals.list_price_total,
                                    )
                                "
                                >{{
                                    compactMoneyByCurrency(
                                        totals.list_price_by_currency,
                                        totals.list_price_total,
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
                                :title="`${bar.label}: ${compactMoneyByCurrency(bar.by_currency)}`"
                            >
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
                        :title="t('Units')"
                        :value="totals.count"
                    >
                        <template #icon><Home /></template>
                    </V2StatCard>
                    <V2StatCard
                        :delay="1"
                        icon-tone="teal"
                        :title="t('Available')"
                        :value="totals.available"
                    >
                        <template #icon><CheckCircle2 /></template>
                    </V2StatCard>
                    <V2StatCard
                        :delay="2"
                        icon-tone="orange"
                        :title="t('Reserved')"
                        :value="totals.reserved"
                    >
                        <template #icon><Clock3 /></template>
                    </V2StatCard>
                    <V2StatCard
                        :delay="3"
                        accent
                        icon-tone="warm"
                        :title="t('Sold')"
                        :value="totals.sold"
                    >
                        <template #icon><Building2 /></template>
                    </V2StatCard>
                </V2StatGrid>
            </template>
        </V2Hero>

        <V2TablePanel
            table-id="units"
            :columns="tableColumns"
            :pending="pending && items.data.length > 0"
        >
            <template #filters>
                <V2FilterBar>
                    <div class="filter-search">
                        <MisSearchInput
                            v-model="filters.search"
                            :placeholder="t('Unit no...')"
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
                    <V2SelectFilter
                        :label="t('Status')"
                        :model-value="filters.status || ''"
                        @change="onStatusChange"
                    >
                        <option value="">{{ t('All statuses') }}</option>
                        <option value="available">{{ t('Available') }}</option>
                        <option value="reserved">{{ t('Reserved') }}</option>
                        <option value="sold">{{ t('Sold') }}</option>
                    </V2SelectFilter>
                    <template #columns>
                        <TableToolbar />
                    </template>
                </V2FilterBar>
            </template>

            <template #default="{ visibleColCount }">
                <table>
                    <thead>
                        <tr>
                            <TableIndexTh />
                            <SortableTh column="unit_no">{{
                                t('Unit')
                            }}</SortableTh>
                            <SortableTh column="project.name">{{
                                t('Project')
                            }}</SortableTh>
                            <SortableTh column="floor_number">{{
                                t('Floor')
                            }}</SortableTh>
                            <SortableTh column="type">{{
                                t('Type')
                            }}</SortableTh>
                            <SortableTh column="area">{{
                                t('Area')
                            }}</SortableTh>
                            <SortableTh column="bedrooms">{{
                                t('Beds')
                            }}</SortableTh>
                            <SortableTh column="cost_price">{{
                                t('Unit cost')
                            }}</SortableTh>
                            <SortableTh column="list_price">{{
                                t('Sale price')
                            }}</SortableTh>
                            <SortableTh column="sales.0.customer.name">{{
                                t('Buyer')
                            }}</SortableTh>
                            <SortableTh column="status">{{
                                t('Status')
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
                            :style="{ '--i': index }"
                        >
                            <TableIndexTd
                                :index="index"
                                :from="items.meta?.from"
                            />
                            <td>
                                <Link
                                    :href="`/units/${item.id}`"
                                    class="code-chip"
                                >
                                    {{ item.unit_no }}
                                </Link>
                            </td>
                            <td>{{ item.project?.name || '—' }}</td>
                            <td class="muted">{{ item.floor_number ?? '—' }}</td>
                            <td class="muted">{{ item.type || '—' }}</td>
                            <td class="nums">{{ item.area ?? '—' }}</td>
                            <td class="nums">{{ item.bedrooms ?? '—' }}</td>
                            <td class="nums">
                                {{ money(item.cost_price, item.currency) }}
                            </td>
                            <td class="nums">
                                {{ money(item.list_price, item.currency) }}
                            </td>
                            <td>
                                {{ item.sales?.[0]?.customer?.name || '—' }}
                            </td>
                            <td>
                                <StatusBadge :status="item.status" />
                            </td>
                            <td class="muted">
                                {{ item.creator?.name || '—' }}
                            </td>
                            <td class="muted nowrap">
                                {{ formatDate(item.created_at) }}
                            </td>
                            <td class="end">
                                <RowActionsMenu :actions="rowActions(item)" />
                            </td>
                        </tr>
                        <EmptyState
                            v-if="!items.data.length"
                            :colspan="visibleColCount"
                            :title="t('No units yet')"
                        >
                            <template #actions>
                                <Link
                                    v-if="can('units.create')"
                                    href="/units/create"
                                    class="create-btn"
                                >
                                    <Plus />
                                    {{ t('Create unit') }}
                                </Link>
                            </template>
                        </EmptyState>
                    </tbody>
                </table>
            </template>

            <template #pager>
                <V2Pager :items="items" :only="onlyKeys" />
            </template>
        </V2TablePanel>
    </V2ListPage>
</template>
