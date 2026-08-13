<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
const props = defineProps<{ item: any; projects: any[]; categories: any[]; customers: any[] }>();
const isEdit = computed(() => !!props.item);
defineOptions({ layout: { breadcrumbs: [{ title: 'Dashboard', href: '/dashboard' }, { title: 'Incomes', href: '/incomes' }, { title: 'Form', href: '#' }] } });
</script>
<template>
  <Head :title="isEdit ? 'Edit Income' : 'Record Income'" />
  <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
    <Heading :title="isEdit ? 'Edit Income' : 'Record Income'" />
    <Form :action="isEdit ? `/incomes/${item.id}` : '/incomes'" :method="isEdit ? 'put' : 'post'" class="max-w-3xl space-y-6 rounded-xl border bg-card p-6" v-slot="{ errors, processing }">
      <div class="grid gap-4 sm:grid-cols-2">
        <div class="grid gap-2"><Label>Income No</Label><Input name="income_no" :default-value="item?.income_no ?? ''" /><InputError :message="errors.income_no" /></div>
        <div class="grid gap-2"><Label>Date</Label><Input name="income_date" type="date" required :default-value="item?.income_date?.slice?.(0,10) ?? new Date().toISOString().slice(0,10)" /></div>
        <div class="grid gap-2"><Label>Category</Label><select name="income_category_id" required class="h-9 rounded-md border border-input bg-transparent px-3 text-sm" :value="item?.income_category_id ?? ''"><option value="" disabled>Select</option><option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option></select></div>
        <div class="grid gap-2"><Label>Project</Label><select name="project_id" class="h-9 rounded-md border border-input bg-transparent px-3 text-sm" :value="item?.project_id ?? ''"><option value="">None</option><option v-for="p in projects" :key="p.id" :value="p.id">{{ p.name }}</option></select></div>
        <div class="grid gap-2"><Label>Customer</Label><select name="customer_id" class="h-9 rounded-md border border-input bg-transparent px-3 text-sm" :value="item?.customer_id ?? ''"><option value="">None</option><option v-for="c in customers" :key="c.id" :value="c.id">{{ c.name }}</option></select></div>
        <div class="grid gap-2"><Label>Received From</Label><Input name="received_from" :default-value="item?.received_from ?? ''" /></div>
        <div class="grid gap-2"><Label>Amount</Label><Input name="amount" type="number" step="0.01" required :default-value="item?.amount ?? ''" /></div>
        <div class="grid gap-2"><Label>Currency</Label><Input name="currency" required :default-value="item?.currency ?? 'USD'" /></div>
        <div class="grid gap-2"><Label>Payment Method</Label><select name="payment_method" class="h-9 rounded-md border border-input bg-transparent px-3 text-sm" :value="item?.payment_method ?? 'cash'"><option value="cash">Cash</option><option value="bank">Bank</option><option value="transfer">Transfer</option></select></div>
        <div class="grid gap-2"><Label>Status</Label><select name="status" class="h-9 rounded-md border border-input bg-transparent px-3 text-sm" :value="item?.status ?? 'approved'"><option value="draft">Draft</option><option value="approved">Approved</option></select></div>
        <div class="grid gap-2"><Label>Reference</Label><Input name="reference_no" :default-value="item?.reference_no ?? ''" /></div>
        <div class="grid gap-2 sm:col-span-2"><Label>Description</Label><textarea name="description" rows="3" class="rounded-md border border-input px-3 py-2 text-sm" :value="item?.description ?? ''"></textarea></div>
      </div>
      <div class="flex gap-2"><Button type="submit" :disabled="processing">Save</Button><Button as-child variant="secondary"><Link href="/incomes">Cancel</Link></Button></div>
    </Form>
  </div>
</template>
