<script setup lang="ts">
import EmptyState from '@/components/EmptyState.vue';
import { MisTableActions } from '@/components/mis';
import MisSearchInput from '@/components/mis/MisSearchInput.vue';
import RowActionsMenu from '@/components/RowActionsMenu.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import TableIndexTd from '@/components/TableIndexTd.vue';
import TableIndexTh from '@/components/TableIndexTh.vue';
import TableToolbar from '@/components/TableToolbar.vue';
import { CreateButton } from '@/components/ui/create-button';
import {
    V2FilterBar,
    V2Hero,
    V2IndicatorCard,
    V2ListPage,
    V2Pager,
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
import { money } from '@/lib/utils';
import { UserCheck, UserMinus, UserRound, Users } from '@lucide/vue';
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';

type Item = Record<string, any>;

const props = defineProps<{
    items: { data: Item[]; links: any[]; meta?: any };
    filters: { search?: string };
    totals: {
        count: number;
        active: number;
        inactive: number;
        on_leave?: number;
        by_status?: {
            active?: number;
            inactive?: number;
            on_leave?: number;
        };
        by_department?: Record<string, number>;
    };
    hireChart?: Array<{ key: string; label: string; value: number }>;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Employees', href: '/employees' },
        ],
    },
});

const onlyKeys = ['items', 'filters', 'totals', 'hireChart'];

const { filters, pending, apply, clear } = useMisFilters(
    '/employees',
    { search: props.filters.search ?? '' },
    { search: '' },
    { only: onlyKeys, liveKeys: ['search'] },
);
const { sortedRows } = provideTableSort(() => props.items.data);
const { t } = useTranslations();
const tableColumns = computed(() => [
    indexTableColumn(),
    { key: 'employee_no', label: t('Employee No') },
    { key: 'name', label: t('Name') },
    { key: 'takhallus', label: t('Takhallus') },
    { key: 'father_name', label: t('Father name') },
    { key: 'department', label: t('Department') },
    { key: 'designation', label: t('Designation') },
    { key: 'working_hours', label: t('Working hours') },
    { key: 'employment_date', label: t('Employment Date') },
    { key: 'phone', label: t('Phone') },
    { key: 'basic_salary', label: t('Basic Salary') },
    { key: 'salary_payment_method', label: t('Salary payment method') },
    { key: 'status', label: t('Status') },
    { key: 'creator.name', label: t('Added by') },
    { key: 'created_at', label: t('Created at') },
    { key: 'actions', label: t('Actions'), locked: true },
]);
const { viewAction, editAction, deleteAction, can, formatDate } = useMisPage();

const workforce = computed(() => {
    const active = Number(props.totals.by_status?.active ?? props.totals.active ?? 0);
    const inactive = Number(props.totals.by_status?.inactive ?? 0);
    const onLeave = Number(
        props.totals.by_status?.on_leave ?? props.totals.on_leave ?? 0,
    );
    const total = Math.max(active + inactive + onLeave, Number(props.totals.count ?? 0), 0);
    const activeShare = total > 0 ? Math.round((active / total) * 100) : 0;

    return {
        active,
        inactive,
        onLeave,
        total,
        activeShare,
        segments: [
            {
                key: 'active',
                label: t('Active'),
                value: active,
                color: 'var(--brand-accent)',
                width:
                    total > 0
                        ? Math.max(active > 0 ? 6 : 0, (active / total) * 100)
                        : 0,
            },
            {
                key: 'on_leave',
                label: t('On leave'),
                value: onLeave,
                color: 'var(--school-gold)',
                width:
                    total > 0
                        ? Math.max(onLeave > 0 ? 6 : 0, (onLeave / total) * 100)
                        : 0,
            },
            {
                key: 'inactive',
                label: t('Inactive'),
                value: inactive,
                color: 'var(--muted-foreground)',
                width:
                    total > 0
                        ? Math.max(inactive > 0 ? 6 : 0, (inactive / total) * 100)
                        : 0,
            },
        ],
    };
});

