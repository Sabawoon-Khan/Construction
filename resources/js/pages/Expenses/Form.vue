<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { Trash2 } from '@lucide/vue';
import { computed, ref } from 'vue';
import CurrencySelect from '@/components/CurrencySelect.vue';
import FileUploadField, { type ExistingUploadFile } from '@/components/FileUploadField.vue';
import InputError from '@/components/InputError.vue';
import { MisFormPage } from '@/components/mis';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useCurrency } from '@/composables/useCurrency';
import { useTranslations } from '@/composables/useTranslations';

const props = defineProps<{
    item: Record<string, any> | null;
    categories?: { id: number; name: string; code: string }[];
    units?: { id: number; unit_name: string }[];
}>();
const isEdit = computed(() => !!props.item);
const { t } = useTranslations();
const { baseCurrency } = useCurrency();
const itemAmount = ref(props.item?.item_amount ?? '');
const perPrice = ref(props.item?.per_price ?? '');
const currency = ref(props.item?.currency ?? baseCurrency.value);
const totalCost = computed(() => {
    const qty = Number(itemAmount.value);
    const price = Number(perPrice.value);
    if (!Number.isFinite(qty) || !Number.isFinite(price)) {
        return '';
    }

    return (qty * price).toFixed(2);
});
const backHref = computed(() =>
    props.item?.project_id ? `/projects/${props.item.project_id}` : '/expenses',
);
const existingReceipts = computed<ExistingUploadFile[]>(() =>
    (props.item?.attachments ?? []).map((file: any) => ({
        id: file.id,
        name: file.original_filename || file.title || t('Receipt'),
        size: file.file_size,
        mime: file.mime_type,
        url: file.download_url,
    })),
);

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Expenses', href: '/expenses' },
            { title: 'Form', href: '#' },
        ],
    },
});
</script>

<template>
    <Head :title="isEdit ? t('Edit expense') : t('Record expense')" />
    <MisFormPage :title="isEdit ? t('Edit expense') : t('Record expense')" :back-href="backHref">
        <Form
            :action="isEdit ? `/expenses/${item!.id}` : '/expenses'"
            :method="isEdit ? 'put' : 'post'"
            class="mis-form-card"
            v-slot="{ errors, processing }"
        >
            <div class="mis-form-grid">
                <div class="grid gap-2">
                    <Label for="paid_to">{{ t('Paid to') }}</Label>
                    <Input id="paid_to" name="paid_to" required :default-value="item?.paid_to ?? ''" />
                    <InputError :message="errors.paid_to" />
                </div>
                <div class="grid gap-2">
                    <Label for="expense_date">{{ t('Date') }}</Label>
                    <Input
                        id="expense_date"
                        name="expense_date"
                        type="date"
                        required
                        :default-value="item?.expense_date?.slice?.(0, 10) ?? new Date().toISOString().slice(0, 10)"
                    />
                    <InputError :message="errors.expense_date" />
                </div>
                <div class="grid gap-2">
                    <Label for="expense_category_id">{{ t('Category') }}</Label>
                    <select
                        id="expense_category_id"
                        name="expense_category_id"
                        class="mis-form-select"
                        :value="item?.expense_category_id ?? ''"
                    >
                        <option value="">{{ t('General') }}</option>
                        <option v-for="c in categories || []" :key="c.id" :value="c.id">{{ c.name }}</option>
                    </select>
                </div>
                <div class="grid gap-2">
                    <Label for="expense_unit_id">{{ t('Unit') }}</Label>
                    <select
                        id="expense_unit_id"
                        name="expense_unit_id"
                        class="mis-form-select"
                        :value="item?.expense_unit_id ?? ''"
                    >
                        <option value="">{{ t('Select unit') }}</option>
                        <option v-for="u in units || []" :key="u.id" :value="u.id">{{ u.unit_name }}</option>
                    </select>
                    <InputError :message="errors.expense_unit_id" />
                </div>
                <div class="grid gap-2">
                    <Label for="item_amount">{{ t('Item amount') }}</Label>
                    <Input id="item_amount" name="item_amount" type="number" step="0.01" min="0" required v-model="itemAmount" />
                    <InputError :message="errors.item_amount" />
                </div>
                <div class="grid gap-2">
                    <Label for="per_price">{{ t('Per price') }}</Label>
                    <Input id="per_price" name="per_price" type="number" step="0.01" min="0" required v-model="perPrice" />
                    <InputError :message="errors.per_price" />
                </div>
                <div class="grid gap-2">
                    <Label for="amount">{{ t('Total cost') }}</Label>
                    <div class="flex gap-2">
                        <Input id="amount" type="number" step="0.01" readonly :model-value="totalCost" class="min-w-0 flex-1 bg-muted/40" />
                        <CurrencySelect id="currency" v-model="currency" name="currency" class="w-[9rem] shrink-0" />
                    </div>
                    <InputError :message="errors.amount" />
                    <InputError :message="errors.currency" />
                </div>
                <div class="grid gap-2">
                    <Label for="reference_no">{{ t('Receipt / reference') }}</Label>
                    <Input id="reference_no" name="reference_no" :default-value="item?.reference_no ?? ''" />
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
                <div class="grid gap-2 mis-form-span">
                    <Label>{{ t('Receipt file') }}</Label>
                    <FileUploadField
                        v-if="isEdit"
                        mode="chunked"
                        destination="attachment"
                        attachable="expenses"
                        :attachable-id="item!.id"
                        multiple
                        :max-files="10"
                        accept="application/pdf,image/jpeg,image/png,image/webp"
                        :existing="existingReceipts"
                    />
                    <div v-else class="grid gap-2">
                        <input
                            type="file"
                            name="documents[]"
                            multiple
                            accept=".pdf,.jpg,.jpeg,.png,.webp"
                            class="text-sm"
                        />
                        <InputError :message="errors.documents" />
                    </div>
                </div>
            </div>
            <div class="mis-form-actions">
                <Button type="submit" :disabled="processing">{{ processing ? t('Saving…') : t('Save expense') }}</Button>
                <Button as-child variant="secondary"><Link :href="backHref">{{ t('Cancel') }}</Link></Button>
                <Form v-if="isEdit" :action="`/expenses/${item!.id}`" method="delete" class="ml-auto" v-slot="{ processing: deleting }">
                    <Button type="submit" variant="destructive" :disabled="deleting">
                        <Trash2 class="size-4" />
                        {{ deleting ? t('Deleting…') : t('Delete') }}
                    </Button>
                </Form>
            </div>
        </Form>
    </MisFormPage>
</template>
