<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowDownToLine, ArrowUpFromLine, Eye, Plus, Printer, Search } from '@lucide/vue';
import { ref } from 'vue';
import EmptyState from '@/components/EmptyState.vue';
import Heading from '@/components/Heading.vue';
import Pagination from '@/components/Pagination.vue';
import RowActionsMenu from '@/components/RowActionsMenu.vue';
import { Button } from '@/components/ui/button';
import type { RowActionItem } from '@/lib/row-actions';
import { money } from '@/lib/utils';

const props = defineProps<{
    items: { data: any[]; links: any[]; meta?: any };
    filters: { type?: string; material_id?: number | string; project_id?: number | string };
    materials: { id: number; code: string; name: string; unit: string }[];
    projects: { id: number; name: string }[];
}>();

const type = ref(props.filters.type ?? '');
const materialId = ref(String(props.filters.material_id ?? ''));
const projectId = ref(String(props.filters.project_id ?? ''));

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Purchases', href: '/purchases' },
        ],
    },
});

function applyFilters() {
    router.get(
        '/purchases',
        {
            type: type.value || undefined,
            material_id: materialId.value || undefined,
            project_id: projectId.value || undefined,
        },
        { preserveState: true, replace: true },
    );
}

function typeLabel(value: string) {
    if (value === 'purchase') return 'Purchase';
    if (value === 'issue') return 'Issue';
    return value;
}

function rowActions(item: { id: number }): RowActionItem[] {
    return [
        { label: 'View', icon: Eye, href: `/purchases/${item.id}` },
        { label: 'Print', icon: Printer, href: `/purchases/${item.id}` },
    ];
}
</script>

<template>
    <Head title="Purchases" />
    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <Heading title="Purchases" description="Record material purchases and issue stock to projects." />
            <div class="flex flex-wrap gap-2">
                <select
                    v-model="type"
                    class="h-9 rounded-md border border-input bg-background px-3 text-sm"
                    @change="applyFilters"
                >
                    <option value="">All movements</option>
                    <option value="purchase">Purchases</option>
                    <option value="issue">Issues</option>
                </select>
                <select
                    v-model="materialId"
                    class="h-9 rounded-md border border-input bg-background px-3 text-sm"
                    @change="applyFilters"
                >
                    <option value="">All materials</option>
                    <option v-for="material in materials" :key="material.id" :value="String(material.id)">
                        {{ material.name }}
                    </option>
                </select>
                <select
                    v-model="projectId"
                    class="h-9 rounded-md border border-input bg-background px-3 text-sm"
                    @change="applyFilters"
                >
                    <option value="">All projects</option>
                    <option v-for="project in projects" :key="project.id" :value="String(project.id)">
                        {{ project.name }}
                    </option>
                </select>
                <Button variant="secondary" @click="applyFilters"><Search class="size-4" /> Filter</Button>
                <Button as-child variant="outline">
                    <Link href="/purchases/create"><ArrowDownToLine class="size-4" /> Record purchase</Link>
                </Button>
                <Button as-child>
                    <Link href="/purchases/issues/create"><ArrowUpFromLine class="size-4" /> Issue</Link>
                </Button>
            </div>
        </div>

        <div class="overflow-hidden rounded-xl border border-sidebar-border/70 bg-card shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="border-b bg-muted/40 text-left text-muted-foreground">
                        <tr>
                            <th class="px-4 py-3 font-medium">Date</th>
                            <th class="px-4 py-3 font-medium">Type</th>
                            <th class="px-4 py-3 font-medium">Material</th>
                            <th class="px-4 py-3 font-medium">Qty</th>
                            <th class="px-4 py-3 font-medium">Supplier / Project</th>
                            <th class="px-4 py-3 font-medium">Amount</th>
                            <th class="px-4 py-3 font-medium">Reference</th>
                            <th class="px-4 py-3 font-medium"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="item in items.data" :key="item.id" class="border-b last:border-0 transition-colors hover:bg-muted/30">
                            <td class="px-4 py-3">{{ item.transaction_date?.slice?.(0, 10) ?? item.transaction_date }}</td>
                            <td class="px-4 py-3">{{ typeLabel(item.type) }}</td>
                            <td class="px-4 py-3">
                                <div class="font-medium">{{ item.material?.name }}</div>
                                <div class="text-xs text-muted-foreground">{{ item.material?.code }}</div>
                            </td>
                            <td class="px-4 py-3 tabular-nums">
                                {{ item.quantity }} {{ item.material?.unit }}
                            </td>
                            <td class="px-4 py-3">
                                <template v-if="item.type === 'purchase'">
                                    {{ item.supplier?.name || '—' }}
                                    <div v-if="item.project?.name" class="text-xs text-muted-foreground">
                                        Direct to {{ item.project.name }}
                                    </div>
                                </template>
                                <template v-else>
                                    {{ item.project?.name || '—' }}
                                </template>
                            </td>
                            <td class="px-4 py-3 tabular-nums">{{ money(item.total_cost, item.currency || 'USD') }}</td>
                            <td class="px-4 py-3">
                                <Link
                                    v-if="item.expense"
                                    :href="`/expenses/${item.expense.id}`"
                                    class="hover:text-primary hover:underline"
                                >
                                    {{ item.expense.expense_no }}
                                </Link>
                                <span v-else>{{ item.reference_no || '—' }}</span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <RowActionsMenu :actions="rowActions(item)" />
                            </td>
                        </tr>
                        <EmptyState v-if="!items.data.length" :colspan="8" title="No purchases yet">
                            <Button as-child size="sm" class="mt-3">
                                <Link href="/purchases/create"><Plus class="size-4" /> Record purchase</Link>
                            </Button>
                        </EmptyState>
                    </tbody>
                </table>
            </div>
            <Pagination :paginator="items" />
        </div>
    </div>
</template>
