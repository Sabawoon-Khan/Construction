<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { ArrowLeft } from '@lucide/vue';
import { computed } from 'vue';
import FileUploadField, { type ExistingUploadFile } from '@/components/FileUploadField.vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useTranslations } from '@/composables/useTranslations';

const props = defineProps<{
    item: Record<string, any> | null;
    categories?: { id: number; name: string; code: string }[];
}>();
const isEdit = computed(() => !!props.item);
const { t } = useTranslations();
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
    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
        <div class="space-y-3">
            <Button as-child variant="ghost" size="sm" class="-ml-2 w-fit">
                <Link href="/expenses"><ArrowLeft class="size-4" /> {{ t('Back') }}</Link>
            </Button>
            <Heading
                :title="isEdit ? t('Edit expense') : t('Record expense')"
                :description="t('Company-wide expenses not tied to a project.')"
            />
        </div>
        <Form
            :action="isEdit ? `/expenses/${item!.id}` : '/expenses'"
            :method="isEdit ? 'put' : 'post'"
            class="max-w-3xl space-y-6 rounded-xl border bg-card p-6"
            v-slot="{ errors, processing }"
        >
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="grid gap-2 sm:col-span-2">
                    <Label for="description">{{ t('Description') }}</Label>
                    <textarea
                        id="description"
                        name="description"
                        rows="3"
                        required
                        class="rounded-md border border-input bg-transparent px-3 py-2 text-sm"
                        :value="item?.description ?? ''"
                    />
                    <InputError :message="errors.description" />
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
                    <Label for="amount">{{ t('Total cost') }}</Label>
                    <Input id="amount" name="amount" type="number" step="0.01" min="0" required :default-value="item?.amount ?? ''" />
                    <InputError :message="errors.amount" />
                </div>
                <div class="grid gap-2">
                    <Label for="expense_category_id">{{ t('Category') }}</Label>
                    <select
                        id="expense_category_id"
                        name="expense_category_id"
                        class="h-9 rounded-md border border-input bg-transparent px-3 text-sm"
                        :value="item?.expense_category_id ?? ''"
                    >
                        <option value="">{{ t('General') }}</option>
                        <option v-for="c in categories || []" :key="c.id" :value="c.id">{{ c.name }}</option>
                    </select>
                </div>
                <div class="grid gap-2">
                    <Label for="reference_no">{{ t('Receipt / reference') }}</Label>
                    <Input id="reference_no" name="reference_no" :default-value="item?.reference_no ?? ''" />
                </div>
                <div class="grid gap-2 sm:col-span-2">
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
                        :hint="t('Upload receipt photos or PDFs.')"
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
                        <p class="text-xs text-muted-foreground">{{ t('Optional. PDF or image of the receipt.') }}</p>
                        <InputError :message="errors.documents" />
                    </div>
                </div>
            </div>
            <div class="flex gap-2">
                <Button type="submit" :disabled="processing">{{ processing ? t('Saving…') : t('Save expense') }}</Button>
                <Button as-child variant="secondary"><Link href="/expenses">{{ t('Cancel') }}</Link></Button>
            </div>
        </Form>
    </div>
</template>
