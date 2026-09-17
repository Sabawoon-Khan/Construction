<script setup lang="ts">
import EmptyState from '@/components/EmptyState.vue';
import RowActionsMenu from '@/components/RowActionsMenu.vue';
import SortableTh from '@/components/SortableTh.vue';
import TableIndexTd from '@/components/TableIndexTd.vue';
import TableIndexTh from '@/components/TableIndexTh.vue';
import TableToolbar from '@/components/TableToolbar.vue';
import { MisTableActions, MisSearchInput  } from '@/components/mis';
import { CreateButton } from '@/components/ui/create-button';
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
import { translateStatus } from '@/lib/translateStatus';
import { CalendarCheck, Trash2, UserCheck, UserX, Users } from '@lucide/vue';
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';

type Item = Record<string, any>;

const props = defineProps<{
    items: { data: Item[]; links: any[]; meta?: any };
    filters: { search?: string; employee_id?: number | null; date?: string };
    totals: {
        count: number;
        present: number;
        absent: number;
        leave: number;
        half_day?: number;
    };
    volumeChart?: Array<{ key: string; label: string; value: number }>;
    employees: { id: number; name: string; employee_no: string }[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Attendance', href: '/attendance' },
        ],
    },
});

const onlyKeys = ['items', 'filters', 'totals', 'volumeChart', 'employees'];

const { filters, pending, apply } = useMisFilters(
    '/attendance',
    {
        search: props.filters.search ?? '',
        employee_id: props.filters.employee_id ? String(props.filters.employee_id) : '',
        date: props.filters.date ?? '',
    },
    { search: '', employee_id: '', date: '' },
    { only: onlyKeys, liveKeys: ['search'] },
);

const { sortedRows } = provideTableSort(() => props.items.data);
const { t } = useTranslations();
const tableColumns = computed(() => [
    indexTableColumn(),
    { key: 'date', label: t('Date') },
    { key: 'employee.name', label: t('Employee') },
    { key: 'check_in', label: t('Check in') },
    { key: 'check_out', label: t('Check out') },
    { key: 'status', label: t('Status') },
    { key: 'notes', label: t('Notes') },
    { key: 'creator.name', label: t('Added by') },
    { key: 'created_at', label: t('Created at') },
    { key: 'actions', label: t('Actions'), locked: true },
]);
const { editAction, can, formatDate } = useMisPage();

