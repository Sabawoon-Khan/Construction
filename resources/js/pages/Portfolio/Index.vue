<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { Trash2 } from '@lucide/vue';
import { computed, ref } from 'vue';
import EmptyState from '@/components/EmptyState.vue';
import SortableTh from '@/components/SortableTh.vue';
import TableFrame from '@/components/TableFrame.vue';
import { MisFilterBar, MisLoadingState, MisSearchInput, MisStatCard, MisStatGrid } from '@/components/mis';
import Pagination from '@/components/Pagination.vue';
import RowActionsMenu from '@/components/RowActionsMenu.vue';
import { useInertiaLoading } from '@/composables/useInertiaLoading';
import { useMisPage } from '@/composables/useMisPage';
import { provideTableSort } from '@/composables/useTableSort';
import { useTranslations } from '@/composables/useTranslations';
import type { RowActionItem } from '@/lib/row-actions';

type Item = Record<string, any>;

const props = defineProps<{
    items: { data: Item[]; links: any[]; meta?: any };
    filters: { search?: string };
    totals: { count: number; published: number };
}>();

const search = ref(props.filters.search ?? '');
const { sortedRows } = provideTableSort(() => props.items.data);
const { t } = useTranslations();
const tableColumns = computed(() => [
    { key: 'title', label: t('Title') },
    { key: 'category', label: t('Category') },
    { key: 'location', label: t('Location') },
    { key: 'media.length', label: t('Media') },
    { key: 'is_published', label: t('Published') },
    { key: 'created_at', label: t('Created at') },
    { key: 'actions', label: t('Actions'), locked: true },
]);
const { editAction, can, formatDate } = useMisPage();
const { loading } = useInertiaLoading();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Portfolio', href: '/portfolio' },
        ],
    },
});

function applySearch() {
    router.get('/portfolio', { search: search.value || undefined }, { preserveState: true, replace: true });
}

function clearFilters() {
    search.value = '';
    applySearch();
}

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

    <div class="flex h-full flex-1 flex-col gap-4">
        <MisFilterBar :apply-label="t('Apply')" :clear-label="t('All')" @apply="applySearch" @clear="clearFilters">
            <MisSearchInput v-model="search" class="min-w-[220px] flex-1" :placeholder="t('Search portfolio...')" />
        </MisFilterBar>

        <MisStatGrid :columns="2">
            <MisStatCard :label="t('Portfolio')" :value="totals.count" />
            <MisStatCard :label="t('Published')" :value="totals.published" />
        </MisStatGrid>

        <MisLoadingState v-if="loading" />

        <div
            v-else
            class="overflow-hidden rounded-2xl border border-border/80 bg-card shadow-soft"
        >
            <TableFrame table-id="portfolio" :columns="tableColumns">
                <table class="min-w-full text-sm">
                    <thead class="bg-muted/30 text-start text-muted-foreground">
                        <tr>
                            <SortableTh column="title">{{ t('Title') }}</SortableTh>
                            <SortableTh column="category">{{ t('Category') }}</SortableTh>
                            <SortableTh column="location">{{ t('Location') }}</SortableTh>
                            <SortableTh column="media.length">{{ t('Media') }}</SortableTh>
                            <SortableTh column="is_published">{{ t('Published') }}</SortableTh>
                            <SortableTh column="created_at">{{ t('Created at') }}</SortableTh>
                            <th class="px-4 py-3 font-medium text-end">{{ t('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="item in sortedRows"
                            :key="item.id"
                            class="border-b last:border-0 transition-colors hover:bg-muted/30"
                        >
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <div
                                        class="size-10 shrink-0 overflow-hidden rounded-lg bg-muted"
                                    >
                                        <img
                                            v-if="item.media?.[0]?.type === 'image' && item.media?.[0]?.url"
                                            :src="item.media[0].url"
                                            :alt="item.title"
                                            class="size-full object-cover"
                                        />
                                        <video
                                            v-else-if="item.media?.[0]?.type === 'video' && item.media?.[0]?.url"
                                            :src="item.media[0].url"
                                            class="size-full object-cover"
                                            muted
                                            playsinline
                                        />
                                    </div>
                                    <div class="min-w-0">
                                        <div class="truncate font-medium">{{ item.title }}</div>
                                        <div class="truncate text-xs text-muted-foreground">{{ item.slug }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3">{{ item.category || '—' }}</td>
                            <td class="px-4 py-3">{{ item.location || '—' }}</td>
                            <td class="px-4 py-3 tabular-nums">{{ item.media_count ?? 0 }}</td>
                            <td class="px-4 py-3">
                                <span
                                    class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium"
                                    :class="
                                        item.is_published
                                            ? 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-400'
                                            : 'bg-muted text-muted-foreground'
                                    "
                                >
                                    {{ item.is_published ? t('Published') : t('Draft') }}
                                </span>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-muted-foreground">{{ formatDate(item.created_at) }}</td>
                            <td class="px-4 py-3">
                                <div class="flex justify-end">
                                    <RowActionsMenu :actions="rowActions(item)" />
                                </div>
                            </td>
                        </tr>
                        <EmptyState v-if="!items.data.length" :colspan="7" :title="t('No portfolio projects yet')" />
                    </tbody>
                </table>
            </TableFrame>
            <Pagination :paginator="items" />
        </div>
    </div>
</template>
