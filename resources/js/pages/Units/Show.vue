<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, Banknote, Info, Pencil } from '@lucide/vue';
import { ref } from 'vue';
import DetailGrid from '@/components/DetailGrid.vue';
import Heading from '@/components/Heading.vue';
import { MisTabs } from '@/components/mis';
import StatusBadge from '@/components/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import { useTranslations } from '@/composables/useTranslations';
import { money } from '@/lib/utils';

defineProps<{ item: Record<string, any> }>();
const tab = ref('info');
const { t } = useTranslations();
const tabs = [
    { id: 'info', label: t('Unit info'), icon: Info },
    { id: 'sales', label: t('Sales'), icon: Banknote },
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
    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div class="space-y-3">
                <Button as-child variant="ghost" size="sm" class="-ml-2 w-fit">
                    <Link href="/units"><ArrowLeft class="size-4" /> {{ t('Back') }}</Link>
                </Button>
                <Heading :title="`${t('Unit')} ${item.unit_no}`" :description="item.project?.name || t('Unit details')" />
            </div>
            <div class="flex items-center gap-2">
                <StatusBadge :status="item.status" />
                <Button as-child>
                    <Link :href="`/units/${item.id}/edit`"><Pencil class="size-4" /> {{ t('Edit') }}</Link>
                </Button>
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-4">
            <div class="rounded-xl border bg-card p-4 shadow-sm">
                <div class="text-sm text-muted-foreground">{{ t('Planned unit cost') }}</div>
                <div class="mt-1 text-2xl font-semibold tabular-nums">{{ money(item.cost_price, item.currency || 'USD') }}</div>
            </div>
            <div class="rounded-xl border bg-card p-4 shadow-sm">
                <div class="text-sm text-muted-foreground">{{ t('Sale price') }}</div>
                <div class="mt-1 text-2xl font-semibold tabular-nums">{{ money(item.list_price, item.currency || 'USD') }}</div>
            </div>
            <div class="rounded-xl border bg-card p-4 shadow-sm">
                <div class="text-sm text-muted-foreground">{{ t('Area') }}</div>
                <div class="mt-1 text-2xl font-semibold tabular-nums">{{ item.area ?? '—' }}</div>
            </div>
            <div class="rounded-xl border bg-card p-4 shadow-sm">
                <div class="text-sm text-muted-foreground">{{ t('Bedrooms') }}</div>
                <div class="mt-1 text-2xl font-semibold tabular-nums">{{ item.bedrooms ?? '—' }}</div>
            </div>
        </div>

        <MisTabs v-model="tab" :tabs="tabs" />

        <DetailGrid
            v-show="tab === 'info'"
            :title="t('Unit details')"
            :items="[
                { label: t('Project'), value: item.project?.name },
                { label: t('Building'), value: item.floor?.building?.name },
                { label: t('Floor'), value: item.floor?.number },
                { label: t('Type'), value: item.type },
                { label: t('Buyer'), value: item.sales?.[0]?.customer?.name || '—' },
                { label: t('Currency'), value: item.currency },
                { label: t('Description'), value: item.description, span: true },
            ]"
        />

        <div v-show="tab === 'sales'" class="overflow-hidden rounded-xl border bg-card shadow-sm">
            <div class="border-b px-5 py-3">
                <h3 class="text-sm font-semibold">{{ t('Sales history') }}</h3>
            </div>
            <table class="min-w-full text-sm">
                <thead class="bg-muted/40 text-left text-muted-foreground">
                    <tr>
                        <th class="px-4 py-3 font-medium">{{ t('Sale') }}</th>
                        <th class="px-4 py-3 font-medium">{{ t('Customer') }}</th>
                        <th class="px-4 py-3 font-medium">{{ t('Net') }}</th>
                        <th class="px-4 py-3 font-medium">{{ t('Status') }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="sale in item.sales" :key="sale.id" class="border-t hover:bg-muted/30">
                        <td class="px-4 py-3">
                            <Link :href="`/sales/${sale.id}`" class="font-medium text-primary hover:underline">{{ sale.sale_no }}</Link>
                        </td>
                        <td class="px-4 py-3">{{ sale.customer?.name || '—' }}</td>
                        <td class="px-4 py-3 tabular-nums">{{ money(Math.max(0, Number(sale.unit?.list_price ?? 0) - Number(sale.discount ?? 0)), sale.currency || 'USD') }}</td>
                        <td class="px-4 py-3"><StatusBadge :status="sale.status" /></td>
                    </tr>
                    <tr v-if="!item.sales?.length">
                        <td colspan="4" class="px-4 py-10 text-center text-muted-foreground">{{ t('No sales yet') }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
