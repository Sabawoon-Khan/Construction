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
}>();

const isEdit = computed(() => !!props.item);

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Customers', href: '/customers' },
            { title: 'Form', href: '/customers/create' },
        ],
    },
});
</script>

<template>
    <Head :title="isEdit ? 'Edit Customers' : 'Create Customers'" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
        <div class="space-y-3">
            <Button as-child variant="ghost" size="sm" class="-ml-2 w-fit">
                <Link href="/customers"><ArrowLeft class="size-4" /> Back</Link>
            </Button>
            <Heading
                :title="isEdit ? 'Edit Customers' : 'Create Customers'"
                description="Fill in the details below and save."
            />
        </div>

        <Form
            :action="isEdit ? `/customers/${item!.id}` : '/customers'"
            :method="isEdit ? 'put' : 'post'"
            class="max-w-3xl space-y-6 rounded-xl border border-sidebar-border/70 bg-card p-6"
            v-slot="{ errors, processing }"
        >
            <div class="grid gap-4 sm:grid-cols-2">
            <div class="grid gap-2">
                <Label for="customer_no">Customer No</Label>
                <Input id="customer_no" name="customer_no" type="text" required :default-value="item?.customer_no ?? ''" />
                <InputError :message="errors.customer_no" />
            </div>
            <div class="grid gap-2">
                <Label for="name">Name</Label>
                <Input id="name" name="name" type="text" required :default-value="item?.name ?? ''" />
                <InputError :message="errors.name" />
            </div>
            <div class="grid gap-2">
                <Label for="father_name">Father Name</Label>
                <Input id="father_name" name="father_name" type="text" :default-value="item?.father_name ?? ''" />
                <InputError :message="errors.father_name" />
            </div>
            <div class="grid gap-2">
                <Label for="phone">Phone</Label>
                <Input id="phone" name="phone" type="text" :default-value="item?.phone ?? ''" />
                <InputError :message="errors.phone" />
            </div>
            <div class="grid gap-2">
                <Label for="email">Email</Label>
                <Input id="email" name="email" type="email" :default-value="item?.email ?? ''" />
                <InputError :message="errors.email" />
            </div>
            <div class="grid gap-2">
                <Label for="national_id">National Id</Label>
                <Input id="national_id" name="national_id" type="text" :default-value="item?.national_id ?? ''" />
                <InputError :message="errors.national_id" />
            </div>
            <div class="grid gap-2 sm:col-span-2">
                <Label for="address">Address</Label>
                <textarea id="address" name="address" rows="3" class="flex min-h-[80px] w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs" :value="item?.address ?? ''"></textarea>
                <InputError :message="errors.address" />
            </div>
            <div class="grid gap-2 sm:col-span-2">
                <Label for="notes">Notes</Label>
                <textarea id="notes" name="notes" rows="3" class="flex min-h-[80px] w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs" :value="item?.notes ?? ''"></textarea>
                <InputError :message="errors.notes" />
            </div>
            </div>

            <div class="flex gap-2">
                <Button type="submit" :disabled="processing">{{ processing ? 'Saving...' : 'Save' }}</Button>
                <Button as-child variant="secondary">
                    <Link href="/customers">Cancel</Link>
                </Button>
            </div>
        </Form>
    </div>
</template>
