<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, Pencil } from '@lucide/vue';
import Can from '@/components/Can.vue';
import DetailGrid from '@/components/DetailGrid.vue';
import RecordAttachments from '@/components/RecordAttachments.vue';
import Heading from '@/components/Heading.vue';
import { computed } from 'vue';
import SortableTh from '@/components/SortableTh.vue';
import TableFrame from '@/components/TableFrame.vue';
import TableIndexTd from '@/components/TableIndexTd.vue';
import TableIndexTh from '@/components/TableIndexTh.vue';
import { indexTableColumn } from '@/composables/useTableColumns';
import StatusBadge from '@/components/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import { useMisPage } from '@/composables/useMisPage';
import { provideTableSort } from '@/composables/useTableSort';
import { useTranslations } from '@/composables/useTranslations';
import { money, moneyByCurrency } from '@/lib/utils';

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
    <div class="flex h-full flex-1 flex-col gap-4">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div class="space-y-3">
                <Button as-child variant="ghost" size="sm" class="-ml-2 w-fit">
                    <Link href="/suppliers"><ArrowLeft class="size-4" /> {{ t('Back') }}</Link>
                </Button>
                <Heading :title="item.name" :description="`${t('Supplier')} ${item.supplier_no || ''}`" />
            </div>
            <Can permission="suppliers.edit">
                <Button as-child>
                    <Link :href="`/suppliers/${item.id}/edit`"><Pencil class="size-4" /> {{ t('Edit') }}</Link>
                </Button>
            </Can>
        </div>

        <div class="grid gap-4 sm:grid-cols-3">
            <div class="rounded-xl border bg-card p-4 shadow-sm">
                <div class="text-sm text-muted-foreground">{{ t('Purchases') }}</div>
                <div class="mt-1 text-2xl font-semibold tabular-nums">{{ stats.purchases_count }}</div>
            </div>
            <div class="rounded-xl border bg-card p-4 shadow-sm">
                <div class="text-sm text-muted-foreground">{{ t('Purchase total') }}</div>
                <div class="mt-1 text-2xl font-semibold tabular-nums">{{ moneyByCurrency(stats.purchases_by_currency, stats.purchases_total) }}</div>
            </div>
            <div class="rounded-xl border bg-card p-4 shadow-sm">
                <div class="text-sm text-muted-foreground">{{ t('Remaining owed') }}</div>
                <div class="mt-1 text-2xl font-semibold tabular-nums text-amber-700">{{ moneyByCurrency(stats.remaining_by_currency, stats.remaining_total) }}</div>
            </div>
        </div>

        <DetailGrid
            :title="t('Supplier details')"
            :items="[
                { label: t('Supplier No'), value: item.supplier_no },
                { label: t('Contact person'), value: item.contact_person },
                { label: t('Phone'), value: item.phone },
                { label: t('Email'), value: item.email },
                { label: t('Address'), value: item.address, span: true },
                { label: t('Notes'), value: item.notes, span: true },
            ]"
        />

        <RecordAttachments
            attachable="suppliers"
            :attachable-id="item.id"
            :attachments="item.attachments"
            boxed
        />

        <div class="overflow-hidden rounded-xl border bg-card shadow-sm">
            <div class="border-b px-5 py-3">
                <h3 class="text-sm font-semibold">{{ t('Recent purchases') }}</h3>
            </div>
            <TableFrame table-id="supplier-purchases" :columns="tableColumns">
            <table class="min-w-full text-sm">
                <thead class="bg-muted/40 text-start text-muted-foreground">
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
        </div>
    </div>
</template>
