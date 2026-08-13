<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { Pencil, Plus, Trash2 } from '@lucide/vue';
import { computed, ref } from 'vue';
import {
    MisEmptyState,
    MisListPage,
    MisListRow,
    MisSegmentedControl,
} from '@/components/mis';
import RowActionsMenu from '@/components/RowActionsMenu.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useTranslations } from '@/composables/useTranslations';
import type { RowActionItem } from '@/lib/row-actions';

type LookupGroup = { key: string; label: string };
type LookupRow = {
    id: number;
    name: string;
    sort_order: number;
    is_active: boolean;
};

const props = defineProps<{
    groups: LookupGroup[];
    activeGroup: string;
    items: Record<string, LookupRow[]>;
}>();

const { t } = useTranslations();

const rows = computed(() => props.items[props.activeGroup] ?? []);

const segmentItems = computed(() =>
    props.groups.map((group) => ({
        id: group.key,
        label: t(group.label),
    })),
);

const activeGroupModel = computed({
    get: () => props.activeGroup,
    set: (key: string) => {
        router.get('/settings/lookups', { group: key }, { preserveState: true });
    },
});

const form = useForm({
    name: '',
    sort_order: 0,
    is_active: true,
});

const editingId = ref<number | null>(null);
const editForm = useForm({
    name: '',
    sort_order: 0,
    is_active: true,
});

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Settings', href: '/settings/profile' },
            { title: 'Lookups', href: '/settings/lookups' },
        ],
    },
});

function createItem(): void {
    form.post(`/settings/lookups/${props.activeGroup}`, {
        preserveScroll: true,
        onSuccess: () => form.reset('name'),
    });
}

function startEdit(row: LookupRow): void {
    editingId.value = row.id;
    editForm.name = row.name;
    editForm.sort_order = row.sort_order;
    editForm.is_active = row.is_active;
}

function saveEdit(): void {
    if (!editingId.value) {
        return;
    }

    editForm.put(`/settings/lookups/${props.activeGroup}/${editingId.value}`, {
        preserveScroll: true,
        onSuccess: () => {
            editingId.value = null;
        },
    });
}

function rowActions(row: LookupRow): RowActionItem[] {
    return [
        {
            label: t('Edit'),
            icon: Pencil,
            onClick: () => startEdit(row),
        },
        {
            label: t('Move to trash'),
            icon: Trash2,
            separator: true,
            variant: 'destructive',
            method: 'delete',
            href: `/settings/lookups/${props.activeGroup}/${row.id}`,
            confirm: {
                title: t('Move to trash'),
                description: t('This item will move to trash. You can restore it later.'),
                confirmLabel: t('Move to trash'),
            },
            confirmVariant: 'destructive',
        },
    ];
}
</script>

<template>
    <Head :title="t('Lookups')" />

    <MisListPage
        :title="t('Lookups')"
        :description="t('Manage shared reference lists for your MIS modules.')"
    >
        <template #actions>
            <MisSegmentedControl
                v-model="activeGroupModel"
                :items="segmentItems"
            />
        </template>

        <form
            class="grid gap-3 sm:grid-cols-[1fr_auto_auto]"
            @submit.prevent="createItem"
        >
            <div class="space-y-1.5">
                <Label for="lookup-name">{{ t('Name') }}</Label>
                <Input
                    id="lookup-name"
                    v-model="form.name"
                    required
                    maxlength="255"
                />
            </div>
            <div class="space-y-1.5">
                <Label for="lookup-sort">{{ t('Sort') }}</Label>
                <Input
                    id="lookup-sort"
                    v-model.number="form.sort_order"
                    type="number"
                    min="0"
                    class="w-24"
                />
            </div>
            <div class="flex items-end">
                <Button type="submit" :disabled="form.processing">
                    <Plus class="size-4" />
                    {{ t('Create') }}
                </Button>
            </div>
        </form>

        <MisEmptyState v-if="rows.length === 0">
            {{ t('No items yet.') }}
        </MisEmptyState>

        <div v-else class="space-y-2">
            <MisListRow
                v-for="row in rows"
                :key="row.id"
                class="flex items-center justify-between gap-3"
            >
                <div v-if="editingId === row.id" class="flex flex-1 gap-2">
                    <Input v-model="editForm.name" class="max-w-sm" />
                    <Button
                        size="sm"
                        :disabled="editForm.processing"
                        @click="saveEdit"
                    >
                        {{ t('Save') }}
                    </Button>
                    <Button
                        size="sm"
                        variant="ghost"
                        @click="editingId = null"
                    >
                        {{ t('Cancel') }}
                    </Button>
                </div>
                <div v-else class="min-w-0">
                    <p class="font-medium">{{ row.name }}</p>
                    <p class="text-xs text-muted-foreground">
                        #{{ row.sort_order }} ·
                        {{ row.is_active ? t('Active') : t('Disabled') }}
                    </p>
                </div>
                <RowActionsMenu
                    v-if="editingId !== row.id"
                    :actions="rowActions(row)"
                />
            </MisListRow>
        </div>
    </MisListPage>
</template>
