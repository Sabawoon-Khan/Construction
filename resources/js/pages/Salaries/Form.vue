<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Trash2 } from '@lucide/vue';
import { computed, watch } from 'vue';
import CurrencySelect from '@/components/CurrencySelect.vue';
import InputError from '@/components/InputError.vue';
import RelatedCreateLink from '@/components/RelatedCreateLink.vue';
import RecordAttachments from '@/components/RecordAttachments.vue';
import { V2FormPage } from '@/components/v2';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useTranslations } from '@/composables/useTranslations';
import { historyBack } from '@/lib/historyBack';
import { money } from '@/lib/utils';

const props = defineProps<{
    item: Record<string, any> | null;
    employees: { id: number; name: string; employee_no: string; basic_salary: number | null; currency?: string }[];
}>();

const isEdit = computed(() => !!props.item);
const { t } = useTranslations();

const form = useForm({
    employee_id: String(props.item?.employee_id ?? ''),
    month: props.item?.month ?? new Date().getMonth() + 1,
    year: props.item?.year ?? new Date().getFullYear(),
    basic_salary: props.item?.basic_salary ?? '',
    bonus: props.item?.bonus ?? '0',
    deduction: props.item?.deduction ?? '0',
    payment_date: props.item?.payment_date?.slice?.(0, 10) ?? '',
    status: props.item?.status ?? 'pending',
    notes: props.item?.notes ?? '',
    currency: props.item?.currency ?? '',
    documents: [] as File[],
});

const currency = computed(() => form.currency);

const netSalary = computed(() => {
    const basic = Number(form.basic_salary) || 0;
    const bonus = Number(form.bonus) || 0;
    const deduction = Number(form.deduction) || 0;
    return Math.max(0, basic + bonus - deduction);
});

watch(() => form.employee_id, (id: string) => {
    const employee = props.employees.find((e: { id: number; basic_salary: number | null; currency?: string }) => String(e.id) === id);
    if (!employee) {
        return;
    }

    if (!isEdit.value && employee.basic_salary !== null && employee.basic_salary !== undefined) {
        form.basic_salary = String(employee.basic_salary);
    }

    if (!form.currency) {
        form.currency = employee.currency;
    }
}, { immediate: true });

function submit() {
    if (isEdit.value) {
        form.put(`/salaries/${props.item!.id}`);
    } else {
        form.post('/salaries');
    }
}

function removeSalary() {
    if (!isEdit.value) {
        return;
    }

    form.delete(`/salaries/${props.item!.id}`);
}

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Salaries', href: '/salaries' },
            { title: 'Form', href: '/salaries/create' },
        ],
    },
});
</script>

