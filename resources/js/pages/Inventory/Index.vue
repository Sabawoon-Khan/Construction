<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { PackageMinus } from '@lucide/vue';
import { computed, ref } from 'vue';
import Can from '@/components/Can.vue';
import EmptyState from '@/components/EmptyState.vue';
import IssueStockDialog from '@/components/IssueStockDialog.vue';
import SortableTh from '@/components/SortableTh.vue';
import TableFrame from '@/components/TableFrame.vue';
import { MisFilterBar, MisPageHeader, MisSearchInput, MisStatCard, MisStatGrid } from '@/components/mis';
import RowActionsMenu from '@/components/RowActionsMenu.vue';
import SettingsAddButton from '@/components/SettingsAddButton.vue';
import { provideTableSort } from '@/composables/useTableSort';
import { useMisPage } from '@/composables/useMisPage';
import { useTranslations } from '@/composables/useTranslations';
import type { RowActionItem } from '@/lib/row-actions';

type StockRow = {
    project_id: number;
    project_name?: string;
    material_id: number;
    material_name?: string;
    material_code?: string;
    unit_name?: string;
    purchased: number;
    issued: number;
    on_hand: number;
    minimum_stock: number;
};

const props = defineProps<{
    items: StockRow[];
    projects: { id: number; name: string }[];
    materials: { id: number; name: string; code: string; unit?: { unit_name: string } | null }[];
    filters: { project_id?: number | null; search?: string };
    totals: { rows: number; on_hand_rows: number; low_stock_rows: number };
}>();

const { t } = useTranslations();
const { can } = useMisPage();
const tableColumns = computed(() => {
    const columns: { key: string; label: string; locked?: boolean }[] = [
        { key: 'project_name', label: t('Project') },
        { key: 'material_name', label: t('Material') },
        { key: 'unit_name', label: t('Unit') },
        { key: 'purchased', label: t('Purchased') },
        { key: 'issued', label: t('Issued') },
        { key: 'on_hand', label: t('On hand') },
    ];
    if (can('inventory.create')) {
        columns.push({ key: 'actions', label: t('Actions'), locked: true });
    }
    return columns;
});
const projectId = ref(props.filters.project_id ? String(props.filters.project_id) : '');
const search = ref(props.filters.search ?? '');
const { sortedRows } = provideTableSort(() => props.items);
const dialogOpen = ref(false);
const dialogPrefill = ref<{ project_id?: number; material_id?: number } | null>(null);

const hasIssueableStock = computed(() => props.items.some((row) => row.on_hand > 0));

function applyFilter() {
    router.get('/inventory', {
        project_id: projectId.value || undefined,
        search: search.value || undefined,
    }, { preserveState: true, replace: true });
}

function clearFilters() {
    search.value = '';
    projectId.value = '';
    applyFilter();
}

function openIssueDialog(row?: StockRow) {
    dialogPrefill.value = row
        ? { project_id: row.project_id, material_id: row.material_id }
        : null;
    dialogOpen.value = true;
}

function isLowStock(row: StockRow) {
    return row.minimum_stock > 0 && row.on_hand > 0 && row.on_hand <= row.minimum_stock;
}

function rowActions(row: StockRow): RowActionItem[] {
    return [
        {
            label: t('Issue from stock'),
            icon: PackageMinus,
            onClick: () => openIssueDialog(row),
            hidden: !can('inventory.create') || row.on_hand <= 0,
        },
    ];
}

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Stock', href: '/inventory' },
        ],
    },
});
</script>

