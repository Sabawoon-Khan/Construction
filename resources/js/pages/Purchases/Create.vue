<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { money } from '@/lib/utils';

const props = defineProps<{
    materials: {
        id: number;
        code: string;
        name: string;
        unit: string;
        current_stock: number;
    }[];
    suppliers: { id: number; name: string }[];
    projects: { id: number; name: string }[];
    preselectedMaterialId?: number | null;
}>();

const materialId = ref(props.preselectedMaterialId ? String(props.preselectedMaterialId) : '');
const quantity = ref('');
const unitCost = ref('');

const selectedMaterial = computed(() => props.materials.find((material) => String(material.id) === materialId.value));

const totalPrice = computed(() => {
    const qty = Number(quantity.value);
    const cost = Number(unitCost.value);

    if (!qty || !cost || qty <= 0 || cost < 0) {
        return null;
    }

    return qty * cost;
});

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Purchases', href: '/purchases' },
            { title: 'Record', href: '#' },
        ],
    },
});
</script>

<template>
    <Head title="Record Purchase" />
    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
        <Heading
            title="Record Purchase"
            description="Buy materials into stock. A cash expense is created automatically. Stock on the material updates after saving."
        />
        <Form
            action="/purchases"
            method="post"
            class="max-w-4xl space-y-6 rounded-xl border bg-card p-6"
            v-slot="{ errors, processing }"
        >
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="grid gap-2 sm:col-span-2">
                    <Label>Material</Label>
                    <select
                        v-model="materialId"
                        name="material_id"
                        required
                        class="h-9 rounded-md border border-input bg-transparent px-3 text-sm"
                    >
                        <option value="" disabled>Select material</option>
                        <option v-for="material in materials" :key="material.id" :value="String(material.id)">
                            {{ material.name }} ({{ material.code }}) — in stock: {{ material.current_stock }} {{ material.unit }}
                        </option>
                    </select>
                    <InputError :message="errors.material_id" />
                </div>

                <div class="grid gap-2">
                    <Label>Supplier</Label>
                    <select name="supplier_id" class="h-9 rounded-md border border-input bg-transparent px-3 text-sm">
                        <option value="">Optional</option>
                        <option v-for="supplier in suppliers" :key="supplier.id" :value="supplier.id">
                            {{ supplier.name }}
                        </option>
                    </select>
                    <InputError :message="errors.supplier_id" />
                </div>

                <div class="grid gap-2">
                    <Label>Project (direct delivery)</Label>
                    <select name="project_id" class="h-9 rounded-md border border-input bg-transparent px-3 text-sm">
                        <option value="">Company warehouse</option>
                        <option v-for="project in projects" :key="project.id" :value="project.id">
                            {{ project.name }}
                        </option>
                    </select>
                    <InputError :message="errors.project_id" />
                </div>

                <div class="grid gap-2">
                    <Label>{{ selectedMaterial ? `Quantity (${selectedMaterial.unit})` : 'Quantity' }}</Label>
                    <Input v-model="quantity" name="quantity" type="number" step="0.001" min="0.001" required />
                    <InputError :message="errors.quantity" />
                </div>

                <div class="grid gap-2">
                    <Label>{{ selectedMaterial ? `Unit cost (per ${selectedMaterial.unit})` : 'Unit cost' }}</Label>
                    <Input v-model="unitCost" name="unit_cost" type="number" step="0.01" min="0" required />
                    <InputError :message="errors.unit_cost" />
                </div>

                <div class="grid gap-2 sm:col-span-2">
                    <Label>Total price</Label>
                    <div class="flex h-9 items-center rounded-md border border-input bg-muted/30 px-3 text-sm font-semibold tabular-nums">
                        {{ totalPrice != null ? money(totalPrice) : '—' }}
                    </div>
                    <p class="text-xs text-muted-foreground">Calculated as quantity × unit cost. Saved as the expense amount.</p>
                </div>

                <div class="grid gap-2">
                    <Label>Date</Label>
                    <Input
                        name="transaction_date"
                        type="date"
                        required
                        :default-value="new Date().toISOString().slice(0, 10)"
                    />
                    <InputError :message="errors.transaction_date" />
                </div>

                <div class="grid gap-2">
                    <Label>Payment method</Label>
                    <select name="payment_method" class="h-9 rounded-md border border-input bg-transparent px-3 text-sm">
                        <option value="cash">Cash</option>
                        <option value="bank">Bank</option>
                        <option value="transfer">Transfer</option>
                    </select>
                    <InputError :message="errors.payment_method" />
                </div>

                <input type="hidden" name="currency" value="USD" />

                <div class="grid gap-2 sm:col-span-2">
                    <Label>Notes</Label>
                    <textarea name="notes" rows="2" class="rounded-md border border-input bg-transparent px-3 py-2 text-sm"></textarea>
                    <InputError :message="errors.notes" />
                </div>
            </div>

            <div class="flex gap-2">
                <Button type="submit" :disabled="processing">{{ processing ? 'Saving...' : 'Record purchase' }}</Button>
                <Button as-child variant="secondary"><Link href="/purchases">Cancel</Link></Button>
            </div>
        </Form>
    </div>
</template>
