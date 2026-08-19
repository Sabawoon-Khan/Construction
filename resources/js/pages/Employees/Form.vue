<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { Trash2 } from '@lucide/vue';
import { computed, ref } from 'vue';
import CurrencySelect from '@/components/CurrencySelect.vue';
import InputError from '@/components/InputError.vue';
import { MisFormPage } from '@/components/mis';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useCurrency } from '@/composables/useCurrency';
import { useTranslations } from '@/composables/useTranslations';

const props = defineProps<{
    item: Record<string, any> | null;
}>();

const isEdit = computed(() => !!props.item);
const { t } = useTranslations();
const { baseCurrency } = useCurrency();
const currency = ref(props.item?.currency ?? baseCurrency.value);

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Employees', href: '/employees' },
            { title: 'Form', href: '/employees/create' },
        ],
    },
});
</script>

<template>
    <Head :title="isEdit ? t('Edit employee') : t('Create employee')" />

    <MisFormPage :title="isEdit ? t('Edit employee') : t('Create employee')" back-href="/employees">
        <Form
            :action="isEdit ? `/employees/${item?.id}` : '/employees'"
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
                    <Label for="employee_no">{{ t('Employee No') }}</Label>
                    <Input id="employee_no" name="employee_no" type="text" :default-value="item?.employee_no ?? ''" />
                    <InputError :message="errors.employee_no" />
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
                <div class="grid gap-2">
                    <Label for="department">{{ t('Department') }}</Label>
                    <Input id="department" name="department" type="text" :default-value="item?.department ?? ''" />
                    <InputError :message="errors.department" />
                </div>
                <div class="grid gap-2">
                    <Label for="designation">{{ t('Designation') }}</Label>
                    <Input id="designation" name="designation" type="text" :default-value="item?.designation ?? ''" />
                    <InputError :message="errors.designation" />
                </div>
                <div class="grid gap-2">
                    <Label for="joining_date">{{ t('Joining Date') }}</Label>
                    <Input id="joining_date" name="joining_date" type="date" :default-value="item?.joining_date?.slice?.(0, 10) ?? ''" />
                    <InputError :message="errors.joining_date" />
                </div>
                <div class="grid gap-2">
                    <Label for="basic_salary">{{ t('Basic Salary') }}</Label>
                    <div class="flex gap-2">
                        <Input id="basic_salary" name="basic_salary" type="number" step="0.01" min="0" :default-value="item?.basic_salary ?? ''" class="min-w-0 flex-1" />
                        <CurrencySelect id="currency" v-model="currency" name="currency" class="w-[9rem] shrink-0" />
                    </div>
                    <InputError :message="errors.basic_salary" />
                    <InputError :message="errors.currency" />
                </div>
                <div class="grid gap-2">
                    <Label for="status">{{ t('Status') }}</Label>
                    <select id="status" name="status" class="mis-form-select">
                        <option value="active" :selected="(item?.status ?? 'active') === 'active'">{{ t('Active') }}</option>
                        <option value="inactive" :selected="(item?.status ?? '') === 'inactive'">{{ t('Inactive') }}</option>
                        <option value="on_leave" :selected="(item?.status ?? '') === 'on_leave'">{{ t('On leave') }}</option>
                    </select>
                    <InputError :message="errors.status" />
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
                    <Link href="/employees">{{ t('Cancel') }}</Link>
                </Button>
                <Form v-if="isEdit" :action="`/employees/${item!.id}`" method="delete" class="ml-auto" v-slot="{ processing: deleting }">
                    <Button type="submit" variant="destructive" :disabled="deleting">
                        <Trash2 class="size-4" />
                        {{ deleting ? t('Deleting…') : t('Delete') }}
                    </Button>
                </Form>
            </div>
        </Form>
    </MisFormPage>
</template>
