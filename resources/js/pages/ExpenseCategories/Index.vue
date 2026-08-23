<script setup lang="ts">
import { Form, Head, router } from '@inertiajs/vue3';
import { Pencil, Plus, Trash2 } from '@lucide/vue';
import { computed, ref } from 'vue';
import Can from '@/components/Can.vue';
import EmptyState from '@/components/EmptyState.vue';
import SortableTh from '@/components/SortableTh.vue';
import TableFrame from '@/components/TableFrame.vue';
import TableIndexTd from '@/components/TableIndexTd.vue';
import TableIndexTh from '@/components/TableIndexTh.vue';
import { indexTableColumn } from '@/composables/useTableColumns';
import InputError from '@/components/InputError.vue';
import { MisFilterBar, MisSearchInput, MisStatCard, MisStatGrid } from '@/components/mis';
import RowActionsMenu from '@/components/RowActionsMenu.vue';
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
import { useMisPage } from '@/composables/useMisPage';
import { provideTableSort } from '@/composables/useTableSort';
import { useTranslations } from '@/composables/useTranslations';
import type { RowActionItem } from '@/lib/row-actions';

type Category = {
    id: number;
    name: string;
    code?: string;
    description?: string | null;
    expenses_count?: number;
    created_at?: string | null;
};

const props = defineProps<{
    items: Category[];
    filters?: { search?: string };
    totals?: { count: number };
}>();

const { t } = useTranslations();
const { can, formatDate } = useMisPage();
const tableColumns = computed(() => [
    indexTableColumn(),
    { key: 'name', label: t('Category') },
    { key: 'expenses_count', label: t('Used in') },
    { key: 'creator.name', label: t('Added by') },
    { key: 'created_at', label: t('Created at') },
    { key: 'actions', label: t('Actions'), locked: true },
]);
const search = ref(props.filters?.search ?? '');
const { sortedRows } = provideTableSort(() => props.items);
const dialogOpen = ref(false);
const editing = ref<Category | null>(null);
const isEdit = computed(() => !!editing.value);

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Expense categories', href: '/expense-categories' },
        ],
    },
});

function applySearch() {
    router.get('/expense-categories', { search: search.value || undefined }, { preserveState: true, replace: true });
}

function clearFilters() {
    search.value = '';
    applySearch();
}

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

    <div class="flex h-full flex-1 flex-col gap-4">
        <MisFilterBar :apply-label="t('Apply')" :clear-label="t('All')" @apply="applySearch" @clear="clearFilters">
            <MisSearchInput
                v-model="search"
                class="min-w-[220px] flex-1"
                :placeholder="t('Search...')"
            />
            <template #actions>
                <Button type="submit" size="sm">{{ t('Apply') }}</Button>
                <Button type="button" size="sm" variant="outline" @click="clearFilters">
                    {{ t('All') }}
                </Button>
                <Can permission="expenses.create">
                    <Button type="button" size="sm" @click="openCreate">
                        <Plus class="size-4" />
                        {{ t('Add') }}
                    </Button>
                </Can>
            </template>
        </MisFilterBar>

        <MisStatGrid :columns="1">
            <MisStatCard :label="t('Categories')" :value="totals?.count ?? items.length" />
        </MisStatGrid>

        <div class="overflow-hidden rounded-2xl border border-border/80 bg-card shadow-soft">
            <TableFrame table-id="expense-categories" :columns="tableColumns">
                <table class="min-w-full text-sm">
                    <thead class="bg-muted/30 text-start text-muted-foreground">
                        <tr>
                            <TableIndexTh />
                            <SortableTh column="name">{{ t('Category') }}</SortableTh>
                            <SortableTh column="expenses_count">{{ t('Used in') }}</SortableTh>
                            <SortableTh column="creator.name">{{ t('Added by') }}</SortableTh>
                            <SortableTh column="created_at">{{ t('Created at') }}</SortableTh>
                            <th class="px-4 py-3 font-medium text-end">{{ t('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="(item, index) in sortedRows"
                            :key="item.id"
                            class="border-b last:border-0 transition-colors hover:bg-muted/30"
                        >
                            <TableIndexTd :index="index" />
                            <td class="px-4 py-3 font-medium">{{ item.name }}</td>
                            <td class="px-4 py-3 tabular-nums text-muted-foreground">
                                {{ item.expenses_count ?? 0 }} {{ t('expenses') }}
                            </td>
                            <td class="px-4 py-3 text-muted-foreground">{{ item.creator?.name || '—' }}</td>
                            <td class="px-4 py-3 whitespace-nowrap text-muted-foreground">{{ formatDate(item.created_at) }}</td>
                            <td class="px-4 py-3">
                                <div class="flex justify-end">
                                    <RowActionsMenu :actions="rowActions(item)" />
                                </div>
                            </td>
                        </tr>
                        <EmptyState v-if="!items.length" :colspan="6" :title="t('No categories yet')" />
                    </tbody>
                </table>
            </TableFrame>
        </div>
    </div>

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
