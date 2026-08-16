<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft } from '@lucide/vue';
import { computed, watch } from 'vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useTranslations } from '@/composables/useTranslations';
import { money } from '@/lib/utils';

const props = defineProps<{
    item: Record<string, any> | null;
    employees: { id: number; name: string; employee_no: string; basic_salary: number | null }[];
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
});

const netSalary = computed(() => {
    const basic = Number(form.basic_salary) || 0;
    const bonus = Number(form.bonus) || 0;
    const deduction = Number(form.deduction) || 0;
    return Math.max(0, basic + bonus - deduction);
});

watch(() => form.employee_id, (id: string) => {
    if (isEdit.value) return;
    const employee = props.employees.find((e: { id: number; basic_salary: number | null }) => String(e.id) === id);
    if (employee && employee.basic_salary !== null && employee.basic_salary !== undefined) {
        form.basic_salary = String(employee.basic_salary);
    }
}, { immediate: true });

function submit() {
    if (isEdit.value) {
        form.put(`/salaries/${props.item!.id}`);
    } else {
        form.post('/salaries');
    }
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

    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
        <div class="space-y-3">
            <Button as-child variant="ghost" size="sm" class="-ml-2 w-fit">
                <Link href="/salaries"><ArrowLeft class="size-4" /> {{ t('Back') }}</Link>
            </Button>
            <Heading
                :title="isEdit ? t('Edit salary') : t('Add salary')"
                :description="t('Create a fixed monthly salary record for an employee.')"
            />
        </div>

        <form class="max-w-3xl space-y-6 rounded-xl border border-sidebar-border/70 bg-card p-6" @submit.prevent="submit">
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="grid gap-2">
                    <Label for="employee_id">{{ t('Employee') }}</Label>
                    <select id="employee_id" v-model="form.employee_id" required class="h-9 rounded-md border border-input bg-transparent px-3 text-sm" :disabled="isEdit">
                        <option value="" disabled>{{ t('Select employee') }}</option>
                        <option v-for="employee in employees" :key="employee.id" :value="String(employee.id)">{{ employee.name }}</option>
                    </select>
                    <InputError :message="form.errors.employee_id" />
                </div>
                <div class="grid gap-2">
                    <Label for="payment_date">{{ t('Payment Date') }}</Label>
                    <Input id="payment_date" v-model="form.payment_date" type="date" />
                    <InputError :message="form.errors.payment_date" />
                </div>
                <div class="grid gap-2">
                    <Label for="month">{{ t('Month') }}</Label>
                    <select id="month" v-model="form.month" required class="h-9 rounded-md border border-input bg-transparent px-3 text-sm">
                        <option v-for="m in 12" :key="m" :value="m">{{ m }}</option>
                    </select>
                    <InputError :message="form.errors.month" />
                </div>
                <div class="grid gap-2">
                    <Label for="year">{{ t('Year') }}</Label>
                    <Input id="year" v-model="form.year" type="number" required />
                    <InputError :message="form.errors.year" />
                </div>
                <div class="grid gap-2">
                    <Label for="basic_salary">{{ t('Basic Salary') }}</Label>
                    <Input id="basic_salary" v-model="form.basic_salary" type="number" step="0.01" min="0" required />
                    <InputError :message="form.errors.basic_salary" />
                </div>
                <div class="grid gap-2">
                    <Label for="bonus">{{ t('Bonus') }}</Label>
                    <Input id="bonus" v-model="form.bonus" type="number" step="0.01" min="0" />
                    <InputError :message="form.errors.bonus" />
                </div>
                <div class="grid gap-2">
                    <Label for="deduction">{{ t('Deduction') }}</Label>
                    <Input id="deduction" v-model="form.deduction" type="number" step="0.01" min="0" />
                    <InputError :message="form.errors.deduction" />
                </div>
                <div class="grid gap-2">
                    <Label for="status">{{ t('Status') }}</Label>
                    <select id="status" v-model="form.status" class="h-9 rounded-md border border-input bg-transparent px-3 text-sm">
                        <option value="pending">{{ t('Pending') }}</option>
                        <option value="paid">{{ t('Paid') }}</option>
                        <option value="cancelled">{{ t('Cancelled') }}</option>
                    </select>
                    <InputError :message="form.errors.status" />
                </div>
                <div class="grid gap-2 sm:col-span-2">
                    <Label for="notes">{{ t('Notes') }}</Label>
                    <textarea id="notes" v-model="form.notes" rows="3" class="flex min-h-[80px] w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs"></textarea>
                    <InputError :message="form.errors.notes" />
                </div>
            </div>

            <div class="flex items-center justify-between rounded-lg border bg-muted/30 px-4 py-3">
                <span class="text-sm text-muted-foreground">{{ t('Net salary') }}</span>
                <span class="text-lg font-semibold tabular-nums">{{ money(netSalary) }}</span>
            </div>

            <div class="flex gap-2">
                <Button type="submit" :disabled="form.processing">{{ form.processing ? t('Saving…') : t('Save') }}</Button>
                <Button as-child variant="secondary">
                    <Link href="/salaries">{{ t('Cancel') }}</Link>
                </Button>
            </div>
        </form>
    </div>
</template>
