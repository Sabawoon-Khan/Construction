<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { ArrowLeft } from '@lucide/vue';
import { computed, ref } from 'vue';
import CurrencySelect from '@/components/CurrencySelect.vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useCurrency } from '@/composables/useCurrency';
import { useTranslations } from '@/composables/useTranslations';
const props = defineProps<{ item: any; projects: any[] }>();
const isEdit = computed(() => !!props.item);
const { t } = useTranslations();
const { baseCurrency } = useCurrency();
const currency = ref(props.item?.currency ?? baseCurrency.value);
defineOptions({ layout: { breadcrumbs: [{ title: 'Dashboard', href: '/dashboard' }, { title: 'Units', href: '/units' }, { title: 'Form', href: '#' }] } });
</script>
<template>
  <Head :title="isEdit ? t('Edit unit') : t('Create unit')" />
  <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
    <div class="space-y-3">
      <Button as-child variant="ghost" size="sm" class="-ml-2 w-fit">
        <Link href="/units"><ArrowLeft class="size-4" /> {{ t('Back') }}</Link>
      </Button>
      <Heading :title="isEdit ? t('Edit unit') : t('Create unit')" />
    </div>
    <Form :action="isEdit ? `/units/${item.id}` : '/units'" :method="isEdit ? 'put' : 'post'" class="max-w-3xl space-y-6 rounded-xl border bg-card p-6" v-slot="{ errors, processing }">
      <div class="grid gap-4 sm:grid-cols-2">
        <div class="grid gap-2"><Label>{{ t('Project') }}</Label><select name="project_id" required class="h-9 rounded-md border px-3 text-sm" :value="item?.project_id ?? ''"><option value="" disabled>{{ t('Select') }}</option><option v-for="p in projects" :key="p.id" :value="p.id">{{ p.name }}</option></select><InputError :message="errors.project_id" /></div>
        <div class="grid gap-2"><Label>{{ t('Unit No') }}</Label><Input name="unit_no" required :default-value="item?.unit_no ?? ''" /><InputError :message="errors.unit_no" /></div>
        <div class="grid gap-2"><Label>{{ t('Floor Number') }}</Label><Input name="floor_number" type="number" :default-value="item?.floor_number ?? 1" /></div>
        <div class="grid gap-2"><Label>{{ t('Type') }}</Label><select name="type" required class="h-9 rounded-md border px-3 text-sm" :value="item?.type ?? 'apartment'"><option value="apartment">{{ t('Apartment') }}</option><option value="house">{{ t('House') }}</option><option value="villa">{{ t('Villa') }}</option><option value="shop">{{ t('Shop') }}</option><option value="office">{{ t('Office') }}</option><option value="other">{{ t('Other') }}</option></select></div>
        <div class="grid gap-2"><Label>{{ t('Area (m2)') }}</Label><Input name="area" type="number" step="0.01" :default-value="item?.area ?? ''" /></div>
        <div class="grid gap-2"><Label>{{ t('Bedrooms') }}</Label><Input name="bedrooms" type="number" :default-value="item?.bedrooms ?? ''" /></div>
        <div class="grid gap-2"><Label>{{ t('Toilets') }}</Label><Input name="toilets" type="number" :default-value="item?.toilets ?? ''" /></div>
        <div class="grid gap-2"><Label>{{ t('Bathrooms') }}</Label><Input name="bathrooms" type="number" :default-value="item?.bathrooms ?? ''" /></div>
        <div class="grid gap-2"><Label>{{ t('Halls') }}</Label><Input name="halls" type="number" :default-value="item?.halls ?? ''" /></div>
        <div class="grid gap-2"><Label>{{ t('Kitchens') }}</Label><Input name="kitchens" type="number" :default-value="item?.kitchens ?? ''" /></div>
        <div class="grid gap-2"><Label>{{ t('Balconies') }}</Label><Input name="balconies" type="number" :default-value="item?.balconies ?? ''" /></div>
        <div class="grid gap-2"><Label>{{ t('Planned unit cost') }}</Label><Input name="cost_price" type="number" step="0.01" :default-value="item?.cost_price ?? 0" /><p class="text-xs text-muted-foreground">{{ t('Target build cost for this unit — not project expenses.') }}</p></div>
        <div class="grid gap-2"><Label>{{ t('Sale price') }}</Label><Input name="list_price" type="number" step="0.01" :default-value="item?.list_price ?? 0" /></div>
        <div class="grid gap-2"><Label>{{ t('Currency') }}</Label><CurrencySelect v-model="currency" /><input type="hidden" name="currency" :value="currency" /></div>
        <div class="grid gap-2"><Label>{{ t('Status') }}</Label><select name="status" class="h-9 rounded-md border px-3 text-sm" :value="item?.status ?? 'available'"><option value="available">{{ t('Available') }}</option><option value="reserved">{{ t('Reserved') }}</option><option value="sold">{{ t('Sold') }}</option></select></div>
        <div class="grid gap-2 sm:col-span-2"><Label>{{ t('Description') }}</Label><textarea name="description" rows="3" class="rounded-md border px-3 py-2 text-sm" :value="item?.description ?? ''"></textarea></div>
      </div>
      <div class="flex gap-2"><Button type="submit" :disabled="processing">{{ t('Save') }}</Button><Button as-child variant="secondary"><Link href="/units">{{ t('Cancel') }}</Link></Button></div>
    </Form>
  </div>
</template>