const statusBreakdown = computed(() => {
    const present = Number(props.totals.present ?? 0);
    const absent = Number(props.totals.absent ?? 0);
    const leave = Number(props.totals.leave ?? 0);
    const halfDay = Number(props.totals.half_day ?? 0);
    const total = Math.max(present + absent + leave + halfDay, Number(props.totals.count ?? 0), 0);
    const presentShare = total > 0 ? Math.round((present / total) * 100) : 0;

    const segments = [
        { key: 'present', label: t('Present'), value: present, color: 'var(--brand-accent)' },
        { key: 'absent', label: t('Absent'), value: absent, color: 'var(--destructive, #e51f56)' },
        { key: 'leave', label: t('Leave'), value: leave, color: 'var(--school-gold)' },
        { key: 'half_day', label: t('Half day'), value: halfDay, color: 'var(--school-navy)' },
    ].map((seg) => ({
        ...seg,
        width:
            total > 0
                ? Math.max(seg.value > 0 ? 6 : 0, (seg.value / total) * 100)
                : 0,
    }));

    return { present, presentShare, total, segments };
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

function onEmployeeChange(value: string) {
    apply({ employee_id: value });
}

function rowActions(item: Item): RowActionItem[] {
    return [
        editAction(`/attendance/${item.id}/edit`, 'attendance.edit'),
        { separator: true, isLabel: true, label: t('Danger') },
        {
            label: t('Delete'),
            icon: Trash2,
            method: 'delete',
            href: `/attendance/${item.id}`,
            variant: 'destructive' as const,
            hidden: !can('attendance.delete'),
            confirm: {
                title: t('Delete attendance record'),
                description: t(
                    'Are you sure you want to delete this attendance record? This action cannot be undone.',
                ),
                confirmLabel: t('Delete'),
            },
        },
    ];
}
</script>

<template>
    <Head :title="t('Attendance')" />

    <V2ListPage>
        <V2Hero image="/images/dashboard-v2-building.png">
            <template #eyebrow>{{ t('People') }}</template>
            <template #title>{{ t('Attendance') }}</template>
            <template #description>
                {{ t('Track daily presence, absences, and leave across your team.') }}
            </template>

            <template #side>
                <div class="hero-cards">
                    <V2IndicatorCard card-class="inventory-card">
                        <template #head>{{ t('Status mix') }}</template>
                        <template #meta
                            >{{ statusBreakdown.presentShare }}% {{ t('Present') }}</template
                        >

                        <div class="inventory-hero">
                            <div class="inventory-hero-copy">
                                <strong>{{ statusBreakdown.present }}</strong>
                                <small>{{ t('Present') }}</small>
                            </div>
                        </div>

                        <div class="inventory-bar" aria-hidden="true">
                            <i
                                v-for="seg in statusBreakdown.segments"
                                :key="seg.key"
                                :style="{
                                    width: `${seg.width}%`,
                                    background: seg.color,
                                }"
                            />
                        </div>

                        <ul class="indicator-list compact">
                            <li
                                v-for="seg in statusBreakdown.segments"
                                :key="seg.key"
                            >
                                <i :style="{ background: seg.color }" />
                                <span>{{ seg.label }}</span>
                                <b>{{ seg.value }}</b>
                            </li>
                        </ul>
                    </V2IndicatorCard>

                    <V2IndicatorCard card-class="money-card">
                        <template #head>{{ t('Records by month') }}</template>
                        <template #meta>{{ totals.count }} {{ t('Total records') }}</template>

                        <div class="money-chart">
                            <div
                                v-for="bar in volumeBars"
                                :key="bar.key"
                                class="money-col"
                                :class="{ peak: bar.peak }"
                                :title="`${bar.label}: ${bar.value}`">
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
                        :title="t('Total records')"
                        :value="totals.count"
                    >
                        <template #icon><Users /></template>
                    </V2StatCard>
                    <V2StatCard
                        :delay="1"
                        icon-tone="teal"
                        :title="t('Present')"
                        :value="totals.present"
                    >
                        <template #icon><UserCheck /></template>
                    </V2StatCard>
                    <V2StatCard
                        :delay="2"
                        icon-tone="orange"
                        :title="t('Absent')"
                        :value="totals.absent"
                    >
                        <template #icon><UserX /></template>
                    </V2StatCard>
                    <V2StatCard
                        :delay="3"
                        accent
                        icon-tone="warm"
                        :title="t('Leave')"
                        :value="totals.leave"
                    >
                        <template #icon><CalendarCheck /></template>
                    </V2StatCard>
                </V2StatGrid>
            </template>
        </V2Hero>

        <V2TablePanel
            table-id="attendance"
            :columns="tableColumns"
            :pending="pending && items.data.length > 0"
            :print-title="t('Attendance')"
        >
            <template #filters>
                <V2FilterBar>
                    <div class="filter-search">
                        <MisSearchInput
                            v-model="filters.search"
                            :placeholder="t('Search...')"
                            @submit="apply()"
                            @clear="apply()"
                        />
                    </div>
                    <V2SelectFilter
                        :label="t('Employee')"
                        :model-value="filters.employee_id || ''"
                        @change="onEmployeeChange"
                    >
                        <option value="">{{ t('All employees') }}</option>
                        <option
                            v-for="employee in employees"
                            :key="employee.id"
                            :value="String(employee.id)"
                        >
                            {{ employee.name }}
                        </option>
                    </V2SelectFilter>
                    <label class="filter-select">
                        <span>{{ t('Date') }}</span>
                        <input
                            v-model="filters.date"
                            type="date"
                            @change="apply()"
                        />
                    </label>
                    <template #columns>
                        <MisTableActions export-url="/attendance/export" :filters="filters">
                            <CreateButton href="/attendance/create" permission="attendance.create">{{ t('Record attendance') }}</CreateButton>
                        </MisTableActions>
                        <TableToolbar />
                    </template>
                </V2FilterBar>
            </template>

            <template #default="{ visibleColCount }">
                <table>
                    <thead>
                        <tr>
                            <TableIndexTh />
                            <SortableTh column="date">{{ t('Date') }}</SortableTh>
                            <SortableTh column="employee.name">{{ t('Employee') }}</SortableTh>
                            <SortableTh column="check_in">{{ t('Check in') }}</SortableTh>
                            <SortableTh column="check_out">{{ t('Check out') }}</SortableTh>
                            <SortableTh column="status">{{ t('Status') }}</SortableTh>
                            <SortableTh column="notes">{{ t('Notes') }}</SortableTh>
                            <SortableTh column="creator.name">{{ t('Added by') }}</SortableTh>
                            <SortableTh column="created_at">{{ t('Created at') }}</SortableTh>
                            <th class="end">{{ t('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="(item, index) in sortedRows"
                            :key="item.id"
                            :style="{ '--i': index }"
                        >
                            <TableIndexTd
                                :index="index"
                                :from="items.meta?.from"
                            />
                            <td class="nowrap">{{ formatDate(item.date) }}</td>
                            <td>
                                <Link
                                    v-if="item.employee"
                                    :href="`/employees/${item.employee.id}`"
                                    class="hover:underline"
                                >
                                    {{ item.employee.name }}
                                </Link>
                                <span v-else>—</span>
                            </td>
                            <td class="nums">{{ item.check_in || '—' }}</td>
                            <td class="nums">{{ item.check_out || '—' }}</td>
                            <td>{{ translateStatus(item.status, t) }}</td>
                            <td class="muted">{{ item.notes || '—' }}</td>
                            <td class="muted">{{ item.creator?.name || '—' }}</td>
                            <td class="muted nowrap">{{ formatDate(item.created_at) }}</td>
                            <td class="end" @click.stop>
                                <RowActionsMenu :actions="rowActions(item)" />
                            </td>
                        </tr>
                        <EmptyState
                            v-if="!items.data.length"
                            :colspan="visibleColCount"
                            :title="t('No attendance records')">
                        </EmptyState>
                    </tbody>
                </table>
            </template>

            <template #pager>
                <V2Pager :items="items" :only="onlyKeys" />
            </template>
        </V2TablePanel>
    </V2ListPage>
</template>
