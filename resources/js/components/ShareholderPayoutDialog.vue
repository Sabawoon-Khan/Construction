<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import CurrencySelect from '@/components/CurrencySelect.vue';
import InputError from '@/components/InputError.vue';
import RelatedCreateLink from '@/components/RelatedCreateLink.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useCurrency } from '@/composables/useCurrency';
import { useTranslations } from '@/composables/useTranslations';

const props = defineProps<{
    open: boolean;
    projectId: number;
    shareholders: { id: number; name: string }[];
}>();

const emit = defineEmits<{
    'update:open': [value: boolean];
}>();

const { t } = useTranslations();
const { baseCurrency } = useCurrency();
const partnerId = ref('');
const currency = ref(baseCurrency.value);

watch(
    () => props.open,
    (open) => {
        if (open) {
            partnerId.value = props.shareholders[0]
                ? String(props.shareholders[0].id)
                : '';
            currency.value = baseCurrency.value;
        }
    },
);
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>{{ t('Record payout') }}</DialogTitle>
            </DialogHeader>

            <Form
                :action="
                    partnerId
                        ? `/projects/${projectId}/shareholders/${partnerId}/payouts`
                        : '#'
                "
                method="post"
                class="grid gap-4"
                v-slot="{ errors, processing }"
            >
                <div class="grid gap-2">
                    <div class="flex items-center justify-between gap-2">
                        <Label for="payout_partner_id">{{
                            t('Shareholder')
                        }}</Label>
                        <RelatedCreateLink
                            href="/partners/create"
                            :label="t('Add shareholder')"
                        />
                    </div>
                    <select
                        id="payout_partner_id"
                        v-model="partnerId"
                        class="h-9 w-full rounded-md border border-input bg-transparent px-3 text-sm shadow-xs"
                        required
                    >
                        <option value="" disabled>
                            {{ t('Select shareholder') }}
                        </option>
                        <option
                            v-for="s in shareholders"
                            :key="s.id"
                            :value="String(s.id)"
                        >
                            {{ s.name }}
                        </option>
                    </select>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="grid gap-2">
                        <Label for="payout_date">{{ t('Date') }}</Label>
                        <Input
                            id="payout_date"
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
                        <Label for="payout_amount">{{ t('Amount') }}</Label>
                        <Input
                            id="payout_amount"
                            name="amount"
                            type="number"
                            step="0.01"
                            min="0.01"
                            required
                            placeholder="0.00"
                        />
                        <InputError :message="errors.amount" />
                    </div>
                </div>

                <div class="grid gap-2">
                    <Label>{{ t('Currency') }}</Label>
                    <CurrencySelect v-model="currency" />
                    <input type="hidden" name="currency" :value="currency" />
                    <InputError :message="errors.currency" />
                </div>

                <DialogFooter class="gap-2 sm:justify-end">
                    <Button
                        type="button"
                        variant="outline"
                        @click="emit('update:open', false)"
                    >
                        {{ t('Cancel') }}
                    </Button>
                    <Button
                        type="submit"
                        :disabled="processing || !partnerId"
                    >
                        {{ processing ? t('Saving…') : t('Record payout') }}
                    </Button>
                </DialogFooter>
            </Form>
        </DialogContent>
    </Dialog>
</template>
