<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    Banknote,
    CircleDollarSign,
    Home,
    Info,
    Paperclip,
    Pencil,
    Ruler,
    Wallet,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import Can from '@/components/Can.vue';
import EmptyState from '@/components/EmptyState.vue';
import {
    MisDetailGrid,
    MisTabs,
} from '@/components/mis';
import RecordAttachments from '@/components/RecordAttachments.vue';
import SortableTh from '@/components/SortableTh.vue';
import TableFrame from '@/components/TableFrame.vue';
import TableIndexTd from '@/components/TableIndexTd.vue';
import TableIndexTh from '@/components/TableIndexTh.vue';
import { indexTableColumn } from '@/composables/useTableColumns';
import StatusBadge from '@/components/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import {
    V2DetailHero,
    V2ListPage,
    V2Panel,
    V2StatCard,
    V2StatGrid,
} from '@/components/v2';
import { useMisPage } from '@/composables/useMisPage';
import { provideTableSort } from '@/composables/useTableSort';
import { useTranslations } from '@/composables/useTranslations';
import { translateStatus } from '@/lib/translateStatus';
import { money } from '@/lib/utils';

const props = defineProps<{ item: Record<string, any> }>();
const tab = ref('info');
const { t } = useTranslations();
const tableColumns = computed(() => [
    indexTableColumn(),
    { key: 'sale_no', label: t('Sale') },
    { key: 'customer.name', label: t('Customer') },
    { key: 'amount', label: t('Amount') },
    { key: 'status', label: t('Status') },
    { key: 'created_at', label: t('Date') },
]);
const { formatDate, can } = useMisPage();
const { sortedRows } = provideTableSort(() => props.item.sales ?? []);
const canCreateSale = computed(
    () => can('sales.create') && props.item.status === 'available',
);

const areaLabel = computed(() => {
    const area = props.item.area;
    if (area === null || area === undefined || area === '') {
        return '—';
    }
    return String(area);
});

const overviewItems = computed(() => [
    { label: t('Project'), value: props.item.project?.name },
    { label: t('Unit No'), value: props.item.unit_no },
    { label: t('Floor'), value: props.item.floor_number ?? '—' },
    { label: t('Type'), value: props.item.type ? translateStatus(String(props.item.type), t) : '—' },
    { label: t('Status'), value: translateStatus(props.item.status, t) },
    { label: t('Currency'), value: props.item.currency },
    {
        label: t('Buyer'),
        value: props.item.sales?.[0]?.customer?.name || '—',
    },
    {
        label: t('Description'),
        value: props.item.description || '—',
        span: 3 as const,
    },
]);

const layoutItems = computed(() =>
    [
        { label: t('Area'), value: areaLabel.value },
        { label: t('Bedrooms'), value: props.item.bedrooms },
        { label: t('Bathrooms'), value: props.item.bathrooms },
        { label: t('Toilets'), value: props.item.toilets },
        { label: t('Halls'), value: props.item.halls },
        { label: t('Kitchens'), value: props.item.kitchens },
        { label: t('Balconies'), value: props.item.balconies },
        { label: t('Corridors'), value: props.item.corridors },
        { label: t('Courtyards'), value: props.item.courtyards },
        { label: t('Parking'), value: props.item.parkings },
    ].map((row) => ({
        ...row,
        value:
            row.value === null || row.value === undefined || row.value === ''
                ? '—'
                : row.value,
    })),
);

const tabs = [
    { id: 'info', label: t('Overview'), icon: Info },
    { id: 'sales', label: t('Sales'), icon: Banknote },
    { id: 'attachments', label: t('Attachments'), icon: Paperclip },
];

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Units', href: '/units' },
            { title: 'Details', href: '#' },
        ],
    },
});
</script>

