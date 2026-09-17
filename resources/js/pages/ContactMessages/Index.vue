<script setup lang="ts">
import EmptyState from '@/components/EmptyState.vue';
import { MisTableActions, MisSearchInput  } from '@/components/mis';
import RowActionsMenu from '@/components/RowActionsMenu.vue';
import SortableTh from '@/components/SortableTh.vue';
import TableIndexTd from '@/components/TableIndexTd.vue';
import TableIndexTh from '@/components/TableIndexTh.vue';
import TableToolbar from '@/components/TableToolbar.vue';
import {
    V2FilterBar,
    V2Hero,
    V2IndicatorCard,
    V2ListPage,
    V2Pager,
    V2SelectFilter,
    V2StatCard,
    V2StatGrid,
    V2TablePanel,
} from '@/components/v2';
import { indexTableColumn } from '@/composables/useTableColumns';
import { useMisFilters } from '@/composables/useMisFilters';
import { useMisPage } from '@/composables/useMisPage';
import { provideTableSort } from '@/composables/useTableSort';
import { useTranslations } from '@/composables/useTranslations';
import type { RowActionItem } from '@/lib/row-actions';
import { Eye, Mail, MailOpen, MessageSquare, Trash2 } from '@lucide/vue';
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';

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
    totals: { count: number; unread: number; read: number };
    volumeChart?: Array<{ key: string; label: string; value: number }>;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Contact', href: '/contact-messages' },
        ],
    },
});

const onlyKeys = ['items', 'filters', 'totals', 'volumeChart'];

const { filters, pending, apply, clear } = useMisFilters(
    '/contact-messages',
    {
        search: props.filters.search ?? '',
        status: props.filters.status ?? '',
    },
    { search: '', status: '' },
    { only: onlyKeys, liveKeys: ['search'] },
);
const { sortedRows } = provideTableSort(() => props.items.data);
const { t } = useTranslations();
const tableColumns = computed(() => [
    indexTableColumn(),
    { key: 'name', label: t('From') },
    { key: 'email', label: t('Email') },
    { key: 'message', label: t('Message') },
    { key: 'read_at', label: t('Status') },
    { key: 'created_at', label: t('Created at') },
    { key: 'actions', label: t('Actions'), locked: true },
]);
const { can, formatDate } = useMisPage();

const readMix = computed(() => {
    const unread = Number(props.totals.unread ?? 0);
    const read = Number(props.totals.read ?? 0);
    const total = Math.max(unread + read, Number(props.totals.count ?? 0), 0);
    const unreadShare = total > 0 ? Math.round((unread / total) * 100) : 0;

    const segments = [
        { key: 'unread', label: t('Unread'), value: unread, color: 'var(--school-gold)' },
        { key: 'read', label: t('Read'), value: read, color: 'var(--brand-accent)' },
    ].map((seg) => ({
        ...seg,
        width:
            total > 0
                ? Math.max(seg.value > 0 ? 6 : 0, (seg.value / total) * 100)
                : 0,
    }));

    return { unread, unreadShare, segments };
});

const volumeBars = computed(() => {
    const rows = props.volumeChart ?? [];
    const source =
        rows.length > 0
            ? rows
            : Array.from({ length: 6 }, (_, index) => ({
                  key: `m-${index}`,
                  label: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'][index],
                  value: 0,
              }));
    const max = Math.max(...source.map((row) => Number(row.value) || 0), 1);

    return source.map((row) => {
        const value = Number(row.value) || 0;
        return {
            key: row.key,
            label: row.label,
            value,
            height: Math.max(value > 0 ? 6 : 3, Math.round((value / max) * 44)),
            peak: value === max && value > 0,
        };
    });
});

function onStatusChange(value: string) {
    apply({ status: value });
}

