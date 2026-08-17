<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { AlertCircle, FileSpreadsheet, Scale } from '@lucide/vue';
import { computed, ref } from 'vue';
import Heading from '@/components/Heading.vue';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import CurrencySelect from '@/components/CurrencySelect.vue';
import { useCurrency } from '@/composables/useCurrency';
import { useTranslations } from '@/composables/useTranslations';

const props = defineProps<{
    filters: { as_of: string; project_id?: number | null; currency?: string };
    restricted?: boolean;
    projects: { id: number; name: string }[];
    report_currency?: string;
    balance_sheet: {
        as_of: string;
        project_id: number | null;
        assets: {
            cash_and_bank: {
                accounts: { id: number; name: string; bank_name: string | null; is_cash: boolean; currency: string; opening_balance: number; balance: number }[];
                bank_total: number;
                cash_on_hand: number;
                total: number;
            };
            accounts_receivable: { items: { id: number; name: string; amount: number; details: any[] }[]; total: number };
            total_current: number;
            total: number;
        };
        memo: {
            inventory: { items: { project_id: number; project_name: string | null; material_id: number; material_name: string; on_hand: number; unit_cost: number; value: number }[]; total: number };
        };
        liabilities: {
            accounts_payable: { items: { id: number; name: string; amount: number; details: any[] }[]; total: number };
            distributions_payable: { items: { partner_id: number; partner_name: string; project_id: number; project_name: string | null; amount: number; ownership_percentage: number; entitled_share: number; paid_total: number }[]; total: number };
            total_current: number;
            total: number;
        };
        equity: {
            partner_capital: { items: { partner_id: number; partner_name: string; amount: number; by_project: { project_id: number; project_name: string | null; amount: number }[] }[]; total: number };
            retained_earnings: number;
            distributions_payable: number;
            total: number;
        };
        metrics: { working_capital: number; current_ratio: number; balance_check: number };
    };
}>();

const { t } = useTranslations();
const { baseCurrency } = useCurrency();
const asOf = ref(props.filters.as_of);
const projectId = ref(String(props.filters.project_id ?? ''));
const reportCurrency = ref(props.report_currency ?? props.filters.currency ?? baseCurrency.value);

const money = (v: unknown) =>
    new Intl.NumberFormat('en-US', { style: 'currency', currency: reportCurrency.value }).format(Number(v ?? 0));

const isBalanced = computed(() => Math.abs(props.balance_sheet.metrics.balance_check) < 0.01);

function apply() {
    router.get(
        '/balance-sheet',
        {
            as_of: asOf.value || undefined,
            project_id: projectId.value || undefined,
            currency: reportCurrency.value || undefined,
        },
        { preserveState: true, replace: true },
    );
}

function exportUrl(format: string) {
    const params = new URLSearchParams({
        format,
        as_of: asOf.value,
    });

    if (projectId.value) {
        params.set('project_id', projectId.value);
    }

    if (reportCurrency.value) {
        params.set('currency', reportCurrency.value);
    }

    return `/balance-sheet/export?${params.toString()}`;
}
</script>

