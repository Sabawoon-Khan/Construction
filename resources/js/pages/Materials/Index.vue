<script setup lang="ts">
import EmptyState from '@/components/EmptyState.vue';
import { MisSearchInput } from '@/components/mis';
import RowActionsMenu from '@/components/RowActionsMenu.vue';
import SortableTh from '@/components/SortableTh.vue';
import TableIndexTd from '@/components/TableIndexTd.vue';
import TableIndexTh from '@/components/TableIndexTh.vue';
import TableToolbar from '@/components/TableToolbar.vue';
import {
    V2FilterBar,
    V2Hero,
    V2IndicatorCard,
    V2ListPage,
    V2Pager,
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
import { money } from '@/lib/utils';
import { Boxes, CheckCircle2, Package, Plus, TriangleAlert } from '@lucide/vue';
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';

type Item = Record<string, any>;

const props = defineProps<{
    items: { data: Item[]; links: any[]; meta?: any };
    filters: { search?: string };
    totals: {
        count: number;
        active: number;
        inactive?: number;
        with_cost?: number;
        low_stock?: number;
        this_month?: number;
    };
    volumeChart?: Array<{ key: string; label: string; count: number }>;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Materials', href: '/materials' },
        ],
    },
});

const onlyKeys = ['items', 'filters', 'totals', 'volumeChart'];

const { filters, pending, apply } = useMisFilters(
    '/materials',
    { search: props.filters.search ?? '' },
    { search: '' },
    { only: onlyKeys, liveKeys: ['search'], debounceMs: 280 },
);
const { sortedRows } = provideTableSort(() => props.items.data);
const { t } = useTranslations();
const tableColumns = computed(() => [
    indexTableColumn(),
    { key: 'code', label: t('Code') },
    { key: 'name', label: t('Name') },
    { key: 'unit.unit_name', label: t('Unit') },
    { key: 'unit_cost', label: t('Unit cost') },
    { key: 'minimum_stock', label: t('Min stock') },
    { key: 'creator.name', label: t('Added by') },
    { key: 'created_at', label: t('Created at') },
    { key: 'actions', label: t('Actions'), locked: true },
]);
const { editAction, deleteAction, can, formatDate } = useMisPage();

const statusBreakdown = computed(() => {
    const active = Number(props.totals.active ?? 0);
    const inactive = Number(
        props.totals.inactive ??
            Math.max(0, Number(props.totals.count ?? 0) - active),
    );
    const total = Math.max(active + inactive, Number(props.totals.count ?? 0), 0);
    const activeShare = total > 0 ? Math.round((active / total) * 100) : 0;

    return {
        active,
        inactive,
        total,
        activeShare,
        segments: [
            {
                key: 'active',
                label: t('Active'),
                value: active,
                color: 'var(--brand-accent)',
                width:
                    total > 0
                        ? Math.max(active > 0 ? 6 : 0, (active / total) * 100)
                        : 0,
            },
            {
                key: 'inactive',
                label: t('Inactive'),
                value: inactive,
                color: 'var(--muted-foreground)',
                width:
                    total > 0
                        ? Math.max(inactive > 0 ? 6 : 0, (inactive / total) * 100)
                        : 0,
            },
        ],
    };
});

const volumeBars = computed(() => {
    const rows = props.volumeChart ?? [];
    const source =
        rows.length > 0
            ? rows
            : Array.from({ length: 6 }, (_, index) => ({
                  key: `m-${index}`,
                  label: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'][index],
                  count: 0,
              }));
    const max = Math.max(...source.map((row) => Number(row.count) || 0), 1);

    return source.map((row) => {
        const count = Number(row.count) || 0;
        return {
            key: row.key,
            label: row.label,
            count,
            height: Math.max(count > 0 ? 10 : 4, Math.round((count / max) * 72)),
            peak: count === max && count > 0,
        };
    });
});

function rowActions(item: Item): RowActionItem[] {
    return [
        editAction(`/materials/${item.id}/edit`, 'materials.edit'),
        deleteAction(
            {
                href: `/materials/${item.id}`,
                title: t('Delete material'),
                description: t('Are you sure you want to delete this material?'),
                trashType: 'materials',
            },
            'materials.delete',
        ),
    ];
}
</script>

