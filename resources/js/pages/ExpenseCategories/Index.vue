<script setup lang="ts">
import Can from '@/components/Can.vue';
import EmptyState from '@/components/EmptyState.vue';
import InputError from '@/components/InputError.vue';
import { MisSearchInput } from '@/components/mis';
import RowActionsMenu from '@/components/RowActionsMenu.vue';
import SortableTh from '@/components/SortableTh.vue';
import TableIndexTd from '@/components/TableIndexTd.vue';
import TableIndexTh from '@/components/TableIndexTh.vue';
import TableToolbar from '@/components/TableToolbar.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    V2FilterBar,
    V2Hero,
    V2IndicatorCard,
    V2ListPage,
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
import {
    FolderKanban,
    Link2,
    Link2Off,
    Pencil,
    Plus,
    Trash2,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import { Form, Head } from '@inertiajs/vue3';

type Category = {
    id: number;
    name: string;
    code?: string;
    description?: string | null;
    is_active?: boolean;
    expenses_count?: number;
    creator?: { name?: string | null } | null;
    created_at?: string | null;
};

const props = defineProps<{
    items: Category[];
    filters?: { search?: string };
    totals?: {
        count: number;
        active?: number;
        used?: number;
        unused?: number;
        expenses_linked?: number;
    };
    usageChart?: Array<{ key: string; label: string; value: number }>;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Expense categories', href: '/expense-categories' },
        ],
    },
});

const onlyKeys = ['items', 'filters', 'totals', 'usageChart'];

const { t } = useTranslations();
const { can, formatDate } = useMisPage();
const { filters, pending, apply, clear } = useMisFilters(
    '/expense-categories',
    { search: props.filters?.search ?? '' },
    { search: '' },
    { only: onlyKeys, liveKeys: ['search'] },
);
const tableColumns = computed(() => [
    indexTableColumn(),
    { key: 'name', label: t('Category') },
    { key: 'expenses_count', label: t('Used in') },
    { key: 'creator.name', label: t('Added by') },
    { key: 'created_at', label: t('Created at') },
    { key: 'actions', label: t('Actions'), locked: true },
]);
const { sortedRows } = provideTableSort(() => props.items);
const dialogOpen = ref(false);
const editing = ref<Category | null>(null);
const isEdit = computed(() => !!editing.value);

const usageMix = computed(() => {
    const used = Number(props.totals?.used ?? 0);
    const unused = Number(props.totals?.unused ?? 0);
    const total = Math.max(used + unused, Number(props.totals?.count ?? 0), 0);
    const usedShare = total > 0 ? Math.round((used / total) * 100) : 0;

    const segments = [
        { key: 'used', label: t('Used'), value: used, color: 'var(--brand-accent)' },
        { key: 'unused', label: t('Unused'), value: unused, color: 'var(--muted-foreground)' },
    ].map((seg) => ({
        ...seg,
        width:
            total > 0
                ? Math.max(seg.value > 0 ? 6 : 0, (seg.value / total) * 100)
                : 0,
    }));

    return { used, usedShare, segments };
});