<template>
    <Head :title="t('Balance Sheet')" />

    <div class="print-document flex h-full flex-1 flex-col gap-6 p-4 md:p-6 print:gap-4 print:p-0">
        <div class="space-y-4 print:hidden">
            <Heading
                title="Balance Sheet"
                description="A snapshot of assets, liabilities, and equity as of a chosen date."
            />

            <div class="flex flex-wrap items-center gap-2 rounded-xl border bg-card p-4">
                <div class="grid gap-1">
                    <label class="text-xs text-muted-foreground">{{ t('As of') }}</label>
                    <Input v-model="asOf" type="date" class="w-auto" />
                </div>

                <select
                    v-model="projectId"
                    class="h-9 min-w-44 rounded-md border border-input bg-transparent px-3 text-sm"
                >
                    <option value="">{{ t('All projects') }}</option>
                    <option v-for="p in projects" :key="p.id" :value="String(p.id)">
                        {{ p.name }}
                    </option>
                </select>

                <div class="grid gap-1">
                    <label class="text-xs text-muted-foreground">{{ t('Currency') }}</label>
                    <CurrencySelect v-model="reportCurrency" />
                </div>

                <Button variant="secondary" class="self-end" @click="apply">{{ t('Filter') }}</Button>

                <div class="ms-auto flex flex-wrap gap-2">
                    <Button as-child variant="outline">
                        <a :href="exportUrl('xlsx')">
                            <FileSpreadsheet class="me-1 size-4" />
                            {{ t('Excel') }}
                        </a>
                    </Button>
                    <Button as-child>
                        <a :href="exportUrl('print').replace('/balance-sheet/export', '/balance-sheet/print')" target="_blank">
                            {{ t('Print / PDF') }}
                        </a>
                    </Button>
                </div>
            </div>
        </div>

        <Alert
            v-if="!isBalanced"
            variant="destructive"
            class="print:hidden"
        >
            <AlertCircle class="size-4" />
            <AlertTitle>{{ t('Balance sheet is not balanced') }}</AlertTitle>
            <AlertDescription>
                {{ t('Difference: :amount. Check that every cash movement is recorded.', { amount: money(balance_sheet.metrics.balance_check) }) }}
            </AlertDescription>
        </Alert>

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <Card>
                <CardHeader class="pb-2">
                    <CardTitle class="text-sm font-normal text-muted-foreground">{{ t('Total Assets') }}</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-semibold tabular-nums">{{ money(balance_sheet.assets.total) }}</div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader class="pb-2">
                    <CardTitle class="text-sm font-normal text-muted-foreground">{{ t('Total Liabilities') }}</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-semibold tabular-nums">{{ money(balance_sheet.liabilities.total) }}</div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader class="pb-2">
                    <CardTitle class="text-sm font-normal text-muted-foreground">{{ t('Total Equity') }}</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-semibold tabular-nums">{{ money(balance_sheet.equity.total) }}</div>
                </CardContent>
            </Card>

            <Card :class="isBalanced ? 'border-emerald-200 bg-emerald-50 dark:border-emerald-900 dark:bg-emerald-950/40' : 'border-amber-200 bg-amber-50 dark:border-amber-900 dark:bg-amber-950/40'">
                <CardHeader class="pb-2">
                    <CardTitle class="text-sm font-normal" :class="isBalanced ? 'text-emerald-800 dark:text-emerald-300' : 'text-amber-800 dark:text-amber-300'">
                        {{ t('Balance Check') }}
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="flex items-center gap-2">
                        <Scale class="size-5" :class="isBalanced ? 'text-emerald-600' : 'text-amber-600'" />
                        <div class="text-2xl font-semibold tabular-nums" :class="isBalanced ? 'text-emerald-900 dark:text-emerald-100' : 'text-amber-900 dark:text-amber-100'">
                            {{ money(balance_sheet.metrics.balance_check) }}
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>

        <div class="grid gap-4 xl:grid-cols-3">
            <div class="space-y-4 xl:col-span-2">
                <Card>
                    <CardHeader>
                        <CardTitle class="text-base">{{ t('Assets') }}</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-6">
                        <div>
                            <div class="flex items-center justify-between border-b pb-2">
                                <span class="font-medium">{{ t('Cash & Bank') }}</span>
                                <span class="font-semibold tabular-nums">{{ money(balance_sheet.assets.cash_and_bank.total) }}</span>
                            </div>
                            <div class="mt-2 space-y-1">
                                <div
                                    v-for="account in balance_sheet.assets.cash_and_bank.accounts"
                                    :key="account.id"
                                    class="flex items-center justify-between py-1 text-sm"
                                >
                                    <span class="text-muted-foreground">
                                        {{ account.name }}
                                        <span v-if="account.bank_name" class="text-xs">({{ account.bank_name }})</span>
                                        <Badge v-if="account.is_cash" variant="outline" class="ms-1 text-xs">{{ t('Cash') }}</Badge>
                                    </span>
                                    <span class="tabular-nums">{{ money(account.balance) }}</span>
                                </div>
                                <div class="flex items-center justify-between py-1 text-sm">
                                    <span class="text-muted-foreground">{{ t('Cash on hand') }}</span>
                                    <span class="tabular-nums">{{ money(balance_sheet.assets.cash_and_bank.cash_on_hand) }}</span>
                                </div>
                            </div>
                        </div>

                        <div>
                            <div class="flex items-center justify-between border-b pb-2">
                                <span class="font-medium">{{ t('Accounts Receivable') }}</span>
                                <span class="font-semibold tabular-nums">{{ money(balance_sheet.assets.accounts_receivable.total) }}</span>
                            </div>
                            <div class="mt-2 space-y-1">
                                <div
                                    v-for="item in balance_sheet.assets.accounts_receivable.items"
                                    :key="item.id"
                                    class="flex items-center justify-between py-1 text-sm"
                                >
                                    <span class="text-muted-foreground">{{ item.name }}</span>
                                    <span class="tabular-nums">{{ money(item.amount) }}</span>
                                </div>
                                <div v-if="!balance_sheet.assets.accounts_receivable.items.length" class="py-2 text-sm text-muted-foreground">
                                    {{ t('No outstanding receivables.') }}
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center justify-between border-t pt-4">
                            <span class="font-semibold">{{ t('Total Current Assets') }}</span>
                            <span class="text-lg font-semibold tabular-nums">{{ money(balance_sheet.assets.total_current) }}</span>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle class="text-base">{{ t('Liabilities') }}</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-6">
                        <div>
                            <div class="flex items-center justify-between border-b pb-2">
                                <span class="font-medium">{{ t('Accounts Payable') }}</span>
                                <span class="font-semibold tabular-nums">{{ money(balance_sheet.liabilities.accounts_payable.total) }}</span>
                            </div>
                            <div class="mt-2 space-y-1">
                                <div
                                    v-for="item in balance_sheet.liabilities.accounts_payable.items"
                                    :key="item.id"
                                    class="flex items-center justify-between py-1 text-sm"
                                >
                                    <span class="text-muted-foreground">{{ item.name }}</span>
                                    <span class="tabular-nums">{{ money(item.amount) }}</span>
                                </div>
                                <div v-if="!balance_sheet.liabilities.accounts_payable.items.length" class="py-2 text-sm text-muted-foreground">
                                    {{ t('No outstanding payables.') }}
                                </div>
                            </div>
                        </div>

                        <div>
                            <div class="flex items-center justify-between border-b pb-2">
                                <span class="font-medium">{{ t('Distributions Payable') }}</span>
                                <span class="font-semibold tabular-nums">{{ money(balance_sheet.liabilities.distributions_payable.total) }}</span>
                            </div>
                            <div class="mt-2 space-y-1">
                                <div
                                    v-for="item in balance_sheet.liabilities.distributions_payable.items"
                                    :key="`${item.partner_id}-${item.project_id}`"
                                    class="flex items-center justify-between py-1 text-sm"
                                >
                                    <span class="text-muted-foreground">
                                        {{ item.partner_name }}
                                        <span v-if="item.project_name" class="text-xs">· {{ item.project_name }}</span>
                                    </span>
                                    <span class="tabular-nums">{{ money(item.amount) }}</span>
                                </div>
                                <div v-if="!balance_sheet.liabilities.distributions_payable.items.length" class="py-2 text-sm text-muted-foreground">
                                    {{ t('No distributions owed to partners.') }}
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center justify-between border-t pt-4">
                            <span class="font-semibold">{{ t('Total Current Liabilities') }}</span>
                            <span class="text-lg font-semibold tabular-nums">{{ money(balance_sheet.liabilities.total_current) }}</span>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle class="text-base">{{ t('Equity') }}</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-6">
                        <div>
                            <div class="flex items-center justify-between border-b pb-2">
                                <span class="font-medium">{{ t('Partner Capital') }}</span>
                                <span class="font-semibold tabular-nums">{{ money(balance_sheet.equity.partner_capital.total) }}</span>
                            </div>
                            <div class="mt-2 space-y-1">
                                <div
                                    v-for="item in balance_sheet.equity.partner_capital.items"
                                    :key="item.partner_id"
                                    class="py-1 text-sm"
                                >
                                    <div class="flex items-center justify-between">
                                        <span class="text-muted-foreground">{{ item.partner_name }}</span>
                                        <span class="tabular-nums">{{ money(item.amount) }}</span>
                                    </div>
                                    <div
                                        v-for="project in item.by_project"
                                        :key="project.project_id"
                                        class="flex items-center justify-between ps-4 text-xs text-muted-foreground"
                                    >
                                        <span>{{ project.project_name ?? t('General') }}</span>
                                        <span class="tabular-nums">{{ money(project.amount) }}</span>
                                    </div>
                                </div>
                                <div v-if="!balance_sheet.equity.partner_capital.items.length" class="py-2 text-sm text-muted-foreground">
                                    {{ t('No partner investments recorded.') }}
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center justify-between border-b pb-2">
                            <span class="font-medium">{{ t('Retained Earnings') }}</span>
                            <span class="font-semibold tabular-nums">{{ money(balance_sheet.equity.retained_earnings) }}</span>
                        </div>

                        <div class="flex items-center justify-between border-t pt-4">
                            <span class="font-semibold">{{ t('Total Equity') }}</span>
                            <span class="text-lg font-semibold tabular-nums">{{ money(balance_sheet.equity.total) }}</span>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <div class="space-y-4">
                <Card>
                    <CardHeader>
                        <CardTitle class="text-base">{{ t('Key Metrics') }}</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div>
                            <div class="text-sm text-muted-foreground">{{ t('Working Capital') }}</div>
                            <div class="text-xl font-semibold tabular-nums">{{ money(balance_sheet.metrics.working_capital) }}</div>
                        </div>
                        <div>
                            <div class="text-sm text-muted-foreground">{{ t('Current Ratio') }}</div>
                            <div class="text-xl font-semibold tabular-nums">{{ balance_sheet.metrics.current_ratio.toFixed(2) }}</div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle class="text-base">{{ t('Memo: Inventory') }}</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="flex items-center justify-between">
                            <span class="font-medium">{{ t('Stock Value') }}</span>
                            <span class="font-semibold tabular-nums">{{ money(balance_sheet.memo.inventory.total) }}</span>
                        </div>
                        <div class="space-y-1">
                            <div
                                v-for="item in balance_sheet.memo.inventory.items"
                                :key="`${item.project_id}-${item.material_id}`"
                                class="flex items-center justify-between py-1 text-sm"
                            >
                                <span class="text-muted-foreground">
                                    {{ item.material_name }}
                                    <span class="text-xs">({{ item.on_hand }})</span>
                                </span>
                                <span class="tabular-nums">{{ money(item.value) }}</span>
                            </div>
                            <div v-if="!balance_sheet.memo.inventory.items.length" class="py-2 text-sm text-muted-foreground">
                                {{ t('No stock on hand.') }}
                            </div>
                        </div>
                        <p class="text-xs text-muted-foreground">
                            {{ t('Inventory is shown as a memo because materials are expensed at purchase in this MIS.') }}
                        </p>
                    </CardContent>
                </Card>
            </div>
        </div>
    </div>
</template>
