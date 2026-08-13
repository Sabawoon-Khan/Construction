<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
const props = defineProps<{ item: any; projects: any[] }>();
const isEdit = computed(() => !!props.item);
defineOptions({ layout: { breadcrumbs: [{ title: 'Dashboard', href: '/dashboard' }, { title: 'Units', href: '/units' }, { title: 'Form', href: '#' }] } });
</script>
<template>
  <Head :title="isEdit ? 'Edit Unit' : 'Create Unit'" />
  <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
    <Heading :title="isEdit ? 'Edit Unit' : 'Create Unit'" />
    <Form :action="isEdit ? `/units/${item.id}` : '/units'" :method="isEdit ? 'put' : 'post'" class="max-w-3xl space-y-6 rounded-xl border bg-card p-6" v-slot="{ errors, processing }">
      <div class="grid gap-4 sm:grid-cols-2">
        <div class="grid gap-2"><Label>Project</Label><select name="project_id" required class="h-9 rounded-md border px-3 text-sm" :value="item?.project_id ?? ''"><option value="" disabled>Select</option><option v-for="p in projects" :key="p.id" :value="p.id">{{ p.name }}</option></select><InputError :message="errors.project_id" /></div>
        <div class="grid gap-2"><Label>Unit No</Label><Input name="unit_no" required :default-value="item?.unit_no ?? ''" /><InputError :message="errors.unit_no" /></div>
        <div class="grid gap-2"><Label>Floor Number</Label><Input name="floor_number" type="number" :default-value="item?.floor_number ?? 1" /></div>
        <div class="grid gap-2"><Label>Area (m2)</Label><Input name="area" type="number" step="0.01" :default-value="item?.area ?? ''" /></div>
        <div class="grid gap-2"><Label>Bedrooms</Label><Input name="bedrooms" type="number" :default-value="item?.bedrooms ?? ''" /></div>
        <div class="grid gap-2"><Label>Sale price</Label><Input name="list_price" type="number" step="0.01" :default-value="item?.list_price ?? 0" /></div>
        <div class="grid gap-2"><Label>Currency</Label><Input name="currency" required :default-value="item?.currency ?? 'USD'" /></div>
        <div class="grid gap-2"><Label>Status</Label><select name="status" class="h-9 rounded-md border px-3 text-sm" :value="item?.status ?? 'available'"><option value="available">Available</option><option value="reserved">Reserved</option><option value="sold">Sold</option></select></div>
        <div class="grid gap-2 sm:col-span-2"><Label>Description</Label><textarea name="description" rows="3" class="rounded-md border px-3 py-2 text-sm" :value="item?.description ?? ''"></textarea></div>
      </div>
      <div class="flex gap-2"><Button type="submit" :disabled="processing">Save</Button><Button as-child variant="secondary"><Link href="/units">Cancel</Link></Button></div>
    </Form>
  </div>
</template>