<template>
    <Head :title="t('Stock')" />
    <div class="flex h-full flex-1 flex-col gap-4">
        <MisPageHeader :title="t('Stock')">
            <template #actions>
                <Can permission="inventory.create">
                    <SettingsAddButton
                        :disabled="!hasIssueableStock"
                        @click="openIssueDialog()"
                    >
                        {{ t('Issue from stock') }}
                    </SettingsAddButton>
                </Can>
            </template>
        </MisPageHeader>

        <MisFilterBar :apply-label="t('Apply')" :clear-label="t('All')" @apply="applyFilter" @clear="clearFilters">
            <MisSearchInput v-model="search" class="min-w-[220px] flex-1" :placeholder="t('Search...')" />
            <select v-model="projectId" class="h-9 min-w-[180px] rounded-md border border-input bg-background px-3 text-sm">
                <option value="">{{ t('All projects') }}</option>
                <option v-for="project in projects" :key="project.id" :value="String(project.id)">{{ project.name }}</option>
            </select>
        </MisFilterBar>

        <MisStatGrid :columns="3">
            <MisStatCard :label="t('Stock rows')" :value="totals.rows" />
            <MisStatCard :label="t('With quantity on hand')" :value="totals.on_hand_rows" />
            <MisStatCard
                :label="t('Low stock')"
                :value="totals.low_stock_rows"
                :tone="totals.low_stock_rows > 0 ? 'warning' : undefined"
            />
        </MisStatGrid>

        <div class="overflow-hidden rounded-2xl border border-border/80 bg-card shadow-soft">
            <TableFrame table-id="inventory" :columns="tableColumns">
                <table class="min-w-full text-sm">
                    <thead class="bg-muted/30 text-start text-muted-foreground">
                        <tr>
                            <SortableTh column="project_name">{{ t('Project') }}</SortableTh>
                            <SortableTh column="material_name">{{ t('Material') }}</SortableTh>
                            <SortableTh column="unit_name">{{ t('Unit') }}</SortableTh>
                            <SortableTh column="purchased">{{ t('Purchased') }}</SortableTh>
                            <SortableTh column="issued">{{ t('Issued') }}</SortableTh>
                            <SortableTh column="on_hand">{{ t('On hand') }}</SortableTh>
                            <th v-if="can('inventory.create')" class="px-4 py-3 font-medium text-end">{{ t('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="(row, index) in sortedRows"
                            :key="`${row.project_id}-${row.material_id}-${index}`"
                            class="border-b last:border-0 transition-colors hover:bg-muted/30"
                            :class="isLowStock(row) ? 'bg-amber-500/5' : ''"
                        >
                            <td class="px-4 py-3">
                                <Link :href="`/projects/${row.project_id}`" class="font-medium hover:underline">{{ row.project_name }}</Link>
                            </td>
                            <td class="px-4 py-3">
                                <span>{{ row.material_name }}</span>
                                <span v-if="row.material_code" class="ms-2 text-muted-foreground">{{ row.material_code }}</span>
                            </td>
                            <td class="px-4 py-3">{{ row.unit_name || '—' }}</td>
                            <td class="px-4 py-3 tabular-nums">{{ row.purchased }}</td>
                            <td class="px-4 py-3 tabular-nums">{{ row.issued }}</td>
                            <td class="px-4 py-3 tabular-nums">
                                <span
                                    class="font-medium"
                                    :class="{
                                        'text-muted-foreground': row.on_hand <= 0,
                                        'text-amber-700 dark:text-amber-400': isLowStock(row),
                                    }"
                                >
                                    {{ row.on_hand }}
                                </span>
                                <span
                                    v-if="isLowStock(row)"
                                    class="ms-2 rounded-full bg-amber-500/15 px-2 py-0.5 text-xs font-medium text-amber-700 dark:text-amber-400"
                                >
                                    {{ t('Low stock') }}
                                </span>
                            </td>
                            <td v-if="can('inventory.create')" class="px-4 py-3">
                                <div class="flex justify-end">
                                    <RowActionsMenu :actions="rowActions(row)" />
                                </div>
                            </td>
                        </tr>
                        <EmptyState
                            v-if="!items.length"
                            :colspan="can('inventory.create') ? 7 : 6"
                            :title="t('No stock yet')"
                        />
                    </tbody>
                </table>
            </TableFrame>
        </div>

        <IssueStockDialog
            v-model:open="dialogOpen"
            :projects="projects"
            :materials="materials"
            :stock-rows="items"
            :prefill="dialogPrefill"
        />
    </div>
</template>
