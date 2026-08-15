<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft } from '@lucide/vue';
import { computed } from 'vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useTranslations } from '@/composables/useTranslations';

const props = defineProps<{
    item: Record<string, any> | null;
}>();

const isEdit = computed(() => !!props.item);
const { t } = useTranslations();

const form = useForm({
    name: props.item?.name ?? '',
    bank_name: props.item?.bank_name ?? '',
    account_number: props.item?.account_number ?? '',
    iban: props.item?.iban ?? '',
    currency: props.item?.currency ?? 'USD',
    opening_balance: props.item?.opening_balance ?? 0,
    is_cash: Boolean(props.item?.is_cash ?? false),
    is_active: Boolean(props.item?.is_active ?? true),
});

function submit(): void {
    if (isEdit.value && props.item) {
        form.put(`/bank-accounts/${props.item.id}`);
        return;
    }

    form.post('/bank-accounts');
}

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
    <Head :title="isEdit ? t('Edit bank account') : t('Create bank account')" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
        <div class="space-y-3">
            <Button as-child variant="ghost" size="sm" class="-ml-2 w-fit">
                <Link href="/bank-accounts"><ArrowLeft class="size-4" /> {{ t('Back') }}</Link>
            </Button>
            <Heading
                :title="isEdit ? t('Edit bank account') : t('Create bank account')"
                :description="t('Cash box or bank account used when receiving sale payments.')"
            />
        </div>

        <form
            class="max-w-3xl space-y-6 rounded-xl border border-sidebar-border/70 bg-card p-6"
            @submit.prevent="submit"
        >
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="grid gap-2">
                    <Label for="name">{{ t('Name') }}</Label>
                    <Input id="name" v-model="form.name" type="text" required />
                    <InputError :message="form.errors.name" />
                </div>
                <div class="grid gap-2">
                    <Label for="bank_name">{{ t('Bank name') }}</Label>
                    <Input id="bank_name" v-model="form.bank_name" type="text" />
                    <InputError :message="form.errors.bank_name" />
                </div>
                <div class="grid gap-2">
                    <Label for="account_number">{{ t('Account number') }}</Label>
                    <Input id="account_number" v-model="form.account_number" type="text" />
                    <InputError :message="form.errors.account_number" />
                </div>
                <div class="grid gap-2">
                    <Label for="iban">{{ t('IBAN') }}</Label>
                    <Input id="iban" v-model="form.iban" type="text" :placeholder="t('Optional — e.g. AF18 ACCT-000009')" />
                    <p class="text-xs text-muted-foreground">{{ t('International Bank Account Number for bank transfers. Leave empty if you only use a local account number.') }}</p>
                    <InputError :message="form.errors.iban" />
                </div>
                <div class="grid gap-2">
                    <Label for="currency">{{ t('Currency') }}</Label>
                    <Input id="currency" v-model="form.currency" type="text" maxlength="3" required />
                    <InputError :message="form.errors.currency" />
                </div>
                <div class="grid gap-2">
                    <Label for="opening_balance">{{ t('Opening balance') }}</Label>
                    <Input id="opening_balance" v-model="form.opening_balance" type="number" step="0.01" />
                    <InputError :message="form.errors.opening_balance" />
                </div>
                <label class="flex items-center gap-2 sm:col-span-2">
                    <input v-model="form.is_cash" type="checkbox" class="size-4 rounded border" />
                    <span class="text-sm">{{ t('Cash account (safe / cash box)') }}</span>
                </label>
                <label class="flex items-center gap-2 sm:col-span-2">
                    <input v-model="form.is_active" type="checkbox" class="size-4 rounded border" />
                    <span class="text-sm">{{ t('Active') }}</span>
                </label>
            </div>

            <div class="flex gap-2">
                <Button type="submit" :disabled="form.processing">{{ form.processing ? t('Saving…') : t('Save') }}</Button>
                <Button as-child variant="secondary">
                    <Link href="/bank-accounts">{{ t('Cancel') }}</Link>
                </Button>
            </div>
        </form>
    </div>
</template>
