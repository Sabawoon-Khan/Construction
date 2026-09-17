<script setup lang="ts">
import EmptyState from '@/components/EmptyState.vue';
import RowActionsMenu from '@/components/RowActionsMenu.vue';
import SortableTh from '@/components/SortableTh.vue';
import TableIndexTd from '@/components/TableIndexTd.vue';
import TableIndexTh from '@/components/TableIndexTh.vue';
import TableToolbar from '@/components/TableToolbar.vue';
import { MisTableActions, MisSearchInput  } from '@/components/mis';
import { CreateButton } from '@/components/ui/create-button';
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
import { Eye, FileImage, Trash2 } from '@lucide/vue';
import { computed } from 'vue';
import { Head } from '@inertiajs/vue3';

type Item = Record<string, any>;

const props = defineProps<{
    items: { data: Item[]; links: any[]; meta?: any };
    filters: { search?: string };
    totals: {
        count: number;
        published: number;
        draft?: number;
        with_media?: number;
    };
    categoryChart?: Array<{ key: string; label: string; value: number }>;
    volumeChart?: Array<{ key: string; label: string; value: number }>;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Portfolio', href: '/portfolio' },
        ],
    },
});

const onlyKeys = ['items', 'filters', 'totals', 'categoryChart', 'volumeChart'];

const { filters, pending, apply, clear } = useMisFilters(
    '/portfolio',
    { search: props.filters.search ?? '' },
    { search: '' },
    { only: onlyKeys, liveKeys: ['search'] },
);

const { sortedRows } = provideTableSort(() => props.items.data);
const { t } = useTranslations();
const tableColumns = computed(() => [
    indexTableColumn(),
    { key: 'title', label: t('Title') },
    { key: 'category', label: t('Category') },
    { key: 'location', label: t('Location') },
    { key: 'media.length', label: t('Media') },
    { key: 'is_published', label: t('Published') },
    { key: 'created_at', label: t('Created at') },
    { key: 'actions', label: t('Actions'), locked: true },
]);
const { editAction, can, formatDate } = useMisPage();

const publishMix = computed(() => {
    const published = Number(props.totals.published ?? 0);
    const draft = Number(
        props.totals.draft ?? Math.max(0, Number(props.totals.count ?? 0) - published),
    );
    const total = Math.max(published + draft, Number(props.totals.count ?? 0), 0);
    const publishedShare = total > 0 ? Math.round((published / total) * 100) : 0;
    const segments = [
        { key: 'published', label: t('Published'), value: published, color: 'var(--brand-accent)' },
        { key: 'draft', label: t('Draft'), value: draft, color: 'var(--muted-foreground)' },
    ].map((seg) => ({
        ...seg,
        width:
            total > 0
                ? Math.max(seg.value > 0 ? 6 : 0, (seg.value / total) * 100)
                : 0,
    }));

    return { published, publishedShare, segments };
});

