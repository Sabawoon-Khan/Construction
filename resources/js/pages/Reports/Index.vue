<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

const props = defineProps<{
    filters: { from: string; to: string; project_id?: number | null };
    projects: { id: number; name: string }[];
    summary: Record<string, number>;
    by_category: { category: string; amount: number; count: number }[];
    partners: {
        id: number;
        name: string;
        ownership_percentage: number;
        profit_share: number;
        entitled_share: number;
        paid_total: number;
        balance_owed: number;
    }[];
    sales: any[];
    expenses: any[];
}>();

const from = ref(props.filters.from);
const to = ref(props.filters.to);
const projectId = ref(String(props.filters.project_id ?? ''));

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Reports', href: '/reports' },
        ],
    },
});

const money = (v: unknown) =>
    new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(Number(v ?? 0));

function apply() {
    router.get(
        '/reports',
        {
            from: from.value || undefined,
            to: to.value || undefined,
            project_id: projectId.value || undefined,
        },
        { preserveState: true, replace: true },
    );
}

function exportCsv() {
    const rows = [
        ['Type', 'Date', 'Reference', 'Party', 'Amount'],
        ...props.sales.map((s) => [
            'Sale',
            s.contract_date,
            s.sale_no,
            s.customer?.name ?? '',
            Math.max(0, Number(s.unit?.list_price ?? 0) - Number(s.discount ?? 0)),
        ]),
        ...props.expenses.map((e) => [
            'Expense',
            e.expense_date,
            e.expense_no,
            e.paid_to ?? e.description ?? '',
            e.amount,
        ]),
    ];
    const csv = rows.map((r) => r.map((c) => `"${String(c ?? '').replaceAll('"', '""')}"`).join(',')).join('\n');
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `mis-report-${from.value}-to-${to.value}.csv`;
    a.click();
    URL.revokeObjectURL(url);
}

function printReport() {
    window.print();
}
</script>

<template>
    <Head title="Reports" />

    <div class="print-document flex h-full flex-1 flex-col gap-6 p-4 md:p-6 print:gap-4 print:p-0">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between print:hidden">
            <Heading
                title="Reports & Analytics"
                description="Daily and monthly MIS: sales, expenses, net profit and shareholder shares."
            />
            <div class="flex flex-wrap gap-2">
                <Input v-model="from" type="date" />
                <Input v-model="to" type="date" />
                <select
                    v-model="projectId"
                    class="h-9 rounded-md border border-input bg-transparent px-3 text-sm"
                >
                    <option value="">All projects</option>
                    <option v-for="p in projects" :key="p.id" :value="String(p.id)">
                        {{ p.name }}
                    </option>
                </select>
                <Button variant="secondary" @click="apply">Filter</Button>
                <Button variant="outline" @click="exportCsv">Excel/CSV</Button>
                <Button @click="printReport">Print / PDF</Button>
            </div>
        </div>

        <div class="hidden print:mb-4 print:block">
            <h1 class="text-xl font-semibold">Reports & Analytics</h1>
            <p class="text-sm text-slate-600">{{ filters.from }} — {{ filters.to }}</p>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-xl border bg-card p-4">
                <div class="text-sm text-muted-foreground">Sales</div>
                <div class="mt-2 text-2xl font-semibold tabular-nums">
                    {{ money(summary.sales_total) }}
                </div>
                <div class="text-xs text-muted-foreground">{{ summary.sales_count }} contracts</div>
            </div>
            <div class="rounded-xl border bg-card p-4">
                <div class="text-sm text-muted-foreground">Other income</div>
                <div class="mt-2 text-2xl font-semibold tabular-nums">
                    {{ money(summary.income_total) }}
                </div>
            </div>
            <div class="rounded-xl border bg-card p-4">
                <div class="text-sm text-muted-foreground">Expenses</div>
                <div class="mt-2 text-2xl font-semibold tabular-nums">
                    {{ money(summary.expense_total) }}
                </div>
                <div class="text-xs text-muted-foreground">{{ summary.expense_count }} entries</div>
            </div>
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 dark:border-emerald-900 dark:bg-emerald-950/40">
                <div class="text-sm text-emerald-800 dark:text-emerald-300">Net profit</div>
                <div class="mt-2 text-2xl font-semibold tabular-nums">
                    {{ money(summary.net_profit) }}
                </div>
            </div>
        </div>

        <div class="grid gap-4 lg:grid-cols-2">
            <div class="rounded-xl border bg-card p-4">
                <h2 class="mb-3 font-medium">Expenses by category</h2>
                <table class="min-w-full text-sm">
                    <thead class="text-left text-muted-foreground">
                        <tr>
                            <th class="py-2">Category</th>
                            <th>Count</th>
                            <th>Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="row in by_category" :key="row.category" class="border-t">
                            <td class="py-2">{{ row.category }}</td>
                            <td>{{ row.count }}</td>
                            <td class="tabular-nums">{{ money(row.amount) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="rounded-xl border bg-card p-4">
                <h2 class="mb-3 font-medium">Shareholder distribution</h2>
                <table class="min-w-full text-sm">
                    <thead class="text-left text-muted-foreground">
                        <tr>
                            <th class="py-2">Shareholder</th>
                            <th>Project</th>
                            <th>Share %</th>
                            <th>Entitled</th>
                            <th>Paid</th>
                            <th>Balance</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="p in partners" :key="p.id" class="border-t">
                            <td class="py-2">{{ p.name }}</td>
                            <td>{{ p.project_name || '—' }}</td>
                            <td>{{ Number(p.ownership_percentage).toFixed(2) }}%</td>
                            <td class="tabular-nums">{{ money(p.entitled_share ?? p.profit_share) }}</td>
                            <td class="tabular-nums">{{ money(p.paid_total ?? 0) }}</td>
                            <td class="font-medium tabular-nums">{{ money(p.balance_owed ?? 0) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>
