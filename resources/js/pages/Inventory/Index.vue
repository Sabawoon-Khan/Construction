<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { PackageMinus, Pencil, Trash2 } from '@lucide/vue';
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
import { useCalendar } from '@/composables/useCalendar';
import { useMisPage } from '@/composables/useMisPage';
import { useTranslations } from '@/composables/useTranslations';
import type { RowActionItem } from '@/lib/row-actions';

type LatestIssue = {
    id: number;
    quantity: number;
    transaction_date?: string | null;
};

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
    latest_issue?: LatestIssue | null;
};

type IssueRow = {
    id: number;
    project_id: number;
    project_name?: string;
    material_id: number;
    material_name?: string;
    material_code?: string;
    unit_name?: string;
    quantity: number;
    transaction_date?: string | null;
};

const props = defineProps<{
    items: StockRow[];
    issues: IssueRow[];
    projects: { id: number; name: string }[];
    materials: { id: number; name: string; code: string; unit?: { unit_name: string } | null }[];
    filters: { project_id?: number | null; search?: string };
    totals: { rows: number; on_hand_rows: number; low_stock_rows: number };
}>();

const { t } = useTranslations();
const { can } = useMisPage();
const calendar = useCalendar();
const formatDate = (value: string | null | undefined) => calendar.format(value) || '—';

const showActions = computed(
    () => can('inventory.create') || can('inventory.edit') || can('inventory.delete'),
);

const tableColumns = computed(() => {
    const columns: { key: string; label: string; locked?: boolean }[] = [
        { key: 'project_name', label: t('Project') },
        { key: 'material_name', label: t('Material') },
        { key: 'unit_name', label: t('Unit') },
        { key: 'purchased', label: t('Purchased') },
        { key: 'issued', label: t('Issued') },
        { key: 'on_hand', label: t('On hand') },
    ];
    if (showActions.value) {
        columns.push({ key: 'actions', label: t('Actions'), locked: true });
    }
    return columns;
});

const issueTableColumns = computed(() => [
    { key: 'project_name', label: t('Project') },
    { key: 'material_name', label: t('Material') },
    { key: 'quantity', label: t('Issued qty') },
    { key: 'transaction_date', label: t('Issue date') },
    { key: 'actions', label: t('Actions'), locked: true },
]);

const projectId = ref(props.filters.project_id ? String(props.filters.project_id) : '');
const search = ref(props.filters.search ?? '');
const { sortedRows } = provideTableSort(() => props.items);
const { sortedRows: sortedIssues } = provideTableSort(() => props.issues);
const dialogOpen = ref(false);
const dialogPrefill = ref<{ project_id?: number; material_id?: number } | null>(null);
const editingIssue = ref<LatestIssue & { project_id: number; material_id: number } | null>(null);

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
    editingIssue.value = null;
    dialogPrefill.value = row
        ? { project_id: row.project_id, material_id: row.material_id }
        : null;
    dialogOpen.value = true;
}

function openEditDialog(
    issue: { id: number; quantity: number; transaction_date?: string | null; project_id?: number; material_id?: number },
    row?: Pick<StockRow, 'project_id' | 'material_id'>,
) {
    editingIssue.value = {
        id: issue.id,
        project_id: row?.project_id ?? issue.project_id ?? 0,
        material_id: row?.material_id ?? issue.material_id ?? 0,
        quantity: issue.quantity,
        transaction_date: issue.transaction_date,
    };
    dialogPrefill.value = {
        project_id: editingIssue.value.project_id,
        material_id: editingIssue.value.material_id,
    };
    dialogOpen.value = true;
}

function isLowStock(row: StockRow) {
    return row.minimum_stock > 0 && row.on_hand > 0 && row.on_hand <= row.minimum_stock;
}

