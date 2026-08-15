<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, Banknote, Handshake, Info, Pencil, Printer, Receipt } from '@lucide/vue';
import { computed, ref } from 'vue';
import Can from '@/components/Can.vue';
import DetailGrid from '@/components/DetailGrid.vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { MisTabs } from '@/components/mis';
import StatusBadge from '@/components/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useTranslations } from '@/composables/useTranslations';
import { formatNumber, money } from '@/lib/utils';

defineProps<{
    item: Record<string, any>;
    stats: Record<string, number>;
    shareholders: {
        id: number;
        name: string;
        ownership_percentage: number;
        contributed_total: number;
        entitled_share: number;
        paid_total: number;
        balance_owed: number;
    }[];
    available_shareholders: { id: number; name: string; phone?: string }[];
    contributions: {
        id: number;
        partner_id: number;
        amount: number;
        currency?: string;
        invested_at: string;
        reference_no?: string;
        notes?: string;
        partner?: { id: number; name: string };
    }[];
    expenses: {
        id: number;
        expense_no: string;
        expense_date: string;
        paid_to?: string;
        description: string;
        amount: number;
        item_amount?: number | null;
        per_price?: number | null;
        currency?: string;
        expense_unit?: { id: number; unit_name: string } | null;
    }[];
    expense_categories?: { id: number; name: string; code: string }[];
    expense_units?: { id: number; unit_name: string }[];
}>();

