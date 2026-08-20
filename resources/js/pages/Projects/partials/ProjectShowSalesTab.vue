<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { useTranslations } from '@/composables/useTranslations';
import { money } from '@/lib/utils';

defineProps<{
    item: Record<string, any>;
}>();

const { t } = useTranslations();
</script>

<template>
    <div class="overflow-hidden rounded-xl border bg-card shadow-sm">
        <div class="border-b px-5 py-3">
            <h3 class="text-sm font-semibold">{{ t('Recent sales') }}</h3>
        </div>
        <table class="min-w-full text-sm">
            <thead class="bg-muted/40 text-start text-muted-foreground">
                <tr>
                    <th class="px-4 py-3 font-medium">{{ t('Sale') }}</th>
                    <th class="px-4 py-3 font-medium">
                        {{ t('Customer') }}
                    </th>
                    <th class="px-4 py-3 font-medium">{{ t('Amount') }}</th>
                </tr>
            </thead>
            <tbody>
                <tr
                    v-for="sale in item.sales"
                    :key="sale.id"
                    class="border-t hover:bg-muted/30"
                >
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
                </tr>
                <tr v-if="!item.sales?.length">
                    <td
                        colspan="3"
                        class="px-4 py-10 text-center text-muted-foreground"
                    >
                        {{ t('No sales yet') }}
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>
