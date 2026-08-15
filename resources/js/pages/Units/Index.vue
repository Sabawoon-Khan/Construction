<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Trash2 } from '@lucide/vue';
import { ref } from 'vue';
import EmptyState from '@/components/EmptyState.vue';
import Heading from '@/components/Heading.vue';
import { MisCreateButton, MisStatCard, MisStatGrid } from '@/components/mis';
import Pagination from '@/components/Pagination.vue';
import RowActionsMenu from '@/components/RowActionsMenu.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { useMisPage } from '@/composables/useMisPage';
import { useTranslations } from '@/composables/useTranslations';
import type { RowActionItem } from '@/lib/row-actions';
import { money } from '@/lib/utils';

const props = defineProps<{
    items: { data: any[]; links: any[]; meta?: any };
    filters: any;
    projects: any[];
    totals: { count: number; available: number; reserved: number; sold: number };
}>();
const search = ref(props.filters.search ?? '');
const projectId = ref(String(props.filters.project_id ?? ''));
const status = ref(props.filters.status ?? '');
const { t } = useTranslations();
const { viewAction, editAction, can } = useMisPage();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Units', href: '/units' },
        ],
    },
});

const apply = () =>
    router.get(
        '/units',
        {
            search: search.value || undefined,
            project_id: projectId.value || undefined,
            status: status.value || undefined,
        },
        { preserveState: true, replace: true },
    );

const unitStatuses = ['available', 'reserved', 'sold'];

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
                description: t('Are you sure you want to delete this unit? This action cannot be undone.'),
                confirmLabel: t('Delete'),
            },
        },
    ];
}
</script>

<template>
    <Head :title="t('Units & Homes')" />
    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <Heading :title="t('Units & Homes')" :description="t('List prices, specs, and sale status for every unit.')" />
            <div class="flex flex-wrap gap-2">
                <Input v-model="search" :placeholder="t('Unit no...')" @keyup.enter="apply" />
                <select v-model="projectId" class="h-9 rounded-md border border-input bg-background px-3 text-sm" @change="apply">
                    <option value="">{{ t('All projects') }}</option>
                    <option v-for="p in projects" :key="p.id" :value="String(p.id)">{{ p.name }}</option>
                </select>
                <select v-model="status" class="h-9 rounded-md border border-input bg-background px-3 text-sm" @change="apply">
                    <option value="">{{ t('All statuses') }}</option>
                    <option value="available">{{ t('Available') }}</option>
                    <option value="reserved">{{ t('Reserved') }}</option>
                    <option value="sold">{{ t('Sold') }}</option>
                </select>
                <Button variant="secondary" @click="apply">{{ t('Filter') }}</Button>
                <MisCreateButton href="/units/create" permission="units.create">{{ t('Add unit') }}</MisCreateButton>
            </div>
        </div>

        <MisStatGrid :columns="4">
            <MisStatCard :label="t('Units')" :value="totals.count" />
            <MisStatCard :label="t('Available')" :value="totals.available" tone="success" />
            <MisStatCard :label="t('Reserved')" :value="totals.reserved" tone="warning" />
            <MisStatCard :label="t('Sold')" :value="totals.sold" />
        </MisStatGrid>

        <div class="overflow-hidden rounded-xl border border-sidebar-border/70 bg-card shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="border-b bg-muted/40 text-left text-muted-foreground">
                        <tr>
                            <th class="px-4 py-3 font-medium">{{ t('Unit') }}</th>
                            <th class="px-4 py-3 font-medium">{{ t('Project') }}</th>
                            <th class="px-4 py-3 font-medium">{{ t('Floor') }}</th>
                            <th class="px-4 py-3 font-medium">{{ t('Type') }}</th>
                            <th class="px-4 py-3 font-medium">{{ t('Area') }}</th>
                            <th class="px-4 py-3 font-medium">{{ t('Beds') }}</th>
                            <th class="px-4 py-3 font-medium">{{ t('Planned cost') }}</th>
                            <th class="px-4 py-3 font-medium">{{ t('Sale price') }}</th>
                            <th class="px-4 py-3 font-medium">{{ t('Buyer') }}</th>
                            <th class="px-4 py-3 font-medium">{{ t('Status') }}</th>
                            <th class="px-4 py-3 font-medium text-right">{{ t('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="item in items.data" :key="item.id" class="border-b last:border-0 transition-colors hover:bg-muted/30">
                            <td class="px-4 py-3 font-medium">
                                <Link :href="`/units/${item.id}`" class="hover:text-primary hover:underline">{{ item.unit_no }}</Link>
                            </td>
                            <td class="px-4 py-3">{{ item.project?.name }}</td>
                            <td class="px-4 py-3">{{ item.floor_number ?? '—' }}</td>
                            <td class="px-4 py-3 capitalize">{{ item.type }}</td>
                            <td class="px-4 py-3">{{ item.area ?? '—' }}</td>
                            <td class="px-4 py-3">{{ item.bedrooms ?? '—' }}</td>
                            <td class="px-4 py-3 tabular-nums">{{ money(item.cost_price, item.currency || 'USD') }}</td>
                            <td class="px-4 py-3 tabular-nums">{{ money(item.list_price, item.currency || 'USD') }}</td>
                            <td class="px-4 py-3">{{ item.sales?.[0]?.customer?.name || '—' }}</td>
                            <td class="px-4 py-3"><StatusBadge :status="item.status" /></td>
                            <td class="px-4 py-3">
                                <div class="flex justify-end">
                                    <RowActionsMenu :actions="rowActions(item)" />
                                </div>
                            </td>
                        </tr>
                        <EmptyState v-if="!items.data.length" :colspan="11" :title="t('No units yet')" />
                    </tbody>
                </table>
            </div>
            <Pagination :paginator="items" />
        </div>
    </div>
</template>
