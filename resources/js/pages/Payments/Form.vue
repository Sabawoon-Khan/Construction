<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

defineProps<{ item: any; projects: any[]; customers: any[] }>();
defineOptions({ layout: { breadcrumbs: [{ title: 'Dashboard', href: '/dashboard' }, { title: 'Payments', href: '/payments' }, { title: 'Form', href: '#' }] } });
</script>
<template>
  <Head title="Record Payment" />
  <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
    <Heading title="Record Payment" description="General receipt not linked to a home sale. Use the sale page to collect installments." />
    <Form action="/payments" method="post" class="max-w-3xl space-y-6 rounded-xl border bg-card p-6" v-slot="{ processing }">
      <div class="grid gap-4 sm:grid-cols-2">
        <div class="grid gap-2"><Label>Customer</Label><select name="customer_id" class="h-9 rounded-md border px-3 text-sm"><option value="">Optional</option><option v-for="c in customers" :key="c.id" :value="c.id">{{ c.name }}</option></select></div>
        <div class="grid gap-2"><Label>Project</Label><select name="project_id" class="h-9 rounded-md border px-3 text-sm"><option value="">Optional</option><option v-for="p in projects" :key="p.id" :value="p.id">{{ p.name }}</option></select></div>
        <div class="grid gap-2"><Label>Amount</Label><Input name="amount" type="number" step="0.01" required /></div>
        <div class="grid gap-2"><Label>Paid At</Label><Input name="paid_at" type="datetime-local" required :default-value="new Date().toISOString().slice(0,16)" /></div>
        <div class="grid gap-2"><Label>Currency</Label><Input name="currency" required default-value="USD" /></div>
        <div class="grid gap-2"><Label>Method</Label><select name="payment_method" class="h-9 rounded-md border px-3 text-sm"><option value="cash">Cash</option><option value="bank">Bank</option><option value="transfer">Transfer</option></select></div>
        <input type="hidden" name="type" value="receipt" />
        <input type="hidden" name="status" value="posted" />
        <div class="grid gap-2"><Label>Reference</Label><Input name="reference_no" /></div>
        <div class="grid gap-2 sm:col-span-2"><Label>Notes</Label><textarea name="notes" rows="2" class="rounded-md border px-3 py-2 text-sm"></textarea></div>
      </div>
      <div class="flex gap-2"><Button type="submit" :disabled="processing">Save Payment</Button><Button as-child variant="secondary"><Link href="/payments">Cancel</Link></Button></div>
    </Form>
  </div>
</template>
