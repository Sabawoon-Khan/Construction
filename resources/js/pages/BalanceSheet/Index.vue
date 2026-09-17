<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { AlertCircle, Scale } from '@lucide/vue';
import { computed, watch } from 'vue';
import {
    MisAlert,
    MisTableActions,
    MisFilterBar,
    MisLoadingState,
    MisMoneyStack,
    MisPageHeader,
    MisSection,
    MisStatCard,
    MisStatGrid,
} from '@/components/mis';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { useMisFilters } from '@/composables/useMisFilters';
import { useTranslations } from '@/composables/useTranslations';
import { moneyByCurrency } from '@/lib/utils';
type CurrencyMap = Record<string, number>;

const props = defineProps<{
    filters: { as_of: string; project_id?: number | null };
    restricted?: boolean;
    projects: { id: number; name: string }[];
    balance_sheet: {
        as_of: string;
        project_id: number | null;
        assets: {
            cash_and_bank: {
                accounts: {
                    id: number;
                    name: string;
                    bank_name: string | null;
                    is_cash: boolean;
                    currency: string;
                    opening_balance: number;
                    balance: number;
                    balance_by_currency?: CurrencyMap;
                }[];
                bank_total: number;
                cash_on_hand: number;
                cash_on_hand_by_currency?: CurrencyMap;
                total: number;
                total_by_currency?: CurrencyMap;
            };
            accounts_receivable: {
                items: { id: number; name: string; amount: number; amount_by_currency?: CurrencyMap; details: any[] }[];
                total: number;
                total_by_currency?: CurrencyMap;
            };
            total_current: number;
            total_current_by_currency?: CurrencyMap;
            total: number;
            total_by_currency?: CurrencyMap;
        };
        memo: {
            inventory: {
                items: {
                    project_id: number;
                    project_name: string | null;
                    material_id: number;
                    material_name: string;
                    on_hand: number;
                    unit_cost: number;
                    value: number;
                    value_by_currency?: CurrencyMap;
                }[];
                total: number;
                total_by_currency?: CurrencyMap;
            };
        };
        liabilities: {
            accounts_payable: {
                items: { id: number; name: string; amount: number; amount_by_currency?: CurrencyMap; details: any[] }[];
                total: number;
                total_by_currency?: CurrencyMap;
            };
            distributions_payable: {
                items: {
                    partner_id: number;
                    partner_name: string;
                    project_id: number;
                    project_name: string | null;
                    amount: number;
                    amount_by_currency?: CurrencyMap;
                    ownership_percentage: number;
                    entitled_share: number;
                    paid_total: number;
                }[];
                total: number;
                total_by_currency?: CurrencyMap;
            };
            total_current: number;
            total_current_by_currency?: CurrencyMap;
            total: number;
            total_by_currency?: CurrencyMap;
        };
        equity: {
            partner_capital: {
                items: {
                    partner_id: number;
                    partner_name: string;
                    amount: number;
                    amount_by_currency?: CurrencyMap;
                    by_project: {
                        project_id: number;
                        project_name: string | null;
                        amount: number;
                        amount_by_currency?: CurrencyMap;
                    }[];
                }[];
                total: number;
                total_by_currency?: CurrencyMap;
            };
            retained_earnings: number;
            retained_earnings_by_currency?: CurrencyMap;
            distributions_payable: number;
            total: number;
            total_by_currency?: CurrencyMap;
        };
        metrics: {
            working_capital: number;
            working_capital_by_currency?: CurrencyMap;
            current_ratio: number;
            balance_check: number;
            balance_check_by_currency?: CurrencyMap;
        };
    };
}>();

const { t } = useTranslations();

const { filters, pending, apply } = useMisFilters(
    '/balance-sheet',
    {
        as_of: props.filters.as_of ?? '',
        project_id: props.filters.project_id ? String(props.filters.project_id) : '',
    },
    { as_of: '', project_id: '' },
);

watch(
    () => props.filters,
    () => {
        filters.as_of = props.filters.as_of ?? '';
        filters.project_id = props.filters.project_id ? String(props.filters.project_id) : '';
    },
);

const isBalanced = computed(() => {
    const byCurrency = props.balance_sheet.metrics.balance_check_by_currency;
    if (byCurrency) {
        return Object.values(byCurrency).every((v) => Math.abs(Number(v ?? 0)) < 0.01);
    }

    return Math.abs(props.balance_sheet.metrics.balance_check) < 0.01;
});

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Balance Sheet', href: '/balance-sheet' },
        ],
    },
});

