<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

const props = defineProps<{
    item: Record<string, any> | null;
    projects: { id: number; name: string }[];
    categories: { id: number; name: string }[];
    suppliers: { id: number; name: string }[];
    contractors: { id: number; name: string }[];
}>();
const isEdit = computed(() => !!props.item);

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Expenses', href: '/expenses' },
            { title: 'Form', href: '#' },
        ],
    },
});
</script>

<template>
    <Head :title="isEdit ? 'Edit Expense' : 'Record Expense'" />
    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
        <Heading :title="isEdit ? 'Edit Expense' : 'Record Expense'" description="Labor, transport, permits and admin costs. Use Purchases for materials." />
        <Form :action="isEdit ? `/expenses/${item!.id}` : '/expenses'" :method="isEdit ? 'put' : 'post'" class="max-w-4xl space-y-6 rounded-xl border bg-card p-6" v-slot="{ errors, processing }">
            <input type="hidden" name="currency" :value="item?.currency ?? 'USD'" />
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="grid gap-2"><Label>Date</Label><Input name="expense_date" type="date" required :default-value="item?.expense_date?.slice?.(0,10) ?? new Date().toISOString().slice(0,10)" /><InputError :message="errors.expense_date" /></div>
                <div class="grid gap-2"><Label>Category</Label>
                    <select name="expense_category_id" required class="h-9 rounded-md border border-input bg-transparent px-3 text-sm" :value="item?.expense_category_id ?? ''">
                        <option value="" disabled>Select category</option>
                        <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
                    </select>
                    <p class="text-xs text-muted-foreground">Material purchases are recorded under <Link href="/purchases/create" class="text-primary hover:underline">Purchases</Link>. Payee is taken from supplier or contractor.</p>
                    <InputError :message="errors.expense_category_id" />
                </div>
                <div class="grid gap-2"><Label>Project</Label>
                    <select name="project_id" class="h-9 rounded-md border border-input bg-transparent px-3 text-sm" :value="item?.project_id ?? ''">
                        <option value="">Company-wide</option>
                        <option v-for="p in projects" :key="p.id" :value="p.id">{{ p.name }}</option>
                    </select>
                </div>
                <div class="grid gap-2"><Label>Amount</Label><Input name="amount" type="number" step="0.01" required :default-value="item?.amount ?? ''" /><InputError :message="errors.amount" /></div>
                <div class="grid gap-2"><Label>Payment Method</Label>
                    <select name="payment_method" class="h-9 rounded-md border border-input bg-transparent px-3 text-sm" :value="item?.payment_method ?? 'cash'">
                        <option value="cash">Cash</option>
                        <option value="bank">Bank</option>
                        <option value="transfer">Transfer</option>
                    </select>
                </div>
                <div class="grid gap-2"><Label>Supplier</Label>
                    <select name="supplier_id" class="h-9 rounded-md border border-input bg-transparent px-3 text-sm" :value="item?.supplier_id ?? ''">
                        <option value="">None</option>
                        <option v-for="s in suppliers" :key="s.id" :value="s.id">{{ s.name }}</option>
                    </select>
                </div>
                <div class="grid gap-2"><Label>Contractor</Label>
                    <select name="contractor_id" class="h-9 rounded-md border border-input bg-transparent px-3 text-sm" :value="item?.contractor_id ?? ''">
                        <option value="">None</option>
                        <option v-for="c in contractors" :key="c.id" :value="c.id">{{ c.name }}</option>
                    </select>
                </div>
                <div class="grid gap-2"><Label>Receipt / Reference</Label><Input name="reference_no" :default-value="item?.reference_no ?? ''" /></div>
                <input type="hidden" name="status" :value="item?.status ?? 'approved'" />
                <div class="grid gap-2 sm:col-span-2"><Label>Description</Label>
                    <textarea name="description" rows="3" class="rounded-md border border-input bg-transparent px-3 py-2 text-sm" :value="item?.description ?? ''"></textarea>
                </div>
            </div>
            <div class="flex gap-2">
                <Button type="submit" :disabled="processing">{{ processing ? 'Saving...' : 'Save Expense' }}</Button>
                <Button as-child variant="secondary"><Link href="/expenses">Cancel</Link></Button>
            </div>
        </Form>
    </div>
</template>
