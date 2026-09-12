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

const props = withDefaults(
    defineProps<{
        item: Record<string, any> | null;
        defaults?: { customer_no?: string; registered_at?: string };
    }>(),
    { defaults: () => ({}) },
);

const isEdit = computed(() => !!props.item);
const { t } = useTranslations();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Customers', href: '/customers' },
            { title: 'Form', href: '/customers/create' },
        ],
    },
});
</script>

<template>
    <Head :title="isEdit ? t('Edit customer') : t('Create customer')" />

    <V2FormPage
        :title="isEdit ? t('Edit customer') : t('Create customer')"
        :eyebrow="t('Customers')"
        :back-href="isEdit ? '/customers' : undefined"
    >
        <Form
            :action="isEdit ? `/customers/${item!.id}` : '/customers'"
            :method="isEdit ? 'put' : 'post'"
            class="mis-form-card"
            v-slot="{ errors, processing }"
        >
            <V2FormSection :title="t('Customer')">
                <div class="mis-form-grid">
                    <div class="v2-field">
                        <Label for="customer_no">{{ t('Customer No') }}</Label>
                        <Input
                            id="customer_no"
                            name="customer_no"
                            type="text"
                            :default-value="item?.customer_no ?? defaults.customer_no ?? ''"
                        />
                        <InputError :message="errors.customer_no" />
                    </div>
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
                        <Label for="takhallus">{{ t('Last name') }}</Label>
                        <Input
                            id="takhallus"
                            name="takhallus"
                            type="text"
                            :default-value="item?.takhallus ?? ''"
                        />
                        <InputError :message="errors.takhallus" />
                    </div>
                    <div class="v2-field">
                        <Label for="father_name">{{ t('Father name') }}</Label>
                        <Input
                            id="father_name"
                            name="father_name"
                            type="text"
                            :default-value="item?.father_name ?? ''"
                        />
                        <InputError :message="errors.father_name" />
                    </div>
                    <div class="v2-field">
                        <Label for="phone">{{ t('Phone') }}</Label>
                        <Input
                            id="phone"
                            name="phone"
                            type="text"
                            :default-value="item?.phone ?? ''"
                            placeholder="07XXXXXXXX"
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
                    <div class="v2-field">
                        <Label for="national_id">{{ t('National ID') }}</Label>
                        <Input
                            id="national_id"
                            name="national_id"
                            type="text"
                            :default-value="item?.national_id ?? ''"
                        />
                        <InputError :message="errors.national_id" />
                    </div>
                    <div class="v2-field">
                        <Label for="type">{{ t('Customer type') }}</Label>
                        <select id="type" name="type" class="mis-form-select">
                            <option
                                value="retail"
                                :selected="(item?.type ?? 'retail') === 'retail'"
                            >
                                {{ t('Retail') }}
                            </option>
                            <option
                                value="wholesale"
                                :selected="(item?.type ?? '') === 'wholesale'"
                            >
                                {{ t('Wholesale') }}
                            </option>
                        </select>
                        <InputError :message="errors.type" />
                    </div>
                    <div class="v2-field">
                        <Label for="status">{{ t('Status') }}</Label>
                        <select id="status" name="status" class="mis-form-select">
                            <option
                                value="active"
                                :selected="(item?.status ?? 'active') === 'active'"
                            >
                                {{ t('Active') }}
                            </option>
                            <option
                                value="inactive"
                                :selected="(item?.status ?? '') === 'inactive'"
                            >
                                {{ t('Inactive') }}
                            </option>
                        </select>
                        <InputError :message="errors.status" />
                    </div>
                    <div class="v2-field">
                        <Label for="registered_at">{{ t('Registration date') }}</Label>
                        <Input
                            id="registered_at"
                            name="registered_at"
                            type="date"
                            :default-value="
                                item?.registered_at?.slice?.(0, 10) ??
                                defaults.registered_at ??
                                ''
                            "
                        />
                        <InputError :message="errors.registered_at" />
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
                        attachable="customers"
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
                    <Link href="/customers">{{ t('Cancel') }}</Link>
                </Button>
                <Form
                    v-if="isEdit"
                    :action="`/customers/${item!.id}`"
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