</script>

<template>
    <Head :title="t('Balance Sheet')" />

    <div class="print-document mx-auto flex h-full w-full max-w-[1600px] flex-1 flex-col gap-6 p-4 md:p-6 print:gap-4 print:p-0">
        <MisPageHeader :title="t('Balance Sheet')" class="print:hidden">
            <template #actions>
                <MisTableActions export-url="/balance-sheet/export" :filters="filters" />
            </template>
        </MisPageHeader>

        <MisFilterBar class="print:hidden" @apply="apply()">
            <div class="grid min-w-[170px] flex-1 gap-1">
                <label class="text-xs text-muted-foreground">{{ t('As of') }}</label>
                <Input v-model="filters.as_of" type="date" class="bg-background/80" />
            </div>

            <div class="grid min-w-[190px] flex-1 gap-1">
                <label class="text-xs text-muted-foreground">{{ t('Project') }}</label>
                <select
                    v-model="filters.project_id"
                    class="h-9 w-full rounded-md border border-input bg-background/80 px-3 text-sm"
                >
                    <option value="">{{ t('All projects') }}</option>
                    <option v-for="p in projects" :key="p.id" :value="String(p.id)">
                        {{ p.name }}
                    </option>
                </select>
            </div>
            <template #actions>
                <Button type="button" size="sm" @click="apply()">{{ t('Apply') }}</Button>
            </template>
        </MisFilterBar>

        <MisLoadingState v-if="pending" :label="t('Loading report…')" />

        <template v-else>
            <MisAlert
                v-if="!isBalanced"
                tone="danger"
                :title="t('Balance sheet is not balanced')"
                :icon="AlertCircle"
                class="print:hidden"
            >
                {{
                    t('Difference: :amount. Check that every cash movement is recorded.', {
                        amount: moneyByCurrency(
                            balance_sheet.metrics.balance_check_by_currency,
                            balance_sheet.metrics.balance_check,
                        ),
                    })
                }}
            </MisAlert>

            <MisStatGrid :columns="4">
                <MisStatCard
                    :label="t('Total Assets')"
                    :amounts="balance_sheet.assets.total_by_currency"
                    :fallback-amount="balance_sheet.assets.total"
                />
                <MisStatCard
                    :label="t('Total Liabilities')"
                    :amounts="balance_sheet.liabilities.total_by_currency"
                    :fallback-amount="balance_sheet.liabilities.total"
                />
                <MisStatCard
                    :label="t('Total Equity')"
                    :amounts="balance_sheet.equity.total_by_currency"
                    :fallback-amount="balance_sheet.equity.total"
                />
                <MisStatCard
                    :label="t('Balance Check')"
                    :amounts="balance_sheet.metrics.balance_check_by_currency"
                    :fallback-amount="balance_sheet.metrics.balance_check"
                    :tone="isBalanced ? 'success' : 'warning'"
                    :icon="Scale"
                />
            </MisStatGrid>

            <div class="grid gap-4 xl:grid-cols-3">
                <div class="space-y-4 xl:col-span-2">
                    <Card>
                        <CardHeader>
                            <CardTitle class="text-base">{{ t('Assets') }}</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-6">
                            <div>
                                <div class="flex items-start justify-between gap-4 border-b pb-2">
                                    <span class="font-medium">{{ t('Cash & Bank') }}</span>
                                    <MisMoneyStack
                                        layout="inline"
                                        class="font-semibold"
                                        :amounts="balance_sheet.assets.cash_and_bank.total_by_currency"
                                        :fallback="balance_sheet.assets.cash_and_bank.total"
                                    />
                                </div>
                                <div class="mt-2 space-y-1">
                                    <div
                                        v-for="account in balance_sheet.assets.cash_and_bank.accounts"
                                        :key="account.id"
                                        class="flex items-start justify-between gap-4 py-1 text-sm"
                                    >
                                        <span class="text-muted-foreground">
                                            {{ account.name }}
                                            <span v-if="account.bank_name" class="text-xs">({{ account.bank_name }})</span>
                                            <Badge v-if="account.is_cash" variant="outline" class="ms-1 text-xs">{{ t('Cash') }}</Badge>
                                        </span>
                                        <MisMoneyStack
                                            layout="inline"
                                            :amounts="account.balance_by_currency"
                                            :fallback="account.balance"
                                        />
                                    </div>
                                    <div class="flex items-start justify-between gap-4 py-1 text-sm">
                                        <span class="text-muted-foreground">{{ t('Cash on hand') }}</span>
                                        <MisMoneyStack
                                            layout="inline"
                                            :amounts="balance_sheet.assets.cash_and_bank.cash_on_hand_by_currency"
                                            :fallback="balance_sheet.assets.cash_and_bank.cash_on_hand"
                                        />
                                    </div>
                                </div>
                            </div>

                            <div>
                                <div class="flex items-start justify-between gap-4 border-b pb-2">
                                    <span class="font-medium">{{ t('Accounts Receivable') }}</span>
                                    <MisMoneyStack
                                        layout="inline"
                                        class="font-semibold"
                                        :amounts="balance_sheet.assets.accounts_receivable.total_by_currency"
                                        :fallback="balance_sheet.assets.accounts_receivable.total"
                                    />
                                </div>
                                <div class="mt-2 space-y-1">
                                    <div
                                        v-for="item in balance_sheet.assets.accounts_receivable.items"
                                        :key="item.id"
                                        class="flex items-start justify-between gap-4 py-1 text-sm"
                                    >
                                        <span class="text-muted-foreground">{{ item.name }}</span>
                                        <MisMoneyStack
                                            layout="inline"
                                            :amounts="item.amount_by_currency"
                                            :fallback="item.amount"
                                        />
                                    </div>
                                    <div v-if="!balance_sheet.assets.accounts_receivable.items.length" class="py-2 text-sm text-muted-foreground">
                                        {{ t('No outstanding receivables.') }}
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-start justify-between gap-4 border-t pt-4">
                                <span class="font-semibold">{{ t('Total Current Assets') }}</span>
                                <MisMoneyStack
                                    layout="inline"
                                    class="text-lg font-semibold"
                                    :amounts="balance_sheet.assets.total_current_by_currency"
                                    :fallback="balance_sheet.assets.total_current"
                                />
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle class="text-base">{{ t('Liabilities') }}</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-6">
                            <div>
                                <div class="flex items-start justify-between gap-4 border-b pb-2">
                                    <span class="font-medium">{{ t('Accounts Payable') }}</span>
                                    <MisMoneyStack
                                        layout="inline"
                                        class="font-semibold"
                                        :amounts="balance_sheet.liabilities.accounts_payable.total_by_currency"
                                        :fallback="balance_sheet.liabilities.accounts_payable.total"
                                    />
                                </div>
                                <div class="mt-2 space-y-1">
                                    <div
                                        v-for="item in balance_sheet.liabilities.accounts_payable.items"
                                        :key="item.id"
                                        class="flex items-start justify-between gap-4 py-1 text-sm"
                                    >
                                        <span class="text-muted-foreground">{{ item.name }}</span>
                                        <MisMoneyStack
                                            layout="inline"
                                            :amounts="item.amount_by_currency"
                                            :fallback="item.amount"
                                        />
                                    </div>
                                    <div v-if="!balance_sheet.liabilities.accounts_payable.items.length" class="py-2 text-sm text-muted-foreground">
                                        {{ t('No outstanding payables.') }}
                                    </div>
                                </div>
                            </div>

                            <div>
                                <div class="flex items-start justify-between gap-4 border-b pb-2">
                                    <span class="font-medium">{{ t('Distributions Payable') }}</span>
                                    <MisMoneyStack
                                        layout="inline"
                                        class="font-semibold"
                                        :amounts="balance_sheet.liabilities.distributions_payable.total_by_currency"
                                        :fallback="balance_sheet.liabilities.distributions_payable.total"
                                    />
                                </div>
                                <div class="mt-2 space-y-1">
                                    <div
                                        v-for="item in balance_sheet.liabilities.distributions_payable.items"
                                        :key="`${item.partner_id}-${item.project_id}`"
                                        class="flex items-start justify-between gap-4 py-1 text-sm"
                                    >
                                        <span class="text-muted-foreground">
                                            {{ item.partner_name }}
                                            <span v-if="item.project_name" class="text-xs">· {{ item.project_name }}</span>
                                        </span>
                                        <MisMoneyStack
                                            layout="inline"
                                            :amounts="item.amount_by_currency"
                                            :fallback="item.amount"
                                        />
                                    </div>
                                    <div v-if="!balance_sheet.liabilities.distributions_payable.items.length" class="py-2 text-sm text-muted-foreground">
                                        {{ t('No distributions owed to partners.') }}
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-start justify-between gap-4 border-t pt-4">
                                <span class="font-semibold">{{ t('Total Current Liabilities') }}</span>
                                <MisMoneyStack
                                    layout="inline"
                                    class="text-lg font-semibold"
                                    :amounts="balance_sheet.liabilities.total_current_by_currency"
                                    :fallback="balance_sheet.liabilities.total_current"
                                />
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle class="text-base">{{ t('Equity') }}</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-6">
                            <div>
                                <div class="flex items-start justify-between gap-4 border-b pb-2">
                                    <span class="font-medium">{{ t('Partner Capital') }}</span>
                                    <MisMoneyStack
                                        layout="inline"
                                        class="font-semibold"
                                        :amounts="balance_sheet.equity.partner_capital.total_by_currency"
                                        :fallback="balance_sheet.equity.partner_capital.total"
                                    />
                                </div>
                                <div class="mt-2 space-y-1">
                                    <div
                                        v-for="item in balance_sheet.equity.partner_capital.items"
                                        :key="item.partner_id"
                                        class="py-1 text-sm"
                                    >
                                        <div class="flex items-start justify-between gap-4">
                                            <span class="text-muted-foreground">{{ item.partner_name }}</span>
                                            <MisMoneyStack
                                                layout="inline"
                                                :amounts="item.amount_by_currency"
                                                :fallback="item.amount"
                                            />
                                        </div>
                                        <div
                                            v-for="project in item.by_project"
                                            :key="project.project_id"
                                            class="flex items-start justify-between gap-4 ps-4 text-xs text-muted-foreground"
                                        >
                                            <span>{{ project.project_name ?? t('General') }}</span>
                                            <MisMoneyStack
                                                layout="inline"
                                                :amounts="project.amount_by_currency"
                                                :fallback="project.amount"
                                            />
                                        </div>
                                    </div>
                                    <div v-if="!balance_sheet.equity.partner_capital.items.length" class="py-2 text-sm text-muted-foreground">
                                        {{ t('No partner investments recorded.') }}
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-start justify-between gap-4 border-b pb-2">
                                <span class="font-medium">{{ t('Retained Earnings') }}</span>
                                <MisMoneyStack
                                    layout="inline"
                                    class="font-semibold"
                                    :amounts="balance_sheet.equity.retained_earnings_by_currency"
                                    :fallback="balance_sheet.equity.retained_earnings"
                                />
                            </div>

                            <div class="flex items-start justify-between gap-4 border-t pt-4">
                                <span class="font-semibold">{{ t('Total Equity') }}</span>
                                <MisMoneyStack
                                    layout="inline"
                                    class="text-lg font-semibold"
                                    :amounts="balance_sheet.equity.total_by_currency"
                                    :fallback="balance_sheet.equity.total"
                                />
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <div class="space-y-4">
                    <MisSection :title="t('Key Metrics')">
                        <MisStatGrid :columns="2">
                            <MisStatCard
                                :label="t('Working Capital')"
                                :amounts="balance_sheet.metrics.working_capital_by_currency"
                                :fallback-amount="balance_sheet.metrics.working_capital"
                            />
                            <MisStatCard
                                :label="t('Current Ratio')"
                                :value="balance_sheet.metrics.current_ratio.toFixed(2)"
                            />
                        </MisStatGrid>
                    </MisSection>

                    <Card>
                        <CardHeader>
                            <CardTitle class="text-base">{{ t('Memo: Inventory') }}</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div class="flex items-start justify-between gap-4">
                                <span class="font-medium">{{ t('Stock Value') }}</span>
                                <MisMoneyStack
                                    layout="inline"
                                    class="font-semibold"
                                    :amounts="balance_sheet.memo.inventory.total_by_currency"
                                    :fallback="balance_sheet.memo.inventory.total"
                                />
                            </div>
                            <div class="space-y-1">
                                <div
                                    v-for="item in balance_sheet.memo.inventory.items"
                                    :key="`${item.project_id}-${item.material_id}`"
                                    class="flex items-start justify-between gap-4 py-1 text-sm"
                                >
                                    <span class="text-muted-foreground">
                                        {{ item.material_name }}
                                        <span class="text-xs">({{ item.on_hand }})</span>
                                    </span>
                                    <MisMoneyStack
                                        layout="inline"
                                        :amounts="item.value_by_currency"
                                        :fallback="item.value"
                                    />
                                </div>
                                <div v-if="!balance_sheet.memo.inventory.items.length" class="py-2 text-sm text-muted-foreground">
                                    {{ t('No stock on hand.') }}
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </template>
    </div>
</template>