function preview(message: string): string {
    const text = message.replace(/\s+/g, ' ').trim();
    return text.length > 80 ? `${text.slice(0, 79)}…` : text;
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
</script>

<template>
    <Head :title="t('Contact')" />

    <V2ListPage>
        <V2Hero image="/images/dashboard-v2-building.png">
            <template #eyebrow>{{ t('Inbox') }}</template>
            <template #title>{{ t('Contact') }}</template>
            <template #description>
                {{ t('Messages from the public site — triage unread and follow up.') }}
            </template>

            <template #side>
                <div class="hero-cards">
                    <V2IndicatorCard card-class="inventory-card">
                        <template #head>{{ t('Read mix') }}</template>
                        <template #meta
                            >{{ readMix.unreadShare }}% {{ t('Unread') }}</template
                        >

                        <div class="inventory-hero">
                            <div class="inventory-hero-copy">
                                <strong>{{ readMix.unread }}</strong>
                                <small>{{ t('Unread') }}</small>
                            </div>
                        </div>

                        <div class="inventory-bar" aria-hidden="true">
                            <i
                                v-for="seg in readMix.segments"
                                :key="seg.key"
                                :style="{
                                    width: `${seg.width}%`,
                                    background: seg.color,
                                }"
                            />
                        </div>

                        <ul class="indicator-list compact">
                            <li
                                v-for="seg in readMix.segments"
                                :key="seg.key"
                            >
                                <i :style="{ background: seg.color }" />
                                <span>{{ seg.label }}</span>
                                <b>{{ seg.value }}</b>
                            </li>
                        </ul>
                    </V2IndicatorCard>

                    <V2IndicatorCard card-class="money-card">
                        <template #head>{{ t('By month') }}</template>
                        <template #meta>{{ totals.count }} {{ t('Total') }}</template>

                        <div class="money-chart">
                            <div
                                v-for="bar in volumeBars"
                                :key="bar.key"
                                class="money-col"
                                :class="{ peak: bar.peak }"
                                :title="`${bar.label}: ${bar.value}`"
                            >
                                <div class="money-pair">
                                    <i
                                        class="usd"
                                        :style="{ height: `${bar.height}px` }"
                                    />
                                </div>
                                <span>{{ bar.label }}</span>
                            </div>
                        </div>
                    </V2IndicatorCard>
                </div>
            </template>

            <template #stats>
                <V2StatGrid>
                    <V2StatCard
                        :delay="0"
                        :title="t('Messages')"
                        :value="totals.count"
                    >
                        <template #icon><MessageSquare /></template>
                    </V2StatCard>
                    <V2StatCard
                        :delay="1"
                        icon-tone="orange"
                        :title="t('Unread')"
                        :value="totals.unread"
                    >
                        <template #icon><Mail /></template>
                    </V2StatCard>
                    <V2StatCard
                        :delay="2"
                        icon-tone="teal"
                        :title="t('Read')"
                        :value="totals.read"
                    >
                        <template #icon><MailOpen /></template>
                    </V2StatCard>
                    <V2StatCard
                        :delay="3"
                        accent
                        icon-tone="warm"
                        :title="t('This month')"
                        :value="volumeBars[volumeBars.length - 1]?.value ?? 0"
                    >
                        <template #icon><Eye /></template>
                    </V2StatCard>
                </V2StatGrid>
            </template>
        </V2Hero>

        <V2TablePanel
            table-id="contact-messages"
            :columns="tableColumns"
            :pending="pending && items.data.length > 0"
        >
            <template #filters>
                <V2FilterBar>
                    <div class="filter-search">
                        <MisSearchInput
                            v-model="filters.search"
                            :placeholder="t('Search messages...')"
                            @submit="apply()"
                            @clear="clear"
                        />
                    </div>
                    <V2SelectFilter
                        :label="t('Status')"
                        :model-value="filters.status || ''"
                        @change="onStatusChange"
                    >
                        <option value="">{{ t('All statuses') }}</option>
                        <option value="unread">{{ t('Unread') }}</option>
                        <option value="read">{{ t('Read') }}</option>
                    </V2SelectFilter>
                    <template #columns>
                        <MisTableActions export-url="/contact-messages/export" :filters="filters" />
                        <TableToolbar />
                    </template>
                </V2FilterBar>
            </template>

            <template #default="{ visibleColCount }">
                <table>
                    <thead>
                        <tr>
                            <TableIndexTh />
                            <SortableTh column="name">{{ t('From') }}</SortableTh>
                            <SortableTh column="email">{{ t('Email') }}</SortableTh>
                            <SortableTh column="message">{{ t('Message') }}</SortableTh>
                            <SortableTh column="read_at">{{ t('Status') }}</SortableTh>
                            <SortableTh column="created_at">{{ t('Created at') }}</SortableTh>
                            <th class="end">{{ t('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="(item, index) in sortedRows"
                            :key="item.id"
                            :style="{ '--i': index }"
                            :class="{ 'font-medium': !item.read_at }"
                        >
                            <TableIndexTd
                                :index="index"
                                :from="items.meta?.from"
                            />
                            <td>
                                <Link
                                    :href="`/contact-messages/${item.id}`"
                                    class="hover:underline"
                                >
                                    {{ item.name }}
                                </Link>
                            </td>
                            <td class="muted">{{ item.email }}</td>
                            <td class="muted">{{ preview(item.message) }}</td>
                            <td>
                                <span class="pill">{{
                                    item.read_at ? t('Read') : t('Unread')
                                }}</span>
                            </td>
                            <td class="muted nowrap">{{ formatDate(item.created_at) }}</td>
                            <td class="end">
                                <RowActionsMenu :actions="rowActions(item)" />
                            </td>
                        </tr>
                        <EmptyState
                            v-if="!items.data.length"
                            :colspan="visibleColCount"
                            :title="t('No contact messages yet')"
                        />
                    </tbody>
                </table>
            </template>

            <template #pager>
                <V2Pager :items="items" :only="onlyKeys" />
            </template>
        </V2TablePanel>
    </V2ListPage>
</template>
