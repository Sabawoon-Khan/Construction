<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { RotateCcw, Trash2 } from '@lucide/vue';
import { computed, ref } from 'vue';
import Can from '@/components/Can.vue';
import {
    MisEmptyState,
    MisFilterBar,
    MisListPage,
    MisPagination,
    MisSearchInput,
} from '@/components/mis';
import RowActionsMenu from '@/components/RowActionsMenu.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { useCalendar } from '@/composables/useCalendar';
import { usePermissions } from '@/composables/usePermissions';
import { useTranslations } from '@/composables/useTranslations';
import type { Paginated } from '@/lib/format';
import type { RowActionItem } from '@/lib/row-actions';

type TrashItem = {
    type: string;
    label: string;
    id: string | number;
    title: string;
    deleted_at: string | null;
};

type TrashType = {
    key: string;
    label: string;
};

const props = defineProps<{
    items: Paginated<TrashItem>;
    types: TrashType[];
    filters?: {
        search?: string | null;
        type?: string | null;
    };
    retention_days: number;
}>();

const { t } = useTranslations();
const calendar = useCalendar();
const { can } = usePermissions();

const search = ref(props.filters?.search ?? '');
const type = ref(props.filters?.type ?? '');

const hasItems = computed(() => props.items.data.length > 0);

function applyFilters(): void {
    router.get(
        '/settings/trash',
        {
            search: search.value || undefined,
            type: type.value || undefined,
        },
        { preserveState: true, replace: true },
    );
}

function clearFilters(): void {
    search.value = '';
    type.value = '';
    applyFilters();
}

function rowActions(item: TrashItem): RowActionItem[] {
    const actions: RowActionItem[] = [];

    if (can('trash.restore')) {
        actions.push({
            label: t('Restore'),
            icon: RotateCcw,
            method: 'post',
            href: '/settings/trash/restore',
            data: { type: item.type, id: item.id },
            confirm: {
                title: t('Restore item?'),
                description: t('This will put the item back where it was.'),
                confirmLabel: t('Restore'),
            },
        });
    }

    if (can('trash.delete')) {
        actions.push({
            label: t('Delete forever'),
            icon: Trash2,
            variant: 'destructive',
            separator: true,
            method: 'delete',
            href: '/settings/trash/item',
            data: { type: item.type, id: item.id },
            confirm: {
                title: t('Delete forever?'),
                description: t(
                    'This cannot be undone. The item will be permanently removed.',
                ),
                confirmLabel: t('Delete forever'),
            },
        });
    }

    return actions;
}

function emptyTrash(): void {
    router.delete('/settings/trash', {
        onBefore: () =>
            window.confirm(
                t(
                    'Permanently delete everything in trash? This cannot be undone.',
                ),
            ),
    });
}
</script>

<template>
    <Head :title="t('Trash')" />

    <MisListPage
        :title="t('Trash')"
        :description="
            t(
                'Deleted items stay here for :days days, then are permanently removed.',
                { days: retention_days },
            )
        "
        :icon="Trash2"
    >
        <template #actions>
            <Can permission="trash.delete">
                <Button
                    v-if="hasItems"
                    variant="destructive"
                    size="sm"
                    @click="emptyTrash"
                >
                    {{ t('Empty trash') }}
                </Button>
            </Can>
        </template>

        <MisFilterBar @apply="applyFilters" @clear="clearFilters">
            <MisSearchInput v-model="search" @submit="applyFilters" />
            <select
                v-model="type"
                class="border-input bg-background h-9 rounded-md border px-3 text-sm"
                @change="applyFilters"
            >
                <option value="">{{ t('All types') }}</option>
                <option
                    v-for="entry in types"
                    :key="entry.key"
                    :value="entry.key"
                >
                    {{ entry.label }}
                </option>
            </select>
        </MisFilterBar>

        <MisEmptyState
            v-if="!hasItems"
            :title="t('Trash is empty')"
            :description="t('Deleted items will appear here.')"
            :icon="Trash2"
        />

        <div v-else class="divide-border divide-y rounded-lg border">
            <div
                v-for="item in items.data"
                :key="`${item.type}:${item.id}`"
                class="flex items-center gap-3 px-4 py-3"
            >
                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <p class="truncate font-medium">{{ item.title }}</p>
                        <Badge variant="secondary">{{ item.label }}</Badge>
                    </div>
                    <p class="text-muted-foreground mt-0.5 text-xs">
                        {{
                            item.deleted_at
                                ? calendar.formatDateTime(item.deleted_at)
                                : '—'
                        }}
                    </p>
                </div>
                <RowActionsMenu :actions="rowActions(item)" />
            </div>
        </div>

        <MisPagination :paginator="items" class="mt-4" />
    </MisListPage>
</template>
