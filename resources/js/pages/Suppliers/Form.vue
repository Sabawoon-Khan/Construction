<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { Trash2 } from '@lucide/vue';
import { computed } from 'vue';
import InputError from '@/components/InputError.vue';
import { MisFormPage } from '@/components/mis';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useTranslations } from '@/composables/useTranslations';

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

    <MisFormPage :title="isEdit ? t('Edit supplier') : t('Create supplier')" back-href="/suppliers">
        <Form
            :action="isEdit ? `/suppliers/${item!.id}` : '/suppliers'"
            :method="isEdit ? 'put' : 'post'"
            class="mis-form-card"
            v-slot="{ errors, processing }"
        >
            <div class="mis-form-grid">
                <div class="grid gap-2">
                    <Label for="name">{{ t('Name') }}</Label>
                    <Input id="name" name="name" type="text" required :default-value="item?.name ?? ''" />
                    <InputError :message="errors.name" />
                </div>
                <div class="grid gap-2">
                    <Label for="supplier_no">{{ t('Supplier No') }}</Label>
                    <Input id="supplier_no" name="supplier_no" type="text" :default-value="item?.supplier_no ?? ''" />
                    <InputError :message="errors.supplier_no" />
                </div>
                <div class="grid gap-2">
                    <Label for="contact_person">{{ t('Contact person') }}</Label>
                    <Input id="contact_person" name="contact_person" type="text" :default-value="item?.contact_person ?? ''" />
                    <InputError :message="errors.contact_person" />
                </div>
                <div class="grid gap-2">
                    <Label for="phone">{{ t('Phone') }}</Label>
                    <Input id="phone" name="phone" type="text" :default-value="item?.phone ?? ''" />
                    <InputError :message="errors.phone" />
                </div>
                <div class="grid gap-2">
                    <Label for="email">{{ t('Email') }}</Label>
                    <Input id="email" name="email" type="email" :default-value="item?.email ?? ''" />
                    <InputError :message="errors.email" />
                </div>
                <div class="grid gap-2 mis-form-span">
                    <Label for="address">{{ t('Address') }}</Label>
                    <textarea id="address" name="address" rows="3" class="mis-form-textarea" :value="item?.address ?? ''"></textarea>
                    <InputError :message="errors.address" />
                </div>
                <div class="grid gap-2 mis-form-span">
                    <Label for="notes">{{ t('Notes') }}</Label>
                    <textarea id="notes" name="notes" rows="3" class="mis-form-textarea" :value="item?.notes ?? ''"></textarea>
                    <InputError :message="errors.notes" />
                </div>
            </div>

            <div class="mis-form-actions">
                <Button type="submit" :disabled="processing">{{ processing ? t('Saving…') : t('Save') }}</Button>
                <Button as-child variant="secondary">
                    <Link href="/suppliers">{{ t('Cancel') }}</Link>
                </Button>
                <Form v-if="isEdit" :action="`/suppliers/${item!.id}`" method="delete" class="ml-auto" v-slot="{ processing: deleting }">
                    <Button type="submit" variant="destructive" :disabled="deleting">
                        <Trash2 class="size-4" />
                        {{ deleting ? t('Deleting…') : t('Delete') }}
                    </Button>
                </Form>
            </div>
        </Form>
    </MisFormPage>
</template>
