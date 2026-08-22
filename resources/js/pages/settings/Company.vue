<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import CurrencySelect from '@/components/CurrencySelect.vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import SortableTh from '@/components/SortableTh.vue';
import TableFrame from '@/components/TableFrame.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useCalendar } from '@/composables/useCalendar';
import { provideTableSort } from '@/composables/useTableSort';
import { useTranslations } from '@/composables/useTranslations';

const props = defineProps<{
    company: {
        id: number;
        name: string;
        name_en?: string | null;
        legal_name?: string | null;
        registration_no?: string | null;
        phone?: string | null;
        email?: string | null;
        address?: string | null;
        base_currency: string;
    };
    exchange_rates: {
        id: number;
        from_currency: string;
        to_currency: string;
        rate: string | number;
        effective_date: string;
    }[];
    currency_pairs: { from: string; to: string }[];
}>();

const { t } = useTranslations();
const tableColumns = computed(() => [
    { key: 'from_currency', label: t('From') },
    { key: 'to_currency', label: t('To') },
    { key: 'rate', label: t('Rate') },
    { key: 'effective_date', label: t('Effective date') },
    { key: 'actions', label: t('Actions'), locked: true },
]);
const { sortedRows } = provideTableSort(() => props.exchange_rates);
const calendar = useCalendar();

const baseCurrency = ref(props.company.base_currency);
const newRate = ref({
    from_currency: props.currency_pairs[0]?.from ?? props.company.base_currency,
    to_currency: props.currency_pairs[0]?.to ?? 'AFN',
    rate: '',
    effective_date: new Date().toISOString().slice(0, 10),
});

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Settings', href: '/settings/profile' },
            { title: 'Company', href: '/settings/company' },
        ],
    },
});
</script>

