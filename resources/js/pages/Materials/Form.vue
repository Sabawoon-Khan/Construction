<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { Trash2 } from '@lucide/vue';
import { computed, ref } from 'vue';
import CurrencySelect from '@/components/CurrencySelect.vue';
import InputError from '@/components/InputError.vue';
import RecordAttachments from '@/components/RecordAttachments.vue';
import { MisFormPage } from '@/components/mis';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useCurrency } from '@/composables/useCurrency';
import { useTranslations } from '@/composables/useTranslations';
import { historyBack } from '@/lib/historyBack';

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

    <MisFormPage :title="isEdit ? t('Edit material') : t('Create material')" :back-href="isEdit ? '/materials' : undefined">
        <Form
            :action="isEdit ? `/materials/${item!.id}` : '/materials'"
            :method="isEdit ? 'put' : 'post'"
            class="mis-form-card"
            v-slot="{ errors, processing }"
        >
            <div class="mis-form-grid">
                <div class="grid gap-2">
                    <Label for="name">{{ t('Name') }}</Label>
                    <Input id="name" name="name" type="text" required :default-value="item?.name ?? ''" />
                    <InputError :message="errors.name" />
                </div>
                <div class="grid gap-2">
                    <Label for="code">{{ t('Code') }}</Label>
                    <Input id="code" name="code" type="text" :default-value="item?.code ?? ''" />
                    <InputError :message="errors.code" />
                </div>
                <div class="grid gap-2">
                    <Label for="material_unit_id">{{ t('Unit') }}</Label>
                    <select id="material_unit_id" name="material_unit_id" class="mis-form-select" :value="item?.material_unit_id ?? ''">
                        <option value="">{{ t('Select unit') }}</option>
                        <option v-for="u in units" :key="u.id" :value="u.id">{{ u.unit_name }}</option>
                    </select>
                    <InputError :message="errors.material_unit_id" />
                </div>
                <div class="grid gap-2">
                    <Label for="unit_cost">{{ t('Default unit cost') }}</Label>
                    <div class="flex gap-2">
                        <Input id="unit_cost" name="unit_cost" type="number" step="0.01" min="0" :default-value="item?.unit_cost ?? 0" class="min-w-0 flex-1" />
                        <CurrencySelect id="currency" v-model="currency" name="currency" class="w-[9rem] shrink-0" />
                    </div>
                    <InputError :message="errors.unit_cost" />
                    <InputError :message="errors.currency" />
                </div>
                <div class="grid gap-2">
                    <Label for="minimum_stock">{{ t('Minimum stock') }}</Label>
                    <Input id="minimum_stock" name="minimum_stock" type="number" step="0.001" min="0" :default-value="item?.minimum_stock ?? 0" />
                    <InputError :message="errors.minimum_stock" />
                </div>
                <div class="flex items-center gap-2 mis-form-span">
                    <input type="hidden" name="is_active" value="0" />
                    <input id="is_active" name="is_active" type="checkbox" value="1" class="size-4" :checked="item?.is_active !== false" />
                    <Label for="is_active">{{ t('Active') }}</Label>
                </div>
            </div>

            <RecordAttachments
                attachable="materials"
                :attachable-id="isEdit ? item!.id : null"
                :attachments="item?.attachments"
                :error="errors.documents"
            />

            <div class="mis-form-actions">
                <Button type="submit" :disabled="processing">{{ processing ? t('Saving…') : t('Save') }}</Button>
                <Button v-if="!isEdit" type="button" variant="secondary" @click="historyBack">{{ t('Cancel') }}</Button>
                <Button v-else as-child variant="secondary">
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
    </MisFormPage>
</template>