function stockRowActions(row: StockRow): RowActionItem[] {
    return [
        {
            label: t('Issue from stock'),
            icon: PackageMinus,
            onClick: () => openIssueDialog(row),
            hidden: !can('inventory.create') || row.on_hand <= 0,
        },
        {
            label: t('Edit'),
            icon: Pencil,
            onClick: () => openEditDialog(row.latest_issue!, row),
            hidden: !can('inventory.edit') || !row.latest_issue,
        },
        { separator: true, isLabel: true, label: t('Danger'), hidden: !can('inventory.delete') || !row.latest_issue },
        {
            label: t('Delete'),
            icon: Trash2,
            method: 'delete',
            href: row.latest_issue ? `/inventory/${row.latest_issue.id}` : '#',
            variant: 'destructive' as const,
            hidden: !can('inventory.delete') || !row.latest_issue,
            confirm: {
                title: t('Delete stock issue'),
                description: t('Delete this stock issue and restore the quantity on hand?'),
                confirmLabel: t('Delete'),
            },
        },
    ];
}

function issueRowActions(issue: IssueRow): RowActionItem[] {
    return [
        {
            label: t('Edit'),
            icon: Pencil,
            onClick: () => openEditDialog(issue),
            hidden: !can('inventory.edit'),
        },
        { separator: true, isLabel: true, label: t('Danger'), hidden: !can('inventory.delete') },
        {
            label: t('Delete'),
            icon: Trash2,
            method: 'delete',
            href: `/inventory/${issue.id}`,
            variant: 'destructive' as const,
            hidden: !can('inventory.delete'),
            confirm: {
                title: t('Delete stock issue'),
                description: t('Delete this stock issue and restore the quantity on hand?'),
                confirmLabel: t('Delete'),
            },
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
                            <th v-if="showActions" class="px-4 py-3 font-medium text-end">{{ t('Actions') }}</th>
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
                            <td v-if="showActions" class="px-4 py-3">
                                <div class="flex justify-end">
                                    <RowActionsMenu :actions="stockRowActions(row)" />
                                </div>
                            </td>
                        </tr>
                        <EmptyState
                            v-if="!items.length"
                            :colspan="showActions ? 7 : 6"
                            :title="t('No stock yet')"
                        />
                    </tbody>
                </table>
            </TableFrame>
        </div>

        <div class="overflow-hidden rounded-2xl border border-border/80 bg-card shadow-soft">
            <div class="border-b px-5 py-4">
                <h3 class="text-sm font-semibold">{{ t('Stock issue dates') }}</h3>
            </div>
            <TableFrame table-id="inventory-issues" :columns="issueTableColumns">
                <table class="min-w-full text-sm">
                    <thead class="bg-muted/30 text-start text-muted-foreground">
                        <tr>
                            <SortableTh column="project_name">{{ t('Project') }}</SortableTh>
                            <SortableTh column="material_name">{{ t('Material') }}</SortableTh>
                            <SortableTh column="quantity">{{ t('Issued qty') }}</SortableTh>
                            <SortableTh column="transaction_date">{{ t('Issue date') }}</SortableTh>
                            <th v-if="can('inventory.edit') || can('inventory.delete')" class="px-4 py-3 font-medium text-end">
                                {{ t('Actions') }}
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="issue in sortedIssues"
                            :key="issue.id"
                            class="border-b last:border-0 transition-colors hover:bg-muted/30"
                        >
                            <td class="px-4 py-3">{{ issue.project_name || '—' }}</td>
                            <td class="px-4 py-3">
                                <span>{{ issue.material_name || '—' }}</span>
                                <span v-if="issue.material_code" class="ms-2 text-muted-foreground">{{ issue.material_code }}</span>
                            </td>
                            <td class="px-4 py-3 tabular-nums">{{ issue.quantity }}</td>
                            <td class="px-4 py-3">{{ formatDate(issue.transaction_date) }}</td>
                            <td v-if="can('inventory.edit') || can('inventory.delete')" class="px-4 py-3">
                                <div class="flex justify-end">
                                    <RowActionsMenu :actions="issueRowActions(issue)" />
                                </div>
                            </td>
                        </tr>
                        <EmptyState
                            v-if="!issues.length"
                            :colspan="can('inventory.edit') || can('inventory.delete') ? 5 : 4"
                            :title="t('No issue dates yet')"
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
            :editing="editingIssue"
        />
    </div>
</template>
