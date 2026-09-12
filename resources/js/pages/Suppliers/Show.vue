<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Package, Pencil, Wallet } from '@lucide/vue';
import Can from '@/components/Can.vue';
import RecordAttachments from '@/components/RecordAttachments.vue';
import { computed } from 'vue';
import SortableTh from '@/components/SortableTh.vue';
import TableFrame from '@/components/TableFrame.vue';
import TableIndexTd from '@/components/TableIndexTd.vue';
import TableIndexTh from '@/components/TableIndexTh.vue';
import { indexTableColumn } from '@/composables/useTableColumns';
import StatusBadge from '@/components/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import { MisDetailGrid, MisMoneyStack } from '@/components/mis';
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
import { money } from '@/lib/utils';

const { t } = useTranslations();
const tableColumns = computed(() => [
    indexTableColumn(),
    { key: 'purchase_no', label: t('Purchase') },
    { key: 'project.name', label: t('Project') },
    { key: 'total_amount', label: t('Total') },
    { key: 'remaining_amount', label: t('Remaining') },
    { key: 'status', label: t('Status') },
    { key: 'created_at', label: t('Created at') },
]);
const { formatDate } = useMisPage();

const props = defineProps<{
    item: Record<string, any>;
    stats: {
        purchases_count: number;
        purchases_total: number;
        remaining_total: number;
        purchases_by_currency?: Record<string, number>;
        remaining_by_currency?: Record<string, number>;
    };
}>();

const { sortedRows } = provideTableSort(() => props.item.purchases ?? []);

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Suppliers', href: '/suppliers' },
            { title: 'Details', href: '#' },
        ],
    },
});
</script>

<template>
    <Head :title="item.name" />
    <V2ListPage>
        <V2DetailHero image="/images/dashboard-v2-building.png">
            <template #eyebrow>{{ t('Suppliers') }}</template>
            <template #title>{{ item.name }}</template>
            <template #description>
                {{ t('Supplier') }} {{ item.supplier_no || '' }}
            </template>
            <template #actions>
                <Can permission="suppliers.edit">
                    <Button as-child>
                        <Link :href="`/suppliers/${item.id}/edit`">
                            <Pencil class="size-4" /> {{ t('Edit') }}
                        </Link>
                    </Button>
                </Can>
            </template>
            <template #stats>
                <V2StatGrid class="detail-stat-grid">
                    <V2StatCard
                        :delay="0"
                        :title="t('Purchases')"
                        :value="stats.purchases_count"
                    >
                        <template #icon><Package /></template>
                    </V2StatCard>
                    <V2StatCard :delay="1" icon-tone="warm" :title="t('Purchase total')">
                        <template #icon><Wallet /></template>
                        <span>{{ t('Purchase total') }}</span>
                        <MisMoneyStack
                            :amounts="stats.purchases_by_currency"
                            :fallback="stats.purchases_total"
                        />
                    </V2StatCard>
                    <V2StatCard :delay="2" accent icon-tone="orange" :title="t('Remaining owed')">
                        <template #icon><Wallet /></template>
                        <span>{{ t('Remaining owed') }}</span>
                        <MisMoneyStack
                            :amounts="stats.remaining_by_currency"
                            :fallback="stats.remaining_total"
                        />
                    </V2StatCard>
                </V2StatGrid>
            </template>
        </V2DetailHero>

        <V2Panel :title="t('Supplier details')">
            <MisDetailGrid
                :items="[
                    { label: t('Supplier No'), value: item.supplier_no },
                    { label: t('Contact person'), value: item.contact_person },
                    { label: t('Phone'), value: item.phone },
                    { label: t('Email'), value: item.email },
                    { label: t('Address'), value: item.address, span: 2 },
                    { label: t('Notes'), value: item.notes, span: 2 },
                ]"
            />
        </V2Panel>

        <RecordAttachments
            attachable="suppliers"
            :attachable-id="item.id"
            :attachments="item.attachments"
            boxed
        />

        <V2Panel :padded="false" :title="t('Recent purchases')">
            <TableFrame table-id="supplier-purchases" :columns="tableColumns">
                <table class="min-w-full text-sm">
                    <thead class="table-chrome-head text-start text-muted-foreground">
                        <tr>
                            <TableIndexTh />
                            <SortableTh column="purchase_no">{{ t('Purchase') }}</SortableTh>
                            <SortableTh column="project.name">{{ t('Project') }}</SortableTh>
                            <SortableTh column="total_amount">{{ t('Total') }}</SortableTh>
                            <SortableTh column="remaining_amount">{{ t('Remaining') }}</SortableTh>
                            <SortableTh column="status">{{ t('Status') }}</SortableTh>
                            <SortableTh column="created_at">{{ t('Created at') }}</SortableTh>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(purchase, index) in sortedRows" :key="purchase.id" class="border-t hover:bg-muted/30">
                            <TableIndexTd :index="index" />
                            <td class="px-4 py-3">
                                <Link :href="`/purchases/${purchase.id}`" class="font-medium text-primary hover:underline">{{ purchase.purchase_no }}</Link>
                            </td>
                            <td class="px-4 py-3">{{ purchase.project?.name || '—' }}</td>
                            <td class="px-4 py-3 tabular-nums">{{ money(purchase.total_amount, purchase.currency) }}</td>
                            <td class="px-4 py-3 tabular-nums text-amber-700">{{ money(purchase.remaining_amount, purchase.currency) }}</td>
                            <td class="px-4 py-3"><StatusBadge :status="purchase.status" /></td>
                            <td class="px-4 py-3 whitespace-nowrap text-muted-foreground">{{ formatDate(purchase.created_at) }}</td>
                        </tr>
                        <tr v-if="!item.purchases?.length">
                            <td colspan="7" class="px-4 py-10 text-center text-muted-foreground">{{ t('No purchases yet') }}</td>
                        </tr>
                    </tbody>
                </table>
            </TableFrame>
        </V2Panel>
    </V2ListPage>
</template>
