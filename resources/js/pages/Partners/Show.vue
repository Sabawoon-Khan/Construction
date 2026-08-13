<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, Pencil } from '@lucide/vue';
import DetailGrid from '@/components/DetailGrid.vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { formatNumber, money } from '@/lib/utils';

defineProps<{
    item: Record<string, any>;
    stats: { investments_total: number; distributions_total: number };
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Shareholders', href: '/partners' },
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
                    <Link href="/partners"><ArrowLeft class="size-4" /> Back</Link>
                </Button>
                <Heading :title="item.name" :description="`Shareholder ${item.partner_no || ''}`" />
            </div>
            <Button as-child>
                <Link :href="`/partners/${item.id}/edit`"><Pencil class="size-4" /> Edit</Link>
            </Button>
        </div>

        <div class="grid gap-4 sm:grid-cols-3">
            <div class="rounded-xl border bg-card p-4 shadow-sm">
                <div class="text-sm text-muted-foreground">Ownership</div>
                <div class="mt-1 text-2xl font-semibold tabular-nums">{{ formatNumber(item.ownership_percentage) }}%</div>
            </div>
            <div class="rounded-xl border bg-card p-4 shadow-sm">
                <div class="text-sm text-muted-foreground">Investments</div>
                <div class="mt-1 text-2xl font-semibold tabular-nums">{{ money(stats.investments_total) }}</div>
            </div>
            <div class="rounded-xl border bg-card p-4 shadow-sm">
                <div class="text-sm text-muted-foreground">Distributions</div>
                <div class="mt-1 text-2xl font-semibold tabular-nums text-emerald-700">{{ money(stats.distributions_total) }}</div>
            </div>
        </div>

        <DetailGrid
            title="Shareholder details"
            :items="[
                { label: 'Partner No', value: item.partner_no },
                { label: 'Phone', value: item.phone },
                { label: 'Email', value: item.email },
                { label: 'National ID', value: item.national_id },
                { label: 'Address', value: item.address, span: true },
                { label: 'Notes', value: item.notes, span: true },
            ]"
        />
    </div>
</template>
