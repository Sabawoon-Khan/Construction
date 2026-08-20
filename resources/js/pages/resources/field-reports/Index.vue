<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref, toRef, watch } from 'vue';
import {
    MisEmptyState,
    MisFilterBar,
    MisListPage,
    MisPagination,
    MisSearchInput,
    MisSelectField,
    MisStatusBadge,
} from '@/components/mis';
import RowActionsMenu from '@/components/RowActionsMenu.vue';
import { Badge } from '@/components/ui/badge';
import { useMisPage } from '@/composables/useMisPage';
import { useTranslations } from '@/composables/useTranslations';
import type { Paginated } from '@/lib/format';
import type { RowActionItem } from '@/lib/row-actions';
import { useOfflineResourceList } from '@/offline/useOfflineResourceList';

const props = defineProps<{
    items: Paginated<{
        id: number;
        client_uuid: string;
        title: string;
        location: string | null;
        status: string;
    }>;
    offlineEnabled: boolean;
    filters?: { search?: string; status?: string };
}>();

const { t } = useTranslations();
const { viewAction, editAction, deleteAction } = useMisPage();
const search = ref(props.filters?.search ?? '');
const status = ref(props.filters?.status ?? '');
let searchTimer: ReturnType<typeof setTimeout> | null = null;

const onlineItems = computed(() => props.items.data);

const { displayItems, showingOfflineCache, online } = useOfflineResourceList({
    resource: 'field_reports',
    offlineEnabled: toRef(props, 'offlineEnabled'),
    onlineItems,
});

const statusOptions = computed(() => [
    { value: '', label: t('All') },
    { value: 'draft', label: t('Draft') },
    { value: 'submitted', label: t('Submitted') },
]);

watch(
    () => props.filters?.search,
    (value) => {
        search.value = value ?? '';
    },
);

watch(
    () => props.filters?.status,
    (value) => {
        status.value = value ?? '';
    },
);

watch(search, (value) => {
    if (searchTimer) {
        clearTimeout(searchTimer);
    }

    searchTimer = setTimeout(() => {
        applyFilters(value, status.value);
    }, 300);
});

function applyFilters(nextSearch = search.value, nextStatus = status.value): void {
    const searchValue = nextSearch.trim();
    const statusValue = nextStatus.trim();
    const currentSearch = (props.filters?.search ?? '').trim();
    const currentStatus = (props.filters?.status ?? '').trim();

    if (searchValue === currentSearch && statusValue === currentStatus) {
        return;
    }

    router.get(
        '/field-reports',
        {
            search: searchValue || undefined,
            status: statusValue || undefined,
        },
        { preserveState: true, replace: true, preserveScroll: true },
    );
}

function clearFilters(): void {
    search.value = '';
    status.value = '';
    router.get('/field-reports', {}, { preserveState: true, replace: true, preserveScroll: true });
}

function rowActions(item: (typeof displayItems.value)[number]): RowActionItem[] {
    if (! item.id) {
        return [];
    }

    return [
        viewAction(`/field-reports/${item.id}`),
        editAction(`/field-reports/${item.id}/edit`, 'field_reports.edit'),
        deleteAction(
            {
                href: `/field-reports/${item.id}`,
                title: t('Move to trash'),
                description: t('Move this field report to trash? You can restore it later.'),
            },
            'field_reports.delete',
        ),
    ];
}

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Field reports', href: '/field-reports' }],
    },
});
</script>

<template>
    <Head :title="t('Field reports')" />

    <MisListPage flat>
        <MisFilterBar
            :apply-label="t('Apply')"
            :clear-label="t('All')"
            @apply="applyFilters()"
            @clear="clearFilters"
        >
            <MisSearchInput
                v-model="search"
                class="min-w-[180px] flex-1"
                :aria-label="t('Search')"
                :placeholder="t('Search...')"
            />
            <MisSelectField
                v-model="status"
                class="min-w-[160px]"
                :label="t('Status')"
                :options="statusOptions"
            />
        </MisFilterBar>

        <p
            v-if="showingOfflineCache"
            class="rounded-md border border-amber-500/30 bg-amber-500/5 px-3 py-2 text-sm"
        >
            {{ t('Showing cached and queued field reports from this device.') }}
        </p>

        <MisEmptyState v-if="displayItems.length === 0">
            {{ t('No field reports yet.') }}
        </MisEmptyState>

        <div
            v-else
            class="overflow-hidden rounded-2xl border border-border/80 bg-card shadow-soft"
        >
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-muted/30 text-start text-muted-foreground">
                        <tr>
                            <th class="px-4 py-3">{{ t('Title') }}</th>
                            <th class="px-4 py-3">{{ t('Location') }}</th>
                            <th class="px-4 py-3">{{ t('Status') }}</th>
                            <th class="px-4 py-3">{{ t('Sync') }}</th>
                            <th class="px-4 py-3 text-end">{{ t('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="item in displayItems"
                            :key="item.client_uuid || item.id"
                            class="border-t align-middle"
                        >
                            <td class="px-4 py-3">
                                <Link
                                    v-if="item.id"
                                    :href="`/field-reports/${item.id}`"
                                    class="font-medium hover:text-primary hover:underline"
                                >
                                    {{ item.title || item.name }}
                                </Link>
                                <span v-else class="font-medium">{{ item.title || item.name }}</span>
                            </td>
                            <td class="px-4 py-3 text-muted-foreground">
                                {{ item.location || t('No location') }}
                            </td>
                            <td class="px-4 py-3">
                                <MisStatusBadge
                                    v-if="item.status"
                                    :status="item.status"
                                    :map="{ submitted: 'default', draft: 'outline' }"
                                />
                            </td>
                            <td class="px-4 py-3">
                                <Badge
                                    v-if="item.pending_local || item.offline_only"
                                    variant="outline"
                                >
                                    {{ t('Pending sync') }}
                                </Badge>
                                <span v-else class="text-muted-foreground">{{ t('Synced') }}</span>
                            </td>
                            <td class="px-4 py-3 text-end">
                                <div class="inline-flex justify-end">
                                    <RowActionsMenu v-if="item.id" :actions="rowActions(item)" />
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <template v-if="online" #pagination>
            <MisPagination :pagination="items" />
        </template>
    </MisListPage>
</template>
