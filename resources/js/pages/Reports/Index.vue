<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { useCalendar } from '@/composables/useCalendar';
import { useCurrency } from '@/composables/useCurrency';

const props = defineProps<{
    filters: { from: string; to: string; project_id?: number | null; preset?: string; currency?: string };
    restricted?: boolean;
    projects: { id: number; name: string }[];
    summary: Record<string, number>;
    expenses_by_currency: Record<string, number>;
    incomes_by_currency: Record<string, number>;
    by_category: { category: string; amount: number; amount_base: number; count: number }[];
    partners: {
        id: number;
        name: string;
        project_name?: string;
        ownership_percentage: number;
        profit_share: number;
        entitled_share: number;
        paid_total: number;
        balance_owed: number;
    }[];
    sales: any[];
    expenses: any[];
    salaries: any[];
    activity: any[];
}>();

const from = ref(props.filters.from);
const to = ref(props.filters.to);
const projectId = ref(String(props.filters.project_id ?? ''));
const calendar = useCalendar();
const { baseCurrency } = useCurrency();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Reports', href: '/reports' },
        ],
    },
});

const money = (v: unknown, currency = baseCurrency.value) =>
    new Intl.NumberFormat('en-US', { style: 'currency', currency }).format(Number(v ?? 0));

const dateOnly = (v: unknown) => calendar.format(v == null ? null : String(v)) || '—';

function apply(preset?: string) {
    router.get(
        '/reports',
        {
            preset: preset ?? 'custom',
            from: preset && preset !== 'custom' ? undefined : from.value || undefined,
            to: preset && preset !== 'custom' ? undefined : to.value || undefined,
            project_id: projectId.value || undefined,
        },
        { preserveState: true, replace: true },
    );
}

function exportUrl(format: string) {
    const params = new URLSearchParams({
        format,
        preset: props.filters.preset || 'custom',
        from: from.value,
        to: to.value,
    });

    if (projectId.value) {
        params.set('project_id', projectId.value);
    }

    return `/reports/export?${params.toString()}`;
}
</script>

