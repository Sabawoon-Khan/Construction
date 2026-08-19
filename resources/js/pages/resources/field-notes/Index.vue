<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref, toRef, watch } from 'vue';
import Can from '@/components/Can.vue';
import { MisFilterBar, MisSearchInput } from '@/components/mis';
import MisPagination from '@/components/MisPagination.vue';
import { Badge } from '@/components/ui/badge';
import { useTranslations } from '@/composables/useTranslations';
import type { Paginated } from '@/lib/format';
import { useOfflineResourceList } from '@/offline/useOfflineResourceList';

const props = defineProps<{
    items: Paginated<{ id: number; name: string; client_uuid?: string }>;
    offlineEnabled?: boolean;
    filters?: { search?: string };
}>();

const { t } = useTranslations();
const search = ref(props.filters?.search ?? '');
let searchTimer: ReturnType<typeof setTimeout> | null = null;
const onlineItems = computed(() => props.items.data);

const { displayItems, showingOfflineCache, online } = useOfflineResourceList({
    resource: 'field_notes',
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
            '/field-notes',
            { search: next || undefined },
            { preserveState: true, replace: true, preserveScroll: true },
        );
    }, 300);
});
</script>

<template>
    <Head :title="t('FieldNote')" />

    <div class="flex flex-col gap-4">

        <MisFilterBar :show-actions="false">
            <MisSearchInput
                v-model="search"
                class="min-w-[220px] flex-1"
                :aria-label="t('Search')"
                :placeholder="t('Search...')"
            />
        </MisFilterBar>

        <p
            v-if="showingOfflineCache"
            class="rounded-md border border-amber-500/30 bg-amber-500/5 px-3 py-2 text-sm"
        >
            {{ t('Showing cached and queued records from this device.') }}
        </p>

        <div class="overflow-hidden rounded-2xl border border-border/80 bg-card shadow-soft">
            <ul class="divide-y">
                <li
                    v-for="item in displayItems"
                    :key="item.client_uuid || item.id"
                    class="flex items-center justify-between px-4 py-3"
                >
                    <div class="flex items-center gap-2">
                        <span>{{ item.name || item.title }}</span>
                        <Badge v-if="item.pending_local || item.offline_only" variant="outline">
                            {{ t('Pending sync') }}
                        </Badge>
                    </div>
                    <Can permission="field_notes.edit">
                        <Link
                            v-if="item.id"
                            :href="`/field-notes/${item.id}/edit`"
                            class="text-sm text-primary"
                        >
                            {{ t('Edit') }}
                        </Link>
                    </Can>
                </li>
            </ul>
            <div v-if="online" class="px-4 pb-4">
                <MisPagination :pagination="items" />
            </div>
        </div>
    </div>
</template>