const contributionPartnerId = ref('');
const payoutPartnerId = ref('');
const tab = ref('info');
const itemAmount = ref('');
const perPrice = ref('');
const totalCost = computed(() => {
    const qty = Number(itemAmount.value);
    const price = Number(perPrice.value);
    if (!Number.isFinite(qty) || !Number.isFinite(price)) {
        return '';
    }

    return (qty * price).toFixed(2);
});
const { t } = useTranslations();
const tabs = [
    { id: 'info', label: t('Project info'), icon: Info },
    { id: 'shareholders', label: t('Shareholders'), icon: Handshake },
    { id: 'expenses', label: t('Expenses'), icon: Receipt },
    { id: 'sales', label: t('Sales'), icon: Banknote },
];

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Projects', href: '/projects' },
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
                    <Link href="/projects"><ArrowLeft class="size-4" /> {{ t('Back') }}</Link>
                </Button>
                <Heading :title="item.name" :description="`${item.code} · ${item.location || t('No location')}`" />
            </div>
            <div class="flex items-center gap-2">
                <StatusBadge :status="item.status" />
                <Button as-child variant="outline">
                    <Link :href="`/reports?project_id=${item.id}`">{{ t('Full report') }}</Link>
                </Button>
                <Can permission="projects.edit">
                    <Button as-child>
                        <Link :href="`/projects/${item.id}/edit`"><Pencil class="size-4" /> {{ t('Edit') }}</Link>
                    </Button>
                </Can>
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="rounded-xl border bg-card p-4 shadow-sm">
                <div class="text-sm text-muted-foreground">{{ t('Sales') }}</div>
                <div class="mt-1 text-2xl font-semibold tabular-nums">{{ money(stats.sales_total) }}</div>
            </div>
            <div class="rounded-xl border bg-card p-4 shadow-sm">
                <div class="text-sm text-muted-foreground">{{ t('Project spend') }}</div>
                <div class="mt-1 text-2xl font-semibold tabular-nums text-rose-700">{{ money(stats.expenses_total) }}</div>
            </div>
            <div class="rounded-xl border bg-card p-4 shadow-sm">
                <div class="text-sm text-muted-foreground">{{ t('Net profit') }}</div>
                <div class="mt-1 text-2xl font-semibold tabular-nums text-emerald-700">{{ money(stats.net_profit) }}</div>
            </div>
            <div class="rounded-xl border bg-card p-4 shadow-sm">
                <div class="text-sm text-muted-foreground">{{ t('Shareholder money in (USD)') }}</div>
                <div class="mt-1 text-2xl font-semibold tabular-nums">{{ money(stats.contributions_total, 'USD') }}</div>
            </div>
        </div>

        <MisTabs v-model="tab" :tabs="tabs" />

        <div v-show="tab === 'info'" class="grid gap-4 lg:grid-cols-2">
            <DetailGrid
                :title="t('Project details')"
                :items="[
                    { label: t('Code'), value: item.code },
                    { label: t('Type'), value: item.type },
                    { label: t('Location'), value: item.location },
                    { label: t('Progress'), value: `${formatNumber(item.progress_percent)}%` },
                    { label: t('Start date'), value: item.start_date },
                    { label: t('Expected completion'), value: item.expected_completion_date },
                    { label: t('Estimated budget'), value: money(item.estimated_budget) },
                    { label: t('Estimated revenue'), value: money(item.estimated_revenue) },
                    { label: t('Description'), value: item.description, span: true },
                ]"
            />
        </div>

        <div v-show="tab === 'sales'" class="overflow-hidden rounded-xl border bg-card shadow-sm">
                <div class="border-b px-5 py-3">
                    <h3 class="text-sm font-semibold">{{ t('Recent sales') }}</h3>
                </div>
                <table class="min-w-full text-sm">
                    <thead class="bg-muted/40 text-left text-muted-foreground">
                        <tr>
                            <th class="px-4 py-3 font-medium">{{ t('Sale') }}</th>
                            <th class="px-4 py-3 font-medium">{{ t('Customer') }}</th>
                            <th class="px-4 py-3 font-medium">{{ t('Amount') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="sale in item.sales" :key="sale.id" class="border-t hover:bg-muted/30">
                            <td class="px-4 py-3">
                                <Link :href="`/sales/${sale.id}`" class="font-medium text-primary hover:underline">{{ sale.sale_no }}</Link>
                            </td>
                            <td class="px-4 py-3">{{ sale.customer?.name || '—' }}</td>
                            <td class="px-4 py-3 tabular-nums">{{ money(Math.max(0, Number(sale.unit?.list_price ?? 0) - Number(sale.discount ?? 0)), sale.currency || 'USD') }}</td>
                        </tr>
                        <tr v-if="!item.sales?.length">
                            <td colspan="3" class="px-4 py-10 text-center text-muted-foreground">{{ t('No sales yet') }}</td>
                        </tr>
                    </tbody>
                </table>
        </div>

        <div v-show="tab === 'shareholders'" class="overflow-hidden rounded-xl border bg-card shadow-sm">
            <div class="border-b px-5 py-4">
                <h3 class="text-sm font-semibold">{{ t('Shareholders') }}</h3>
                <p class="text-xs text-muted-foreground">
                    {{ t('Money they put in is capital, not an expense. Profit share is calculated from sales minus project spend. Payouts are tracked separately.') }}
                </p>
            </div>

            <Form
                :action="`/projects/${item.id}/shareholders`"
                method="post"
                class="grid gap-4 border-b bg-muted/20 p-5 sm:grid-cols-4"
                v-slot="{ errors, processing }"
            >
                <div class="grid gap-2 sm:col-span-2">
                    <Label for="partner_id">{{ t('Shareholder') }}</Label>
                    <select
                        id="partner_id"
                        name="partner_id"
                        required
                        class="h-9 w-full rounded-md border border-input bg-transparent px-3 text-sm shadow-xs"
                    >
                        <option value="" disabled selected>{{ t('Select from list') }}</option>
                        <option v-for="s in available_shareholders" :key="s.id" :value="s.id">
                            {{ s.name }}{{ s.phone ? ` · ${s.phone}` : '' }}
                        </option>
                    </select>
                    <InputError :message="errors.partner_id" />
                    <p v-if="!available_shareholders.length" class="text-xs text-muted-foreground">
                        {{ t('All shareholders are already on this project.') }}
                        <Can permission="partners.create">
                            <Link href="/partners/create" class="text-primary hover:underline">{{ t('Add a new shareholder') }}</Link>
                        </Can>
                    </p>
                </div>
                <div class="grid gap-2">
                    <Label for="ownership_percentage">{{ t('Ownership %') }}</Label>
                    <Input id="ownership_percentage" name="ownership_percentage" type="number" step="0.01" min="0" max="100" required />
                    <InputError :message="errors.ownership_percentage" />
                </div>
                <Can permission="partners.create">
                    <Button as-child variant="outline" class="w-full">
                        <Link href="/partners/create">{{ t('New shareholder') }}</Link>
                    </Button>
                </Can>
                <div class="sm:col-span-4">
                    <Button type="submit" :disabled="processing || !available_shareholders.length">
                        {{ processing ? t('Saving…') : t('Add to project') }}
                    </Button>
                </div>
            </Form>

            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-muted/40 text-left text-muted-foreground">
                        <tr>
                            <th class="px-4 py-3 font-medium">{{ t('Name') }}</th>
                            <th class="px-4 py-3 font-medium">{{ t('Share') }}</th>
                            <th class="px-4 py-3 font-medium">{{ t('Money in (USD)') }}</th>
                            <th class="px-4 py-3 font-medium">{{ t('Profit share (USD)') }}</th>
                            <th class="px-4 py-3 font-medium">{{ t('Paid out (USD)') }}</th>
                            <th class="px-4 py-3 font-medium">{{ t('Still owed (USD)') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="s in shareholders" :key="s.id" class="border-t hover:bg-muted/30">
                            <td class="px-4 py-3 font-medium">{{ s.name }}</td>
                            <td class="px-4 py-3 tabular-nums">{{ formatNumber(s.ownership_percentage) }}%</td>
                            <td class="px-4 py-3 tabular-nums">{{ money(s.contributed_total, 'USD') }}</td>
                            <td class="px-4 py-3 tabular-nums">{{ money(s.entitled_share, 'USD') }}</td>
                            <td class="px-4 py-3 tabular-nums text-emerald-700">{{ money(s.paid_total, 'USD') }}</td>
                            <td class="px-4 py-3 tabular-nums text-amber-700">{{ money(s.balance_owed, 'USD') }}</td>
                        </tr>
                        <tr v-if="!shareholders.length">
                            <td colspan="6" class="px-4 py-10 text-center text-muted-foreground">{{ t('No shareholders yet') }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="shareholders.length" class="grid gap-4 border-t bg-muted/10 p-5 lg:grid-cols-2">
                <Form
                    :action="contributionPartnerId ? `/projects/${item.id}/shareholders/${contributionPartnerId}/contributions` : '#'"
                    method="post"
                    class="grid gap-3 rounded-lg border bg-card p-4"
                    v-slot="{ errors, processing }"
                >
                    <h4 class="text-sm font-medium">Record money in (USD)</h4>
                    <div class="grid gap-2">
                        <Label for="contribution_partner">Shareholder</Label>
                        <select
                            id="contribution_partner"
                            v-model="contributionPartnerId"
                            class="h-9 rounded-md border border-input bg-transparent px-3 text-sm"
                            required
                        >
                            <option value="" disabled>Select shareholder</option>
                            <option v-for="s in shareholders" :key="`c-${s.id}`" :value="String(s.id)">{{ s.name }}</option>
                        </select>
                    </div>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div class="grid gap-2">
                            <Label>Date</Label>
                            <Input name="invested_at" type="date" required :default-value="new Date().toISOString().slice(0, 10)" />
                            <InputError :message="errors.invested_at" />
                        </div>
                        <div class="grid gap-2">
                            <Label>Amount (USD)</Label>
                            <Input name="amount" type="number" step="0.01" min="0.01" required placeholder="0.00" />
                            <InputError :message="errors.amount" />
                        </div>
                    </div>
                    <Button type="submit" size="sm" :disabled="processing || !contributionPartnerId">Record contribution</Button>
                </Form>

                <Form
                    :action="payoutPartnerId ? `/projects/${item.id}/shareholders/${payoutPartnerId}/payouts` : '#'"
                    method="post"
                    class="grid gap-3 rounded-lg border bg-card p-4"
                    v-slot="{ errors, processing }"
                >
                    <h4 class="text-sm font-medium">Record payout (USD)</h4>
                    <div class="grid gap-2">
                        <Label for="payout_partner">Shareholder</Label>
                        <select
                            id="payout_partner"
                            v-model="payoutPartnerId"
                            class="h-9 rounded-md border border-input bg-transparent px-3 text-sm"
                            required
                        >
                            <option value="" disabled>Select shareholder</option>
                            <option v-for="s in shareholders" :key="`p-${s.id}`" :value="String(s.id)">{{ s.name }}</option>
                        </select>
                    </div>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div class="grid gap-2">
                            <Label>Date</Label>
                            <Input name="distributed_at" type="date" required :default-value="new Date().toISOString().slice(0, 10)" />
                            <InputError :message="errors.distributed_at" />
                        </div>
                        <div class="grid gap-2">
                            <Label>Amount (USD)</Label>
                            <Input name="amount" type="number" step="0.01" min="0.01" required placeholder="0.00" />
                            <InputError :message="errors.amount" />
                        </div>
                    </div>
                    <Button type="submit" size="sm" :disabled="processing || !payoutPartnerId">Record payout</Button>
                </Form>
            </div>

            <div v-if="contributions.length" class="border-t">
                <div class="border-b px-5 py-3">
                    <h4 class="text-sm font-semibold">{{ t('Contribution history (USD)') }}</h4>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-muted/40 text-left text-muted-foreground">
                            <tr>
                                <th class="px-4 py-3 font-medium">{{ t('Date') }}</th>
                                <th class="px-4 py-3 font-medium">{{ t('Shareholder') }}</th>
                                <th class="px-4 py-3 font-medium">{{ t('Amount (USD)') }}</th>
                                <th class="px-4 py-3 font-medium">{{ t('Reference') }}</th>
                                <th class="px-4 py-3 font-medium">{{ t('Notes') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="c in contributions" :key="c.id" class="border-t hover:bg-muted/30">
                                <td class="px-4 py-3">{{ c.invested_at?.slice?.(0, 10) ?? c.invested_at }}</td>
                                <td class="px-4 py-3 font-medium">{{ c.partner?.name || '—' }}</td>
                                <td class="px-4 py-3 tabular-nums">{{ money(c.amount, c.currency || 'USD') }}</td>
                                <td class="px-4 py-3">{{ c.reference_no || '—' }}</td>
                                <td class="px-4 py-3">{{ c.notes || '—' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div v-show="tab === 'expenses'" class="overflow-hidden rounded-xl border bg-card shadow-sm">
            <div class="border-b px-5 py-4">
                <h3 class="text-sm font-semibold">{{ t('Project expenses') }}</h3>
                <p class="text-xs text-muted-foreground">{{ t('Building costs and operating spend for this project only.') }}</p>
            </div>

            <Form
                :action="`/projects/${item.id}/expenses`"
                method="post"
                class="grid gap-4 border-b bg-muted/20 p-5 sm:grid-cols-4"
                v-slot="{ errors, processing }"
            >
                <div class="grid gap-2 sm:col-span-2">
                    <Label for="description">{{ t('Description') }}</Label>
                    <Input id="description" name="description" required :placeholder="t('What was this expense for?')" />
                    <InputError :message="errors.description" />
                </div>
                <div class="grid gap-2">
                    <Label for="expense_unit_id">{{ t('Unit') }}</Label>
                    <select id="expense_unit_id" name="expense_unit_id" class="h-9 rounded-md border border-input bg-transparent px-3 text-sm">
                        <option value="">{{ t('Select unit') }}</option>
                        <option v-for="u in expense_units || []" :key="u.id" :value="u.id">{{ u.unit_name }}</option>
                    </select>
                    <Link href="/expense-units" class="text-xs text-muted-foreground hover:underline">{{ t('Manage units') }}</Link>
                </div>
                <div class="grid gap-2">
                    <Label for="item_amount">{{ t('Item amount') }}</Label>
                    <Input id="item_amount" name="item_amount" type="number" step="0.01" min="0" required v-model="itemAmount" />
                    <InputError :message="errors.item_amount" />
                </div>
                <div class="grid gap-2">
                    <Label for="per_price">{{ t('Per price') }}</Label>
                    <Input id="per_price" name="per_price" type="number" step="0.01" min="0" required v-model="perPrice" />
                    <InputError :message="errors.per_price" />
                </div>
                <div class="grid gap-2">
                    <Label for="amount">{{ t('Total cost') }}</Label>
                    <Input id="amount" type="number" step="0.01" readonly :model-value="totalCost" class="bg-muted/40" />
                    <InputError :message="errors.amount" />
                </div>
                <div class="grid gap-2">
                    <Label for="paid_to">{{ t('Paid to') }}</Label>
                    <Input id="paid_to" name="paid_to" required />
                    <InputError :message="errors.paid_to" />
                </div>
                <div class="grid gap-2">
                    <Label for="expense_date">{{ t('Date') }}</Label>
                    <Input
                        id="expense_date"
                        name="expense_date"
                        type="date"
                        required
                        :default-value="new Date().toISOString().slice(0, 10)"
                    />
                    <InputError :message="errors.expense_date" />
                </div>
                <div class="grid gap-2">
                    <Label for="expense_category_id">{{ t('Category') }}</Label>
                    <select id="expense_category_id" name="expense_category_id" class="h-9 rounded-md border border-input bg-transparent px-3 text-sm">
                        <option value="">{{ t('General') }}</option>
                        <option v-for="c in expense_categories || []" :key="c.id" :value="c.id">{{ c.name }}</option>
                    </select>
                </div>
                <div class="grid gap-2">
                    <Label for="reference_no">{{ t('Receipt / reference') }}</Label>
                    <Input id="reference_no" name="reference_no" />
                </div>
                <div class="grid gap-2 sm:col-span-2">
                    <Label for="documents">{{ t('Receipt file') }}</Label>
                    <input
                        id="documents"
                        type="file"
                        name="documents[]"
                        multiple
                        accept=".pdf,.jpg,.jpeg,.png,.webp"
                        class="text-sm"
                    />
                    <InputError :message="errors.documents" />
                </div>
                <div class="sm:col-span-4">
                    <Button type="submit" :disabled="processing">{{ processing ? t('Saving…') : t('Add expense') }}</Button>
                </div>
            </Form>

            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-muted/40 text-left text-muted-foreground">
                        <tr>
                            <th class="px-4 py-3 font-medium">{{ t('Date') }}</th>
                            <th class="px-4 py-3 font-medium">{{ t('Paid to') }}</th>
                            <th class="px-4 py-3 font-medium">{{ t('Description') }}</th>
                            <th class="px-4 py-3 font-medium">{{ t('Unit') }}</th>
                            <th class="px-4 py-3 font-medium">{{ t('Item amount') }}</th>
                            <th class="px-4 py-3 font-medium">{{ t('Per price') }}</th>
                            <th class="px-4 py-3 font-medium">{{ t('Total cost') }}</th>
                            <th class="px-4 py-3 font-medium text-right">{{ t('Voucher') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="expense in expenses" :key="expense.id" class="border-t hover:bg-muted/30">
                            <td class="px-4 py-3">{{ expense.expense_date?.slice?.(0, 10) ?? expense.expense_date }}</td>
                            <td class="px-4 py-3">{{ expense.paid_to || '—' }}</td>
                            <td class="px-4 py-3">{{ expense.description || '—' }}</td>
                            <td class="px-4 py-3">{{ expense.expense_unit?.unit_name || '—' }}</td>
                            <td class="px-4 py-3 tabular-nums">{{ expense.item_amount ?? '—' }}</td>
                            <td class="px-4 py-3 tabular-nums">{{ expense.per_price != null ? money(expense.per_price, expense.currency || 'USD') : '—' }}</td>
                            <td class="px-4 py-3 tabular-nums">{{ money(expense.amount, expense.currency || 'USD') }}</td>
                            <td class="px-4 py-3 text-right">
                                <Button as-child variant="ghost" size="sm">
                                    <Link :href="`/expenses/${expense.id}`"><Printer class="size-4" /> {{ t('Print') }}</Link>
                                </Button>
                            </td>
                        </tr>
                        <tr v-if="!expenses.length">
                            <td colspan="8" class="px-4 py-10 text-center text-muted-foreground">{{ t('No expenses yet') }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>
