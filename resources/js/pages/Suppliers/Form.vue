<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
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

    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
        <div class="space-y-3">
            <Button as-child variant="ghost" size="sm" class="-ml-2 w-fit">
                <Link href="/suppliers"><ArrowLeft class="size-4" /> {{ t('Back') }}</Link>
            </Button>
            <Heading
                :title="isEdit ? t('Edit supplier') : t('Create supplier')"
                :description="t('Fill in the details below and save.')"
            />
        </div>

        <Form
            :action="isEdit ? `/suppliers/${item!.id}` : '/suppliers'"
            :method="isEdit ? 'put' : 'post'"
            class="max-w-3xl space-y-6 rounded-xl border border-sidebar-border/70 bg-card p-6"
            v-slot="{ errors, processing }"
        >
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="grid gap-2">
                    <Label for="supplier_no">{{ t('Supplier No') }}</Label>
                    <Input id="supplier_no" name="supplier_no" type="text" :default-value="item?.supplier_no ?? ''" :placeholder="t('Leave blank to auto-generate')" />
                    <InputError :message="errors.supplier_no" />
                </div>
                <div class="grid gap-2">
                    <Label for="name">{{ t('Name') }}</Label>
                    <Input id="name" name="name" type="text" required :default-value="item?.name ?? ''" />
                    <InputError :message="errors.name" />
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
                <div class="grid gap-2 sm:col-span-2">
                    <Label for="address">{{ t('Address') }}</Label>
                    <textarea id="address" name="address" rows="3" class="flex min-h-[80px] w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs" :value="item?.address ?? ''"></textarea>
                    <InputError :message="errors.address" />
                </div>
                <div class="grid gap-2 sm:col-span-2">
                    <Label for="notes">{{ t('Notes') }}</Label>
                    <textarea id="notes" name="notes" rows="3" class="flex min-h-[80px] w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs" :value="item?.notes ?? ''"></textarea>
                    <InputError :message="errors.notes" />
                </div>
            </div>

            <div class="flex gap-2">
                <Button type="submit" :disabled="processing">{{ processing ? t('Saving…') : t('Save') }}</Button>
                <Button as-child variant="secondary">
                    <Link href="/suppliers">{{ t('Cancel') }}</Link>
                </Button>
            </div>
        </Form>
    </div>
</template>