<template>
    <Head :title="t('Company settings')" />

    <h1 class="sr-only">{{ t('Company settings') }}</h1>

    <div class="flex flex-col space-y-6">
        <Heading variant="small" :title="t('Company')" />

        <Form
            action="/settings/company"
            method="put"
            class="space-y-6"
            v-slot="{ errors, processing }"
        >
            <div class="grid gap-2">
                <Label for="name">{{ t('Company name') }}</Label>
                <Input id="name" name="name" required :default-value="company.name" />
                <InputError :message="errors.name" />
            </div>

            <div class="grid gap-2">
                <Label for="name_en">{{ t('Company name (English)') }}</Label>
                <Input
                    id="name_en"
                    name="name_en"
                    :default-value="company.name_en ?? ''"
                />
                <InputError :message="errors.name_en" />
            </div>

            <div class="grid gap-2">
                <Label for="legal_name">{{ t('Legal name') }}</Label>
                <Input
                    id="legal_name"
                    name="legal_name"
                    :default-value="company.legal_name ?? ''"
                />
                <InputError :message="errors.legal_name" />
            </div>

            <div class="grid gap-2">
                <Label for="registration_no">{{ t('Registration no') }}</Label>
                <Input
                    id="registration_no"
                    name="registration_no"
                    :default-value="company.registration_no ?? ''"
                />
                <InputError :message="errors.registration_no" />
            </div>

            <div class="grid gap-2">
                <Label for="phone">{{ t('Phone') }}</Label>
                <Input id="phone" name="phone" :default-value="company.phone ?? ''" />
                <InputError :message="errors.phone" />
            </div>

            <div class="grid gap-2">
                <Label for="email">{{ t('Email') }}</Label>
                <Input
                    id="email"
                    type="email"
                    name="email"
                    :default-value="company.email ?? ''"
                />
                <InputError :message="errors.email" />
            </div>

            <div class="grid gap-2">
                <Label for="base_currency">{{ t('Base currency') }}</Label>
                <CurrencySelect id="base_currency" v-model="baseCurrency" />
                <input type="hidden" name="base_currency" :value="baseCurrency" />
                <InputError :message="errors.base_currency" />
            </div>

            <div class="grid gap-2">
                <Label for="address">{{ t('Address') }}</Label>
                <textarea
                    id="address"
                    name="address"
                    rows="3"
                    class="flex min-h-[80px] w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs"
                    :value="company.address ?? ''"
                />
                <InputError :message="errors.address" />
            </div>

            <Button type="submit" :disabled="processing">
                {{ processing ? t('Saving...') : t('Save company') }}
            </Button>
        </Form>

        <div class="space-y-4 rounded-xl border p-6">
            <Heading variant="small" :title="t('Exchange rates')" />

            <Form
                action="/settings/company/exchange-rates"
                method="post"
                class="grid gap-4 sm:grid-cols-4"
                v-slot="{ errors, processing }"
            >
                <input type="hidden" name="from_currency" :value="newRate.from_currency" />
                <div class="grid gap-2">
                    <Label for="rate_to_currency">{{ t('To currency') }}</Label>
                    <select
                        id="rate_to_currency"
                        v-model="newRate.to_currency"
                        name="to_currency"
                        class="h-9 rounded-md border border-input bg-transparent px-3 text-sm"
                    >
                        <option
                            v-for="pair in currency_pairs"
                            :key="`${pair.from}-${pair.to}`"
                            :value="pair.to"
                        >
                            {{ pair.to }}
                        </option>
                    </select>
                    <InputError :message="errors.to_currency" />
                </div>
                <div class="grid gap-2">
                    <Label for="rate">{{ t('Rate') }}</Label>
                    <Input
                        id="rate"
                        v-model="newRate.rate"
                        name="rate"
                        type="number"
                        step="0.000001"
                        min="0"
                        required
                        placeholder="1.000000"
                    />
                    <InputError :message="errors.rate" />
                </div>
                <div class="grid gap-2">
                    <Label for="effective_date">{{ t('Effective date') }}</Label>
                    <Input
                        id="effective_date"
                        v-model="newRate.effective_date"
                        name="effective_date"
                        type="date"
                        required
                    />
                    <InputError :message="errors.effective_date" />
                </div>
                <div class="flex items-end">
                    <Button type="submit" :disabled="processing" class="w-full">
                        {{ processing ? t('Saving...') : t('Add rate') }}
                    </Button>
                </div>
            </Form>

            <TableFrame table-id="company-exchange-rates" :columns="tableColumns">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b text-start text-muted-foreground">
                            <SortableTh class="py-2 pr-4" column="from_currency">{{ t('From') }}</SortableTh>
                            <SortableTh class="py-2 pr-4" column="to_currency">{{ t('To') }}</SortableTh>
                            <SortableTh class="py-2 pr-4" column="rate">{{ t('Rate') }}</SortableTh>
                            <SortableTh class="py-2 pr-4" column="effective_date">{{ t('Effective date') }}</SortableTh>
                            <th class="py-2 text-end">{{ t('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <tr v-for="rate in sortedRows" :key="rate.id">
                            <td class="py-3 pr-4">{{ rate.from_currency }}</td>
                            <td class="py-3 pr-4">{{ rate.to_currency }}</td>
                            <td class="py-3 pr-4 tabular-nums">{{ Number(rate.rate).toFixed(6) }}</td>
                            <td class="py-3 pr-4 whitespace-nowrap">{{ calendar.format(rate.effective_date) || '—' }}</td>
                            <td class="py-3 text-end">
                                <Form
                                    :action="`/settings/company/exchange-rates/${rate.id}`"
                                    method="post"
                                    v-slot="{ processing }"
                                >
                                    <input type="hidden" name="_method" value="delete" />
                                    <Button type="submit" :disabled="processing" variant="ghost" size="sm">
                                        {{ t('Delete') }}
                                    </Button>
                                </Form>
                            </td>
                        </tr>
                        <tr v-if="!exchange_rates.length">
                            <td colspan="5" class="py-6 text-center text-muted-foreground">
                                {{ t('No exchange rates yet') }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </TableFrame>
        </div>
    </div>
</template>