const usageBars = computed(() => {
    const rows = props.usageChart ?? [];
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

function openCreate() {
    editing.value = null;
    dialogOpen.value = true;
}

function openEdit(item: Category) {
    editing.value = item;
    dialogOpen.value = true;
}

function rowActions(item: Category): RowActionItem[] {
    return [
        {
            label: t('Edit'),
            icon: Pencil,
            onClick: () => openEdit(item),
            hidden: !can('expenses.edit'),
        },
        { separator: true, isLabel: true, label: t('Danger') },
        {
            label: t('Delete'),
            icon: Trash2,
            method: 'delete',
            href: `/expense-categories/${item.id}`,
            variant: 'destructive' as const,
            hidden: !can('expenses.delete') || (item.expenses_count ?? 0) > 0,
            confirm: {
                title: t('Delete category'),
                description: t('Are you sure you want to delete this category?'),
                confirmLabel: t('Delete'),
            },
        },
    ];
}
</script>

<template>
    <Head :title="t('Expense categories')" />

    <V2ListPage>
        <V2Hero image="/images/dashboard-v2-building.png">
            <template #eyebrow>{{ t('Lookups') }}</template>
            <template #title>{{ t('Expense categories') }}</template>
            <template #description>
                {{ t('Organize spend — see which categories are linked to expenses.') }}
            </template>

            <template #side>
                <Can permission="expenses.create">
                    <button type="button" class="create-btn" @click="openCreate">
                        <Plus />
                        {{ t('Add') }}
                    </button>
                </Can>

                <div class="hero-cards">
                    <V2IndicatorCard card-class="inventory-card">
                        <template #head>{{ t('Usage') }}</template>
                        <template #meta
                            >{{ usageMix.usedShare }}% {{ t('Used') }}</template
                        >

                        <div class="inventory-hero">
                            <div class="inventory-hero-copy">
                                <strong>{{ usageMix.used }}</strong>
                                <small>{{ t('Used') }}</small>
                            </div>
                        </div>

                        <div class="inventory-bar" aria-hidden="true">
                            <i
                                v-for="seg in usageMix.segments"
                                :key="seg.key"
                                :style="{
                                    width: `${seg.width}%`,
                                    background: seg.color,
                                }"
                            />
                        </div>

                        <ul class="indicator-list compact">
                            <li
                                v-for="seg in usageMix.segments"
                                :key="seg.key"
                            >
                                <i :style="{ background: seg.color }" />
                                <span>{{ seg.label }}</span>
                                <b>{{ seg.value }}</b>
                            </li>
                        </ul>
                    </V2IndicatorCard>

                    <V2IndicatorCard card-class="money-card">
                        <template #head>{{ t('Linked expenses') }}</template>
                        <template #meta
                            >{{ totals?.expenses_linked ?? 0 }} {{ t('Total') }}</template
                        >

                        <div class="money-chart">
                            <div
                                v-for="bar in usageBars"
                                :key="bar.key"
                                class="money-col"
                                :class="{ peak: bar.peak }"
                                :title="`${bar.label}: ${bar.value}`"
                            >
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
                        :title="t('Categories')"
                        :value="totals?.count ?? items.length"
                    >
                        <template #icon><FolderKanban /></template>
                    </V2StatCard>
                    <V2StatCard
                        :delay="1"
                        icon-tone="teal"
                        :title="t('Active')"
                        :value="totals?.active ?? totals?.count ?? 0"
                    >
                        <template #icon><Link2 /></template>
                    </V2StatCard>
                    <V2StatCard
                        :delay="2"
                        icon-tone="warm"
                        :title="t('Used')"
                        :value="totals?.used ?? 0"
                    >
                        <template #icon><Link2 /></template>
                    </V2StatCard>
                    <V2StatCard
                        :delay="3"
                        accent
                        icon-tone="orange"
                        :title="t('Unused')"
                        :value="totals?.unused ?? 0"
                    >
                        <template #icon><Link2Off /></template>
                    </V2StatCard>
                </V2StatGrid>
            </template>
        </V2Hero>

        <V2TablePanel
            table-id="expense-categories"
            :columns="tableColumns"
            :pending="pending && items.length > 0"
        >
            <template #filters>
                <V2FilterBar>
                    <div class="filter-search">
                        <MisSearchInput
                            v-model="filters.search"
                            :placeholder="t('Search...')"
                            @submit="apply()"
                            @clear="clear"
                        />
                    </div>
                    <template #columns>
                        <TableToolbar />
                    </template>
                </V2FilterBar>
            </template>

            <template #default="{ visibleColCount }">
                <table>
                    <thead>
                        <tr>
                            <TableIndexTh />
                            <SortableTh column="name">{{ t('Category') }}</SortableTh>
                            <SortableTh column="expenses_count">{{ t('Used in') }}</SortableTh>
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
                            <TableIndexTd :index="index" />
                            <td>
                                <b>{{ item.name }}</b>
                            </td>
                            <td class="muted">
                                {{ item.expenses_count ?? 0 }} {{ t('expenses') }}
                            </td>
                            <td class="muted">{{ item.creator?.name || '—' }}</td>
                            <td class="muted nowrap">{{ formatDate(item.created_at) }}</td>
                            <td class="end">
                                <RowActionsMenu :actions="rowActions(item)" />
                            </td>
                        </tr>
                        <EmptyState
                            v-if="!items.length"
                            :colspan="visibleColCount"
                            :title="t('No categories yet')"
                        />
                    </tbody>
                </table>
            </template>
        </V2TablePanel>
    </V2ListPage>

    <Dialog v-model:open="dialogOpen">
        <DialogContent class="sm:max-w-md">
            <Form
                :action="isEdit ? `/expense-categories/${editing!.id}` : '/expense-categories'"
                :method="isEdit ? 'put' : 'post'"
                class="grid gap-4"
                :key="editing?.id ?? 'create'"
                v-slot="{ errors, processing }"
            >
                <DialogHeader>
                    <DialogTitle>{{ isEdit ? t('Edit') : t('Add') }} {{ t('Category') }}</DialogTitle>
                </DialogHeader>
                <div class="grid gap-2">
                    <Label for="category_name">{{ t('Category name') }}</Label>
                    <Input
                        id="category_name"
                        name="name"
                        required
                        :default-value="editing?.name ?? ''"
                    />
                    <InputError :message="errors.name" />
                </div>
                <div class="grid gap-2">
                    <Label for="category_description">{{ t('Description') }}</Label>
                    <textarea
                        id="category_description"
                        name="description"
                        rows="2"
                        class="rounded-md border border-input bg-transparent px-3 py-2 text-sm"
                        :default-value="editing?.description ?? ''"
                    />
                    <InputError :message="errors.description" />
                </div>
                <DialogFooter>
                    <Button type="button" variant="outline" @click="dialogOpen = false">
                        {{ t('Cancel') }}
                    </Button>
                    <Button type="submit" :disabled="processing">
                        {{ processing ? t('Saving…') : t('Save') }}
                    </Button>
                </DialogFooter>
            </Form>
        </DialogContent>
    </Dialog>
</template>
