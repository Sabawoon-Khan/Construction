<script setup lang="ts">
import { Form, Head, router } from '@inertiajs/vue3';
import { Pencil, Plus, Trash2 } from '@lucide/vue';
import { computed, ref } from 'vue';
import Can from '@/components/Can.vue';
import EmptyState from '@/components/EmptyState.vue';
import SortableTh from '@/components/SortableTh.vue';
import TableFrame from '@/components/TableFrame.vue';
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

const props = defineProps<{
    items: { id: number; unit_name: string }[];
    filters?: { search?: string };
    totals?: { count: number };
}>();

const { t } = useTranslations();
const { can } = useMisPage();
const tableColumns = computed(() => [
    { key: 'unit_name', label: t('Unit name') },
    { key: 'creator.name', label: t('Added by') },
    { key: 'actions', label: t('Actions'), locked: true },
]);
const search = ref(props.filters?.search ?? '');
const { sortedRows } = provideTableSort(() => props.items);
const dialogOpen = ref(false);
const editing = ref<{ id: number; unit_name: string } | null>(null);
const isEdit = computed(() => !!editing.value);

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Expense units', href: '/expense-units' },
        ],
    },
});

function applySearch() {
    router.get('/expense-units', { search: search.value || undefined }, { preserveState: true, replace: true });
}

function clearFilters() {
    search.value = '';
    applySearch();
}

function openCreate() {
    editing.value = null;
    dialogOpen.value = true;
}

function openEdit(item: { id: number; unit_name: string }) {
    editing.value = item;
    dialogOpen.value = true;
}

function rowActions(item: { id: number; unit_name: string }): RowActionItem[] {
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
            href: `/expense-units/${item.id}`,
            variant: 'destructive' as const,
            hidden: !can('expenses.delete'),
            confirm: {
                title: t('Delete unit'),
                description: t('Are you sure you want to delete this unit?'),
                confirmLabel: t('Delete'),
            },
        },
    ];
}
</script>

<template>
    <Head :title="t('Expense units')" />

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
            <MisStatCard :label="t('Units')" :value="totals?.count ?? items.length" />
        </MisStatGrid>

        <div class="overflow-hidden rounded-2xl border border-border/80 bg-card shadow-soft">
            <TableFrame table-id="expense-units" :columns="tableColumns">
                <table class="min-w-full text-sm">
                    <thead class="bg-muted/30 text-start text-muted-foreground">
                        <tr>
                            <SortableTh column="unit_name">{{ t('Unit name') }}</SortableTh>
                            <SortableTh column="creator.name">{{ t('Added by') }}</SortableTh>

                            <th class="px-4 py-3 font-medium text-end">{{ t('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="item in sortedRows"
                            :key="item.id"
                            class="border-b last:border-0 transition-colors hover:bg-muted/30"
                        >
                            <td class="px-4 py-3 font-medium">{{ item.unit_name }}</td>
                            <td class="px-4 py-3 text-muted-foreground">{{ item.creator?.name || '—' }}</td>

                            <td class="px-4 py-3">
                                <div class="flex justify-end">
                                    <RowActionsMenu :actions="rowActions(item)" />
                                </div>
                            </td>
                        </tr>
                        <EmptyState v-if="!items.length" :colspan="3" :title="t('No units yet')" />
                    </tbody>
                </table>
            </TableFrame>
        </div>
    </div>

    <Dialog v-model:open="dialogOpen">
        <DialogContent class="sm:max-w-md">
            <Form
                :action="isEdit ? `/expense-units/${editing!.id}` : '/expense-units'"
                :method="isEdit ? 'put' : 'post'"
                class="grid gap-4"
                :key="editing?.id ?? 'create'"
                v-slot="{ errors, processing }"
            >
                <DialogHeader>
                    <DialogTitle>{{ isEdit ? t('Edit') : t('Add') }} {{ t('Expense units') }}</DialogTitle>
                </DialogHeader>
                <div class="grid gap-2">
                    <Label for="unit_name">{{ t('Unit name') }}</Label>
                    <Input
                        id="unit_name"
                        name="unit_name"
                        required
                        :default-value="editing?.unit_name ?? ''"
                    />
                    <InputError :message="errors.unit_name" />
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
