<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Search, Trash2 } from '@lucide/vue';
import { ref } from 'vue';
import EmptyState from '@/components/EmptyState.vue';
import Heading from '@/components/Heading.vue';
import { MisCreateButton, MisStatCard, MisStatGrid } from '@/components/mis';
import Pagination from '@/components/Pagination.vue';
import RowActionsMenu from '@/components/RowActionsMenu.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
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

    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <Heading :title="t('Materials')" :description="t('Catalog of items you buy into stock and issue to projects.')" />
            <div class="flex flex-wrap gap-2">
                <div class="relative min-w-[220px] flex-1">
                    <Search class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                    <Input v-model="search" class="pl-9" :placeholder="t('Search materials...')" @keyup.enter="applySearch" />
                </div>
                <Button variant="secondary" @click="applySearch">{{ t('Search') }}</Button>
                <MisCreateButton href="/materials/create" permission="materials.create">{{ t('Add material') }}</MisCreateButton>
            </div>
        </div>

        <MisStatGrid :columns="2">
            <MisStatCard :label="t('Materials')" :value="totals.count" />
            <MisStatCard :label="t('Active')" :value="totals.active" />
        </MisStatGrid>

        <div class="overflow-hidden rounded-xl border border-sidebar-border/70 bg-card shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="border-b bg-muted/40 text-left text-muted-foreground">
                        <tr>
                            <th class="px-4 py-3 font-medium">{{ t('Code') }}</th>
                            <th class="px-4 py-3 font-medium">{{ t('Name') }}</th>
                            <th class="px-4 py-3 font-medium">{{ t('Unit') }}</th>
                            <th class="px-4 py-3 font-medium">{{ t('Unit cost') }}</th>
                            <th class="px-4 py-3 font-medium">{{ t('Min stock') }}</th>
                            <th class="px-4 py-3 font-medium text-right">{{ t('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="item in items.data" :key="item.id" class="border-b last:border-0 transition-colors hover:bg-muted/30">
                            <td class="px-4 py-3 font-medium">{{ item.code }}</td>
                            <td class="px-4 py-3">{{ item.name }}</td>
                            <td class="px-4 py-3">{{ item.unit?.unit_name || '—' }}</td>
                            <td class="px-4 py-3 tabular-nums">{{ money(item.unit_cost) }}</td>
                            <td class="px-4 py-3 tabular-nums">{{ item.minimum_stock }}</td>
                            <td class="px-4 py-3">
                                <div class="flex justify-end">
                                    <RowActionsMenu :actions="rowActions(item)" />
                                </div>
                            </td>
                        </tr>
                        <EmptyState v-if="!items.data.length" :colspan="6" :title="t('No materials yet')" :description="t('Add cement, steel, and other items before purchasing.')" />
                    </tbody>
                </table>
            </div>
            <Pagination :paginator="items" />
        </div>
    </div>
</template>
