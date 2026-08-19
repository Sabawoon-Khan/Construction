<script setup lang="ts">
import { Form, Head, router } from '@inertiajs/vue3';
import { Pencil, Plus, Trash2 } from '@lucide/vue';
import { ref } from 'vue';
import Can from '@/components/Can.vue';
import EmptyState from '@/components/EmptyState.vue';
import InputError from '@/components/InputError.vue';
import { MisSearchInput } from '@/components/mis';
import RowActionsMenu from '@/components/RowActionsMenu.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { useMisPage } from '@/composables/useMisPage';
import { useTranslations } from '@/composables/useTranslations';
import type { RowActionItem } from '@/lib/row-actions';

const props = defineProps<{
    items: { id: number; unit_name: string }[];
    filters?: { search?: string };
}>();

const { t } = useTranslations();
const { can } = useMisPage();
const editingId = ref<number | null>(null);
const search = ref(props.filters?.search ?? '');

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

function rowActions(item: { id: number; unit_name: string }): RowActionItem[] {
    return [
        {
            label: t('Edit'),
            icon: Pencil,
            onClick: () => {
                editingId.value = item.id;
            },
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
        <div
            class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-border/80 bg-card p-4 shadow-soft"
        >
            <form class="flex min-w-0 flex-1 flex-wrap items-center gap-2" @submit.prevent="applySearch">
                <MisSearchInput
                    v-model="search"
                    class="min-w-[220px] max-w-sm flex-1"
                    :placeholder="t('Search...')"
                    @submit="applySearch"
                    @clear="clearFilters"
                />
                <Button type="submit" size="sm">{{ t('Apply') }}</Button>
                <Button type="button" size="sm" variant="outline" @click="clearFilters">
                    {{ t('All') }}
                </Button>
            </form>

            <Can permission="expenses.create">
                <Form
                    action="/expense-units"
                    method="post"
                    class="flex items-center gap-2"
                    v-slot="{ errors, processing }"
                >
                    <div class="grid gap-1">
                        <Input
                            id="unit_name"
                            name="unit_name"
                            required
                            class="h-9 w-44 sm:w-52"
                            :placeholder="t('Unit name')"
                        />
                        <InputError :message="errors.unit_name" />
                    </div>
                    <Button type="submit" size="sm" class="shrink-0" :disabled="processing">
                        <Plus class="size-4" />
                        {{ processing ? t('Saving…') : t('Add') }}
                    </Button>
                </Form>
            </Can>
        </div>

        <div class="overflow-hidden rounded-2xl border border-border/80 bg-card shadow-soft">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-muted/30 text-left text-muted-foreground">
                        <tr>
                            <th class="px-4 py-3 font-medium">{{ t('Unit name') }}</th>
                            <th class="px-4 py-3 font-medium text-right">{{ t('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="item in items"
                            :key="item.id"
                            class="border-b last:border-0 transition-colors hover:bg-muted/30"
                        >
                            <td class="px-4 py-3">
                                <Form
                                    v-if="editingId === item.id"
                                    :action="`/expense-units/${item.id}`"
                                    method="put"
                                    class="flex max-w-md items-center gap-2"
                                    v-slot="{ errors, processing }"
                                >
                                    <Input name="unit_name" required :default-value="item.unit_name" />
                                    <Button type="submit" size="sm" :disabled="processing">{{ t('Save') }}</Button>
                                    <Button type="button" size="sm" variant="ghost" @click="editingId = null">
                                        {{ t('Cancel') }}
                                    </Button>
                                    <InputError :message="errors.unit_name" />
                                </Form>
                                <span v-else class="font-medium">{{ item.unit_name }}</span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex justify-end">
                                    <RowActionsMenu :actions="rowActions(item)" />
                                </div>
                            </td>
                        </tr>
                        <EmptyState v-if="!items.length" :colspan="2" :title="t('No units yet')" />
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>
