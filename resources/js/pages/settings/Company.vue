<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { ref } from 'vue';
import CurrencySelect from '@/components/CurrencySelect.vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

const props = defineProps<{
    company: {
        id: number;
        name: string;
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
    <Head title="Company settings" />

    <h1 class="sr-only">Company settings</h1>

    <div class="flex flex-col space-y-6">
        <Heading
            variant="small"
            title="Company"
            description="This MIS belongs to one construction company. Projects, sales, expenses and incomes all roll up here."
        />

        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900 dark:border-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-100">
            Profit for shareholders is calculated from this company's sales and incomes minus its expenses.
        </div>

        <Form
            action="/settings/company"
            method="put"
            class="space-y-6"
            v-slot="{ errors, processing }"
        >
            <div class="grid gap-2">
                <Label for="name">Company name</Label>
                <Input id="name" name="name" required :default-value="company.name" />
                <InputError :message="errors.name" />
            </div>

            <div class="grid gap-2">
                <Label for="legal_name">Legal name</Label>
                <Input
                    id="legal_name"
                    name="legal_name"
                    :default-value="company.legal_name ?? ''"
                />
                <InputError :message="errors.legal_name" />
            </div>

            <div class="grid gap-2">
                <Label for="registration_no">Registration no</Label>
                <Input
                    id="registration_no"
                    name="registration_no"
                    :default-value="company.registration_no ?? ''"
                />
                <InputError :message="errors.registration_no" />
            </div>

            <div class="grid gap-2">
                <Label for="phone">Phone</Label>
                <Input id="phone" name="phone" :default-value="company.phone ?? ''" />
                <InputError :message="errors.phone" />
            </div>

            <div class="grid gap-2">
                <Label for="email">Email</Label>
                <Input
                    id="email"
                    type="email"
                    name="email"
                    :default-value="company.email ?? ''"
                />
                <InputError :message="errors.email" />
            </div>

            <div class="grid gap-2">
                <Label for="base_currency">Base currency</Label>
                <CurrencySelect id="base_currency" v-model="baseCurrency" />
                <input type="hidden" name="base_currency" :value="baseCurrency" />
                <InputError :message="errors.base_currency" />
            </div>

            <div class="grid gap-2">
                <Label for="address">Address</Label>
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
                {{ processing ? 'Saving...' : 'Save company' }}
            </Button>
        </Form>

        <div class="space-y-4 rounded-xl border p-6">
            <Heading
                variant="small"
                title="Exchange rates"
                description="Set rates from the base currency to other currencies by effective date. Used to convert amounts into the base currency for reporting."
            />

            <Form
                action="/settings/company/exchange-rates"
                method="post"
                class="grid gap-4 sm:grid-cols-4"
                v-slot="{ errors, processing }"
            >
                <input type="hidden" name="from_currency" :value="newRate.from_currency" />
                <div class="grid gap-2">
                    <Label for="rate_to_currency">To currency</Label>
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
                    <Label for="rate">Rate</Label>
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
                    <Label for="effective_date">Effective date</Label>
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
                        {{ processing ? 'Saving...' : 'Add rate' }}
                    </Button>
                </div>
            </Form>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b text-start text-muted-foreground">
                            <th class="py-2 pr-4">From</th>
                            <th class="py-2 pr-4">To</th>
                            <th class="py-2 pr-4">Rate</th>
                            <th class="py-2 pr-4">Effective date</th>
                            <th class="py-2 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <tr v-for="rate in exchange_rates" :key="rate.id">
                            <td class="py-3 pr-4">{{ rate.from_currency }}</td>
                            <td class="py-3 pr-4">{{ rate.to_currency }}</td>
                            <td class="py-3 pr-4 tabular-nums">{{ Number(rate.rate).toFixed(6) }}</td>
                            <td class="py-3 pr-4">{{ rate.effective_date }}</td>
                            <td class="py-3 text-right">
                                <Form
                                    :action="`/settings/company/exchange-rates/${rate.id}`"
                                    method="post"
                                    v-slot="{ processing }"
                                >
                                    <input type="hidden" name="_method" value="delete" />
                                    <Button type="submit" :disabled="processing" variant="ghost" size="sm">
                                        Delete
                                    </Button>
                                </Form>
                            </td>
                        </tr>
                        <tr v-if="!exchange_rates.length">
                            <td colspan="5" class="py-6 text-center text-muted-foreground">
                                No exchange rates yet. Add one above.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>
