<script setup lang="ts">
import EmptyState from '@/components/EmptyState.vue';
import { MisSearchInput, MisTableActions } from '@/components/mis';
import RowActionsMenu from '@/components/RowActionsMenu.vue';
import SortableTh from '@/components/SortableTh.vue';
import TableIndexTd from '@/components/TableIndexTd.vue';
import TableIndexTh from '@/components/TableIndexTh.vue';
import TableToolbar from '@/components/TableToolbar.vue';
import { CreateButton } from '@/components/ui/create-button';
import {
    V2FilterBar,
    V2Hero,
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
import { Archive, FileStack } from '@lucide/vue';
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';

type Item = {
    id: number;
    title: string;
    category: string;
    category_label?: string;
    document_date?: string | null;
    attachments_count?: number;
    creator?: { name?: string | null } | null;
    created_at?: string | null;
};

type CategoryOption = { value: string; label: string };

const props = defineProps<{
    items: { data: Item[]; links: any[]; meta?: any };
    filters: { search?: string | null; category?: string | null };
    categories: CategoryOption[];
    totals: { count: number };
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Archive', href: '/archive' },
        ],
    },
});

const onlyKeys = ['items', 'filters', 'totals', 'categories'];

const { filters, pending, apply } = useMisFilters(
    '/archive',
    {
        search: props.filters.search ?? '',
        category: props.filters.category ?? '',
    },
    { search: '', category: '' },
    { only: onlyKeys, liveKeys: ['search'] },
);

const { sortedRows } = provideTableSort(() => props.items.data);
const { t } = useTranslations();
const { viewAction, editAction, deleteAction, formatDate } = useMisPage();

const tableColumns = computed(() => [
    indexTableColumn(),
    { key: 'title', label: t('Title') },
    { key: 'category_label', label: t('Category') },
    { key: 'document_date', label: t('Document date') },
    { key: 'attachments_count', label: t('Files') },
    { key: 'creator.name', label: t('Added by') },
    { key: 'created_at', label: t('Created at') },
    { key: 'actions', label: t('Actions'), locked: true },
]);

function onCategoryChange(value: string) {
    apply({ category: value });
}

function rowActions(item: Item): RowActionItem[] {
    return [
        viewAction(`/archive/${item.id}`),
        editAction(`/archive/${item.id}/edit`, 'archive.edit'),
        deleteAction(
            {
                href: `/archive/${item.id}`,
                title: t('Delete archive document'),
                description: t('Are you sure you want to delete this archive document?'),
                trashType: 'archive_documents',
            },
            'archive.delete',
        ),
    ];
}
</script>

<template>
    <Head :title="t('Archive')" />

    <V2ListPage>
        <V2Hero image="/images/dashboard-v2-building.png">
            <template #eyebrow>{{ t('Documents') }}</template>
            <template #title>{{ t('Archive') }}</template>
            <template #description>{{ t('Store contracts and other files.') }}</template>
            <template #stats>
                <V2StatGrid>
                    <V2StatCard :delay="0" :title="t('Documents')" :value="totals.count">
                        <template #icon><Archive /></template>
                    </V2StatCard>
                </V2StatGrid>
            </template>
        </V2Hero>

        <V2TablePanel
            table-id="archive"
            :columns="tableColumns"
            :pending="pending && items.data.length > 0"
            :print-title="t('Archive')"
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
                        :label="t('Category')"
                        :model-value="filters.category || ''"
                        @change="onCategoryChange"
                    >
                        <option value="">{{ t('All') }}</option>
                        <option
                            v-for="cat in categories"
                            :key="cat.value"
                            :value="cat.value"
                        >
                            {{ cat.label }}
                        </option>
                    </V2SelectFilter>
                    <template #columns>
                        <MisTableActions :filters="filters">
                            <CreateButton href="/archive/create" permission="archive.create">
                                {{ t('Create') }}
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
                            <SortableTh column="title">{{ t('Title') }}</SortableTh>
                            <SortableTh column="category_label">{{ t('Category') }}</SortableTh>
                            <SortableTh column="document_date">{{ t('Document date') }}</SortableTh>
                            <SortableTh column="attachments_count">{{ t('Files') }}</SortableTh>
                            <SortableTh column="creator.name">{{ t('Added by') }}</SortableTh>
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
                            <TableIndexTd :index="index" :from="items.meta?.from" />
                            <td>
                                <Link :href="`/archive/${item.id}`" class="code-chip">
                                    {{ item.title || '—' }}
                                </Link>
                            </td>
                            <td><span class="pill">{{ item.category_label || item.category }}</span></td>
                            <td class="muted nowrap">{{ formatDate(item.document_date) }}</td>
                            <td>
                                <span class="pill inline-flex items-center gap-1">
                                    <FileStack class="size-3.5 opacity-70" />
                                    {{ item.attachments_count ?? 0 }}
                                </span>
                            </td>
                            <td class="muted">{{ item.creator?.name || '—' }}</td>
                            <td class="muted nowrap">{{ formatDate(item.created_at) }}</td>
                            <td class="end">
                                <RowActionsMenu :actions="rowActions(item)" />
                            </td>
                        </tr>
                        <EmptyState
                            v-if="!items.data.length"
                            :colspan="visibleColCount"
                            :title="t('No archive documents yet')"
                        />
                    </tbody>
                </table>
            </template>
            <template #pager><V2Pager :items="items" :only="onlyKeys" /></template>
        </V2TablePanel>
    </V2ListPage>
</template>
