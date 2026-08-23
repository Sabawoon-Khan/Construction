<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import SortableTh from '@/components/SortableTh.vue';
import TableFrame from '@/components/TableFrame.vue';
import TableIndexTd from '@/components/TableIndexTd.vue';
import TableIndexTh from '@/components/TableIndexTh.vue';
import { indexTableColumn } from '@/composables/useTableColumns';
import { useMisPage } from '@/composables/useMisPage';
import { provideTableSort } from '@/composables/useTableSort';
import { useTranslations } from '@/composables/useTranslations';
import { money } from '@/lib/utils';

const props = defineProps<{
    item: Record<string, any>;
}>();

const { t } = useTranslations();
const tableColumns = computed(() => [
    indexTableColumn(),
    { key: 'sale_no', label: t('Sale') },
    { key: 'customer.name', label: t('Customer') },
    { key: 'unit.list_price', label: t('Amount') },
    { key: 'created_at', label: t('Created at') },
]);
const { formatDate } = useMisPage();
const { sortedRows } = provideTableSort(() => props.item.sales ?? []);
</script>

<template>
    <div class="overflow-hidden rounded-xl border bg-card shadow-sm">
        <div class="border-b px-5 py-3">
            <h3 class="text-sm font-semibold">{{ t('Recent sales') }}</h3>
        </div>
        <TableFrame table-id="project-sales" :columns="tableColumns">
        <table class="min-w-full text-sm">
            <thead class="bg-muted/40 text-start text-muted-foreground">
                <tr>
                    <TableIndexTh />
                    <SortableTh column="sale_no">{{ t('Sale') }}</SortableTh>
                    <SortableTh column="customer.name">
                        {{ t('Customer') }}
                    </SortableTh>
                    <SortableTh column="unit.list_price">{{ t('Amount') }}</SortableTh>
                    <SortableTh column="created_at">{{ t('Created at') }}</SortableTh>
                </tr>
            </thead>
            <tbody>
                <tr
                    v-for="(sale, index) in sortedRows"
                    :key="sale.id"
                    class="border-t hover:bg-muted/30"
                >
                    <TableIndexTd :index="index" />
                    <td class="px-4 py-3">
                        <Link
                            :href="`/sales/${sale.id}`"
                            class="font-medium text-primary hover:underline"
                            >{{ sale.sale_no }}</Link
                        >
                    </td>
                    <td class="px-4 py-3">
                        {{ sale.customer?.name || '—' }}
                    </td>
                    <td class="px-4 py-3 tabular-nums">
                        {{
                            money(
                                Math.max(
                                    0,
                                    Number(sale.unit?.list_price ?? 0) -
                                        Number(sale.discount ?? 0),
                                ),
                                sale.currency,
                            )
                        }}
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap text-muted-foreground">{{ formatDate(sale.created_at) }}</td>
                </tr>
                <tr v-if="!item.sales?.length">
                    <td
                        colspan="5"
                        class="px-4 py-10 text-center text-muted-foreground"
                    >
                        {{ t('No sales yet') }}
                    </td>
                </tr>
            </tbody>
        </table>
        </TableFrame>
    </div>
</template>
