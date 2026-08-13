<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

const props = defineProps<{ item: Record<string, any> | null }>();
const isEdit = computed(() => !!props.item);

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Materials', href: '/materials' },
            { title: 'Form', href: '#' },
        ],
    },
});
</script>

<template>
    <Head :title="isEdit ? 'Edit Material' : 'Create Material'" />
    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
        <Heading :title="isEdit ? 'Edit Material' : 'Create Material'" />
        <Form :action="isEdit ? `/materials/${item!.id}` : '/materials'" :method="isEdit ? 'put' : 'post'" class="max-w-3xl space-y-6 rounded-xl border bg-card p-6" v-slot="{ errors, processing }">
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="grid gap-2"><Label for="code">Code</Label><Input id="code" name="code" required :default-value="item?.code ?? ''" /><InputError :message="errors.code" /></div>
                <div class="grid gap-2"><Label for="name">Name</Label><Input id="name" name="name" required :default-value="item?.name ?? ''" /><InputError :message="errors.name" /></div>
                <div class="grid gap-2"><Label for="unit">Unit</Label><Input id="unit" name="unit" required :default-value="item?.unit ?? 'pcs'" /><InputError :message="errors.unit" /></div>
                <div class="grid gap-2"><Label for="currency">Currency</Label><Input id="currency" name="currency" required :default-value="item?.currency ?? 'USD'" /><InputError :message="errors.currency" /></div>
                <div class="grid gap-2"><Label for="unit_cost">Unit Cost</Label><Input id="unit_cost" name="unit_cost" type="number" step="0.01" :default-value="item?.unit_cost ?? 0" /><InputError :message="errors.unit_cost" /></div>
                <div class="grid gap-2"><Label for="minimum_stock">Minimum Stock</Label><Input id="minimum_stock" name="minimum_stock" type="number" step="0.001" :default-value="item?.minimum_stock ?? 0" /><InputError :message="errors.minimum_stock" /></div>
                <div class="flex items-center gap-2 sm:col-span-2">
                    <input id="is_active" type="checkbox" name="is_active" value="1" class="size-4 rounded border" :checked="item?.is_active ?? true" />
                    <Label for="is_active">Active</Label>
                </div>
            </div>
            <div class="flex gap-2">
                <Button type="submit" :disabled="processing">{{ processing ? 'Saving...' : 'Save' }}</Button>
                <Button as-child variant="secondary"><Link href="/materials">Cancel</Link></Button>
            </div>
        </Form>
    </div>
</template>