const categoryBars = computed(() => {
    const rows =
        (props.categoryChart?.length ? props.categoryChart : props.volumeChart) ??
        [];
    const source =
        rows.length > 0
            ? rows
            : Array.from({ length: 6 }, (_, index) => ({
                  key: `c-${index}`,
                  label: ['A', 'B', 'C', 'D', 'E', 'F'][index],
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

function rowActions(item: Item): RowActionItem[] {
    return [
        editAction(`/portfolio/${item.id}/edit`, 'portfolio.edit'),
        { separator: true, isLabel: true, label: t('Danger') },
        {
            label: t('Delete'),
            icon: Trash2,
            method: 'delete',
            href: `/portfolio/${item.id}`,
            variant: 'destructive' as const,
            hidden: !can('portfolio.delete'),
            confirm: {
                title: t('Delete portfolio project'),
                description: t('Are you sure you want to delete this portfolio project?'),
                confirmLabel: t('Delete'),
            },
        },
    ];
}
</script>

<template>
    <Head :title="t('Portfolio')" />

    <V2ListPage>
        <V2Hero image="/images/projects-v2-hero-luxury.png">
            <template #eyebrow>{{ t('Marketing') }}</template>
            <template #title>{{ t('Portfolio') }}</template>
            <template #description>
                {{ t('Showcase completed and ongoing work with media and publish status.') }}
            </template>

            <template #side>
                <div class="hero-cards">
                    <V2IndicatorCard card-class="inventory-card">
                        <template #head>{{ t('Publish status') }}</template>
                        <template #meta
                            >{{ publishMix.publishedShare }}% {{ t('Published') }}</template
                        >

                        <div class="inventory-hero">
                            <div class="inventory-hero-copy">
                                <strong>{{ publishMix.published }}</strong>
                                <small>{{ t('Published') }}</small>
                            </div>
                        </div>

                        <div class="inventory-bar" aria-hidden="true">
                            <i
                                v-for="seg in publishMix.segments"
                                :key="seg.key"
                                :style="{
                                    width: `${seg.width}%`,
                                    background: seg.color,
                                }"
                            />
                        </div>

                        <ul class="indicator-list compact">
                            <li
                                v-for="seg in publishMix.segments"
                                :key="seg.key"
                            >
                                <i :style="{ background: seg.color }" />
                                <span>{{ seg.label }}</span>
                                <b>{{ seg.value }}</b>
                            </li>
                        </ul>
                    </V2IndicatorCard>

                    <V2IndicatorCard card-class="money-card">
                        <template #head>{{ t('By category') }}</template>
                        <template #meta>{{ totals.count }} {{ t('Portfolio') }}</template>

                        <div class="money-chart">
                            <div
                                v-for="bar in categoryBars"
                                :key="bar.key"
                                class="money-col"
                                :class="{ peak: bar.peak }"
                                :title="`${bar.label}: ${bar.value}`">
                                <div class="money-pair">
                                    <i
                                        class="afn"
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
                        :title="t('Portfolio')"
                        :value="totals.count"
                    >
                        <template #icon><FileImage /></template>
                    </V2StatCard>
                    <V2StatCard
                        :delay="1"
                        icon-tone="teal"
                        :title="t('Published')"
                        :value="totals.published"
                    >
                        <template #icon><Eye /></template>
                    </V2StatCard>
                    <V2StatCard
                        :delay="2"
                        icon-tone="warm"
                        :title="t('Draft')"
                        :value="totals.draft ?? 0"
                    >
                        <template #icon><FileImage /></template>
                    </V2StatCard>
                    <V2StatCard
                        :delay="3"
                        accent
                        icon-tone="orange"
                        :title="t('With media')"
                        :value="totals.with_media ?? 0"
                    >
                        <template #icon><FileImage /></template>
                    </V2StatCard>
                </V2StatGrid>
            </template>
        </V2Hero>

        <V2TablePanel
            table-id="portfolio"
            :columns="tableColumns"
            :pending="pending && items.data.length > 0"
        >
            <template #filters>
                <V2FilterBar>
                    <div class="filter-search">
                        <MisSearchInput
                            v-model="filters.search"
                            :placeholder="t('Search portfolio...')"
                            @submit="apply()"
                            @clear="clear"
                        />
                    </div>
                    <template #columns>
                        <MisTableActions export-url="/portfolio/export" :filters="filters">
                            <CreateButton href="/portfolio/create" permission="portfolio.create">{{ t('Create') }}</CreateButton>
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
                            <SortableTh column="title">{{ t('Title') }}</SortableTh>
                            <SortableTh column="category">{{ t('Category') }}</SortableTh>
                            <SortableTh column="location">{{ t('Location') }}</SortableTh>
                            <SortableTh column="media.length">{{ t('Media') }}</SortableTh>
                            <SortableTh column="is_published">{{ t('Published') }}</SortableTh>
                            <SortableTh column="created_at">{{ t('Created at') }}</SortableTh>
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
                                <div class="name-cell">
                                    <div
                                        class="size-10 shrink-0 overflow-hidden rounded-lg bg-muted"
                                    >
                                        <img
                                            v-if="
                                                item.media?.[0]?.type === 'image' &&
                                                item.media?.[0]?.url
                                            "
                                            :src="item.media[0].url"
                                            :alt="item.title"
                                            class="size-full object-cover"
                                        />
                                        <video
                                            v-else-if="
                                                item.media?.[0]?.type === 'video' &&
                                                item.media?.[0]?.url
                                            "
                                            :src="item.media[0].url"
                                            class="size-full object-cover"
                                            muted
                                            playsinline
                                        />
                                    </div>
                                    <div>
                                        <b>{{ item.title }}</b>
                                        <small>{{ item.slug }}</small>
                                    </div>
                                </div>
                            </td>
                            <td>{{ item.category || '—' }}</td>
                            <td class="muted">{{ item.location || '—' }}</td>
                            <td>
                                <span class="pill">{{ item.media_count ?? 0 }}</span>
                            </td>
                            <td>
                                <span class="pill">
                                    {{ item.is_published ? t('Published') : t('Draft') }}
                                </span>
                            </td>
                            <td class="muted nowrap">{{ formatDate(item.created_at) }}</td>
                            <td class="end" @click.stop>
                                <RowActionsMenu :actions="rowActions(item)" />
                            </td>
                        </tr>
                        <EmptyState
                            v-if="!items.data.length"
                            :colspan="visibleColCount"
                            :title="t('No portfolio projects yet')">
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
