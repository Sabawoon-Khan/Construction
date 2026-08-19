<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import {
    ArrowLeft,
    Banknote,
    Handshake,
    Info,
    Package,
    Pencil,
    Printer,
    Receipt,
    Trash2,
    Warehouse,
} from '@lucide/vue';
import { ref } from 'vue';
import Can from '@/components/Can.vue';
import CurrencySelect from '@/components/CurrencySelect.vue';
import DetailGrid from '@/components/DetailGrid.vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { MisTabs } from '@/components/mis';
import StatusBadge from '@/components/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useCalendar } from '@/composables/useCalendar';
import { useCurrency } from '@/composables/useCurrency';
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
        partner?: { id: number; name: string };
    }[];
    expenses: {
        id: number;
        expense_no: string;
        expense_date: string;
        paid_to?: string;
        description: string;
        amount: number;
        currency?: string;
        purchase_id?: number | null;
    }[];
    expense_categories?: { id: number; name: string; code: string }[];
    purchases?: {
        id: number;
        purchase_no: string;
        purchase_date: string;
        total_amount: number;
        paid_amount: number;
        remaining_amount?: number;
        currency?: string;
        status: string;
        supplier?: { id: number; name: string };
    }[];
    stock?: {
        material_id: number;
        purchased: number;
        issued: number;
        on_hand: number;
        last_issue_at?: string | null;
    }[];
    stock_materials?: {
        id: number;
        name: string;
        code: string;
        unit?: { unit_name: string } | null;
    }[];
    stock_issues?: {
        id: number;
        material_id: number;
        quantity: number;
        transaction_date?: string | null;
        material?: { id: number; name: string } | null;
    }[];
}>();

const contributionPartnerId = ref('');
const payoutPartnerId = ref('');
const tab = ref('info');
const { t } = useTranslations();
const { baseCurrency } = useCurrency();
const expenseCurrency = ref(baseCurrency.value);
const contributionCurrency = ref(baseCurrency.value);
const payoutCurrency = ref(baseCurrency.value);
const calendar = useCalendar();
const formatDate = (value: string | null | undefined) =>
    calendar.format(value) || '—';
