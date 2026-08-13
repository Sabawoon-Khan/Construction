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
            { title: 'Incomes', href: '/incomes' },
            { title: 'Form', href: '#' },
        ],
    },
});
</script>

<template>
    <Head :title="isEdit ? 'Edit Income' : 'Record Income'" />
    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
        <Heading
            :title="isEdit ? 'Edit Income' : 'Record Income'"
            description="Company-wide income not tied to a project."
        />
        <Form
            :action="isEdit ? `/incomes/${item!.id}` : '/incomes'"
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
                    <Label for="income_date">Date</Label>
                    <Input
                        id="income_date"
                        name="income_date"
                        type="date"
                        required
                        :default-value="item?.income_date?.slice?.(0, 10) ?? new Date().toISOString().slice(0, 10)"
                    />
                    <InputError :message="errors.income_date" />
                </div>
                <div class="grid gap-2">
                    <Label for="amount">Total amount</Label>
                    <Input id="amount" name="amount" type="number" step="0.01" min="0" required :default-value="item?.amount ?? ''" />
                    <InputError :message="errors.amount" />
                </div>
            </div>
            <div class="flex gap-2">
                <Button type="submit" :disabled="processing">{{ processing ? 'Saving...' : 'Save Income' }}</Button>
                <Button as-child variant="secondary"><Link href="/incomes">Cancel</Link></Button>
            </div>
        </Form>
    </div>
</template>
