<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, Pencil } from '@lucide/vue';
import Can from '@/components/Can.vue';
import DetailGrid from '@/components/DetailGrid.vue';
import Heading from '@/components/Heading.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import { useTranslations } from '@/composables/useTranslations';
import { money } from '@/lib/utils';

defineProps<{
    item: Record<string, any>;
    stats: { payments_total: number; incomes_total: number; expenses_total: number };
}>();
const { t } = useTranslations();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Bank Accounts', href: '/bank-accounts' },
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
                    <Link href="/bank-accounts"><ArrowLeft class="size-4" /> {{ t('Back') }}</Link>
                </Button>
                <Heading :title="item.name" :description="item.bank_name || t('Bank account')" />
            </div>
            <div class="flex items-center gap-2">
                <StatusBadge :status="item.is_active ? 'active' : 'inactive'" />
                <Can permission="bank_accounts.edit">
                    <Button as-child>
                        <Link :href="`/bank-accounts/${item.id}/edit`"><Pencil class="size-4" /> {{ t('Edit') }}</Link>
                    </Button>
                </Can>
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="rounded-xl border bg-card p-4 shadow-sm">
                <div class="text-sm text-muted-foreground">{{ t('Opening balance') }}</div>
                <div class="mt-1 text-2xl font-semibold tabular-nums">{{ money(item.opening_balance, item.currency || 'USD') }}</div>
            </div>
            <div class="rounded-xl border bg-card p-4 shadow-sm">
                <div class="text-sm text-muted-foreground">{{ t('Payments') }}</div>
                <div class="mt-1 text-2xl font-semibold tabular-nums">{{ money(stats.payments_total) }}</div>
            </div>
            <div class="rounded-xl border bg-card p-4 shadow-sm">
                <div class="text-sm text-muted-foreground">{{ t('Incomes') }}</div>
                <div class="mt-1 text-2xl font-semibold tabular-nums text-emerald-700">{{ money(stats.incomes_total) }}</div>
            </div>
            <div class="rounded-xl border bg-card p-4 shadow-sm">
                <div class="text-sm text-muted-foreground">{{ t('Expenses') }}</div>
                <div class="mt-1 text-2xl font-semibold tabular-nums text-rose-700">{{ money(stats.expenses_total) }}</div>
            </div>
        </div>

        <DetailGrid
            :title="t('Account details')"
            :items="[
                { label: t('Bank name'), value: item.bank_name },
                { label: t('Account number'), value: item.account_number },
                { label: t('IBAN'), value: item.iban },
                { label: t('Currency'), value: item.currency },
                { label: t('Cash account'), value: item.is_cash ? t('Yes') : t('No') },
                { label: t('Status'), value: item.is_active ? t('Active') : t('Inactive') },
            ]"
        />
    </div>
</template>
