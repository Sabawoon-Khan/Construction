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
        average_unit_cost: number;
        stock_value: number;
    }[];
    projects: { id: number; name: string }[];
    preselectedMaterialId?: number | null;
}>();

const materialId = ref(props.preselectedMaterialId ? String(props.preselectedMaterialId) : '');
const quantity = ref('');

const selectedMaterial = computed(() => props.materials.find((material) => String(material.id) === materialId.value));

const quantityNumber = computed(() => Number(quantity.value));

const exceedsStock = computed(() => {
    if (!selectedMaterial.value || !quantity.value) return false;

    return quantityNumber.value > selectedMaterial.value.current_stock;
});

const issueValue = computed(() => {
    if (!selectedMaterial.value || !quantity.value || quantityNumber.value <= 0) {
        return null;
    }

    return quantityNumber.value * selectedMaterial.value.average_unit_cost;
});

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Purchases', href: '/purchases' },
            { title: 'Issue', href: '#' },
        ],
    },
});
</script>

<template>
    <Head title="Issue to Project" />
    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
        <Heading title="Issue to Project" description="Issue materials from stock to a project. No new cash expense is created." />
        <Form
            action="/purchases/issues"
            method="post"
            class="max-w-3xl space-y-6 rounded-xl border bg-card p-6"
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
                            {{ material.name }} — available: {{ material.current_stock }} {{ material.unit }}
                        </option>
                    </select>
                    <InputError :message="errors.material_id" />
                </div>

                <div v-if="selectedMaterial" class="sm:col-span-2 rounded-lg border bg-muted/30 px-4 py-3 text-sm">
                    <div>
                        Available stock:
                        <span class="font-semibold tabular-nums">{{ selectedMaterial.current_stock }} {{ selectedMaterial.unit }}</span>
                    </div>
                    <div class="mt-1 text-muted-foreground">
                        Stock value:
                        <span class="font-medium tabular-nums">{{ money(selectedMaterial.stock_value) }}</span>
                        at {{ money(selectedMaterial.average_unit_cost) }} / {{ selectedMaterial.unit }}
                    </div>
                </div>

                <div class="grid gap-2 sm:col-span-2">
                    <Label>Project</Label>
                    <select name="project_id" required class="h-9 rounded-md border border-input bg-transparent px-3 text-sm">
                        <option value="" disabled>Select project</option>
                        <option v-for="project in projects" :key="project.id" :value="project.id">
                            {{ project.name }}
                        </option>
                    </select>
                    <InputError :message="errors.project_id" />
                </div>

                <div class="grid gap-2">
                    <Label>{{ selectedMaterial ? `Quantity (${selectedMaterial.unit})` : 'Quantity' }}</Label>
                    <Input
                        v-model="quantity"
                        name="quantity"
                        type="number"
                        step="0.001"
                        min="0.001"
                        :max="selectedMaterial?.current_stock ?? undefined"
                        required
                    />
                    <p v-if="selectedMaterial" class="text-xs text-muted-foreground">
                        Maximum: {{ selectedMaterial.current_stock }} {{ selectedMaterial.unit }}
                    </p>
                    <p v-if="exceedsStock" class="text-xs text-destructive">
                        Cannot issue more than available stock.
                    </p>
                    <InputError :message="errors.quantity" />
                </div>

                <div class="grid gap-2">
                    <Label>Issue value</Label>
                    <div class="flex h-9 items-center rounded-md border border-input bg-muted/30 px-3 text-sm font-semibold tabular-nums">
                        {{ issueValue != null ? money(issueValue) : '—' }}
                    </div>
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

                <div class="grid gap-2 sm:col-span-2">
                    <Label>Notes</Label>
                    <textarea name="notes" rows="2" class="rounded-md border border-input bg-transparent px-3 py-2 text-sm"></textarea>
                    <InputError :message="errors.notes" />
                </div>
            </div>

            <div class="flex gap-2">
                <Button type="submit" :disabled="processing || exceedsStock">{{ processing ? 'Saving...' : 'Issue to project' }}</Button>
                <Button as-child variant="secondary"><Link href="/purchases">Cancel</Link></Button>
            </div>
        </Form>
    </div>
</template>
