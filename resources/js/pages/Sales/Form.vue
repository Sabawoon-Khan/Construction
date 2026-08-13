<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { money } from '@/lib/utils';

const props = defineProps<{ item: any; projects: any[]; customers: any[]; units: any[] }>();
const isEdit = computed(() => !!props.item);
const unitId = ref(props.item?.unit_id ? String(props.item.unit_id) : '');
const selectedUnit = computed(() => props.units.find((u) => String(u.id) === unitId.value));
const installments = ref(props.item?.installments?.map((i: any) => ({ due_date: i.due_date?.slice?.(0,10), amount: i.amount, notes: i.notes })) || [
  { due_date: '', amount: '', notes: '' },
  { due_date: '', amount: '', notes: '' },
]);
defineOptions({ layout: { breadcrumbs: [{ title: 'Dashboard', href: '/dashboard' }, { title: 'Sales', href: '/sales' }, { title: 'Form', href: '#' }] } });
function addRow() { installments.value.push({ due_date: '', amount: '', notes: '' }); }
</script>
<template>
  <Head :title="isEdit ? 'Edit Sale' : 'Create Sale'" />
  <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
    <Heading :title="isEdit ? 'Edit Sale' : 'Create Sale'" description="Pick the unit — its list price is the sale price." />
    <Form :action="isEdit ? `/sales/${item.id}` : '/sales'" :method="isEdit ? 'put' : 'post'" class="max-w-4xl space-y-6 rounded-xl border bg-card p-6" v-slot="{ errors, processing }">
      <div class="grid gap-4 sm:grid-cols-2">
        <div class="grid gap-2"><Label>Project</Label><select name="project_id" required class="h-9 rounded-md border px-3 text-sm" :value="item?.project_id ?? ''"><option value="" disabled>Select</option><option v-for="p in projects" :key="p.id" :value="p.id">{{ p.name }}</option></select><InputError :message="errors.project_id" /></div>
        <div class="grid gap-2"><Label>Unit</Label><select name="unit_id" required v-model="unitId" class="h-9 rounded-md border px-3 text-sm"><option value="" disabled>Select</option><option v-for="u in units" :key="u.id" :value="String(u.id)">{{ u.unit_no }} — {{ money(u.list_price, u.currency || 'USD') }} ({{ u.status }})</option></select><InputError :message="errors.unit_id" /></div>
        <div v-if="selectedUnit" class="sm:col-span-2 rounded-lg border bg-muted/30 px-4 py-3 text-sm">
          Sale price: <span class="font-semibold tabular-nums">{{ money(selectedUnit.list_price, selectedUnit.currency || 'USD') }}</span>
          <span v-if="Number(selectedUnit.list_price) <= 0" class="ml-2 text-destructive">Set list price on the unit first.</span>
        </div>
        <div class="grid gap-2"><Label>Customer</Label><select name="customer_id" required class="h-9 rounded-md border px-3 text-sm" :value="item?.customer_id ?? ''"><option value="" disabled>Select</option><option v-for="c in customers" :key="c.id" :value="c.id">{{ c.name }}</option></select></div>
        <div class="grid gap-2"><Label>Contract Date</Label><Input name="contract_date" type="date" :default-value="item?.contract_date?.slice?.(0,10) ?? new Date().toISOString().slice(0,10)" /></div>
        <div class="grid gap-2"><Label>Discount</Label><Input name="discount" type="number" step="0.01" :default-value="item?.discount ?? 0" /></div>
        <div class="grid gap-2"><Label>Currency</Label><Input name="currency" :default-value="item?.currency ?? 'USD'" /></div>
        <div class="grid gap-2"><Label>Status</Label><select name="status" class="h-9 rounded-md border px-3 text-sm" :value="item?.status ?? 'active'"><option value="draft">Draft</option><option value="active">Active</option><option value="completed">Completed</option><option value="cancelled">Cancelled</option></select></div>
        <div class="grid gap-2"><Label>Sale No</Label><Input name="sale_no" :default-value="item?.sale_no ?? ''" placeholder="Auto if empty" /></div>
        <div class="grid gap-2 sm:col-span-2"><Label>Notes</Label><textarea name="notes" rows="2" class="rounded-md border px-3 py-2 text-sm" :value="item?.notes ?? ''"></textarea></div>
      </div>

      <div class="space-y-3" v-if="!isEdit">
        <div class="flex items-center justify-between"><h3 class="font-medium">Installments</h3><Button type="button" variant="outline" size="sm" @click="addRow">Add row</Button></div>
        <div v-for="(row, idx) in installments" :key="idx" class="grid gap-2 sm:grid-cols-3">
          <Input :name="`installments[${idx}][due_date]`" type="date" v-model="row.due_date" />
          <Input :name="`installments[${idx}][amount]`" type="number" step="0.01" placeholder="Amount" v-model="row.amount" />
          <Input :name="`installments[${idx}][notes]`" placeholder="Notes" v-model="row.notes" />
        </div>
      </div>

      <div class="flex gap-2"><Button type="submit" :disabled="processing">Save Sale</Button><Button as-child variant="secondary"><Link href="/sales">Cancel</Link></Button></div>
    </Form>
  </div>
</template>