<template>
    <Head :title="`${t('Unit')} ${item.unit_no}`" />
    <V2ListPage>
        <V2DetailHero image="/images/dashboard-v2-building.png">
            <template #eyebrow>{{ t('Units') }}</template>
            <template #title>{{ t('Unit') }} {{ item.unit_no }}</template>
            <template #description>
                {{ item.project?.name || t('Unit details') }}
            </template>
            <template #actions>
                <StatusBadge :status="item.status" />
                <Can v-if="canCreateSale" permission="sales.create">
                    <Button as-child>
                        <Link :href="`/sales/create?unit_id=${item.id}`">
                            <Banknote class="size-4" /> {{ t('Create sale') }}
                        </Link>
                    </Button>
                </Can>
                <Can permission="units.edit">
                    <Button as-child :variant="canCreateSale ? 'outline' : 'default'">
                        <Link :href="`/units/${item.id}/edit`">
                            <Pencil class="size-4" /> {{ t('Edit') }}
                        </Link>
                    </Button>
                </Can>
            </template>
            <template #stats>
                <V2StatGrid class="detail-stat-grid">
                    <V2StatCard
                        :delay="0"
                        :title="t('Unit cost')"
                        :value="money(item.cost_price, item.currency)"
                    >
                        <template #icon><Wallet /></template>
                    </V2StatCard>
                    <V2StatCard
                        :delay="1"
                        icon-tone="warm"
                        :title="t('Sale price')"
                        :value="money(item.list_price, item.currency)"
                    >
                        <template #icon><CircleDollarSign /></template>
                    </V2StatCard>
                    <V2StatCard
                        :delay="2"
                        icon-tone="teal"
                        :title="t('Area')"
                        :value="areaLabel"
                    >
                        <template #icon><Ruler /></template>
                    </V2StatCard>
                    <V2StatCard
                        :delay="3"
                        icon-tone="orange"
                        :title="t('Bedrooms')"
                        :value="item.bedrooms ?? '—'"
                    >
                        <template #icon><Home /></template>
                    </V2StatCard>
                </V2StatGrid>
            </template>
        </V2DetailHero>

        <MisTabs v-model="tab" :tabs="tabs" />

        <div v-show="tab === 'info'" class="space-y-4">
            <V2Panel :title="t('General')">
                <MisDetailGrid :items="overviewItems" />
            </V2Panel>
            <V2Panel :title="t('Layout')">
                <MisDetailGrid :items="layoutItems" :columns="4" />
            </V2Panel>
        </div>

        <V2Panel
            v-show="tab === 'sales'"
            :padded="false"
            :title="t('Sales')"
            :description="t('Contracts linked to this unit')"
        >
            <template #actions>
                <Can v-if="canCreateSale" permission="sales.create">
                    <Button as-child size="sm">
                        <Link :href="`/sales/create?unit_id=${item.id}`">
                            {{ t('Create sale') }}
                        </Link>
                    </Button>
                </Can>
            </template>
            <TableFrame table-id="unit-sales" :columns="tableColumns">
                <table class="min-w-full text-sm">
                    <thead class="table-chrome-head text-start text-muted-foreground">
                        <tr>
                            <TableIndexTh />
                            <SortableTh column="sale_no">{{ t('Sale') }}</SortableTh>
                            <SortableTh column="customer.name">{{ t('Customer') }}</SortableTh>
                            <SortableTh column="amount">{{ t('Amount') }}</SortableTh>
                            <SortableTh column="status">{{ t('Status') }}</SortableTh>
                            <SortableTh column="created_at">{{ t('Date') }}</SortableTh>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="(sale, index) in sortedRows"
                            :key="sale.id"
                            class="border-t transition-colors hover:bg-muted/30"
                        >
                            <TableIndexTd :index="index" />
                            <td class="px-4 py-3">
                                <Link
                                    :href="`/sales/${sale.id}`"
                                    class="font-medium text-primary hover:underline"
                                >
                                    {{ sale.sale_no }}
                                </Link>
                            </td>
                            <td class="px-4 py-3">{{ sale.customer?.name || '—' }}</td>
                            <td class="px-4 py-3 tabular-nums">
                                {{
                                    money(
                                        Math.max(
                                            0,
                                            Number(sale.unit?.list_price ?? 0) -
                                                Number(sale.discount ?? 0),
                                        ),
                                        sale.currency || item.currency,
                                    )
                                }}
                            </td>
                            <td class="px-4 py-3">
                                <StatusBadge :status="sale.status" />
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-muted-foreground">
                                {{ formatDate(sale.created_at) }}
                            </td>
                        </tr>
                        <EmptyState
                            v-if="!item.sales?.length"
                            :colspan="6"
                            :title="t('No sales yet')"
                        >
                            <template v-if="canCreateSale" #actions>
                                <Can permission="sales.create">
                                    <Button as-child size="sm">
                                        <Link :href="`/sales/create?unit_id=${item.id}`">
                                            {{ t('Create sale') }}
                                        </Link>
                                    </Button>
                                </Can>
                            </template>
                        </EmptyState>
                    </tbody>
                </table>
            </TableFrame>
        </V2Panel>

        <div v-show="tab === 'attachments'">
            <RecordAttachments
                boxed
                attachable="units"
                :attachable-id="item.id"
                :attachments="item.attachments"
            />
        </div>
    </V2ListPage>
</template>
