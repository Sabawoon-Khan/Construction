<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Printer } from '@lucide/vue';
import Can from '@/components/Can.vue';
import { Button } from '@/components/ui/button';
import { useCalendar } from '@/composables/useCalendar';
import { useCurrency } from '@/composables/useCurrency';
import { useTranslations } from '@/composables/useTranslations';
import { formatNumber, money } from '@/lib/utils';

defineProps<{
    item: Record<string, any>;
    shareholders: {
        id: number;
        name: string;
        ownership_percentage: number;
        contributed_total: number;
        entitled_share: number;
        paid_total: number;
        balance_owed: number;
    }[];
    contributions: {
        id: number;
        partner_id: number;
        amount: number;
        currency?: string;
        invested_at: string;
        partner?: { id: number; name: string };
    }[];
}>();

const emit = defineEmits<{
    'add-shareholder': [];
    'record-contribution': [];
    'record-payout': [];
}>();

const { t } = useTranslations();
const { baseCurrency } = useCurrency();
const calendar = useCalendar();
const formatDate = (value: string | null | undefined) =>
    calendar.format(value) || '—';
</script>

<template>
    <div class="space-y-4">
        <div class="overflow-hidden rounded-xl border bg-card shadow-sm">
            <div
                class="flex flex-wrap items-center justify-between gap-3 border-b px-5 py-4"
            >
                <div>
                    <h3 class="text-sm font-semibold">
                        {{ t('Shareholders') }}
                    </h3>
                    <p class="mt-0.5 text-sm text-muted-foreground">
                        {{ t('Ownership, contributions and payouts') }}
                    </p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <Can permission="partners.create">
                        <Button
                            v-if="shareholders.length"
                            size="sm"
                            variant="outline"
                            @click="emit('record-contribution')"
                        >
                            {{ t('Record money in') }}
                        </Button>
                        <Button
                            v-if="shareholders.length"
                            size="sm"
                            variant="outline"
                            @click="emit('record-payout')"
                        >
                            {{ t('Record payout') }}
                        </Button>
                        <Button
                            size="sm"
                            @click="emit('add-shareholder')"
                        >
                            {{ t('Add shareholder') }}
                        </Button>
                    </Can>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-muted/40 text-start text-muted-foreground">
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
                            <td class="px-4 py-3 font-medium">
                                {{ s.name }}
                            </td>
                            <td class="px-4 py-3 tabular-nums">
                                {{ formatNumber(s.ownership_percentage) }}%
                            </td>
                            <td class="px-4 py-3 tabular-nums">
                                {{
                                    money(
                                        s.contributed_total,
                                        baseCurrency,
                                    )
                                }}
                            </td>
                            <td class="px-4 py-3 tabular-nums">
                                {{
                                    money(s.entitled_share, baseCurrency)
                                }}
                            </td>
                            <td
                                class="px-4 py-3 text-emerald-700 tabular-nums"
                            >
                                {{ money(s.paid_total, baseCurrency) }}
                            </td>
                            <td
                                class="px-4 py-3 text-amber-700 tabular-nums"
                            >
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
        </div>

        <div
            v-if="contributions.length"
            class="overflow-hidden rounded-xl border bg-card shadow-sm"
        >
            <div class="border-b px-5 py-4">
                <h3 class="text-sm font-semibold">
                    {{ t('Contribution history') }}
                </h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead
                        class="bg-muted/40 text-start text-muted-foreground"
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
                            <th class="px-4 py-3 text-end font-medium">
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
</template>
