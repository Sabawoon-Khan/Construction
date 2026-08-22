<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import InputError from '@/components/InputError.vue';
import RelatedCreateLink from '@/components/RelatedCreateLink.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useTranslations } from '@/composables/useTranslations';

type StockRow = {
    project_id: number;
    material_id: number;
    on_hand: number;
};

type Material = {
    id: number;
    name: string;
    code: string;
    unit?: { unit_name: string } | null;
};

type EditingIssue = {
    id: number;
    project_id: number;
    material_id: number;
    quantity: number;
    transaction_date?: string | null;
};

const props = defineProps<{
    open: boolean;
    projects: { id: number; name: string }[];
    materials: Material[];
    stockRows: StockRow[];
    prefill?: { project_id?: number; material_id?: number } | null;
    editing?: EditingIssue | null;
}>();

const emit = defineEmits<{
    'update:open': [value: boolean];
}>();

const { t } = useTranslations();
const projectId = ref('');
const materialId = ref('');
const quantity = ref('');
const transactionDate = ref('');

const isEdit = computed(() => !!props.editing?.id);
const formAction = computed(() =>
    isEdit.value ? `/inventory/${props.editing!.id}` : '/inventory',
);
const formMethod = computed(() => (isEdit.value ? 'put' : 'post'));

watch(
    () => [props.open, props.prefill, props.editing] as const,
    () => {
        if (!props.open) {
            return;
        }

        projectId.value = props.editing?.project_id
            ? String(props.editing.project_id)
            : props.prefill?.project_id
              ? String(props.prefill.project_id)
              : '';
        materialId.value = props.editing?.material_id
            ? String(props.editing.material_id)
            : props.prefill?.material_id
              ? String(props.prefill.material_id)
              : '';
        quantity.value = props.editing ? String(props.editing.quantity) : '';
        transactionDate.value =
            props.editing?.transaction_date?.slice?.(0, 10)
            ?? new Date().toISOString().slice(0, 10);
    },
    { immediate: true },
);

const availableMaterials = computed(() => {
    if (isEdit.value) {
        return props.materials.filter((material) => String(material.id) === materialId.value);
    }

    if (!projectId.value) {
        const ids = new Set(
            props.stockRows.filter((row) => row.on_hand > 0).map((row) => row.material_id),
        );

        return props.materials.filter((material) => ids.has(material.id));
    }

    const ids = new Set(
        props.stockRows
            .filter((row) => String(row.project_id) === projectId.value && row.on_hand > 0)
            .map((row) => row.material_id),
    );

    return props.materials.filter((material) => ids.has(material.id));
});

const onHandForSelection = computed(() => {
    if (!projectId.value || !materialId.value) {
        return null;
    }

    const row = props.stockRows.find(
        (item) =>
            String(item.project_id) === projectId.value &&
            String(item.material_id) === materialId.value,
    );

    return row?.on_hand ?? null;
});

const maxQuantity = computed(() => {
    if (isEdit.value && props.editing) {
        return (onHandForSelection.value ?? 0) + props.editing.quantity;
    }

    return onHandForSelection.value ?? undefined;
});

function close() {
    emit('update:open', false);
}
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent class="sm:max-w-lg">
            <DialogHeader>
                <DialogTitle>
                    {{ isEdit ? t('Edit stock issue') : t('Issue from stock') }}
                </DialogTitle>
                <DialogDescription>
                    {{
                        isEdit
                            ? t('Update the issued quantity or date. Stock on hand will be recalculated.')
                            : t('Reduce on-hand quantity for a project. Expense was recorded at purchase.')
                    }}
                </DialogDescription>
            </DialogHeader>

            <Form
                :action="formAction"
                :method="formMethod"
                class="grid gap-4"
                v-slot="{ errors, processing }"
                @success="close"
            >
                <div class="grid gap-2">
                    <div class="flex items-center justify-between gap-2">
                        <Label>{{ t('Project') }}</Label>
                        <RelatedCreateLink v-if="!isEdit" href="/projects/create" :label="t('Add project')" />
                    </div>
                    <select
                        v-model="projectId"
                        name="project_id"
                        required
                        class="h-9 rounded-md border border-input bg-background px-3 text-sm"
                        :disabled="isEdit"
                    >
                        <option value="" disabled>{{ t('Select project') }}</option>
                        <option v-for="project in projects" :key="project.id" :value="String(project.id)">
                            {{ project.name }}
                        </option>
                    </select>
                    <InputError :message="errors.project_id" />
                </div>

                <div class="grid gap-2">
                    <div class="flex items-center justify-between gap-2">
                        <Label>{{ t('Material') }}</Label>
                        <RelatedCreateLink v-if="!isEdit" href="/materials/create" :label="t('Add material')" />
                    </div>
                    <select
                        v-model="materialId"
                        name="material_id"
                        required
                        class="h-9 rounded-md border border-input bg-background px-3 text-sm"
                        :disabled="isEdit || !projectId"
                    >
                        <option value="" disabled>{{ t('Select material') }}</option>
                        <option v-for="material in availableMaterials" :key="material.id" :value="String(material.id)">
                            {{ material.name }}{{ material.unit?.unit_name ? ` (${material.unit.unit_name})` : '' }}
                        </option>
                    </select>
                    <InputError :message="errors.material_id" />
                    <p v-if="onHandForSelection != null" class="text-xs text-muted-foreground">
                        {{ t('On hand') }}: {{ onHandForSelection }}
                        <span v-if="isEdit && maxQuantity != null">
                            · {{ t('Max issue qty') }}: {{ maxQuantity }}
                        </span>
                    </p>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="grid gap-2">
                        <Label>{{ t('Qty to issue') }}</Label>
                        <Input
                            v-model="quantity"
                            name="quantity"
                            type="number"
                            step="0.001"
                            min="0.001"
                            :max="maxQuantity ?? undefined"
                            required
                        />
                        <InputError :message="errors.quantity" />
                    </div>
                    <div class="grid gap-2">
                        <Label>{{ t('Date') }}</Label>
                        <Input
                            v-model="transactionDate"
                            name="transaction_date"
                            type="date"
                        />
                        <InputError :message="errors.transaction_date" />
                    </div>
                </div>

                <DialogFooter class="gap-2 sm:gap-0">
                    <Button type="button" variant="outline" @click="close">
                        {{ t('Cancel') }}
                    </Button>
                    <Button type="submit" :disabled="processing || !projectId || !materialId">
                        {{ processing ? t('Saving…') : isEdit ? t('Save changes') : t('Issue from stock') }}
                    </Button>
                </DialogFooter>
            </Form>
        </DialogContent>
    </Dialog>
</template>
