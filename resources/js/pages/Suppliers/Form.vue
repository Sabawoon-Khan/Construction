<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { Trash2 } from '@lucide/vue';
import { computed } from 'vue';
import InputError from '@/components/InputError.vue';
import RecordAttachments from '@/components/RecordAttachments.vue';
import { V2FormPage, V2FormSection } from '@/components/v2';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useTranslations } from '@/composables/useTranslations';
import { historyBack } from '@/lib/historyBack';

const props = defineProps<{
    item: Record<string, any> | null;
}>();

const isEdit = computed(() => !!props.item);
const { t } = useTranslations();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Suppliers', href: '/suppliers' },
            { title: 'Form', href: '/suppliers/create' },
        ],
    },
});
</script>

<template>
    <Head :title="isEdit ? t('Edit supplier') : t('Create supplier')" />

    <V2FormPage
        :title="isEdit ? t('Edit supplier') : t('Create supplier')"
        :eyebrow="t('Suppliers')"
        :back-href="isEdit ? '/suppliers' : undefined"
    >
        <Form
            :action="isEdit ? `/suppliers/${item!.id}` : '/suppliers'"
            :method="isEdit ? 'put' : 'post'"
            class="mis-form-card"
            v-slot="{ errors, processing }"
        >
            <V2FormSection :title="t('Supplier')">
                <div class="mis-form-grid">
                    <div class="v2-field">
                        <Label for="name">{{ t('Name') }}</Label>
                        <Input
                            id="name"
                            name="name"
                            type="text"
                            required
                            :default-value="item?.name ?? ''"
                        />
                        <InputError :message="errors.name" />
                    </div>
                    <div class="v2-field">
                        <Label for="supplier_no">{{ t('Supplier No') }}</Label>
                        <Input
                            id="supplier_no"
                            name="supplier_no"
                            type="text"
                            :default-value="item?.supplier_no ?? ''"
                        />
                        <InputError :message="errors.supplier_no" />
                    </div>
                    <div class="v2-field">
                        <Label for="contact_person">{{ t('Contact person') }}</Label>
                        <Input
                            id="contact_person"
                            name="contact_person"
                            type="text"
                            :default-value="item?.contact_person ?? ''"
                        />
                        <InputError :message="errors.contact_person" />
                    </div>
                    <div class="v2-field">
                        <Label for="phone">{{ t('Phone') }}</Label>
                        <Input
                            id="phone"
                            name="phone"
                            type="text"
                            :default-value="item?.phone ?? ''"
                        />
                        <InputError :message="errors.phone" />
                    </div>
                    <div class="v2-field">
                        <Label for="email">{{ t('Email') }}</Label>
                        <Input
                            id="email"
                            name="email"
                            type="email"
                            :default-value="item?.email ?? ''"
                        />
                        <InputError :message="errors.email" />
                    </div>
                    <div class="v2-field mis-form-span">
                        <Label for="address">{{ t('Address') }}</Label>
                        <textarea
                            id="address"
                            name="address"
                            rows="2"
                            class="mis-form-textarea"
                            :value="item?.address ?? ''"
                        ></textarea>
                        <InputError :message="errors.address" />
                    </div>
                    <div class="v2-field mis-form-span">
                        <Label for="notes">{{ t('Notes') }}</Label>
                        <textarea
                            id="notes"
                            name="notes"
                            rows="2"
                            class="mis-form-textarea"
                            :value="item?.notes ?? ''"
                        ></textarea>
                        <InputError :message="errors.notes" />
                    </div>
                </div>

                <div class="mt-3">
                    <RecordAttachments
                        attachable="suppliers"
                        :attachable-id="isEdit ? item!.id : null"
                        :attachments="item?.attachments"
                        :error="errors.documents"
                    />
                </div>
            </V2FormSection>

            <div class="mis-form-actions">
                <Button type="submit" :disabled="processing">
                    {{ processing ? t('Saving…') : t('Save') }}
                </Button>
                <Button
                    v-if="!isEdit"
                    type="button"
                    variant="secondary"
                    @click="historyBack"
                >
                    {{ t('Cancel') }}
                </Button>
                <Button v-else as-child variant="secondary">
                    <Link href="/suppliers">{{ t('Cancel') }}</Link>
                </Button>
                <Form
                    v-if="isEdit"
                    :action="`/suppliers/${item!.id}`"
                    method="delete"
                    class="ml-auto"
                    v-slot="{ processing: deleting }"
                >
                    <Button type="submit" variant="destructive" :disabled="deleting">
                        <Trash2 class="size-4" />
                        {{ deleting ? t('Deleting…') : t('Delete') }}
                    </Button>
                </Form>
            </div>
        </Form>
    </V2FormPage>
</template>
