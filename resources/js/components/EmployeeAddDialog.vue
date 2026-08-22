<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import InputError from '@/components/InputError.vue';
import RelatedCreateLink from '@/components/RelatedCreateLink.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useTranslations } from '@/composables/useTranslations';

const props = defineProps<{
    open: boolean;
    projectId: number;
    availableEmployees: {
        id: number;
        name: string;
        employee_no?: string;
        department?: string;
        designation?: string;
        working_hours?: string;
        phone?: string;
    }[];
}>();

const emit = defineEmits<{
    'update:open': [value: boolean];
}>();

const { t } = useTranslations();
const selectedEmployeeId = ref('');
const startedAt = ref(new Date().toISOString().slice(0, 10));
const roleOnProject = ref('');
const workingHours = ref('');
const notes = ref('');

watch(
    () => props.open,
    (isOpen) => {
        if (!isOpen) {
            return;
        }

        selectedEmployeeId.value = '';
        startedAt.value = new Date().toISOString().slice(0, 10);
        roleOnProject.value = '';
        workingHours.value = '';
        notes.value = '';
    },
);

watch(selectedEmployeeId, (id) => {
    const employee = props.availableEmployees.find((entry) => String(entry.id) === id);
    if (!employee) {
        return;
    }

    roleOnProject.value = employee.designation || '';
    workingHours.value = employee.working_hours || '';
});

function employeeLabel(employee: {
    name: string;
    employee_no?: string;
    department?: string;
    designation?: string;
    phone?: string;
}): string {
    const parts = [employee.name];

    if (employee.employee_no) {
        parts.push(employee.employee_no);
    }

    if (employee.designation || employee.department) {
        parts.push(employee.designation || employee.department || '');
    }

    if (employee.phone) {
        parts.push(employee.phone);
    }

    return parts.filter(Boolean).join(' · ');
}
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>{{ t('Add employee to project') }}</DialogTitle>
            </DialogHeader>

            <Form
                :action="`/projects/${projectId}/employees`"
                method="post"
                class="grid gap-4"
                v-slot="{ errors, processing }"
            >
                <div class="grid gap-2">
                    <div class="flex items-center justify-between gap-2">
                        <Label for="add_employee_id">{{ t('Employee') }}</Label>
                        <RelatedCreateLink
                            href="/employees/create"
                            :label="t('New employee')"
                        />
                    </div>
                    <select
                        id="add_employee_id"
                        v-model="selectedEmployeeId"
                        name="employee_id"
                        required
                        class="h-9 w-full rounded-md border border-input bg-transparent px-3 text-sm shadow-xs"
                    >
                        <option value="" disabled>
                            {{ t('Select employee') }}
                        </option>
                        <option
                            v-for="employee in availableEmployees"
                            :key="employee.id"
                            :value="String(employee.id)"
                        >
                            {{ employeeLabel(employee) }}
                        </option>
                    </select>
                    <InputError :message="errors.employee_id" />
                    <p
                        v-if="!availableEmployees.length"
                        class="text-sm text-muted-foreground"
                    >
                        {{ t('No employees available') }}
                    </p>
                    <p v-else class="text-sm text-muted-foreground">
                        {{ t('Create the employee first, then assign them here.') }}
                    </p>
                </div>

                <div class="grid gap-2">
                    <Label for="add_started_at">{{ t('Started on project') }}</Label>
                    <Input
                        id="add_started_at"
                        v-model="startedAt"
                        name="started_at"
                        type="date"
                        required
                    />
                    <InputError :message="errors.started_at" />
                </div>

                <div class="grid gap-2">
                    <Label for="add_role_on_project">{{ t('Role on project') }}</Label>
                    <Input
                        id="add_role_on_project"
                        v-model="roleOnProject"
                        name="role_on_project"
                        :placeholder="t('e.g. Site engineer')"
                    />
                    <InputError :message="errors.role_on_project" />
                </div>

                <div class="grid gap-2">
                    <Label for="add_working_hours">{{ t('Working hours') }}</Label>
                    <Input
                        id="add_working_hours"
                        v-model="workingHours"
                        name="working_hours"
                        :placeholder="t('e.g. 8:00 AM – 5:00 PM')"
                    />
                    <InputError :message="errors.working_hours" />
                </div>

                <div class="grid gap-2">
                    <Label for="add_notes">{{ t('Notes') }}</Label>
                    <textarea
                        id="add_notes"
                        v-model="notes"
                        name="notes"
                        rows="3"
                        class="min-h-20 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs"
                    />
                    <InputError :message="errors.notes" />
                </div>

                <DialogFooter class="gap-2 sm:justify-end">
                    <Button
                        type="button"
                        variant="outline"
                        @click="emit('update:open', false)"
                    >
                        {{ t('Cancel') }}
                    </Button>
                    <Button
                        type="submit"
                        :disabled="processing || !availableEmployees.length"
                    >
                        {{ processing ? t('Saving…') : t('Add to project') }}
                    </Button>
                </DialogFooter>
            </Form>
        </DialogContent>
    </Dialog>
</template>
