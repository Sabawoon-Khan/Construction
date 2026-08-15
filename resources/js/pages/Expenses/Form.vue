<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { ArrowLeft } from '@lucide/vue';
import { computed } from 'vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

const props = defineProps<{
    item: Record<string, any> | null;
    categories?: { id: number; name: string; code: string }[];
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
        <div class="space-y-3">
            <Button as-child variant="ghost" size="sm" class="-ml-2 w-fit">
                <Link href="/expenses"><ArrowLeft class="size-4" /> Back</Link>
            </Button>
            <Heading
                :title="isEdit ? 'Edit Expense' : 'Record Expense'"
                description="Company-wide expenses not tied to a project."
            />
        </div>
        <Form
            :action="isEdit ? `/expenses/${item!.id}` : '/expenses'"
            :method="isEdit ? 'put' : 'post'"
            class="max-w-3xl space-y-6 rounded-xl border bg-card p-6"
            v-slot="{ errors, processing }"
        >
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="grid gap-2 sm:col-span-2">
                    <Label for="description">Description</Label>
                    <textarea
                        id="description"
                        name="description"
                        rows="3"
                        required
                        class="rounded-md border border-input bg-transparent px-3 py-2 text-sm"
                        :value="item?.description ?? ''"
                    />
                    <InputError :message="errors.description" />
                </div>
                <div class="grid gap-2">
                    <Label for="expense_date">Date</Label>
                    <Input
                        id="expense_date"
                        name="expense_date"
                        type="date"
                        required
                        :default-value="item?.expense_date?.slice?.(0, 10) ?? new Date().toISOString().slice(0, 10)"
                    />
                    <InputError :message="errors.expense_date" />
                </div>
                <div class="grid gap-2">
                    <Label for="amount">Total cost</Label>
                    <Input id="amount" name="amount" type="number" step="0.01" min="0" required :default-value="item?.amount ?? ''" />
                    <InputError :message="errors.amount" />
                </div>
                <div class="grid gap-2">
                    <Label for="expense_category_id">Category</Label>
                    <select
                        id="expense_category_id"
                        name="expense_category_id"
                        class="h-9 rounded-md border border-input bg-transparent px-3 text-sm"
                        :value="item?.expense_category_id ?? ''"
                    >
                        <option value="">General</option>
                        <option v-for="c in categories || []" :key="c.id" :value="c.id">{{ c.name }}</option>
                    </select>
                </div>
                <div class="grid gap-2">
                    <Label for="reference_no">Receipt / reference</Label>
                    <Input id="reference_no" name="reference_no" :default-value="item?.reference_no ?? ''" />
                </div>
            </div>
            <div class="flex gap-2">
                <Button type="submit" :disabled="processing">{{ processing ? 'Saving...' : 'Save Expense' }}</Button>
                <Button as-child variant="secondary"><Link href="/expenses">Cancel</Link></Button>
            </div>
        </Form>
    </div>
</template>