const hireBars = computed(() => {
    const rows = props.hireChart ?? [];
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

const departmentRows = computed(() => {
    const entries = Object.entries(props.totals.by_department ?? {});
    const total = Math.max(
        entries.reduce((sum, [, value]) => sum + Number(value || 0), 0),
        Number(props.totals.count ?? 0),
        1,
    );
    const colors = ['var(--school-navy)', 'var(--brand-accent)', 'var(--school-gold)', 'var(--muted-foreground)', '#e51f56', 'var(--school-navy)'];

    return entries.slice(0, 5).map(([label, value], index) => {
        const count = Number(value) || 0;
        return {
            key: label,
            label,
            value: count,
            color: colors[index % colors.length],
            width: Math.max(count > 0 ? 6 : 0, (count / total) * 100),
        };
    });
});

function paymentMethodLabel(method: string | null | undefined): string {
    if (method === 'cash') {
        return t('Cash');
    }

    if (method === 'bank') {
        return t('Bank');
    }

    return '—';
}

function rowActions(item: Item): RowActionItem[] {
    return [
        viewAction(`/employees/${item.id}`),
        editAction(`/employees/${item.id}/edit`, 'employees.edit'),
        deleteAction(
            {
                href: `/employees/${item.id}`,
                title: t('Delete employee'),
                description: t(
                    'Are you sure you want to delete this employee? This action cannot be undone.',
                ),
                trashType: 'employees',
            },
            'employees.delete',
        ),
    ];
}
</script>

<template>
    <Head :title="t('Employees')" />

    <V2ListPage>
        <V2Hero image="/images/dashboard-v2-building.png">
            <template #eyebrow>{{ t('People') }}</template>
            <template #title>{{ t('Employees') }}</template>
            <template #description>
                {{
                    t(
                        'Headcount, status, and recent hires — keep your workforce roster clear.',
                    )
                }}
            </template>

            <template #side>
                <div class="hero-cards">
                    <V2IndicatorCard card-class="inventory-card">
                        <template #head>{{ t('Workforce') }}</template>
                        <template #meta
                            >{{ workforce.activeShare }}% {{ t('Active') }}</template
                        >

                        <div class="inventory-hero">
                            <div class="inventory-hero-copy">
                                <strong>{{ workforce.active }}</strong>
                                <small>{{ t('Active') }}</small>
                            </div>
                        </div>

                        <div class="inventory-bar" aria-hidden="true">
                            <i
                                v-for="seg in workforce.segments"
                                :key="seg.key"
                                :style="{
                                    width: `${seg.width}%`,
                                    background: seg.color,
                                }"
                            />
                        </div>

                        <ul class="indicator-list compact">
                            <li
                                v-for="seg in workforce.segments"
                                :key="seg.key"
                            >
                                <i :style="{ background: seg.color }" />
                                <span>{{ seg.label }}</span>
                                <b>{{ seg.value }}</b>
                            </li>
                        </ul>

                        <template v-if="departmentRows.length">
                            <div class="inventory-bar" aria-hidden="true">
                                <i
                                    v-for="seg in departmentRows"
                                    :key="seg.key"
                                    :style="{
                                        width: `${seg.width}%`,
                                        background: seg.color,
                                    }"
                                />
                            </div>
                            <ul class="indicator-list compact">
                                <li
                                    v-for="seg in departmentRows"
                                    :key="`dept-${seg.key}`"
                                >
                                    <i :style="{ background: seg.color }" />
                                    <span>{{ seg.label }}</span>
                                    <b>{{ seg.value }}</b>
                                </li>
                            </ul>
                        </template>
                    </V2IndicatorCard>

                    <V2IndicatorCard card-class="money-card">
                        <template #head>{{ t('Hires by month') }}</template>
                        <template #meta>{{ totals.count }} {{ t('Total') }}</template>

                        <div class="money-chart">
                            <div
                                v-for="bar in hireBars"
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
                        :title="t('Employees')"
                        :value="totals.count"
                    >
                        <template #icon><Users /></template>
                    </V2StatCard>
                    <V2StatCard
                        :delay="1"
                        icon-tone="teal"
                        :title="t('Active')"
                        :value="totals.active"
                    >
                        <template #icon><UserCheck /></template>
                    </V2StatCard>
                    <V2StatCard
                        :delay="2"
                        icon-tone="warm"
                        :title="t('On leave')"
                        :value="totals.on_leave ?? 0"
                    >
                        <template #icon><UserRound /></template>
                    </V2StatCard>
                    <V2StatCard
                        :delay="3"
                        accent
                        icon-tone="orange"
                        :title="t('Inactive')"
                        :value="totals.inactive"
                    >
                        <template #icon><UserMinus /></template>
                    </V2StatCard>
                </V2StatGrid>
            </template>
        </V2Hero>

        <V2TablePanel
            table-id="employees"
            :columns="tableColumns"
            :pending="pending && items.data.length > 0"
            :print-title="t('Employees')"
        >
            <template #filters>
                <V2FilterBar>
                    <div class="filter-search">
                        <MisSearchInput
                            v-model="filters.search"
                            :placeholder="t('Search employees...')"
                            @submit="apply()"
                            @clear="clear"
                        />
                    </div>
                    <template #columns>
                        <MisTableActions export-url="/employees/export" :filters="filters">
                            <CreateButton href="/employees/create" permission="employees.create">{{ t('Create') }}</CreateButton>
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
                            <th>{{ t('Employee No') }}</th>
                            <th>{{ t('Name') }}</th>
                            <th>{{ t('Takhallus') }}</th>
                            <th>{{ t('Father name') }}</th>
                            <th>{{ t('Department') }}</th>
                            <th>{{ t('Designation') }}</th>
                            <th>{{ t('Working hours') }}</th>
                            <th>{{ t('Employment Date') }}</th>
                            <th>{{ t('Phone') }}</th>
                            <th>{{ t('Basic Salary') }}</th>
                            <th>{{ t('Salary payment method') }}</th>
                            <th>{{ t('Status') }}</th>
                            <th>{{ t('Added by') }}</th>
                            <th>{{ t('Created at') }}</th>
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
                            <td>
                                <Link
                                    :href="`/employees/${item.id}`"
                                    class="code-chip"
                                >
                                    {{ item.employee_no || '—' }}
                                </Link>
                            </td>
                            <td>{{ item.name || '—' }}</td>
                            <td>{{ item.takhallus || '—' }}</td>
                            <td>{{ item.father_name || '—' }}</td>
                            <td>{{ item.department || '—' }}</td>
                            <td>{{ item.designation || '—' }}</td>
                            <td>{{ item.working_hours || '—' }}</td>
                            <td class="muted nowrap">
                                {{ formatDate(item.joining_date) }}
                            </td>
                            <td>{{ item.phone || '—' }}</td>
                            <td class="nums">
                                {{
                                    item.basic_salary
                                        ? money(item.basic_salary, item.currency)
                                        : '—'
                                }}
                            </td>
                            <td>
                                {{ paymentMethodLabel(item.salary_payment_method) }}
                            </td>
                            <td>
                                <StatusBadge :status="item.status" />
                            </td>
                            <td class="muted">{{ item.creator?.name || '—' }}</td>
                            <td class="muted nowrap">
                                {{ formatDate(item.created_at) }}
                            </td>
                            <td class="end">
                                <RowActionsMenu :actions="rowActions(item)" />
                            </td>
                        </tr>
                        <EmptyState
                            v-if="!items.data.length"
                            :colspan="visibleColCount"
                            :title="t('No employees yet')">
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
