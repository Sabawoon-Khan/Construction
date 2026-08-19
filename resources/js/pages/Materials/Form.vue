<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, Trash2 } from '@lucide/vue';
import { computed, ref } from 'vue';
import CurrencySelect from '@/components/CurrencySelect.vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useCurrency } from '@/composables/useCurrency';
import { useTranslations } from '@/composables/useTranslations';

const props = defineProps<{
    item: Record<string, any> | null;
    units: { id: number; unit_name: string }[];
}>();

const isEdit = computed(() => !!props.item);
const { t } = useTranslations();
const { baseCurrency } = useCurrency();
const currency = ref(props.item?.currency ?? baseCurrency.value);

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Materials', href: '/materials' },
            { title: 'Form', href: '/materials/create' },
        ],
    },
});
</script>

<template>
    <Head :title="isEdit ? t('Edit material') : t('Create material')" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
        <div class="space-y-3">
            <Button as-child variant="ghost" size="sm" class="-ml-2 w-fit">
                <Link href="/materials"><ArrowLeft class="size-4" /> {{ t('Back') }}</Link>
            </Button>
            <Heading
                :title="isEdit ? t('Edit material') : t('Create material')"
                :description="t('Materials are what you purchase into stock.')"
            />
        </div>

        <Form
            :action="isEdit ? `/materials/${item!.id}` : '/materials'"
            :method="isEdit ? 'put' : 'post'"
            class="max-w-3xl space-y-6 rounded-xl border border-sidebar-border/70 bg-card p-6"
            v-slot="{ errors, processing }"
        >
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="grid gap-2">
                    <Label for="code">{{ t('Code') }}</Label>
                    <Input id="code" name="code" type="text" :default-value="item?.code ?? ''" :placeholder="t('Leave blank to auto-generate')" />
                    <InputError :message="errors.code" />
                </div>
                <div class="grid gap-2">
                    <Label for="name">{{ t('Name') }}</Label>
                    <Input id="name" name="name" type="text" required :default-value="item?.name ?? ''" />
                    <InputError :message="errors.name" />
                </div>
                <div class="grid gap-2">
                    <Label for="material_unit_id">{{ t('Unit') }}</Label>
                    <select id="material_unit_id" name="material_unit_id" class="h-9 rounded-md border border-input bg-transparent px-3 text-sm" :value="item?.material_unit_id ?? ''">
                        <option value="">{{ t('Select unit') }}</option>
                        <option v-for="u in units" :key="u.id" :value="u.id">{{ u.unit_name }}</option>
                    </select>
                    <InputError :message="errors.material_unit_id" />
                </div>
                <div class="grid gap-2">
                    <Label for="unit_cost">{{ t('Default unit cost') }}</Label>
                    <Input id="unit_cost" name="unit_cost" type="number" step="0.01" min="0" :default-value="item?.unit_cost ?? 0" />
                    <InputError :message="errors.unit_cost" />
                </div>
                <div class="grid gap-2">
                    <Label for="minimum_stock">{{ t('Minimum stock') }}</Label>
                    <Input id="minimum_stock" name="minimum_stock" type="number" step="0.001" min="0" :default-value="item?.minimum_stock ?? 0" />
                    <InputError :message="errors.minimum_stock" />
                </div>
                <div class="grid gap-2">
                    <Label for="currency">{{ t('Currency') }}</Label>
                    <CurrencySelect id="currency" v-model="currency" />
                    <input type="hidden" name="currency" :value="currency" />
                    <InputError :message="errors.currency" />
                </div>
                <div class="flex items-center gap-2 sm:col-span-2">
                    <input type="hidden" name="is_active" value="0" />
                    <input id="is_active" name="is_active" type="checkbox" value="1" class="size-4" :checked="item?.is_active !== false" />
                    <Label for="is_active">{{ t('Active') }}</Label>
                </div>
            </div>

            <div class="flex gap-2">
                <Button type="submit" :disabled="processing">{{ processing ? t('Saving…') : t('Save') }}</Button>
                <Button as-child variant="secondary">
                    <Link href="/materials">{{ t('Cancel') }}</Link>
                </Button>
                <Form v-if="isEdit" :action="`/materials/${item!.id}`" method="delete" class="ml-auto" v-slot="{ processing: deleting }">
                    <Button type="submit" variant="destructive" :disabled="deleting">
                        <Trash2 class="size-4" />
                        {{ deleting ? t('Deleting…') : t('Delete') }}
                    </Button>
                </Form>
            </div>
        </Form>
    </div>
</template>
