<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Trash2 } from '@lucide/vue';
import { computed } from 'vue';
import InputError from '@/components/InputError.vue';
import RelatedCreateLink from '@/components/RelatedCreateLink.vue';
import RecordAttachments from '@/components/RecordAttachments.vue';
import { V2FormPage } from '@/components/v2';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useTranslations } from '@/composables/useTranslations';
import { historyBack } from '@/lib/historyBack';

const props = defineProps<{
    item: Record<string, any> | null;
    employees: { id: number; name: string; employee_no: string }[];
}>();

const isEdit = computed(() => !!props.item);
const { t } = useTranslations();

const form = useForm({
    employee_id: String(props.item?.employee_id ?? ''),
    employee_ids: [] as string[],
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

function removeAttendance() {
    if (!isEdit.value) {
        return;
    }

    form.delete(`/attendance/${props.item!.id}`);
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

    <V2FormPage
        :title="isEdit ? t('Edit attendance') : t('Record attendance')"
        :eyebrow="t('Attendance')"
        :description="isEdit ? t('Update this attendance.') : t('Record attendance to track employee presence.')"
        :back-href="isEdit ? '/attendance' : undefined"
    >
        <form class="mis-form-card" @submit.prevent="submit">
            <div class="mis-form-grid">
                <div v-if="isEdit" class="grid gap-2">
                    <div class="flex items-center justify-between gap-2">
                        <Label for="employee_id">{{ t('Employee') }}</Label>
                        <RelatedCreateLink href="/employees/create" :label="t('Add employee')" />
                    </div>
                    <select id="employee_id" v-model="form.employee_id" required class="mis-form-select">
                        <option value="" disabled>{{ t('Select employee') }}</option>
                        <option v-for="employee in employees" :key="employee.id" :value="String(employee.id)">{{ employee.name }}</option>
                    </select>
                    <InputError :message="form.errors.employee_id" />
                </div>
                <div v-else class="grid gap-2 mis-form-span">
                    <div class="flex items-center justify-between">
                        <Label>{{ t('Employees') }}</Label>
                        <div class="flex items-center gap-3 text-xs">
                            <RelatedCreateLink href="/employees/create" :label="t('Add employee')" />
                            <button type="button" class="text-primary hover:underline" @click="form.employee_ids = employees.map((e) => String(e.id))">{{ t('Select all') }}</button>
                            <button type="button" class="text-primary hover:underline" @click="form.employee_ids = []">{{ t('Clear') }}</button>
                        </div>
                    </div>
                    <div class="max-h-56 overflow-auto rounded-md border p-3">
                        <label
                            v-for="employee in employees"
                            :key="employee.id"
                            class="flex items-center gap-2 py-1 text-sm"
                        >
                            <input
                                v-model="form.employee_ids"
                                type="checkbox"
                                :value="String(employee.id)"
                                class="size-4"
                            />
                            <span>{{ employee.name }}</span>
                            <span class="text-muted-foreground">({{ employee.employee_no }})</span>
                        </label>
                    </div>
                    <InputError :message="form.errors.employee_ids" />
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
                    <select id="status" v-model="form.status" class="mis-form-select">
                        <option value="present">{{ t('Present') }}</option>
                        <option value="absent">{{ t('Absent') }}</option>
                        <option value="leave">{{ t('Leave') }}</option>
                        <option value="half_day">{{ t('Half day') }}</option>
                    </select>
                    <InputError :message="form.errors.status" />
                </div>
                <div class="grid gap-2 mis-form-span">
                    <Label for="notes">{{ t('Notes') }}</Label>
                    <textarea id="notes" v-model="form.notes" rows="3" class="mis-form-textarea"></textarea>
                    <InputError :message="form.errors.notes" />
                </div>
            </div>

            <RecordAttachments
                v-if="isEdit"
                attachable="attendance"
                :attachable-id="item!.id"
                :attachments="item?.attachments"
            />

            <div class="mis-form-actions">
                <Button type="submit" :disabled="form.processing">{{ form.processing ? t('Saving…') : t('Save') }}</Button>
                <Button v-if="!isEdit" type="button" variant="secondary" @click="historyBack">{{ t('Cancel') }}</Button>
                <Button v-else as-child variant="secondary">
                    <Link href="/attendance">{{ t('Cancel') }}</Link>
                </Button>
                <Button v-if="isEdit" type="button" variant="destructive" class="ml-auto" :disabled="form.processing" @click="removeAttendance">
                    <Trash2 class="size-4" />
                    {{ form.processing ? t('Deleting…') : t('Delete') }}
                </Button>
            </div>
        </form>
    </V2FormPage>
</template>
