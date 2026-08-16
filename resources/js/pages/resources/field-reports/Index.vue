<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ClipboardList } from '@lucide/vue';
import { computed, ref, toRef, watch } from 'vue';
import {
    MisCreateButton,
    MisEmptyState,
    MisListPage,
    MisListRow,
    MisPagination,
    MisSearchInput,
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
    filters?: { search?: string };
}>();

const { t } = useTranslations();
const { editAction, deleteAction } = useMisPage();
const search = ref(props.filters?.search ?? '');
let searchTimer: ReturnType<typeof setTimeout> | null = null;

const onlineItems = computed(() => props.items.data);

const { displayItems, showingOfflineCache, online } = useOfflineResourceList({
    resource: 'field_reports',
    offlineEnabled: toRef(props, 'offlineEnabled'),
    onlineItems,
});

watch(
    () => props.filters?.search,
    (value) => {
        search.value = value ?? '';
    },
);

watch(search, (value) => {
    if (searchTimer) {
        clearTimeout(searchTimer);
    }

    searchTimer = setTimeout(() => {
        const next = value.trim();
        const current = (props.filters?.search ?? '').trim();
        if (next === current) {
            return;
        }

        router.get(
            '/field-reports',
            { search: next || undefined },
            { preserveState: true, replace: true, preserveScroll: true },
        );
    }, 300);
});

function rowActions(item: (typeof displayItems.value)[number]): RowActionItem[] {
    if (! item.id) {
        return [];
    }

    return [
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

    <MisListPage
        :title="t('Field reports')"
        :icon="ClipboardList"
        :description="
            offlineEnabled
                ? t(
                      'Demo offline resource. Create reports while offline; they sync when you reconnect.',
                  )
                : t(
                      'Enable KIT_OFFLINE=true to use offline submission for this demo.',
                  )
        "
    >
        <template #actions>
            <MisCreateButton href="/field-reports/create" permission="field_reports.create">
                {{ t('Create') }}
            </MisCreateButton>
        </template>

        <MisSearchInput
            v-model="search"
            class="max-w-sm"
            :aria-label="t('Search')"
            :placeholder="t('Search...')"
        />

        <p
            v-if="showingOfflineCache"
            class="mb-3 rounded-md border border-amber-500/30 bg-amber-500/5 px-3 py-2 text-sm"
        >
            {{ t('Showing cached and queued field reports from this device.') }}
        </p>

        <MisEmptyState v-if="displayItems.length === 0">
            {{ t('No field reports yet.') }}
        </MisEmptyState>

        <div v-else class="space-y-3">
            <MisListRow
                v-for="item in displayItems"
                :key="item.client_uuid || item.id"
                class="flex flex-wrap items-center justify-between gap-3"
            >
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="font-medium">{{ item.title || item.name }}</span>
                        <Badge v-if="item.status" variant="secondary">{{ item.status }}</Badge>
                        <Badge
                            v-if="item.pending_local || item.offline_only"
                            variant="outline"
                        >
                            {{ t('Pending sync') }}
                        </Badge>
                    </div>
                    <p class="text-sm text-muted-foreground">
                        {{ item.location || t('No location') }}
                    </p>
                </div>
                <RowActionsMenu v-if="item.id" :actions="rowActions(item)" />
            </MisListRow>
        </div>

        <template v-if="online" #pagination>
            <MisPagination :pagination="items" />
        </template>
    </MisListPage>
</template>
