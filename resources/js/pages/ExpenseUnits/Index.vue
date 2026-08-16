<script setup lang="ts">
import { Form, Head, router } from '@inertiajs/vue3';
import { Pencil, Plus, Search, Trash2 } from '@lucide/vue';
import { ref } from 'vue';
import Can from '@/components/Can.vue';
import EmptyState from '@/components/EmptyState.vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import RowActionsMenu from '@/components/RowActionsMenu.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
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

function rowActions(item: { id: number; unit_name: string }): RowActionItem[] {
    return [
        {
            label: t('Edit'),
            icon: Pencil,
            onClick: () => { editingId.value = item.id; },
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
    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <Heading :title="t('Expense units')" :description="t('Units you can pick when recording a purchased item.')" />
            <div class="flex flex-wrap gap-2">
                <div class="relative min-w-[220px] flex-1">
                    <Search class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                    <Input v-model="search" class="pl-9" :placeholder="t('Search...')" @keyup.enter="applySearch" />
                </div>
                <Button variant="secondary" @click="applySearch">{{ t('Search') }}</Button>
            </div>
        </div>

        <Can permission="expenses.create">
            <Form action="/expense-units" method="post" class="flex max-w-xl flex-wrap items-end gap-3 rounded-xl border bg-card p-4" v-slot="{ errors, processing }">
                <div class="grid min-w-[220px] flex-1 gap-2">
                    <Label for="unit_name">{{ t('Unit name') }}</Label>
                    <Input id="unit_name" name="unit_name" required :placeholder="t('e.g. bag, kg, piece')" />
                    <InputError :message="errors.unit_name" />
                </div>
                <Button type="submit" :disabled="processing">
                    <Plus class="size-4" /> {{ processing ? t('Saving…') : t('Add unit') }}
                </Button>
            </Form>
        </Can>

        <div class="overflow-hidden rounded-xl border bg-card shadow-sm">
            <table class="min-w-full text-sm">
                <thead class="border-b bg-muted/40 text-left text-muted-foreground">
                    <tr>
                        <th class="px-4 py-3 font-medium">{{ t('Unit name') }}</th>
                        <th class="px-4 py-3 font-medium text-right">{{ t('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="item in items" :key="item.id" class="border-b last:border-0">
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
                                <Button type="button" size="sm" variant="ghost" @click="editingId = null">{{ t('Cancel') }}</Button>
                                <InputError :message="errors.unit_name" />
                            </Form>
                            <span v-else>{{ item.unit_name }}</span>
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
</template>
