<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Plus, Trash2 } from '@lucide/vue';
import { computed } from 'vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import CurrencySelect from '@/components/CurrencySelect.vue';
import { useCurrency } from '@/composables/useCurrency';
import { useTranslations } from '@/composables/useTranslations';
import { money } from '@/lib/utils';

type Line = { material_id: string; quantity: string; unit_cost: string };

const props = defineProps<{
    item: any;
    projects: { id: number; name: string }[];
    suppliers: { id: number; name: string; supplier_no: string }[];
    materials: { id: number; name: string; code: string; unit_cost: number; unit?: { unit_name: string } | null }[];
    project_id?: number | null;
}>();

const isEdit = computed(() => !!props.item);
const { t } = useTranslations();

const { baseCurrency } = useCurrency();
const form = useForm({
    project_id: String(props.item?.project_id ?? props.project_id ?? ''),
    supplier_id: String(props.item?.supplier_id ?? ''),
    purchase_date: props.item?.purchase_date?.slice?.(0, 10) ?? new Date().toISOString().slice(0, 10),
    reference_no: String(props.item?.reference_no ?? ''),
    notes: String(props.item?.notes ?? ''),
    currency: props.item?.currency ?? baseCurrency.value,
    paid_now: '0',
    payment_method: 'cash',
    items: (props.item?.items?.map((row: any) => ({
        material_id: String(row.material_id),
        quantity: String(row.quantity),
        unit_cost: String(row.unit_cost),
    })) ?? [{ material_id: '', quantity: '', unit_cost: '' }]) as Line[],
});

const lineTotal = (line: Line) => {
    const qty = Number(line.quantity);
    const cost = Number(line.unit_cost);
    if (!Number.isFinite(qty) || !Number.isFinite(cost)) {
        return 0;
    }

    return qty * cost;
};

const grandTotal = computed(() => form.items.reduce((sum, line) => sum + lineTotal(line), 0));

function addLine() {
    form.items.push({ material_id: '', quantity: '', unit_cost: '' });
}

function removeLine(index: number) {
    if (form.items.length === 1) {
        form.items[0] = { material_id: '', quantity: '', unit_cost: '' };

        return;
    }

    form.items.splice(index, 1);
}

function onMaterialChange(index: number) {
    const material = props.materials.find((m) => String(m.id) === form.items[index].material_id);
    if (material && !form.items[index].unit_cost) {
        form.items[index].unit_cost = String(material.unit_cost ?? '');
    }
}

function submit() {
    if (isEdit.value) {
        form.transform((data) => ({
            purchase_date: data.purchase_date,
            reference_no: data.reference_no,
            notes: data.notes,
        })).put(`/purchases/${props.item.id}`);

        return;
    }

    form.post('/purchases');
}

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Purchases', href: '/purchases' },
            { title: 'Form', href: '/purchases/create' },
        ],
    },
});
</script>

