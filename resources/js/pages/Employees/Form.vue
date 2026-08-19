<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, Trash2 } from '@lucide/vue';
import { computed, ref } from 'vue';
import CurrencySelect from '@/components/CurrencySelect.vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
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

    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
        <div class="space-y-3">
            <Button as-child variant="ghost" size="sm" class="-ml-2 w-fit">
                <Link href="/employees"><ArrowLeft class="size-4" /> {{ t('Back') }}</Link>
            </Button>
            <Heading
                :title="isEdit ? t('Edit employee') : t('Create employee')"
                :description="t('Fill in the employee details below and save.')"
            />
        </div>

        <Form
            :action="isEdit ? `/employees/${item?.id}` : '/employees'"
            :method="isEdit ? 'put' : 'post'"
            class="max-w-3xl space-y-6 rounded-xl border border-sidebar-border/70 bg-card p-6"
            v-slot="{ errors, processing }"
        >
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="grid gap-2">
                    <Label for="employee_no">{{ t('Employee No') }}</Label>
                    <Input id="employee_no" name="employee_no" type="text" :default-value="item?.employee_no ?? ''" :placeholder="t('Leave blank to auto-generate')" />
                    <InputError :message="errors.employee_no" />
                </div>
                <div class="grid gap-2">
                    <Label for="name">{{ t('Name') }}</Label>
                    <Input id="name" name="name" type="text" required :default-value="item?.name ?? ''" />
                    <InputError :message="errors.name" />
                </div>
                <div class="grid gap-2">
                    <Label for="email">{{ t('Email') }}</Label>
                    <Input id="email" name="email" type="email" :default-value="item?.email ?? ''" />
                    <InputError :message="errors.email" />
                </div>
                <div class="grid gap-2">
                    <Label for="phone">{{ t('Phone') }}</Label>
                    <Input id="phone" name="phone" type="text" :default-value="item?.phone ?? ''" />
                    <InputError :message="errors.phone" />
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
                    <Input id="basic_salary" name="basic_salary" type="number" step="0.01" min="0" :default-value="item?.basic_salary ?? ''" />
                    <InputError :message="errors.basic_salary" />
                </div>
                <div class="grid gap-2">
                    <Label for="currency">{{ t('Currency') }}</Label>
                    <CurrencySelect id="currency" v-model="currency" />
                    <input type="hidden" name="currency" :value="currency" />
                    <InputError :message="errors.currency" />
                </div>
                <div class="grid gap-2">
                    <Label for="status">{{ t('Status') }}</Label>
                    <select id="status" name="status" class="h-9 rounded-md border border-input bg-transparent px-3 text-sm">
                        <option value="active" :selected="(item?.status ?? 'active') === 'active'">{{ t('Active') }}</option>
                        <option value="inactive" :selected="(item?.status ?? '') === 'inactive'">{{ t('Inactive') }}</option>
                        <option value="on_leave" :selected="(item?.status ?? '') === 'on_leave'">{{ t('On leave') }}</option>
                    </select>
                    <InputError :message="errors.status" />
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
    </div>
</template>
