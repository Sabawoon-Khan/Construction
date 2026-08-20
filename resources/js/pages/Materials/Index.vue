<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Trash2 } from '@lucide/vue';
import { ref } from 'vue';
import EmptyState from '@/components/EmptyState.vue';
import { MisFilterBar, MisLoadingState, MisSearchInput, MisStatCard, MisStatGrid } from '@/components/mis';
import Pagination from '@/components/Pagination.vue';
import RowActionsMenu from '@/components/RowActionsMenu.vue';
import { useInertiaLoading } from '@/composables/useInertiaLoading';
import { useMisPage } from '@/composables/useMisPage';
import { useTranslations } from '@/composables/useTranslations';
import type { RowActionItem } from '@/lib/row-actions';
import { money } from '@/lib/utils';

type Item = Record<string, any>;

const props = defineProps<{
    items: { data: Item[]; links: any[]; meta?: any };
    filters: { search?: string };
    totals: { count: number; active: number };
}>();

const search = ref(props.filters.search ?? '');
const { t } = useTranslations();
const { editAction, can } = useMisPage();
const { loading } = useInertiaLoading();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Materials', href: '/materials' },
        ],
    },
});

function applySearch() {
    router.get('/materials', { search: search.value || undefined }, { preserveState: true, replace: true });
}

function clearFilters() {
    search.value = '';
    applySearch();
}

function rowActions(item: Item): RowActionItem[] {
    return [
        editAction(`/materials/${item.id}/edit`, 'materials.edit'),
        { separator: true, isLabel: true, label: t('Danger') },
        {
            label: t('Delete'),
            icon: Trash2,
            method: 'delete',
            href: `/materials/${item.id}`,
            variant: 'destructive' as const,
            hidden: !can('materials.delete'),
            confirm: {
                title: t('Delete material'),
                description: t('Are you sure you want to delete this material?'),
                confirmLabel: t('Delete'),
            },
        },
    ];
}
</script>

<template>
    <Head :title="t('Materials')" />

    <div class="flex h-full flex-1 flex-col gap-4">
        <MisFilterBar :apply-label="t('Apply')" :clear-label="t('All')" @apply="applySearch" @clear="clearFilters">
            <MisSearchInput v-model="search" class="min-w-[220px] flex-1" :placeholder="t('Search materials...')" />
        </MisFilterBar>

        <MisStatGrid :columns="2">
            <MisStatCard :label="t('Materials')" :value="totals.count" />
            <MisStatCard :label="t('Active')" :value="totals.active" />
        </MisStatGrid>

        <MisLoadingState v-if="loading" />

        <div
            v-else
            class="overflow-hidden rounded-2xl border border-border/80 bg-card shadow-soft"
        >
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-muted/30 text-start text-muted-foreground">
                        <tr>
                            <th class="px-4 py-3 font-medium">{{ t('Code') }}</th>
                            <th class="px-4 py-3 font-medium">{{ t('Name') }}</th>
                            <th class="px-4 py-3 font-medium">{{ t('Unit') }}</th>
                            <th class="px-4 py-3 font-medium">{{ t('Unit cost') }}</th>
                            <th class="px-4 py-3 font-medium">{{ t('Min stock') }}</th>
                            <th class="px-4 py-3 font-medium">{{ t('Added by') }}</th>

                            <th class="px-4 py-3 font-medium text-end">{{ t('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="item in items.data" :key="item.id" class="border-b last:border-0 transition-colors hover:bg-muted/30">
                            <td class="px-4 py-3 font-medium">{{ item.code }}</td>
                            <td class="px-4 py-3">{{ item.name }}</td>
                            <td class="px-4 py-3">{{ item.unit?.unit_name || '—' }}</td>
                            <td class="px-4 py-3 tabular-nums">{{ money(item.unit_cost, item.currency) }}</td>
                            <td class="px-4 py-3 tabular-nums">{{ item.minimum_stock }}</td>
                            <td class="px-4 py-3 text-muted-foreground">{{ item.creator?.name || '—' }}</td>

                            <td class="px-4 py-3">
                                <div class="flex justify-end">
                                    <RowActionsMenu :actions="rowActions(item)" />
                                </div>
                            </td>
                        </tr>
                        <EmptyState v-if="!items.data.length" :colspan="7" :title="t('No materials yet')" />
                    </tbody>
                </table>
            </div>
            <Pagination :paginator="items" />
        </div>
    </div>
</template>