<template>
    <Head :title="t('Materials')" />

    <V2ListPage>
        <V2Hero image="/images/projects-v2-hero-luxury.png">
            <template #eyebrow>{{ t('Materials') }}</template>
            <template #title>{{ t('Materials') }}</template>
            <template #description>
                {{ t('Track codes, units, and costs for purchasing and stock.') }}
            </template>

            <template #side>
                <Link
                    v-if="can('materials.create')"
                    href="/materials/create"
                    class="create-btn"
                >
                    <Plus />
                    {{ t('Create') }}
                </Link>

                <div class="hero-cards">
                    <V2IndicatorCard card-class="inventory-card">
                        <template #head>{{ t('Status') }}</template>
                        <template #meta
                            >{{ statusBreakdown.activeShare }}%
                            {{ t('Active') }}</template
                        >

                        <div class="inventory-hero">
                            <div class="inventory-hero-copy">
                                <strong>{{ statusBreakdown.active }}</strong>
                                <small>{{ t('Active') }}</small>
                            </div>
                        </div>

                        <div class="inventory-bar" aria-hidden="true">
                            <i
                                v-for="seg in statusBreakdown.segments"
                                :key="seg.key"
                                :style="{
                                    width: `${seg.width}%`,
                                    background: seg.color,
                                }"
                            />
                        </div>

                        <ul class="indicator-list compact">
                            <li
                                v-for="seg in statusBreakdown.segments"
                                :key="seg.key"
                            >
                                <i :style="{ background: seg.color }" />
                                <span>{{ seg.label }}</span>
                                <b>{{ seg.value }}</b>
                            </li>
                        </ul>
                    </V2IndicatorCard>

                    <V2IndicatorCard card-class="money-card">
                        <template #head>{{ t('This month') }}</template>
                        <template #meta>{{
                            totals.this_month ?? 0
                        }}
                        {{ t('This month') }}</template>

                        <div class="pipeline-chart" aria-hidden="true">
                            <div
                                v-for="bar in volumeBars"
                                :key="bar.key"
                                class="pipeline-col"
                                :title="`${bar.label}: ${bar.count}`"
                            >
                                <b>{{ bar.count }}</b>
                                <i
                                    :style="{
                                        height: `${bar.height}px`,
                                        background: bar.peak
                                            ? 'var(--school-gold)'
                                            : 'var(--school-navy)',
                                    }"
                                />
                                <span class="muted">{{ bar.label }}</span>
                            </div>
                        </div>
                    </V2IndicatorCard>
                </div>
            </template>

            <template #stats>
                <V2StatGrid>
                    <V2StatCard
                        :delay="0"
                        :title="t('Materials')"
                        :value="totals.count"
                    >
                        <template #icon><Package /></template>
                    </V2StatCard>
                    <V2StatCard
                        :delay="1"
                        icon-tone="teal"
                        :title="t('Active')"
                        :value="totals.active"
                    >
                        <template #icon><CheckCircle2 /></template>
                    </V2StatCard>
                    <V2StatCard
                        :delay="2"
                        icon-tone="warm"
                        :title="t('Inactive')"
                        :value="totals.inactive ?? 0"
                    >
                        <template #icon><Boxes /></template>
                    </V2StatCard>
                    <V2StatCard
                        :delay="3"
                        accent
                        icon-tone="orange"
                        :title="t('Low stock')"
                        :value="totals.low_stock ?? 0"
                    >
                        <template #icon><TriangleAlert /></template>
                    </V2StatCard>
                </V2StatGrid>
            </template>
        </V2Hero>

        <V2TablePanel
            table-id="materials"
            :columns="tableColumns"
            :pending="pending && items.data.length > 0"
        >
            <template #filters>
                <V2FilterBar>
                    <div class="filter-search">
                        <MisSearchInput
                            v-model="filters.search"
                            :placeholder="t('Search materials...')"
                            @submit="apply()"
                        />
                    </div>
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
                            <SortableTh column="code">{{
                                t('Code')
                            }}</SortableTh>
                            <SortableTh column="name">{{
                                t('Name')
                            }}</SortableTh>
                            <SortableTh column="unit.unit_name">{{
                                t('Unit')
                            }}</SortableTh>
                            <SortableTh column="unit_cost">{{
                                t('Unit cost')
                            }}</SortableTh>
                            <SortableTh column="minimum_stock">{{
                                t('Min stock')
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
                                <span class="code-chip">{{
                                    item.code || '—'
                                }}</span>
                            </td>
                            <td>
                                <div class="name-cell">
                                    <b>{{ item.name || '—' }}</b>
                                    <small>{{
                                        item.unit?.unit_name || '—'
                                    }}</small>
                                </div>
                            </td>
                            <td class="muted">
                                {{ item.unit?.unit_name || '—' }}
                            </td>
                            <td class="nums">
                                {{ money(item.unit_cost, item.currency) }}
                            </td>
                            <td class="nums">{{ item.minimum_stock }}</td>
                            <td class="muted">
                                {{ item.creator?.name || '—' }}
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
                            :title="t('No materials yet')"
                        >
                            <template #actions>
                                <Link
                                    v-if="can('materials.create')"
                                    href="/materials/create"
                                    class="create-btn"
                                >
                                    <Plus />
                                    {{ t('Create') }}
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