<template>
    <Head :title="isEdit ? t('Edit salary') : t('Add salary')" />

    <V2FormPage
        :title="isEdit ? t('Edit salary') : t('Add salary')"
        :eyebrow="t('Salaries')"
        :back-href="isEdit ? '/salaries' : undefined"
    >
        <form class="mis-form-card" @submit.prevent="submit">
            <div class="v2-form-split">
                <div class="v2-form-split-main">
                    <div class="v2-form-group">
                        <div class="mis-form-grid">
                            <div class="v2-field">
                                <div class="flex items-center justify-between gap-2">
                                    <Label for="employee_id">{{ t('Employee') }}</Label>
                                    <RelatedCreateLink
                                        href="/employees/create"
                                        :label="t('Add employee')"
                                    />
                                </div>
                                <select
                                    id="employee_id"
                                    v-model="form.employee_id"
                                    required
                                    class="mis-form-select"
                                    :disabled="isEdit"
                                >
                                    <option value="" disabled>{{ t('Select employee') }}</option>
                                    <option
                                        v-for="employee in employees"
                                        :key="employee.id"
                                        :value="String(employee.id)"
                                    >
                                        {{ employee.name }}
                                    </option>
                                </select>
                                <InputError :message="form.errors.employee_id" />
                            </div>
                            <div class="v2-field">
                                <Label for="payment_date">{{ t('Payment Date') }}</Label>
                                <Input
                                    id="payment_date"
                                    v-model="form.payment_date"
                                    type="date"
                                />
                                <InputError :message="form.errors.payment_date" />
                            </div>
                            <div class="v2-field">
                                <Label for="month">{{ t('Month') }}</Label>
                                <select
                                    id="month"
                                    v-model="form.month"
                                    required
                                    class="mis-form-select"
                                >
                                    <option v-for="m in 12" :key="m" :value="m">{{ m }}</option>
                                </select>
                                <InputError :message="form.errors.month" />
                            </div>
                            <div class="v2-field">
                                <Label for="year">{{ t('Year') }}</Label>
                                <Input id="year" v-model="form.year" type="number" required />
                                <InputError :message="form.errors.year" />
                            </div>
                            <div class="v2-field">
                                <Label for="basic_salary">{{ t('Basic Salary') }}</Label>
                                <div class="flex gap-2">
                                    <Input
                                        id="basic_salary"
                                        v-model="form.basic_salary"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        required
                                        class="min-w-0 flex-1"
                                    />
                                    <CurrencySelect
                                        v-model="form.currency"
                                        class="w-[9rem] shrink-0"
                                    />
                                </div>
                                <InputError :message="form.errors.basic_salary" />
                                <InputError :message="form.errors.currency" />
                            </div>
                            <div class="v2-field">
                                <Label for="bonus">{{ t('Bonus') }}</Label>
                                <Input
                                    id="bonus"
                                    v-model="form.bonus"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                />
                                <InputError :message="form.errors.bonus" />
                            </div>
                            <div class="v2-field">
                                <Label for="deduction">{{ t('Deduction') }}</Label>
                                <Input
                                    id="deduction"
                                    v-model="form.deduction"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                />
                                <InputError :message="form.errors.deduction" />
                            </div>
                            <div class="v2-field">
                                <Label for="status">{{ t('Status') }}</Label>
                                <select
                                    id="status"
                                    v-model="form.status"
                                    class="mis-form-select"
                                >
                                    <option value="pending">{{ t('Pending') }}</option>
                                    <option value="paid">{{ t('Paid') }}</option>
                                    <option value="cancelled">{{ t('Cancelled') }}</option>
                                </select>
                                <InputError :message="form.errors.status" />
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
                                    v-model="form.notes"
                                    rows="4"
                                    class="mis-form-textarea"
                                ></textarea>
                                <InputError :message="form.errors.notes" />
                            </div>
                        </div>
                        <div class="v2-form-split-block">
                            <div class="v2-form-total">
                                <span class="v2-form-total-label">{{ t('Net salary') }}</span>
                                <span class="v2-form-total-value">
                                    {{ money(netSalary, currency) }}
                                </span>
                            </div>
                        </div>
                        <div class="v2-form-split-block">
                            <h2 class="v2-form-split-block-title">{{ t('Attachments') }}</h2>
                            <RecordAttachments
                                attachable="salaries"
                                :attachable-id="isEdit ? item!.id : null"
                                :attachments="item?.attachments"
                                v-model:files="form.documents"
                                :error="form.errors.documents"
                            />
                        </div>
                    </div>
                </aside>
            </div>

            <div class="mis-form-actions">
                <Button type="submit" :disabled="form.processing">
                    {{ form.processing ? t('Saving…') : t('Save') }}
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
                    <Link href="/salaries">{{ t('Cancel') }}</Link>
                </Button>
                <Button
                    v-if="isEdit"
                    type="button"
                    variant="destructive"
                    class="ml-auto"
                    :disabled="form.processing"
                    @click="removeSalary"
                >
                    <Trash2 class="size-4" />
                    {{ form.processing ? t('Deleting…') : t('Delete') }}
                </Button>
            </div>
        </form>
    </V2FormPage>
</template>
