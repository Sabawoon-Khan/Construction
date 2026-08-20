<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { Trash2 } from '@lucide/vue';
import { computed } from 'vue';
import InputError from '@/components/InputError.vue';
import RecordAttachments from '@/components/RecordAttachments.vue';
import { MisFormPage } from '@/components/mis';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useTranslations } from '@/composables/useTranslations';

const props = defineProps<{
    item: Record<string, any> | null;
    users?: { id: number; name: string; email: string }[];
}>();

const isEdit = computed(() => !!props.item);
const { t } = useTranslations();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Settings', href: '/settings/appearance' },
            { title: 'Shareholders', href: '/partners' },
            { title: 'Form', href: '/partners/create' },
        ],
    },
});
</script>

<template>
    <Head :title="isEdit ? t('Edit shareholder') : t('Add shareholder')" />

    <MisFormPage :title="isEdit ? t('Edit shareholder') : t('Add shareholder')" back-href="/partners">
        <Form
            :action="isEdit ? `/partners/${item!.id}` : '/partners'"
            :method="isEdit ? 'put' : 'post'"
            class="mis-form-card"
            v-slot="{ errors, processing }"
        >
            <div class="mis-form-grid">
                <div class="grid gap-2 mis-form-span">
                    <Label for="name">{{ t('Name') }}</Label>
                    <Input id="name" name="name" type="text" required :default-value="item?.name ?? ''" />
                    <InputError :message="errors.name" />
                </div>
                <div v-if="isEdit" class="grid gap-2">
                    <Label for="partner_no">{{ t('Partner No') }}</Label>
                    <Input id="partner_no" name="partner_no" type="text" required :default-value="item?.partner_no ?? ''" />
                    <InputError :message="errors.partner_no" />
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
                    <Label for="user_id">{{ t('Login account') }}</Label>
                    <select
                        id="user_id"
                        name="user_id"
                        class="mis-form-select"
                        :value="item?.user_id ?? ''"
                    >
                        <option value="">{{ t('No login — view-only record') }}</option>
                        <option v-for="u in users || []" :key="u.id" :value="u.id">{{ u.name }} · {{ u.email }}</option>
                    </select>
                    <InputError :message="errors.user_id" />
                </div>
                <div class="grid gap-2">
                    <Label for="national_id">{{ t('National ID') }}</Label>
                    <Input id="national_id" name="national_id" type="text" :default-value="item?.national_id ?? ''" />
                    <InputError :message="errors.national_id" />
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

            <RecordAttachments
                attachable="partners"
                :attachable-id="isEdit ? item!.id : null"
                :attachments="item?.attachments"
                :error="errors.documents"
            />

            <div class="mis-form-actions">
                <Button type="submit" :disabled="processing">{{ processing ? t('Saving…') : t('Save') }}</Button>
                <Button as-child variant="secondary">
                    <Link href="/partners">{{ t('Cancel') }}</Link>
                </Button>
                <Form v-if="isEdit" :action="`/partners/${item!.id}`" method="delete" class="ml-auto" v-slot="{ processing: deleting }">
                    <Button type="submit" variant="destructive" :disabled="deleting">
                        <Trash2 class="size-4" />
                        {{ deleting ? t('Deleting…') : t('Delete') }}
                    </Button>
                </Form>
            </div>
        </Form>
    </MisFormPage>
</template>
