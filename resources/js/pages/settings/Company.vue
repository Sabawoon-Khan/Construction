<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

defineProps<{
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
}>();

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
                <Input
                    id="base_currency"
                    name="base_currency"
                    required
                    :default-value="company.base_currency"
                />
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
    </div>
</template>
