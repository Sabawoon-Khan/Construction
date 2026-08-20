<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { Trash2 } from '@lucide/vue';
import { computed, ref } from 'vue';
import CurrencySelect from '@/components/CurrencySelect.vue';
import InputError from '@/components/InputError.vue';
import RecordAttachments from '@/components/RecordAttachments.vue';
import { MisFormPage } from '@/components/mis';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useCurrency } from '@/composables/useCurrency';
import { useTranslations } from '@/composables/useTranslations';
import { historyBack } from '@/lib/historyBack';

const props = defineProps<{ item: Record<string, any> | null }>();
const isEdit = computed(() => !!props.item);
const { t } = useTranslations();
const { baseCurrency } = useCurrency();
const currency = ref(props.item?.currency ?? baseCurrency.value);

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Incomes', href: '/incomes' },
            { title: 'Form', href: '#' },
        ],
    },
});
</script>

<template>
    <Head :title="isEdit ? t('Edit income') : t('Record income')" />
    <MisFormPage :title="isEdit ? t('Edit income') : t('Record income')" :back-href="isEdit ? '/incomes' : undefined">
        <Form
            :action="isEdit ? `/incomes/${item!.id}` : '/incomes'"
            :method="isEdit ? 'put' : 'post'"
            class="mis-form-card"
            v-slot="{ errors, processing }"
        >
            <div class="mis-form-grid">
                <div class="grid gap-2">
                    <Label for="income_date">{{ t('Date') }}</Label>
                    <Input
                        id="income_date"
                        name="income_date"
                        type="date"
                        required
                        :default-value="item?.income_date?.slice?.(0, 10) ?? new Date().toISOString().slice(0, 10)"
                    />
                    <InputError :message="errors.income_date" />
                </div>
                <div class="grid gap-2">
                    <Label for="amount">{{ t('Total amount') }}</Label>
                    <div class="flex gap-2">
                        <Input id="amount" name="amount" type="number" step="0.01" min="0" required :default-value="item?.amount ?? ''" class="min-w-0 flex-1" />
                        <CurrencySelect id="currency" v-model="currency" name="currency" class="w-[9rem] shrink-0" />
                    </div>
                    <InputError :message="errors.amount" />
                    <InputError :message="errors.currency" />
                </div>
                <div class="grid gap-2 mis-form-span">
                    <Label for="description">{{ t('Description') }}</Label>
                    <textarea
                        id="description"
                        name="description"
                        rows="3"
                        required
                        class="mis-form-textarea"
                        :value="item?.description ?? ''"
                    />
                    <InputError :message="errors.description" />
                </div>
            </div>
            <RecordAttachments
                attachable="incomes"
                :attachable-id="isEdit ? item!.id : null"
                :attachments="item?.attachments"
                :error="errors.documents"
            />
            <div class="mis-form-actions">
                <Button type="submit" :disabled="processing">{{ processing ? t('Saving…') : t('Save income') }}</Button>
                <Button v-if="!isEdit" type="button" variant="secondary" @click="historyBack">{{ t('Cancel') }}</Button>
                <Button v-else as-child variant="secondary">
                    <Link href="/incomes">{{ t('Cancel') }}</Link>
                </Button>
                <Form v-if="isEdit" :action="`/incomes/${item!.id}`" method="delete" class="ml-auto" v-slot="{ processing: deleting }">
                    <Button type="submit" variant="destructive" :disabled="deleting">
                        <Trash2 class="size-4" />
                        {{ deleting ? t('Deleting…') : t('Delete') }}
                    </Button>
                </Form>
            </div>
        </Form>
    </MisFormPage>
</template>
