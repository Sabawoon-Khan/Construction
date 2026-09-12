<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { Trash2 } from '@lucide/vue';
import { computed, ref } from 'vue';
import CurrencySelect from '@/components/CurrencySelect.vue';
import InputError from '@/components/InputError.vue';
import RecordAttachments from '@/components/RecordAttachments.vue';
import { V2FormPage } from '@/components/v2';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useCurrency } from '@/composables/useCurrency';
import { useTranslations } from '@/composables/useTranslations';
import { historyBack } from '@/lib/historyBack';

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

    <V2FormPage
        :title="isEdit ? t('Edit employee') : t('Create employee')"
        :eyebrow="t('Employees')"
        :back-href="isEdit ? '/employees' : undefined"
    >
        <Form
            :action="isEdit ? `/employees/${item?.id}` : '/employees'"
            :method="isEdit ? 'put' : 'post'"
            class="mis-form-card"
            v-slot="{ errors, processing }"
        >
            <div class="v2-form-split">
                <div class="v2-form-split-main">
                    <div class="v2-form-group">
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
                                <Label for="takhallus">{{ t('Takhallus') }}</Label>
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
                                <Label for="employee_no">{{ t('Employee No') }}</Label>
                                <Input
                                    id="employee_no"
                                    name="employee_no"
                                    type="text"
                                    :default-value="item?.employee_no ?? ''"
                                />
                                <InputError :message="errors.employee_no" />
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
                            <div class="v2-field">
                                <Label for="department">{{ t('Department') }}</Label>
                                <Input
                                    id="department"
                                    name="department"
                                    type="text"
                                    :default-value="item?.department ?? ''"
                                />
                                <InputError :message="errors.department" />
                            </div>
                            <div class="v2-field">
                                <Label for="designation">{{ t('Designation') }}</Label>
                                <Input
                                    id="designation"
                                    name="designation"
                                    type="text"
                                    :default-value="item?.designation ?? ''"
                                />
                                <InputError :message="errors.designation" />
                            </div>
                            <div class="v2-field">
                                <Label for="working_hours">{{ t('Working hours') }}</Label>
                                <Input
                                    id="working_hours"
                                    name="working_hours"
                                    type="text"
                                    :default-value="item?.working_hours ?? ''"
                                    :placeholder="t('e.g. 8 hours per day')"
                                />
                                <InputError :message="errors.working_hours" />
                            </div>
                            <div class="v2-field">
                                <Label for="joining_date">{{ t('Employment Date') }}</Label>
                                <Input
                                    id="joining_date"
                                    name="joining_date"
                                    type="date"
                                    :default-value="item?.joining_date?.slice?.(0, 10) ?? ''"
                                />
                                <InputError :message="errors.joining_date" />
                            </div>
                            <div class="v2-field">
                                <Label for="basic_salary">{{ t('Basic Salary') }}</Label>
                                <div class="flex gap-2">
                                    <Input
                                        id="basic_salary"
                                        name="basic_salary"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        :default-value="item?.basic_salary ?? ''"
                                        class="min-w-0 flex-1"
                                    />
                                    <CurrencySelect
                                        id="currency"
                                        v-model="currency"
                                        name="currency"
                                        class="w-[8.5rem] shrink-0"
                                    />
                                </div>
                                <InputError :message="errors.basic_salary" />
                                <InputError :message="errors.currency" />
                            </div>
                            <div class="v2-field">
                                <Label for="salary_payment_method">{{
                                    t('Salary payment method')
                                }}</Label>
                                <select
                                    id="salary_payment_method"
                                    name="salary_payment_method"
                                    class="mis-form-select"
                                >
                                    <option value="" :selected="!item?.salary_payment_method">
                                        {{ t('Select') }}
                                    </option>
                                    <option
                                        value="cash"
                                        :selected="item?.salary_payment_method === 'cash'"
                                    >
                                        {{ t('Cash') }}
                                    </option>
                                    <option
                                        value="bank"
                                        :selected="item?.salary_payment_method === 'bank'"
                                    >
                                        {{ t('Bank') }}
                                    </option>
                                </select>
                                <InputError :message="errors.salary_payment_method" />
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
                                    <option
                                        value="on_leave"
                                        :selected="(item?.status ?? '') === 'on_leave'"
                                    >
                                        {{ t('On leave') }}
                                    </option>
                                </select>
                                <InputError :message="errors.status" />
                            </div>
                        </div>
                    </div>
                </div>

                <aside class="v2-form-split-aside">
                    <div class="v2-form-split-panel">
                        <div class="v2-form-split-block">
                            <h2 class="v2-form-split-block-title">{{ t('Notes') }}</h2>
                            <div class="v2-field">
                                <Label for="notes" class="sr-only">{{ t('Notes') }}</Label>
                                <textarea
                                    id="notes"
                                    name="notes"
                                    rows="4"
                                    class="mis-form-textarea"
                                    :value="item?.notes ?? ''"
                                ></textarea>
                                <InputError :message="errors.notes" />
                            </div>
                        </div>
                        <div class="v2-form-split-block">
                            <h2 class="v2-form-split-block-title">{{ t('Attachments') }}</h2>
                            <RecordAttachments
                                attachable="employees"
                                :attachable-id="isEdit ? item!.id : null"
                                :attachments="item?.attachments"
                                :error="errors.documents"
                            />
                        </div>
                    </div>
                </aside>
            </div>

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
                    <Link href="/employees">{{ t('Cancel') }}</Link>
                </Button>
                <Form
                    v-if="isEdit"
                    :action="`/employees/${item!.id}`"
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
