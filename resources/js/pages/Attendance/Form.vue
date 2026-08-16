<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
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
    employees: { id: number; name: string; employee_no: string }[];
}>();

const isEdit = computed(() => !!props.item);
const { t } = useTranslations();

const form = useForm({
    employee_id: String(props.item?.employee_id ?? ''),
    date: props.item?.date?.slice?.(0, 10) ?? new Date().toISOString().slice(0, 10),
    check_in: props.item?.check_in?.slice?.(0, 5) ?? '',
    check_out: props.item?.check_out?.slice?.(0, 5) ?? '',
    status: props.item?.status ?? 'present',
    notes: props.item?.notes ?? '',
});

function submit() {
    if (isEdit.value) {
        form.put(`/attendance/${props.item!.id}`);
    } else {
        form.post('/attendance');
    }
}

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Attendance', href: '/attendance' },
            { title: 'Form', href: '/attendance/create' },
        ],
    },
});
</script>

<template>
    <Head :title="isEdit ? t('Edit attendance') : t('Record attendance')" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
        <div class="space-y-3">
            <Button as-child variant="ghost" size="sm" class="-ml-2 w-fit">
                <Link href="/attendance"><ArrowLeft class="size-4" /> {{ t('Back') }}</Link>
            </Button>
            <Heading
                :title="isEdit ? t('Edit attendance') : t('Record attendance')"
                :description="t('Select the employee and record their attendance for the day.')"
            />
        </div>

        <form class="max-w-3xl space-y-6 rounded-xl border border-sidebar-border/70 bg-card p-6" @submit.prevent="submit">
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="grid gap-2">
                    <Label for="employee_id">{{ t('Employee') }}</Label>
                    <select id="employee_id" v-model="form.employee_id" required class="h-9 rounded-md border border-input bg-transparent px-3 text-sm">
                        <option value="" disabled>{{ t('Select employee') }}</option>
                        <option v-for="employee in employees" :key="employee.id" :value="String(employee.id)">{{ employee.name }}</option>
                    </select>
                    <InputError :message="form.errors.employee_id" />
                </div>
                <div class="grid gap-2">
                    <Label for="date">{{ t('Date') }}</Label>
                    <Input id="date" v-model="form.date" type="date" required />
                    <InputError :message="form.errors.date" />
                </div>
                <div class="grid gap-2">
                    <Label for="check_in">{{ t('Check in') }}</Label>
                    <Input id="check_in" v-model="form.check_in" type="time" />
                    <InputError :message="form.errors.check_in" />
                </div>
                <div class="grid gap-2">
                    <Label for="check_out">{{ t('Check out') }}</Label>
                    <Input id="check_out" v-model="form.check_out" type="time" />
                    <InputError :message="form.errors.check_out" />
                </div>
                <div class="grid gap-2">
                    <Label for="status">{{ t('Status') }}</Label>
                    <select id="status" v-model="form.status" class="h-9 rounded-md border border-input bg-transparent px-3 text-sm">
                        <option value="present">{{ t('Present') }}</option>
                        <option value="absent">{{ t('Absent') }}</option>
                        <option value="leave">{{ t('Leave') }}</option>
                        <option value="half_day">{{ t('Half day') }}</option>
                    </select>
                    <InputError :message="form.errors.status" />
                </div>
                <div class="grid gap-2 sm:col-span-2">
                    <Label for="notes">{{ t('Notes') }}</Label>
                    <textarea id="notes" v-model="form.notes" rows="3" class="flex min-h-[80px] w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs"></textarea>
                    <InputError :message="form.errors.notes" />
                </div>
            </div>

            <div class="flex gap-2">
                <Button type="submit" :disabled="form.processing">{{ form.processing ? t('Saving…') : t('Save') }}</Button>
                <Button as-child variant="secondary">
                    <Link href="/attendance">{{ t('Cancel') }}</Link>
                </Button>
            </div>
        </form>
    </div>
</template>
