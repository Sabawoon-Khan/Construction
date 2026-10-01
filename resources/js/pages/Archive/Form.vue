<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { computed } from 'vue';
import InputError from '@/components/InputError.vue';
import RecordAttachments from '@/components/RecordAttachments.vue';
import { V2FormPage } from '@/components/v2';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useTranslations } from '@/composables/useTranslations';
import { historyBack } from '@/lib/historyBack';

type CategoryOption = { value: string; label: string };

const props = defineProps<{
    item: Record<string, any> | null;
    categories: CategoryOption[];
}>();

const isEdit = computed(() => !!props.item);
const { t } = useTranslations();

const defaultCategory = computed(
    () => props.item?.category ?? props.categories[0]?.value ?? 'contract',
);

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Archive', href: '/archive' },
            { title: 'Form', href: '/archive/create' },
        ],
    },
});
</script>

<template>
    <Head :title="isEdit ? t('Edit archive document') : t('Add archive document')" />

    <V2FormPage
        :title="isEdit ? t('Edit archive document') : t('Add archive document')"
        :eyebrow="t('Archive')"
        :back-href="isEdit ? '/archive' : undefined"
    >
        <Form
            :action="isEdit ? `/archive/${item!.id}` : '/archive'"
            :method="isEdit ? 'put' : 'post'"
            class="mis-form-card"
            v-slot="{ errors, processing }"
        >
            <div class="v2-form-split">
                <div class="v2-form-split-main">
                    <div class="v2-form-group">
                        <div class="mis-form-grid">
                            <div class="v2-field mis-form-span-2">
                                <Label for="title">{{ t('Title') }}</Label>
                                <Input
                                    id="title"
                                    name="title"
                                    type="text"
                                    required
                                    :default-value="item?.title ?? ''"
                                />
                                <InputError :message="errors.title" />
                            </div>
                            <div class="v2-field">
                                <Label for="category">{{ t('Category') }}</Label>
                                <select
                                    id="category"
                                    name="category"
                                    class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                                    required
                                >
                                    <option
                                        v-for="cat in categories"
                                        :key="cat.value"
                                        :value="cat.value"
                                        :selected="cat.value === defaultCategory"
                                    >
                                        {{ cat.label }}
                                    </option>
                                </select>
                                <InputError :message="errors.category" />
                            </div>
                            <div class="v2-field">
                                <Label for="document_date">{{ t('Document date') }}</Label>
                                <Input
                                    id="document_date"
                                    name="document_date"
                                    type="date"
                                    :default-value="item?.document_date?.slice?.(0, 10) ?? item?.document_date ?? ''"
                                />
                                <InputError :message="errors.document_date" />
                            </div>
                            <div class="v2-field mis-form-span-2">
                                <Label for="notes">{{ t('Notes') }}</Label>
                                <textarea
                                    id="notes"
                                    name="notes"
                                    rows="4"
                                    class="flex min-h-[80px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                                    :value="item?.notes ?? ''"
                                />
                                <InputError :message="errors.notes" />
                            </div>
                        </div>
                    </div>
                </div>
                <div class="v2-form-split-aside">
                    <div class="v2-form-group">
                        <div class="v2-form-split-block">
                            <h2 class="v2-form-split-block-title">{{ t('Files') }}</h2>
                            <RecordAttachments
                                attachable="archive"
                                :attachable-id="isEdit ? item!.id : null"
                                :attachments="item?.attachments"
                                :error="errors.documents"
                            />
                        </div>
                    </div>
                </div>
            </div>

            <div class="mis-form-actions">
                <Button type="button" variant="outline" @click="historyBack('/archive')">
                    {{ t('Cancel') }}
                </Button>
                <Button type="submit" :disabled="processing">
                    {{ isEdit ? t('Save') : t('Create') }}
                </Button>
            </div>
        </Form>
    </V2FormPage>
</template>