const tabs = [
    { id: 'info', label: t('Project info'), icon: Info },
    { id: 'shareholders', label: t('Shareholders'), icon: Handshake },
    { id: 'expenses', label: t('Expenses'), icon: Receipt },
    { id: 'purchases', label: t('Purchases'), icon: Package },
    { id: 'stock', label: t('Stock'), icon: Warehouse },
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
        <div
            class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between"
        >
            <div class="space-y-3">
                <Button as-child variant="ghost" size="sm" class="-ml-2 w-fit">
                    <Link href="/projects"
                        ><ArrowLeft class="size-4" /> {{ t('Back') }}</Link
                    >
                </Button>
                <Heading
                    :title="item.name"
                    :description="`${item.code} · ${item.location || t('No location')}`"
                />
            </div>
            <div class="flex items-center gap-2">
                <StatusBadge :status="item.status" />
                <Button as-child variant="outline">
                    <Link :href="`/reports?project_id=${item.id}`">{{
                        t('Full report')
                    }}</Link>
                </Button>
                <Can permission="projects.edit">
                    <Button as-child>
                        <Link :href="`/projects/${item.id}/edit`"
                            ><Pencil class="size-4" /> {{ t('Edit') }}</Link
                        >
                    </Button>
                </Can>
                <Can permission="projects.delete">
                    <Form :action="`/projects/${item.id}`" method="delete" v-slot="{ processing }">
                        <Button type="submit" variant="destructive" :disabled="processing">
                            <Trash2 class="size-4" /> {{ processing ? t('Deleting…') : t('Delete') }}
                        </Button>
                    </Form>
                </Can>
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="rounded-xl border bg-card p-4 shadow-sm">
                <div class="text-sm text-muted-foreground">
                    {{ t('Sales') }}
                </div>
                <div class="mt-1 text-2xl font-semibold tabular-nums">
                    {{
                        money(
                            stats.sales_total_base ?? stats.sales_total,
                            baseCurrency,
                        )
                    }}
                </div>
            </div>
            <div class="rounded-xl border bg-card p-4 shadow-sm">
                <div class="text-sm text-muted-foreground">
                    {{ t('Project spend') }}
                </div>
                <div
                    class="mt-1 text-2xl font-semibold text-rose-700 tabular-nums"
                >
                    {{
                        money(
                            stats.expenses_total_base ?? stats.expenses_total,
                            baseCurrency,
                        )
                    }}
                </div>
            </div>
            <div class="rounded-xl border bg-card p-4 shadow-sm">
                <div class="text-sm text-muted-foreground">
                    {{ t('Owed to suppliers') }}
                </div>
                <div
                    class="mt-1 text-2xl font-semibold text-amber-700 tabular-nums"
                >
                    {{
                        money(
                            stats.purchases_remaining_base ??
                                stats.purchases_remaining ??
                                0,
                            baseCurrency,
                        )
                    }}
                </div>
            </div>
            <div class="rounded-xl border bg-card p-4 shadow-sm">
                <div class="text-sm text-muted-foreground">
                    {{ t('Net profit') }}
                </div>
                <div
                    class="mt-1 text-2xl font-semibold text-emerald-700 tabular-nums"
                >
                    {{ money(stats.net_profit, baseCurrency) }}
                </div>
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
                    {
                        label: t('Progress'),
                        value: `${formatNumber(item.progress_percent)}%`,
                    },
                    {
                        label: t('Start date'),
                        value: formatDate(item.start_date),
                    },
                    {
                        label: t('Expected completion'),
                        value: formatDate(item.expected_completion_date),
                    },
                    {
                        label: t('Estimated budget'),
                        value: money(item.estimated_budget),
                    },
                    {
                        label: t('Estimated revenue'),
                        value: money(item.estimated_revenue),
                    },
                    {
                        label: t('Description'),
                        value: item.description,
                        span: true,
                    },
                ]"
            />
        </div>

        <div
            v-show="tab === 'sales'"
            class="overflow-hidden rounded-xl border bg-card shadow-sm"
        >
            <div class="border-b px-5 py-3">
                <h3 class="text-sm font-semibold">{{ t('Recent sales') }}</h3>
            </div>
            <table class="min-w-full text-sm">
                <thead class="bg-muted/40 text-left text-muted-foreground">
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
                                    sale.currency || 'USD',
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

        <div
            v-show="tab === 'shareholders'"
            class="overflow-hidden rounded-xl border bg-card shadow-sm"
        >
            <div class="border-b px-5 py-4">
                <h3 class="text-sm font-semibold">{{ t('Shareholders') }}</h3>
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
                        <option value="" disabled selected>
                            {{ t('Select from list') }}
                        </option>
                        <option
                            v-for="s in available_shareholders"
                            :key="s.id"
                            :value="s.id"
                        >
                            {{ s.name }}{{ s.phone ? ` · ${s.phone}` : '' }}
                        </option>
                    </select>
                    <InputError :message="errors.partner_id" />
                </div>
                <div class="grid gap-2">
                    <Label for="ownership_percentage">{{
                        t('Ownership %')
                    }}</Label>
                    <Input
                        id="ownership_percentage"
                        name="ownership_percentage"
                        type="number"
                        step="0.01"
                        min="0"
                        max="100"
                        required
                    />
                    <InputError :message="errors.ownership_percentage" />
                </div>
                <Can permission="partners.create">
                    <Button as-child variant="outline" class="w-full">
                        <Link href="/partners/create">{{
                            t('New shareholder')
                        }}</Link>
                    </Button>
                </Can>
                <div class="sm:col-span-4">
                    <Button
                        type="submit"
                        :disabled="processing || !available_shareholders.length"
                    >
                        {{ processing ? t('Saving…') : t('Add to project') }}
                    </Button>
                </div>
            </Form>

            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-muted/40 text-left text-muted-foreground">
                        <tr>
                            <th class="px-4 py-3 font-medium">
                                {{ t('Name') }}
                            </th>
                            <th class="px-4 py-3 font-medium">
                                {{ t('Share') }}
                            </th>
                            <th class="px-4 py-3 font-medium">
                                {{ t('Money in') }}
                            </th>
                            <th class="px-4 py-3 font-medium">
                                {{ t('Profit share') }}
                            </th>
                            <th class="px-4 py-3 font-medium">
                                {{ t('Paid out') }}
                            </th>
                            <th class="px-4 py-3 font-medium">
                                {{ t('Still owed') }}
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="s in shareholders"
                            :key="s.id"
                            class="border-t hover:bg-muted/30"
                        >
                            <td class="px-4 py-3 font-medium">{{ s.name }}</td>
                            <td class="px-4 py-3 tabular-nums">
                                {{ formatNumber(s.ownership_percentage) }}%
                            </td>
                            <td class="px-4 py-3 tabular-nums">
                                {{ money(s.contributed_total, baseCurrency) }}
                            </td>
                            <td class="px-4 py-3 tabular-nums">
                                {{ money(s.entitled_share, baseCurrency) }}
                            </td>
                            <td class="px-4 py-3 text-emerald-700 tabular-nums">
                                {{ money(s.paid_total, baseCurrency) }}
                            </td>
                            <td class="px-4 py-3 text-amber-700 tabular-nums">
                                {{ money(s.balance_owed, baseCurrency) }}
                            </td>
                        </tr>
                        <tr v-if="!shareholders.length">
                            <td
                                colspan="6"
                                class="px-4 py-10 text-center text-muted-foreground"
                            >
                                {{ t('No shareholders yet') }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div
                v-if="shareholders.length"
                class="grid gap-4 border-t bg-muted/10 p-5 lg:grid-cols-2"
            >
                <Form
                    :action="
                        contributionPartnerId
                            ? `/projects/${item.id}/shareholders/${contributionPartnerId}/contributions`
                            : '#'
                    "
                    method="post"
                    class="grid gap-3 rounded-lg border bg-card p-4"
                    v-slot="{ errors, processing }"
                >
                    <h4 class="text-sm font-medium">Record money in</h4>
                    <div class="grid gap-2">
                        <Label for="contribution_partner">Shareholder</Label>
                        <select
                            id="contribution_partner"
                            v-model="contributionPartnerId"
                            class="h-9 rounded-md border border-input bg-transparent px-3 text-sm"
                            required
                        >
                            <option value="" disabled>
                                Select shareholder
                            </option>
                            <option
                                v-for="s in shareholders"
                                :key="`c-${s.id}`"
                                :value="String(s.id)"
                            >
                                {{ s.name }}
                            </option>
                        </select>
                    </div>
                    <div class="grid gap-3 sm:grid-cols-3">
                        <div class="grid gap-2">
                            <Label>Date</Label>
                            <Input
                                name="invested_at"
                                type="date"
                                required
                                :default-value="
                                    new Date().toISOString().slice(0, 10)
                                "
                            />
                            <InputError :message="errors.invested_at" />
                        </div>
                        <div class="grid gap-2">
                            <Label>Amount</Label>
                            <Input
                                name="amount"
                                type="number"
                                step="0.01"
                                min="0.01"
                                required
                                placeholder="0.00"
                            />
                            <InputError :message="errors.amount" />
                        </div>
                        <div class="grid gap-2">
                            <Label>Currency</Label>
                            <CurrencySelect v-model="contributionCurrency" />
                            <input
                                type="hidden"
                                name="currency"
                                :value="contributionCurrency"
                            />
                            <InputError :message="errors.currency" />
                        </div>
                    </div>
                    <Button
                        type="submit"
                        size="sm"
                        :disabled="processing || !contributionPartnerId"
                        >Record contribution</Button
                    >
                </Form>

                <Form
                    :action="
                        payoutPartnerId
                            ? `/projects/${item.id}/shareholders/${payoutPartnerId}/payouts`
                            : '#'
                    "
                    method="post"
                    class="grid gap-3 rounded-lg border bg-card p-4"
                    v-slot="{ errors, processing }"
                >
                    <h4 class="text-sm font-medium">Record payout</h4>
                    <div class="grid gap-2">
                        <Label for="payout_partner">Shareholder</Label>
                        <select
                            id="payout_partner"
                            v-model="payoutPartnerId"
                            class="h-9 rounded-md border border-input bg-transparent px-3 text-sm"
                            required
                        >
                            <option value="" disabled>
                                Select shareholder
                            </option>
                            <option
                                v-for="s in shareholders"
                                :key="`p-${s.id}`"
                                :value="String(s.id)"
                            >
                                {{ s.name }}
                            </option>
                        </select>
                    </div>
                    <div class="grid gap-3 sm:grid-cols-3">
                        <div class="grid gap-2">
                            <Label>Date</Label>
                            <Input
                                name="distributed_at"
                                type="date"
                                required
                                :default-value="
                                    new Date().toISOString().slice(0, 10)
                                "
                            />
                            <InputError :message="errors.distributed_at" />
                        </div>
                        <div class="grid gap-2">
                            <Label>Amount</Label>
                            <Input
                                name="amount"
                                type="number"
                                step="0.01"
                                min="0.01"
                                required
                                placeholder="0.00"
                            />
                            <InputError :message="errors.amount" />
                        </div>
                        <div class="grid gap-2">
                            <Label>Currency</Label>
                            <CurrencySelect v-model="payoutCurrency" />
                            <input
                                type="hidden"
                                name="currency"
                                :value="payoutCurrency"
                            />
                            <InputError :message="errors.currency" />
                        </div>
                    </div>
                    <Button
                        type="submit"
                        size="sm"
                        :disabled="processing || !payoutPartnerId"
                        >Record payout</Button
                    >
                </Form>
            </div>

            <div v-if="contributions.length" class="border-t">
                <div class="border-b px-5 py-3">
                    <h4 class="text-sm font-semibold">
                        {{ t('Contribution history') }}
                    </h4>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead
                            class="bg-muted/40 text-left text-muted-foreground"
                        >
                            <tr>
                                <th class="px-4 py-3 font-medium">
                                    {{ t('Date') }}
                                </th>
                                <th class="px-4 py-3 font-medium">
                                    {{ t('Shareholder') }}
                                </th>
                                <th class="px-4 py-3 font-medium">
                                    {{ t('Amount') }}
                                </th>
                                <th class="px-4 py-3 text-right font-medium">
                                    {{ t('Actions') }}
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="c in contributions"
                                :key="c.id"
                                class="border-t hover:bg-muted/30"
                            >
                                <td class="px-4 py-3">
                                    {{ formatDate(c.invested_at) }}
                                </td>
                                <td class="px-4 py-3 font-medium">
                                    {{ c.partner?.name || '—' }}
                                </td>
                                <td class="px-4 py-3 tabular-nums">
                                    {{
                                        money(
                                            c.amount,
                                            c.currency || baseCurrency,
                                        )
                                    }}
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex justify-end">
                                        <Button
                                            as-child
                                            size="sm"
                                            variant="outline"
                                        >
                                            <Link
                                                :href="`/projects/${item.id}/contributions/${c.id}`"
                                            >
                                                <Printer class="size-4" />
                                                {{ t('Voucher') }}
                                            </Link>
                                        </Button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div
            v-show="tab === 'expenses'"
            class="overflow-hidden rounded-xl border bg-card shadow-sm"
        >
            <div
                class="flex flex-wrap items-start justify-between gap-3 border-b px-5 py-4"
            >
                <div>
                    <h3 class="text-sm font-semibold">
                        {{ t('Project expenses') }}
                    </h3>
                </div>
                <Can permission="purchases.create">
                    <Button as-child size="sm" variant="outline">
                        <Link
                            :href="`/purchases/create?project_id=${item.id}`"
                            >{{
                                t('Buying materials? Record a purchase')
                            }}</Link
                        >
                    </Button>
                </Can>
            </div>

            <Form
                :action="`/projects/${item.id}/expenses`"
                method="post"
                class="grid gap-4 border-b bg-muted/20 p-5 sm:grid-cols-4"
                v-slot="{ errors, processing }"
            >
                <div class="grid gap-2 sm:col-span-2">
                    <Label for="description">{{ t('Description') }}</Label>
                    <Input
                        id="description"
                        name="description"
                        required
                        :placeholder="t('e.g. site labor, transport, salary')"
                    />
                    <InputError :message="errors.description" />
                </div>
                <div class="grid gap-2">
                    <Label for="amount">{{ t('Amount') }}</Label>
                    <Input
                        id="amount"
                        name="amount"
                        type="number"
                        step="0.01"
                        min="0.01"
                        required
                    />
                    <InputError :message="errors.amount" />
                </div>
                <div class="grid gap-2">
                    <Label for="currency">{{ t('Currency') }}</Label>
                    <CurrencySelect id="currency" v-model="expenseCurrency" />
                    <input
                        type="hidden"
                        name="currency"
                        :value="expenseCurrency"
                    />
                    <InputError :message="errors.currency" />
                </div>
                <div class="grid gap-2">
                    <Label for="paid_to">{{ t('Paid to') }}</Label>
                    <Input
                        id="paid_to"
                        name="paid_to"
                        required
                        :placeholder="t('Worker, contractor, or payee')"
                    />
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
                    <select
                        id="expense_category_id"
                        name="expense_category_id"
                        class="h-9 rounded-md border border-input bg-transparent px-3 text-sm"
                    >
                        <option value="">{{ t('General') }}</option>
                        <option
                            v-for="c in expense_categories || []"
                            :key="c.id"
                            :value="c.id"
                        >
                            {{ c.name }}
                        </option>
                    </select>
                </div>
                <div class="grid gap-2">
                    <Label for="reference_no">{{
                        t('Receipt / reference')
                    }}</Label>
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
                    <Button type="submit" :disabled="processing">{{
                        processing ? t('Saving…') : t('Add expense')
                    }}</Button>
                </div>
            </Form>

            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-muted/40 text-left text-muted-foreground">
                        <tr>
                            <th class="px-4 py-3 font-medium">
                                {{ t('Date') }}
                            </th>
                            <th class="px-4 py-3 font-medium">
                                {{ t('Paid to') }}
                            </th>
                            <th class="px-4 py-3 font-medium">
                                {{ t('Description') }}
                            </th>
                            <th class="px-4 py-3 font-medium">
                                {{ t('Amount') }}
                            </th>
                            <th class="px-4 py-3 text-right font-medium">
                                {{ t('Actions') }}
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="expense in expenses"
                            :key="expense.id"
                            class="border-t hover:bg-muted/30"
                        >
                            <td class="px-4 py-3">
                                {{ formatDate(expense.expense_date) }}
                            </td>
                            <td class="px-4 py-3">
                                {{ expense.paid_to || '—' }}
                            </td>
                            <td class="px-4 py-3">
                                {{ expense.description || '—' }}
                                <span
                                    v-if="expense.purchase_id"
                                    class="ml-1 text-xs text-muted-foreground"
                                    >{{ t('(from purchase)') }}</span
                                >
                            </td>
                            <td class="px-4 py-3 tabular-nums">
                                {{
                                    money(
                                        expense.amount,
                                        expense.currency || baseCurrency,
                                    )
                                }}
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap justify-end gap-1">
                                    <Button as-child variant="ghost" size="sm">
                                        <Link :href="`/expenses/${expense.id}`"
                                            ><Printer class="size-4" />
                                            {{ t('Print') }}</Link
                                        >
                                    </Button>
                                    <Can permission="expenses.edit">
                                        <Button
                                            v-if="!expense.purchase_id"
                                            as-child
                                            variant="ghost"
                                            size="sm"
                                        >
                                            <Link :href="`/expenses/${expense.id}/edit`"
                                                ><Pencil class="size-4" />
                                                {{ t('Edit') }}</Link
                                            >
                                        </Button>
                                    </Can>
                                    <Can permission="expenses.delete">
                                        <Form
                                            v-if="!expense.purchase_id"
                                            :action="`/expenses/${expense.id}`"
                                            method="delete"
                                            v-slot="{ processing }"
                                        >
                                            <Button
                                                type="submit"
                                                variant="ghost"
                                                size="sm"
                                                class="text-destructive hover:text-destructive"
                                                :disabled="processing"
                                            >
                                                <Trash2 class="size-4" />
                                                {{ processing ? t('Deleting…') : t('Delete') }}
                                            </Button>
                                        </Form>
                                    </Can>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="!expenses.length">
                            <td
                                colspan="5"
                                class="px-4 py-10 text-center text-muted-foreground"
                            >
                                {{ t('No expenses yet') }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div
            v-show="tab === 'purchases'"
            class="overflow-hidden rounded-xl border bg-card shadow-sm"
        >
            <div
                class="flex flex-wrap items-center justify-between gap-3 border-b px-5 py-4"
            >
                <div>
                    <h3 class="text-sm font-semibold">
                        {{ t('Project purchases') }}
                    </h3>
                </div>
                <Can permission="purchases.create">
                    <Button as-child size="sm">
                        <Link
                            :href="`/purchases/create?project_id=${item.id}`"
                            >{{ t('New purchase') }}</Link
                        >
                    </Button>
                </Can>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-muted/40 text-left text-muted-foreground">
                        <tr>
                            <th class="px-4 py-3 font-medium">
                                {{ t('Purchase') }}
                            </th>
                            <th class="px-4 py-3 font-medium">
                                {{ t('Date') }}
                            </th>
                            <th class="px-4 py-3 font-medium">
                                {{ t('Supplier') }}
                            </th>
                            <th class="px-4 py-3 font-medium">
                                {{ t('Total') }}
                            </th>
                            <th class="px-4 py-3 font-medium">
                                {{ t('Remaining') }}
                            </th>
                            <th class="px-4 py-3 font-medium">
                                {{ t('Status') }}
                            </th>
                            <th class="px-4 py-3 font-medium text-right">
                                {{ t('Pay') }}
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="purchase in purchases || []"
                            :key="purchase.id"
                            class="border-t hover:bg-muted/30"
                        >
                            <td class="px-4 py-3">
                                <Link
                                    :href="`/purchases/${purchase.id}`"
                                    class="font-medium text-primary hover:underline"
                                    >{{ purchase.purchase_no }}</Link
                                >
                            </td>
                            <td class="px-4 py-3">
                                {{ formatDate(purchase.purchase_date) }}
                            </td>
                            <td class="px-4 py-3">
                                {{ purchase.supplier?.name || '—' }}
                            </td>
                            <td class="px-4 py-3 tabular-nums">
                                {{
                                    money(
                                        purchase.total_amount,
                                        purchase.currency || baseCurrency,
                                    )
                                }}
                            </td>
                            <td class="px-4 py-3 text-amber-700 tabular-nums">
                                {{
                                    money(
                                        purchase.remaining_amount ??
                                            Math.max(
                                                0,
                                                Number(purchase.total_amount) -
                                                    Number(
                                                        purchase.paid_amount,
                                                    ),
                                            ),
                                        purchase.currency || baseCurrency,
                                    )
                                }}
                            </td>
                            <td class="px-4 py-3">
                                <StatusBadge :status="purchase.status" />
                            </td>
                            <td class="px-4 py-3 text-right">
                                <Can permission="purchases.edit">
                                    <Form
                                        v-if="(purchase.remaining_amount ?? Math.max(0, Number(purchase.total_amount) - Number(purchase.paid_amount))) > 0"
                                        :action="`/projects/${item.id}/purchases/${purchase.id}/pay-remaining`"
                                        method="post"
                                        class="flex flex-wrap items-center justify-end gap-2"
                                        v-slot="{ errors, processing }"
                                    >
                                        <input type="hidden" name="amount" :value="purchase.remaining_amount ?? Math.max(0, Number(purchase.total_amount) - Number(purchase.paid_amount))" />
                                        <select name="payment_method" class="h-8 rounded-md border border-input bg-transparent px-2 text-xs">
                                            <option value="cash">{{ t('Cash') }}</option>
                                            <option value="bank">{{ t('Bank') }}</option>
                                            <option value="transfer">{{ t('Transfer') }}</option>
                                        </select>
                                        <Input name="reference_no" type="text" class="h-8 w-[100px] text-xs" :placeholder="t('Ref')" />
                                        <Input name="paid_at" type="date" class="h-8 w-[130px] text-xs" :default-value="new Date().toISOString().slice(0, 10)" />
                                        <Button type="submit" size="sm" :disabled="processing">{{ t('Pay') }}</Button>
                                        <InputError :message="errors.amount" />
                                    </Form>
                                    <span v-else class="text-xs text-muted-foreground">—</span>
                                </Can>
                            </td>
                        </tr>
                        <tr v-if="!(purchases || []).length">
                            <td
                                colspan="7"
                                class="px-4 py-10 text-center text-muted-foreground"
                            >
                                {{ t('No purchases yet') }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div v-show="tab === 'stock'" class="space-y-4">
            <Form
                action="/inventory"
                method="post"
                class="grid gap-4 rounded-xl border bg-card p-5 sm:grid-cols-4"
                v-slot="{ errors, processing }"
            >
                <input type="hidden" name="project_id" :value="item.id" />
                <input
                    type="hidden"
                    name="redirect_to"
                    :value="`/projects/${item.id}`"
                />
                <div class="grid gap-2 sm:col-span-2">
                    <Label>{{ t('Material') }}</Label>
                    <select
                        name="material_id"
                        required
                        class="h-9 rounded-md border px-3 text-sm"
                    >
                        <option value="">{{ t('Select material') }}</option>
                        <option
                            v-for="row in (stock || []).filter(
                                (s) => s.on_hand > 0,
                            )"
                            :key="row.material_id"
                            :value="row.material_id"
                        >
                            {{
                                stock_materials?.find(
                                    (m) => m.id === row.material_id,
                                )?.name || row.material_id
                            }}
                            ({{ t('on hand') }} {{ row.on_hand }})
                        </option>
                    </select>
                    <InputError :message="errors.material_id" />
                </div>
                <div class="grid gap-2">
                    <Label>{{ t('Qty to issue') }}</Label>
                    <Input
                        name="quantity"
                        type="number"
                        step="0.001"
                        min="0.001"
                        required
                    />
                    <InputError :message="errors.quantity" />
                </div>
                <div class="grid gap-2">
                    <Label>{{ t('Date') }}</Label>
                    <Input
                        name="transaction_date"
                        type="date"
                        :default-value="new Date().toISOString().slice(0, 10)"
                    />
                </div>
                <div class="sm:col-span-4">
                    <Can permission="inventory.create">
                        <Button type="submit" :disabled="processing">{{
                            processing ? t('Saving…') : t('Issue from stock')
                        }}</Button>
                    </Can>
                </div>
            </Form>

            <div class="grid gap-4 lg:grid-cols-2">
                <div
                    class="overflow-hidden rounded-xl border bg-card shadow-sm"
                >
                    <div class="border-b px-5 py-4">
                        <h3 class="text-sm font-semibold">
                            {{ t('Stock on hand') }}
                        </h3>
                    </div>
                    <table class="min-w-full text-sm">
                        <thead
                            class="bg-muted/40 text-left text-muted-foreground"
                        >
                            <tr>
                                <th class="px-4 py-3 font-medium">
                                    {{ t('Material') }}
                                </th>
                                <th class="px-4 py-3 font-medium">
                                    {{ t('Unit') }}
                                </th>
                                <th class="px-4 py-3 font-medium">
                                    {{ t('On hand') }}
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="row in stock || []"
                                :key="`onhand-${row.material_id}`"
                                class="border-t"
                            >
                                <td class="px-4 py-3">
                                    {{
                                        stock_materials?.find(
                                            (m) => m.id === row.material_id,
                                        )?.name || '—'
                                    }}
                                </td>
                                <td class="px-4 py-3">
                                    {{
                                        stock_materials?.find(
                                            (m) => m.id === row.material_id,
                                        )?.unit?.unit_name || '—'
                                    }}
                                </td>
                                <td class="px-4 py-3 font-medium tabular-nums">
                                    {{ row.on_hand }}
                                </td>
                            </tr>
                            <tr v-if="!(stock || []).length">
                                <td
                                    colspan="3"
                                    class="px-4 py-10 text-center text-muted-foreground"
                                >
                                    {{ t('No stock yet') }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div
                    class="overflow-hidden rounded-xl border bg-card shadow-sm"
                >
                    <div class="border-b px-5 py-4">
                        <h3 class="text-sm font-semibold">
                            {{ t('Stock issue dates') }}
                        </h3>
                    </div>
                    <table class="min-w-full text-sm">
                        <thead
                            class="bg-muted/40 text-left text-muted-foreground"
                        >
                            <tr>
                                <th class="px-4 py-3 font-medium">
                                    {{ t('Material') }}
                                </th>
                                <th class="px-4 py-3 font-medium">
                                    {{ t('Issued qty') }}
                                </th>
                                <th class="px-4 py-3 font-medium">
                                    {{ t('Issue date') }}
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="issue in stock_issues || []"
                                :key="`issued-${issue.id}`"
                                class="border-t"
                            >
                                <td class="px-4 py-3">
                                    {{
                                        issue.material?.name ||
                                        stock_materials?.find(
                                            (m) => m.id === issue.material_id,
                                        )?.name ||
                                        '—'
                                    }}
                                </td>
                                <td class="px-4 py-3 tabular-nums">
                                    {{ issue.quantity }}
                                </td>
                                <td class="px-4 py-3">
                                    {{
                                        issue.transaction_date
                                            ? formatDate(issue.transaction_date)
                                            : '—'
                                    }}
                                </td>
                            </tr>
                            <tr v-if="!(stock_issues || []).length">
                                <td
                                    colspan="3"
                                    class="px-4 py-10 text-center text-muted-foreground"
                                >
                                    {{ t('No issue dates yet') }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</template>
