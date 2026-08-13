<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

const props = defineProps<{
    item: Record<string, any> | null;
}>();

const isEdit = computed(() => !!props.item);

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Bank Accounts', href: '/bank-accounts' },
            { title: 'Form', href: '/bank-accounts/create' },
        ],
    },
});
</script>

<template>
    <Head :title="isEdit ? 'Edit Bank Accounts' : 'Create Bank Accounts'" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
        <Heading
            :title="isEdit ? 'Edit Bank Accounts' : 'Create Bank Accounts'"
            description="Fill in the details below and save."
        />

        <Form
            :action="isEdit ? `/bank-accounts/${item!.id}` : '/bank-accounts'"
            :method="isEdit ? 'put' : 'post'"
            class="max-w-3xl space-y-6 rounded-xl border border-sidebar-border/70 bg-card p-6"
            v-slot="{ errors, processing }"
        >
            <div class="grid gap-4 sm:grid-cols-2">
            <div class="grid gap-2">
                <Label for="name">Name</Label>
                <Input id="name" name="name" type="text" required :default-value="item?.name ?? ''" />
                <InputError :message="errors.name" />
            </div>
            <div class="grid gap-2">
                <Label for="bank_name">Bank Name</Label>
                <Input id="bank_name" name="bank_name" type="text" :default-value="item?.bank_name ?? ''" />
                <InputError :message="errors.bank_name" />
            </div>
            <div class="grid gap-2">
                <Label for="account_number">Account Number</Label>
                <Input id="account_number" name="account_number" type="text" :default-value="item?.account_number ?? ''" />
                <InputError :message="errors.account_number" />
            </div>
            <div class="grid gap-2">
                <Label for="iban">Iban</Label>
                <Input id="iban" name="iban" type="text" :default-value="item?.iban ?? ''" />
                <InputError :message="errors.iban" />
            </div>
            <div class="grid gap-2">
                <Label for="currency">Currency</Label>
                <Input id="currency" name="currency" type="text" required :default-value="item?.currency ?? ''" />
                <InputError :message="errors.currency" />
            </div>
            <div class="grid gap-2">
                <Label for="opening_balance">Opening Balance</Label>
                <Input id="opening_balance" name="opening_balance" type="number" step="0.01" :default-value="item?.opening_balance ?? ''" />
                <InputError :message="errors.opening_balance" />
            </div>
            <div class="flex items-center gap-2 sm:col-span-2">
                <input id="is_cash" type="checkbox" name="is_cash" value="1" class="size-4 rounded border" :checked="item?.is_cash ?? true" />
                <Label for="is_cash">Is Cash</Label>
                <InputError :message="errors.is_cash" />
            </div>
            <div class="flex items-center gap-2 sm:col-span-2">
                <input id="is_active" type="checkbox" name="is_active" value="1" class="size-4 rounded border" :checked="item?.is_active ?? true" />
                <Label for="is_active">Is Active</Label>
                <InputError :message="errors.is_active" />
            </div>
            </div>

            <div class="flex gap-2">
                <Button type="submit" :disabled="processing">{{ processing ? 'Saving...' : 'Save' }}</Button>
                <Button as-child variant="secondary">
                    <Link href="/bank-accounts">Cancel</Link>
                </Button>
            </div>
        </Form>
    </div>
</template>
