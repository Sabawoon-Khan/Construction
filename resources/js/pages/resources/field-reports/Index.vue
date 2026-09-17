<script setup lang="ts">
import EmptyState from '@/components/EmptyState.vue';
import { MisTableActions, MisSearchInput, MisStatusBadge  } from '@/components/mis';
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
import { ClipboardList, FilePenLine, MapPin, Send } from '@lucide/vue';
import { computed, toRef } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import { useOfflineResourceList } from '@/offline/useOfflineResourceList';

type Item = {
    id: number;
    client_uuid: string;
    title: string;
    location: string | null;
    status: string;
    created_at?: string | null;
    name?: string;
};

const props = defineProps<{
    items: { data: Item[]; links: any[]; meta?: any };
    filters: { search?: string; status?: string };
    totals: { count: number; draft: number; submitted: number };
    volumeChart?: Array<{ key: string; label: string; value: number }>;
    offlineEnabled: boolean;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Field reports', href: '/field-reports' },
        ],
    },
});

const onlyKeys = ['items', 'filters', 'totals', 'volumeChart'];

const { filters, pending, apply } = useMisFilters(
    '/field-reports',
    {
        search: props.filters.search ?? '',
        status: props.filters.status ?? '',
    },
    { search: '', status: '' },
    { only: onlyKeys, liveKeys: ['search'] },
);

const onlineItems = computed(() => props.items.data);
const { displayItems, showingOfflineCache, online } = useOfflineResourceList({
    resource: 'field_reports',
    offlineEnabled: toRef(props, 'offlineEnabled'),
    onlineItems,
});
const { sortedRows } = provideTableSort(() => displayItems.value);
const { t } = useTranslations();
const tableColumns = computed(() => [
    indexTableColumn(),
    { key: 'title', label: t('Title') },
    { key: 'location', label: t('Location') },
    { key: 'status', label: t('Status') },
    { key: 'created_at', label: t('Created at') },
    { key: 'actions', label: t('Actions'), locked: true },
]);
const { viewAction, editAction, deleteAction, can, formatDate } = useMisPage();

