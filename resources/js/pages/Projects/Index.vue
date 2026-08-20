<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Trash2 } from '@lucide/vue';
import { ref } from 'vue';
import EmptyState from '@/components/EmptyState.vue';
import { MisFilterBar, MisSearchInput, MisStatCard, MisStatGrid } from '@/components/mis';
import Pagination from '@/components/Pagination.vue';
import RowActionsMenu from '@/components/RowActionsMenu.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { translateStatus } from '@/lib/translateStatus';
import { useMisPage } from '@/composables/useMisPage';
import { useTranslations } from '@/composables/useTranslations';
import type { RowActionItem } from '@/lib/row-actions';
import { formatNumber, moneyByCurrency } from '@/lib/utils';

type Item = Record<string, any>;

const props = defineProps<{
    items: { data: Item[]; links: any[]; meta?: any };
    filters: { search?: string };
    totals: {
        count: number;
        active: number;
        units: number;
        sales_total: number;
        sales_by_currency?: Record<string, number>;
    };
}>();

const search = ref(props.filters.search ?? '');
const { t } = useTranslations();
const { viewAction, editAction, can } = useMisPage();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Projects', href: '/projects' },
        ],
    },
});

function applySearch() {
    router.get('/projects', { search: search.value || undefined }, { preserveState: true, replace: true });
}

function clearFilters() {
    search.value = '';
    applySearch();
}

const projectStatuses = ['planning', 'under_construction', 'ready_for_sale', 'sold', 'completed', 'on_hold'];

function statusLabel(status: string): string {
    return translateStatus(status, t);
}

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
        { separator: true, isLabel: true, label: t('Danger') },
        {
            label: t('Delete'),
            icon: Trash2,
            method: 'delete',
            href: `/projects/${item.id}`,
            variant: 'destructive' as const,
            hidden: !can('projects.delete'),
            confirm: {
                title: t('Delete project'),
                description: t('Are you sure you want to delete this project? This action cannot be undone.'),
                confirmLabel: t('Delete'),
            },
        },
    ];
}
</script>

<template>
    <Head :title="t('Projects')" />

    <div class="flex h-full flex-1 flex-col gap-4">
        <MisFilterBar :apply-label="t('Apply')" :clear-label="t('All')" @apply="applySearch" @clear="clearFilters">
            <MisSearchInput v-model="search" class="min-w-[220px] flex-1" :placeholder="t('Search projects...')" />
        </MisFilterBar>

        <MisStatGrid :columns="4">
            <MisStatCard :label="t('Projects')" :value="totals.count" />
            <MisStatCard :label="t('Active')" :value="totals.active" />
            <MisStatCard :label="t('Units')" :value="totals.units" />
            <MisStatCard :label="t('Sales total')" :value="moneyByCurrency(totals.sales_by_currency, totals.sales_total)" tone="success" />
        </MisStatGrid>

        <div class="overflow-hidden rounded-2xl border border-border/80 bg-card shadow-soft">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-muted/30 text-start text-muted-foreground">
                        <tr>
                            <th class="px-4 py-3 font-medium">{{ t('Code') }}</th>
                            <th class="px-4 py-3 font-medium">{{ t('Name') }}</th>
                            <th class="px-4 py-3 font-medium">{{ t('Location') }}</th>
                            <th class="px-4 py-3 font-medium">{{ t('Status') }}</th>
                            <th class="px-4 py-3 font-medium">{{ t('Progress') }}</th>
                            <th class="px-4 py-3 font-medium">{{ t('Units') }}</th>
                            <th class="px-4 py-3 font-medium">{{ t('Added by') }}</th>

                            <th class="px-4 py-3 text-end font-medium">{{ t('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="item in items.data"
                            :key="item.id"
                            class="border-b last:border-0 transition-colors hover:bg-muted/30"
                        >
                            <td class="px-4 py-3 font-medium">
                                <Link :href="`/projects/${item.id}`" class="hover:text-primary hover:underline">
                                    {{ item.code || '—' }}
                                </Link>
                            </td>
                            <td class="px-4 py-3">{{ item.name || '—' }}</td>
                            <td class="px-4 py-3">{{ item.location || '—' }}</td>
                            <td class="px-4 py-3"><StatusBadge :status="item.status" /></td>
                            <td class="px-4 py-3 tabular-nums">{{ formatNumber(item.progress_percent) }}%</td>
                            <td class="px-4 py-3">{{ item.units_count }}</td>
                            <td class="px-4 py-3 text-muted-foreground">{{ item.creator?.name || '—' }}</td>

                            <td class="px-4 py-3">
                                <div class="flex justify-end">
                                    <RowActionsMenu :actions="rowActions(item)" />
                                </div>
                            </td>
                        </tr>
                        <EmptyState
                            v-if="!items.data.length"
                            :colspan="8"
                            :title="t('No projects yet')"
                        />
                    </tbody>
                </table>
            </div>
            <Pagination :paginator="items" />
        </div>
    </div>
</template>
