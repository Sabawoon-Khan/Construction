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
}>();

const isEdit = computed(() => !!props.item);

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Expense Categories', href: '/expense-categories' },
            { title: 'Form', href: '/expense-categories/create' },
        ],
    },
});
</script>

<template>
    <Head :title="isEdit ? 'Edit Expense Categories' : 'Create Expense Categories'" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
        <Heading
            :title="isEdit ? 'Edit Expense Categories' : 'Create Expense Categories'"
            description="Fill in the details below and save."
        />

        <Form
            :action="isEdit ? `/expense-categories/${item!.id}` : '/expense-categories'"
            :method="isEdit ? 'put' : 'post'"
            class="max-w-3xl space-y-6 rounded-xl border border-sidebar-border/70 bg-card p-6"
            v-slot="{ errors, processing }"
        >
            <div class="grid gap-4 sm:grid-cols-2">
            <div class="grid gap-2">
                <Label for="name">Name</Label>
                <Input id="name" name="name" type="text" required :default-value="item?.name ?? ''" />
                <InputError :message="errors.name" />
            </div>
            <div class="grid gap-2">
                <Label for="code">Code</Label>
                <Input id="code" name="code" type="text" :default-value="item?.code ?? ''" />
                <InputError :message="errors.code" />
            </div>
            <div class="grid gap-2 sm:col-span-2">
                <Label for="description">Description</Label>
                <textarea id="description" name="description" rows="3" class="flex min-h-[80px] w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs" :value="item?.description ?? ''"></textarea>
                <InputError :message="errors.description" />
            </div>
            <div class="flex items-center gap-2 sm:col-span-2">
                <input id="is_active" type="checkbox" name="is_active" value="1" class="size-4 rounded border" :checked="item?.is_active ?? true" />
                <Label for="is_active">Is Active</Label>
                <InputError :message="errors.is_active" />
            </div>
            </div>

            <div class="flex gap-2">
                <Button type="submit" :disabled="processing">{{ processing ? 'Saving...' : 'Save' }}</Button>
                <Button as-child variant="secondary">
                    <Link href="/expense-categories">Cancel</Link>
                </Button>
            </div>
        </Form>
    </div>
</template>