<template>
    <Head :title="isEdit ? t('Edit purchase') : t('New purchase')" />
    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
        <div class="space-y-3">
            <Button as-child variant="ghost" size="sm" class="-ml-2 w-fit">
                <Link href="/purchases"><ArrowLeft class="size-4" /> {{ t('Back') }}</Link>
            </Button>
            <Heading
                :title="isEdit ? t('Edit purchase') : t('New purchase')"
                :description="isEdit ? t('Line items cannot be changed after stock is recorded.') : t('The full amount is a project expense now. Paid now can be zero.')"
            />
        </div>

        <form class="max-w-5xl space-y-6 rounded-xl border border-sidebar-border/70 bg-card p-6" @submit.prevent="submit">
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="grid gap-2">
                    <Label>{{ t('Project') }}</Label>
                    <select v-model="form.project_id" :disabled="isEdit" required class="h-9 rounded-md border border-input bg-transparent px-3 text-sm">
                        <option value="" disabled>{{ t('Select project') }}</option>
                        <option v-for="project in projects" :key="project.id" :value="String(project.id)">{{ project.name }}</option>
                    </select>
                    <InputError :message="form.errors.project_id" />
                </div>
                <div class="grid gap-2">
                    <Label>{{ t('Supplier') }}</Label>
                    <select v-model="form.supplier_id" :disabled="isEdit" required class="h-9 rounded-md border border-input bg-transparent px-3 text-sm">
                        <option value="" disabled>{{ t('Select supplier') }}</option>
                        <option v-for="supplier in suppliers" :key="supplier.id" :value="String(supplier.id)">{{ supplier.name }}</option>
                    </select>
                    <p class="text-xs text-muted-foreground">
                        <Link href="/suppliers/create" class="hover:underline">{{ t('Add supplier') }}</Link>
                    </p>
                    <InputError :message="form.errors.supplier_id" />
                </div>
                <div class="grid gap-2">
                    <Label>{{ t('Date') }}</Label>
                    <Input v-model="form.purchase_date" type="date" required />
                    <InputError :message="form.errors.purchase_date" />
                </div>
                <div class="grid gap-2">
                    <Label>{{ t('Reference') }}</Label>
                    <Input v-model="form.reference_no" :placeholder="t('Bill / invoice no')" />
                    <InputError :message="form.errors.reference_no" />
                </div>
                <div class="grid gap-2">
                    <Label for="currency">{{ t('Currency') }}</Label>
                    <CurrencySelect id="currency" v-model="form.currency" />
                    <InputError :message="form.errors.currency" />
                </div>
            </div>

            <div v-if="!isEdit" class="space-y-3">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-semibold">{{ t('Materials') }}</h3>
                    <Button type="button" variant="outline" size="sm" @click="addLine">
                        <Plus class="size-4" /> {{ t('Add line') }}
                    </Button>
                </div>
                <InputError :message="form.errors.items" />
                <div class="overflow-x-auto rounded-lg border">
                    <table class="min-w-full text-sm">
                        <thead class="bg-muted/40 text-left text-muted-foreground">
                            <tr>
                                <th class="px-3 py-2 font-medium">{{ t('Material') }}</th>
                                <th class="px-3 py-2 font-medium">{{ t('Qty') }}</th>
                                <th class="px-3 py-2 font-medium">{{ t('Unit cost') }}</th>
                                <th class="px-3 py-2 font-medium">{{ t('Line total') }}</th>
                                <th class="px-3 py-2"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(line, index) in form.items" :key="index" class="border-t">
                                <td class="px-3 py-2">
                                    <select v-model="line.material_id" required class="h-9 w-full min-w-[180px] rounded-md border px-2 text-sm" @change="onMaterialChange(index)">
                                        <option value="">{{ t('Select') }}</option>
                                        <option v-for="material in materials" :key="material.id" :value="String(material.id)">
                                            {{ material.name }}{{ material.unit?.unit_name ? ` (${material.unit.unit_name})` : '' }}
                                        </option>
                                    </select>
                                </td>
                                <td class="px-3 py-2">
                                    <Input v-model="line.quantity" type="number" step="0.001" min="0.001" required class="min-w-[100px]" />
                                </td>
                                <td class="px-3 py-2">
                                    <Input v-model="line.unit_cost" type="number" step="0.01" min="0" required class="min-w-[100px]" />
                                </td>
                                <td class="px-3 py-2 tabular-nums">{{ money(lineTotal(line), form.currency) }}</td>
                                <td class="px-3 py-2">
                                    <Button type="button" variant="ghost" size="sm" @click="removeLine(index)">
                                        <Trash2 class="size-4" />
                                    </Button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <p class="text-xs text-muted-foreground">
                    <Link href="/materials/create" class="hover:underline">{{ t('Add material') }}</Link>
                </p>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div v-if="!isEdit" class="grid gap-2">
                    <Label>{{ t('Paid now') }}</Label>
                    <Input v-model="form.paid_now" type="number" step="0.01" min="0" />
                    <p class="text-xs text-muted-foreground">{{ t('Leave 0 if you will pay the supplier later.') }}</p>
                    <InputError :message="form.errors.paid_now" />
                </div>
                <div v-if="!isEdit" class="grid gap-2">
                    <Label>{{ t('Payment method') }}</Label>
                    <select v-model="form.payment_method" class="h-9 rounded-md border px-3 text-sm">
                        <option value="cash">{{ t('Cash') }}</option>
                        <option value="bank">{{ t('Bank') }}</option>
                        <option value="transfer">{{ t('Transfer') }}</option>
                    </select>
                </div>
                <div class="grid gap-2 sm:col-span-2">
                    <Label>{{ t('Notes') }}</Label>
                    <textarea v-model="form.notes" rows="3" class="flex min-h-[80px] w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs"></textarea>
                </div>
            </div>

            <div v-if="!isEdit" class="flex items-center justify-between rounded-lg border bg-muted/30 px-4 py-3">
                <span class="text-sm text-muted-foreground">{{ t('Purchase total (project expense)') }}</span>
                <span class="text-lg font-semibold tabular-nums">{{ money(grandTotal, form.currency) }}</span>
            </div>

            <div class="flex gap-2">
                <Button type="submit" :disabled="form.processing">{{ form.processing ? t('Saving…') : t('Save') }}</Button>
                <Button as-child variant="secondary">
                    <Link href="/purchases">{{ t('Cancel') }}</Link>
                </Button>
            </div>
        </form>
    </div>
</template>