const statusMix = computed(() => {
    const draft = Number(props.totals.draft ?? 0);
    const submitted = Number(props.totals.submitted ?? 0);
    const total = Math.max(draft + submitted, Number(props.totals.count ?? 0), 0);
    const submittedShare = total > 0 ? Math.round((submitted / total) * 100) : 0;
    const segments = [
        {
            key: 'submitted',
            label: t('Submitted'),
            value: submitted,
            color: 'var(--brand-accent, #ff8a3a)',
        },
        {
            key: 'draft',
            label: t('Draft'),
            value: draft,
            color: 'var(--school-navy, #1a1a1a)',
        },
    ].map((seg) => ({
        ...seg,
        width:
            total > 0
                ? Math.max(seg.value > 0 ? 6 : 0, (seg.value / total) * 100)
                : 0,
    }));

    return { draft, submitted, submittedShare, total, segments };
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

function onStatusChange(value: string) {
    apply({ status: value });
}

function rowActions(item: Item): RowActionItem[] {
    if (!item.id) {
        return [];
    }

    return [
        viewAction(`/field-reports/${item.id}`),
        editAction(`/field-reports/${item.id}/edit`, 'field_reports.edit'),
        deleteAction(
            {
                href: `/field-reports/${item.id}`,
                title: t('Move to trash'),
                description: t(
                    'Move this field report to trash? You can restore it later.',
                ),
                trashType: 'field_reports',
            },
            'field_reports.delete',
        ),
    ];
}
</script>

<template>
    <Head :title="t('Field reports')" />

    <V2ListPage>
        <V2Hero image="/images/dashboard-v2-building.png">
            <template #eyebrow>{{ t('Field') }}</template>
            <template #title>{{ t('Field reports') }}</template>
            <template #description>
                {{
                    t(
                        'Capture site notes offline — they sync when you reconnect.',
                    )
                }}
            </template>

            <template #side>
                <div class="hero-cards">
                    <V2IndicatorCard card-class="inventory-card">
                        <template #head>{{ t('Status mix') }}</template>
                        <template #meta
                            >{{ statusMix.submittedShare }}%
                            {{ t('Submitted') }}</template
                        >

                        <div class="inventory-hero">
                            <div class="inventory-hero-copy">
                                <strong>{{ statusMix.submitted }}</strong>
                                <small>{{ t('Submitted') }}</small>
                            </div>
                        </div>

                        <div class="inventory-bar" aria-hidden="true">
                            <i
                                v-for="seg in statusMix.segments"
                                :key="seg.key"
                                :style="{
                                    width: `${seg.width}%`,
                                    background: seg.color,
                                }"
                            />
                        </div>

                        <ul class="indicator-list compact">
                            <li
                                v-for="seg in statusMix.segments"
                                :key="seg.key"
                            >
                                <i :style="{ background: seg.color }" />
                                <span>{{ seg.label }}</span>
                                <b>{{ seg.value }}</b>
                            </li>
                        </ul>
                    </V2IndicatorCard>

                    <V2IndicatorCard card-class="money-card">
                        <template #head>{{ t('Reports by month') }}</template>
                        <template #meta
                            >{{ totals.count }} {{ t('Total') }}</template
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
                        :title="t('Total reports')"
                        :value="totals.count"
                    >
                        <template #icon><ClipboardList /></template>
                    </V2StatCard>
                    <V2StatCard
                        :delay="1"
                        icon-tone="orange"
                        :title="t('Draft')"
                        :value="totals.draft"
                    >
                        <template #icon><FilePenLine /></template>
                    </V2StatCard>
                    <V2StatCard
                        :delay="2"
                        accent
                        icon-tone="teal"
                        :title="t('Submitted')"
                        :value="totals.submitted"
                    >
                        <template #icon><Send /></template>
                    </V2StatCard>
                    <V2StatCard
                        :delay="3"
                        icon-tone="warm"
                        :title="t('Locations')"
                        :value="displayItems.filter((item) => item.location).length"
                    >
                        <template #icon><MapPin /></template>
                    </V2StatCard>
                </V2StatGrid>
            </template>
        </V2Hero>

        <p
            v-if="showingOfflineCache"
            class="rounded-2xl border border-amber-500/30 bg-amber-500/10 px-4 py-2.5 text-sm"
        >
            {{ t('Showing cached and queued field reports from this device.') }}
        </p>

        <V2TablePanel
            table-id="field-reports"
            :columns="tableColumns"
            :pending="pending && displayItems.length > 0"
            :print-title="t('Field reports')"
        >
            <template #filters>
                <V2FilterBar>
                    <div class="filter-search">
                        <MisSearchInput
                            v-model="filters.search"
                            :placeholder="t('Search...')"
                            @submit="apply()"
                            @clear="apply()"
                        />
                    </div>
                    <V2SelectFilter
                        :label="t('Status')"
                        :model-value="filters.status || ''"
                        @change="onStatusChange"
                    >
                        <option value="">{{ t('All') }}</option>
                        <option value="draft">{{ t('Draft') }}</option>
                        <option value="submitted">{{ t('Submitted') }}</option>
                    </V2SelectFilter>
                    <template #columns>
                        <MisTableActions export-url="/field-reports/export" :filters="filters">
                            <CreateButton href="/field-reports/create" permission="field_reports.create">{{ t('Create') }}</CreateButton>
                        </MisTableActions>
                        <TableToolbar />
                    </template>
                </V2FilterBar>
            </template>

            <table class="min-w-full text-sm">
                <thead class="table-chrome-head text-start text-muted-foreground">
                    <tr>
                        <TableIndexTh />
                        <SortableTh column="title">{{ t('Title') }}</SortableTh>
                        <SortableTh column="location">{{
                            t('Location')
                        }}</SortableTh>
                        <SortableTh column="status">{{ t('Status') }}</SortableTh>
                        <SortableTh column="created_at">{{
                            t('Created at')
                        }}</SortableTh>
                        <th class="px-4 py-3 text-end">{{ t('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="(item, index) in sortedRows"
                        :key="item.client_uuid || item.id"
                        class="border-t align-middle"
                    >
                        <TableIndexTd :index="index" />
                        <td class="px-4 py-3">
                            <Link
                                v-if="item.id"
                                :href="`/field-reports/${item.id}`"
                                class="font-medium hover:text-primary hover:underline"
                            >
                                {{ item.title || item.name }}
                            </Link>
                            <span v-else class="font-medium">{{
                                item.title || item.name
                            }}</span>
                        </td>
                        <td class="px-4 py-3 text-muted-foreground">
                            {{ item.location || t('No location') }}
                        </td>
                        <td class="px-4 py-3">
                            <MisStatusBadge :status="item.status" />
                        </td>
                        <td
                            class="px-4 py-3 whitespace-nowrap text-muted-foreground"
                        >
                            {{ formatDate(item.created_at) }}
                        </td>
                        <td class="px-4 py-3 text-end">
                            <div class="inline-flex justify-end">
                                <RowActionsMenu
                                    v-if="item.id"
                                    :actions="rowActions(item)"
                                />
                            </div>
                        </td>
                    </tr>
                    <EmptyState
                        v-if="!displayItems.length"
                        :colspan="6"
                        :title="t('No field reports yet.')"
                        :description="
                            t(
                                'Capture site notes offline — they sync when you reconnect.',
                            )
                        "
                    >
                        </EmptyState>
                </tbody>
            </table>
        </V2TablePanel>

        <V2Pager v-if="online" :items="items" :only="onlyKeys" />
    </V2ListPage>
</template>