<template>
    <Head title="Reports" />

    <div class="print-document flex h-full flex-1 flex-col gap-6 p-4 md:p-6 print:gap-4 print:p-0">
        <div class="space-y-4 print:hidden">
            <Heading
                title="Reports & Analytics"
                description="Daily and monthly MIS: sales, expenses, salaries, net profit and shareholder shares."
            />
            <div class="flex flex-wrap items-center gap-2 rounded-xl border bg-card p-4">
                <Button variant="outline" :class="filters.preset === 'today' ? 'border-emerald-600' : ''" @click="apply('today')">
                    Daily
                </Button>
                <Button variant="outline" :class="filters.preset === 'month' ? 'border-emerald-600' : ''" @click="apply('month')">
                    Monthly
                </Button>
                <Button variant="outline" :class="filters.preset === 'year' ? 'border-emerald-600' : ''" @click="apply('year')">
                    Yearly
                </Button>
                <Input v-model="from" type="date" class="w-auto" />
                <Input v-model="to" type="date" class="w-auto" />
                <select v-model="projectId" class="h-9 min-w-44 rounded-md border border-input bg-transparent px-3 text-sm">
                    <option value="">All projects</option>
                    <option v-for="p in projects" :key="p.id" :value="String(p.id)">
                        {{ p.name }}
                    </option>
                </select>
                <Button variant="secondary" @click="apply('custom')">Filter</Button>
                <div class="ms-auto flex flex-wrap gap-2">
                    <Button as-child variant="outline">
                        <a :href="exportUrl('xlsx')">Excel</a>
                    </Button>
                    <Button as-child>
                        <a :href="exportUrl('print').replace('/reports/export', '/reports/print')" target="_blank">Print / PDF</a>
                    </Button>
                </div>
            </div>
        </div>

        <div class="grid gap-4 md:grid-cols-2">
            <div class="rounded-2xl border bg-card p-6">
                <div class="text-sm text-muted-foreground">Sales</div>
                <div class="mt-3 text-3xl font-semibold tabular-nums">{{ money(summary.sales_total) }}</div>
                <div class="mt-1 text-sm text-muted-foreground">{{ summary.sales_count }} contracts</div>
            </div>
            <div class="rounded-2xl border bg-card p-6">
                <div class="text-sm text-muted-foreground">Total revenue</div>
                <div class="mt-3 text-3xl font-semibold tabular-nums">{{ money(summary.revenue_total) }}</div>
            </div>
            <div class="rounded-2xl border bg-card p-6">
                <div class="text-sm text-muted-foreground">Operating expenses</div>
                <div class="mt-3 text-3xl font-semibold tabular-nums">{{ money(summary.operating_expense_total) }}</div>
            </div>
            <div class="rounded-2xl border bg-card p-6">
                <div class="text-sm text-muted-foreground">Paid salaries</div>
                <div class="mt-3 text-3xl font-semibold tabular-nums">{{ money(summary.salary_total) }}</div>
            </div>
            <div class="rounded-2xl border bg-card p-6">
                <div class="text-sm text-muted-foreground">All expenses</div>
                <div class="mt-3 text-3xl font-semibold tabular-nums">{{ money(summary.expense_total) }}</div>
            </div>
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-6 dark:border-emerald-900 dark:bg-emerald-950/40">
                <div class="text-sm text-emerald-800 dark:text-emerald-300">Net profit</div>
                <div class="mt-3 text-3xl font-semibold tabular-nums">{{ money(summary.net_profit) }}</div>
                <div class="mt-1 text-sm text-muted-foreground">Sales + income − expenses − salaries</div>
            </div>
        </div>

        <div class="grid gap-4 md:grid-cols-2">
            <div class="rounded-2xl border bg-card p-6">
                <div class="flex items-center justify-between">
                    <h2 class="font-medium">AFN account</h2>
                    <span class="text-xs font-semibold rounded-full bg-muted px-2 py-1">AFN</span>
                </div>
                <div class="mt-4 grid grid-cols-2 gap-4">
                    <div>
                        <div class="text-sm text-muted-foreground">Expenses</div>
                        <div class="mt-1 text-2xl font-semibold tabular-nums text-rose-700">{{ money(props.expenses_by_currency.AFN ?? 0, 'AFN') }}</div>
                    </div>
                    <div>
                        <div class="text-sm text-muted-foreground">Incomes</div>
                        <div class="mt-1 text-2xl font-semibold tabular-nums text-emerald-700">{{ money(props.incomes_by_currency.AFN ?? 0, 'AFN') }}</div>
                    </div>
                </div>
            </div>
            <div class="rounded-2xl border bg-card p-6">
                <div class="flex items-center justify-between">
                    <h2 class="font-medium">USD account</h2>
                    <span class="text-xs font-semibold rounded-full bg-muted px-2 py-1">USD</span>
                </div>
                <div class="mt-4 grid grid-cols-2 gap-4">
                    <div>
                        <div class="text-sm text-muted-foreground">Expenses</div>
                        <div class="mt-1 text-2xl font-semibold tabular-nums text-rose-700">{{ money(props.expenses_by_currency.USD ?? 0, 'USD') }}</div>
                    </div>
                    <div>
                        <div class="text-sm text-muted-foreground">Incomes</div>
                        <div class="mt-1 text-2xl font-semibold tabular-nums text-emerald-700">{{ money(props.incomes_by_currency.USD ?? 0, 'USD') }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="rounded-2xl border bg-card p-6">
            <h2 class="mb-3 font-medium">Expenses by category</h2>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="text-left text-muted-foreground">
                        <tr>
                            <th class="py-2 pr-4">Category</th>
                            <th class="py-2 pr-4">Count</th>
                            <th class="py-2">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="row in by_category" :key="row.category" class="border-t">
                            <td class="py-3 pr-4">{{ row.category }}</td>
                            <td class="py-3 pr-4">{{ row.count }}</td>
                            <td class="py-3 tabular-nums">{{ money(row.amount_base ?? row.amount) }}</td>
                        </tr>
                        <tr v-if="!by_category.length">
                            <td colspan="3" class="py-6 text-muted-foreground">No expenses in this period.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="rounded-2xl border bg-card p-6">
            <h2 class="mb-3 font-medium">Shareholder distribution</h2>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[720px] text-sm">
                    <thead class="text-left text-muted-foreground">
                        <tr>
                            <th class="whitespace-nowrap py-2 pr-4">Shareholder</th>
                            <th class="whitespace-nowrap py-2 pr-4">Project</th>
                            <th class="whitespace-nowrap py-2 pr-4">Share %</th>
                            <th class="whitespace-nowrap py-2 pr-4">Entitled</th>
                            <th class="whitespace-nowrap py-2 pr-4">Paid</th>
                            <th class="whitespace-nowrap py-2">Balance</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="p in partners" :key="`${p.id}-${p.project_name}`" class="border-t">
                            <td class="whitespace-nowrap py-3 pr-4">{{ p.name }}</td>
                            <td class="whitespace-nowrap py-3 pr-4">{{ p.project_name || '—' }}</td>
                            <td class="whitespace-nowrap py-3 pr-4">{{ Number(p.ownership_percentage).toFixed(2) }}%</td>
                            <td class="whitespace-nowrap py-3 pr-4 tabular-nums">{{ money(p.entitled_share ?? p.profit_share) }}</td>
                            <td class="whitespace-nowrap py-3 pr-4 tabular-nums">{{ money(p.paid_total ?? 0) }}</td>
                            <td class="whitespace-nowrap py-3 font-medium tabular-nums">{{ money(p.balance_owed ?? 0) }}</td>
                        </tr>
                        <tr v-if="!partners.length">
                            <td colspan="6" class="py-6 text-muted-foreground">No shareholders in this period.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div v-if="!restricted" class="rounded-2xl border bg-card p-6">
            <h2 class="mb-3 font-medium">New sales</h2>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="text-left text-muted-foreground">
                        <tr>
                            <th class="py-2 pr-4">Date</th>
                            <th class="py-2 pr-4">Buyer</th>
                            <th class="py-2">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="s in sales" :key="s.id" class="border-t">
                            <td class="whitespace-nowrap py-3 pr-4">{{ dateOnly(s.contract_date) }}</td>
                            <td class="py-3 pr-4">{{ s.customer?.name }}</td>
                            <td class="whitespace-nowrap py-3 tabular-nums">{{ money(Math.max(0, Number(s.unit?.list_price ?? 0) - Number(s.discount ?? 0)), s.currency || baseCurrency.value) }}</td>
                        </tr>
                        <tr v-if="!sales.length">
                            <td colspan="3" class="py-6 text-muted-foreground">No sales in this period.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div v-if="!restricted" class="rounded-2xl border bg-card p-6">
            <h2 class="mb-3 font-medium">New expenses & salaries</h2>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[640px] text-sm">
                    <thead class="text-left text-muted-foreground">
                        <tr>
                            <th class="whitespace-nowrap py-2 pr-4">Date</th>
                            <th class="whitespace-nowrap py-2 pr-4">Category</th>
                            <th class="py-2 pr-4">Description</th>
                            <th class="whitespace-nowrap py-2">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="e in expenses" :key="e.id" class="border-t">
                            <td class="whitespace-nowrap py-3 pr-4">{{ dateOnly(e.expense_date) }}</td>
                            <td class="whitespace-nowrap py-3 pr-4">{{ e.category?.name || 'General' }}</td>
                            <td class="py-3 pr-4">{{ e.description }}</td>
                            <td class="whitespace-nowrap py-3 tabular-nums">{{ money(e.amount, e.currency || baseCurrency.value) }}</td>
                        </tr>
                        <tr v-if="!expenses.length">
                            <td colspan="4" class="py-6 text-muted-foreground">No expenses in this period.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div v-if="!restricted && activity.length" class="rounded-2xl border bg-card p-6">
            <h2 class="mb-3 font-medium">Project activity</h2>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <tbody>
                        <tr v-for="row in activity" :key="row.id" class="border-t">
                            <td class="whitespace-nowrap py-3 pr-4">{{ dateOnly(row.created_at) }}</td>
                            <td class="py-3 pr-4">{{ row.subject?.name || 'Project' }}</td>
                            <td class="py-3">{{ row.from_status }} → {{ row.to_status }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>
