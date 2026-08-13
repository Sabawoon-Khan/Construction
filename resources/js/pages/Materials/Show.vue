<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowDownToLine, ArrowLeft, ArrowUpFromLine, Pencil } from '@lucide/vue';
import DetailGrid from '@/components/DetailGrid.vue';
import Heading from '@/components/Heading.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import { formatNumber, money } from '@/lib/utils';

defineProps<{ item: Record<string, any> }>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Materials', href: '/materials' },
            { title: 'Details', href: '#' },
        ],
    },
});
</script>

<template>
    <Head :title="item.name" />
    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div class="space-y-3">
                <Button as-child variant="ghost" size="sm" class="-ml-2 w-fit">
                    <Link href="/materials"><ArrowLeft class="size-4" /> Back</Link>
                </Button>
                <Heading :title="item.name" :description="`Material ${item.code || ''}`" />
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <StatusBadge :status="item.is_active ? 'active' : 'inactive'" />
                <Button as-child variant="outline">
                    <Link :href="`/purchases/create?material_id=${item.id}`">
                        <ArrowDownToLine class="size-4" /> Purchase
                    </Link>
                </Button>
                <Button as-child variant="outline">
                    <Link :href="`/purchases/issues/create?material_id=${item.id}`">
                        <ArrowUpFromLine class="size-4" /> Issue
                    </Link>
                </Button>
                <Button as-child>
                    <Link :href="`/materials/${item.id}/edit`"><Pencil class="size-4" /> Edit</Link>
                </Button>
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-3">
            <div class="rounded-xl border bg-card p-4 shadow-sm">
                <div class="text-sm text-muted-foreground">Current stock</div>
                <div
                    class="mt-1 text-2xl font-semibold tabular-nums"
                    :class="Number(item.current_stock) < Number(item.minimum_stock) ? 'text-amber-700' : ''"
                >
                    {{ formatNumber(item.current_stock, 3) }} {{ item.unit }}
                </div>
            </div>
            <div class="rounded-xl border bg-card p-4 shadow-sm">
                <div class="text-sm text-muted-foreground">Avg unit cost</div>
                <div class="mt-1 text-2xl font-semibold tabular-nums">
                    {{ item.average_unit_cost > 0 ? money(item.average_unit_cost, item.currency || 'USD') : '—' }}
                </div>
            </div>
            <div class="rounded-xl border bg-card p-4 shadow-sm">
                <div class="text-sm text-muted-foreground">Min stock</div>
                <div class="mt-1 text-2xl font-semibold tabular-nums">{{ formatNumber(item.minimum_stock, 0) }}</div>
            </div>
        </div>

        <DetailGrid
            title="Material details"
            :items="[
                { label: 'Code', value: item.code },
                { label: 'Unit', value: item.unit },
                { label: 'Status', value: item.is_active ? 'Active' : 'Inactive' },
            ]"
        />
    </div>
</template>
