<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, CalendarClock, FileText, Info } from '@lucide/vue';
import { computed, ref } from 'vue';
import FileUploadField, { type ExistingUploadFile } from '@/components/FileUploadField.vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { MisTabs } from '@/components/mis';
import StatusBadge from '@/components/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { money } from '@/lib/utils';

const props = defineProps<{ item: any; projects: any[]; customers: any[]; units: any[] }>();
const isEdit = computed(() => !!props.item);
const tab = ref('info');
const unitId = ref(props.item?.unit_id ? String(props.item.unit_id) : '');
const selectedUnit = computed(() => props.units.find((u) => String(u.id) === unitId.value));
const installments = ref(props.item?.installments?.map((i: any) => ({ due_date: i.due_date?.slice?.(0,10), amount: i.amount, paid_now: '', notes: i.notes })) || [
  { due_date: '', amount: '', paid_now: '', notes: '' },
  { due_date: '', amount: '', paid_now: '', notes: '' },
]);
const existingDocuments = computed<ExistingUploadFile[]>(() =>
    (props.item?.attachments ?? []).map((file: any) => ({
        id: file.id,
        name: file.original_filename || file.title || 'Document',
        size: file.file_size,
        mime: file.mime_type,
        url: file.download_url,
    })),
);
const tabs = [
    { id: 'info', label: 'Sale info', icon: Info },
    { id: 'schedule', label: 'Payment schedule', icon: CalendarClock },
    { id: 'documents', label: 'Contract documents', icon: FileText },
];
defineOptions({ layout: { breadcrumbs: [{ title: 'Dashboard', href: '/dashboard' }, { title: 'Sales', href: '/sales' }, { title: 'Form', href: '#' }] } });
function addRow() { installments.value.push({ due_date: '', amount: '', paid_now: '', notes: '' }); }
</script>
<template>
  <Head :title="isEdit ? 'Edit Sale' : 'Create Sale'" />
  <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
    <div class="space-y-3">
      <Button as-child variant="ghost" size="sm" class="-ml-2 w-fit">
        <Link href="/sales"><ArrowLeft class="size-4" /> Back</Link>
      </Button>
      <Heading :title="isEdit ? 'Edit Sale' : 'Create Sale'" description="Sell a unit to a customer. Sale price is the contract total." />
    </div>
    <Form :action="isEdit ? `/sales/${item.id}` : '/sales'" :method="isEdit ? 'put' : 'post'" class="max-w-4xl space-y-6 rounded-xl border bg-card p-6" v-slot="{ errors, processing }">
      <MisTabs v-model="tab" :tabs="tabs" />

      <div v-show="tab === 'info'" class="grid gap-4 sm:grid-cols-2">
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

      <div v-show="tab === 'schedule'" class="space-y-3">
        <template v-if="!isEdit">
          <div class="flex items-center justify-between">
            <p class="text-sm text-muted-foreground">Split the sale price into due dates. Use Paid now if money is received today.</p>
            <Button type="button" variant="outline" size="sm" @click="addRow">Add row</Button>
          </div>
          <div class="hidden text-xs text-muted-foreground sm:grid sm:grid-cols-4 sm:gap-2">
            <span>Due date</span><span>Amount due</span><span>Paid now</span><span>Notes</span>
          </div>
          <div v-for="(row, idx) in installments" :key="idx" class="grid gap-2 sm:grid-cols-4">
            <Input :name="`installments[${idx}][due_date]`" type="date" v-model="row.due_date" />
            <Input :name="`installments[${idx}][amount]`" type="number" step="0.01" placeholder="Amount due" v-model="row.amount" />
            <Input :name="`installments[${idx}][paid_now]`" type="number" step="0.01" placeholder="Received today" v-model="row.paid_now" />
            <Input :name="`installments[${idx}][notes]`" placeholder="Notes" v-model="row.notes" />
          </div>
          <InputError :message="errors.installments" />
        </template>
        <template v-else>
          <p class="text-sm text-muted-foreground">Receive more money from the sale view page. The schedule is set when the sale is created.</p>
          <table class="min-w-full text-sm">
            <thead class="bg-muted/40 text-left text-muted-foreground">
              <tr>
                <th class="px-3 py-2 font-medium">Due</th>
                <th class="px-3 py-2 font-medium">Amount</th>
                <th class="px-3 py-2 font-medium">Paid</th>
                <th class="px-3 py-2 font-medium">Status</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="row in item.installments" :key="row.id" class="border-t">
                <td class="px-3 py-2">{{ row.due_date }}</td>
                <td class="px-3 py-2 tabular-nums">{{ money(row.amount) }}</td>
                <td class="px-3 py-2 tabular-nums">{{ money(row.paid_amount) }}</td>
                <td class="px-3 py-2"><StatusBadge :status="row.status" /></td>
              </tr>
              <tr v-if="!item.installments?.length">
                <td colspan="4" class="px-3 py-8 text-center text-muted-foreground">No installments</td>
              </tr>
            </tbody>
          </table>
          <Button as-child type="button" variant="outline" size="sm">
            <Link :href="`/sales/${item.id}#receive-payment`">Receive payment</Link>
          </Button>
        </template>
      </div>

      <div v-show="tab === 'documents'" class="space-y-3">
        <p class="text-sm text-muted-foreground">Upload the signed contract and related files (ID copies, receipts).</p>
        <FileUploadField
          v-if="isEdit"
          mode="chunked"
          destination="attachment"
          attachable="sales"
          :attachable-id="item.id"
          multiple
          :max-files="10"
          accept="application/pdf,image/jpeg,image/png,image/webp,.doc,.docx"
          :existing="existingDocuments"
        />
        <div v-else class="grid gap-2">
          <input type="file" name="documents[]" multiple accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx" class="text-sm" />
          <InputError :message="errors.documents" />
        </div>
      </div>

      <div class="flex gap-2"><Button type="submit" :disabled="processing">Save Sale</Button><Button as-child variant="secondary"><Link href="/sales">Cancel</Link></Button></div>
    </Form>
  </div>
</template>
