<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Trash2 } from '@lucide/vue';
import { computed, ref } from 'vue';
import EmptyState from '@/components/EmptyState.vue';
import SortableTh from '@/components/SortableTh.vue';
import TableFrame from '@/components/TableFrame.vue';
import {
    MisFilterBar,
    MisLoadingState,
    MisSearchInput,
    MisStatCard,
    MisStatGrid,
} from '@/components/mis';
import Pagination from '@/components/Pagination.vue';
import RowActionsMenu from '@/components/RowActionsMenu.vue';
import { useInertiaLoading } from '@/composables/useInertiaLoading';
import { useMisPage } from '@/composables/useMisPage';
import { provideTableSort } from '@/composables/useTableSort';
import { useTranslations } from '@/composables/useTranslations';
import type { RowActionItem } from '@/lib/row-actions';

type Item = {
    id: number;
    name: string;
    email: string;
    phone?: string | null;
    message: string;
    read_at?: string | null;
    created_at?: string | null;
};

const props = defineProps<{
    items: { data: Item[]; links: any[]; meta?: any };
    filters: { search?: string | null; status?: string | null };
    totals: { count: number; unread: number };
}>();

const search = ref(props.filters.search ?? '');
const status = ref(props.filters.status ?? '');
const { sortedRows } = provideTableSort(() => props.items.data);
const { t } = useTranslations();
const tableColumns = computed(() => [
    { key: 'name', label: t('From') },
    { key: 'email', label: t('Email') },
    { key: 'message', label: t('Message') },
    { key: 'read_at', label: t('Status') },
    { key: 'created_at', label: t('Created at') },
    { key: 'actions', label: t('Actions'), locked: true },
]);
const { can, formatDate } = useMisPage();
const { loading } = useInertiaLoading();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Contact', href: '/contact-messages' },
        ],
    },
});

function applySearch() {
    router.get(
        '/contact-messages',
        {
            search: search.value || undefined,
            status: status.value || undefined,
        },
        { preserveState: true, replace: true },
    );
}

function clearFilters() {
    search.value = '';
    status.value = '';
    applySearch();
}

function rowActions(item: Item): RowActionItem[] {
    return [
        {
            label: t('View'),
            href: `/contact-messages/${item.id}`,
        },
        { separator: true, isLabel: true, label: t('Danger') },
        {
            label: t('Delete'),
            icon: Trash2,
            method: 'delete',
            href: `/contact-messages/${item.id}`,
            variant: 'destructive' as const,
            hidden: !can('contact_messages.delete'),
            confirm: {
                title: t('Delete contact message'),
                description: t('Are you sure you want to delete this message?'),
                confirmLabel: t('Delete'),
            },
        },
    ];
}

function preview(message: string): string {
    const text = message.replace(/\s+/g, ' ').trim();
    return text.length > 80 ? `${text.slice(0, 79)}…` : text;
}
</script>

<template>
    <Head :title="t('Contact')" />

    <div class="flex h-full flex-1 flex-col gap-4">
        <MisFilterBar :apply-label="t('Apply')" :clear-label="t('All')" @apply="applySearch" @clear="clearFilters">
            <MisSearchInput
                v-model="search"
                class="min-w-[220px] flex-1"
                :placeholder="t('Search messages...')"
            />
            <select
                v-model="status"
                class="h-10 rounded-xl border border-border/80 bg-background px-3 text-sm"
                @change="applySearch"
            >
                <option value="">{{ t('All statuses') }}</option>
                <option value="unread">{{ t('Unread') }}</option>
                <option value="read">{{ t('Read') }}</option>
            </select>
        </MisFilterBar>

        <MisStatGrid :columns="2">
            <MisStatCard :label="t('Messages')" :value="totals.count" />
            <MisStatCard :label="t('Unread')" :value="totals.unread" />
        </MisStatGrid>

        <MisLoadingState v-if="loading" />

        <div
            v-else
            class="overflow-hidden rounded-2xl border border-border/80 bg-card shadow-soft"
        >
            <TableFrame table-id="contact-messages" :columns="tableColumns">
                <table class="min-w-full text-sm">
                    <thead class="bg-muted/30 text-start text-muted-foreground">
                        <tr>
                            <SortableTh column="name">{{ t('From') }}</SortableTh>
                            <SortableTh column="email">{{ t('Email') }}</SortableTh>
                            <SortableTh column="message">{{ t('Message') }}</SortableTh>
                            <SortableTh column="read_at">{{ t('Status') }}</SortableTh>
                            <SortableTh column="created_at">{{ t('Created at') }}</SortableTh>
                            <th class="px-4 py-3 font-medium text-end">{{ t('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="item in sortedRows"
                            :key="item.id"
                            class="border-b last:border-0 transition-colors hover:bg-muted/30"
                            :class="{ 'font-medium': !item.read_at }"
                        >
                            <td class="px-4 py-3">
                                <Link
                                    :href="`/contact-messages/${item.id}`"
                                    class="hover:underline"
                                >
                                    {{ item.name }}
                                </Link>
                            </td>
                            <td class="px-4 py-3 text-muted-foreground">{{ item.email }}</td>
                            <td class="px-4 py-3 text-muted-foreground">{{ preview(item.message) }}</td>
                            <td class="px-4 py-3">
                                <span
                                    class="inline-flex rounded-full px-2 py-0.5 text-xs"
                                    :class="
                                        item.read_at
                                            ? 'bg-muted text-muted-foreground'
                                            : 'bg-amber-100 text-amber-800'
                                    "
                                >
                                    {{ item.read_at ? t('Read') : t('Unread') }}
                                </span>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-muted-foreground">{{ formatDate(item.created_at) }}</td>
                            <td class="px-4 py-3">
                                <div class="flex justify-end">
                                    <RowActionsMenu :actions="rowActions(item)" />
                                </div>
                            </td>
                        </tr>
                        <EmptyState
                            v-if="!items.data.length"
                            :colspan="6"
                            :title="t('No contact messages yet')"
                        />
                    </tbody>
                </table>
            </TableFrame>
            <Pagination :paginator="items" />
        </div>
    </div>
</template>
