<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { CalendarClock, FileText, Info, Trash2 } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import CurrencySelect from '@/components/CurrencySelect.vue';
import InputError from '@/components/InputError.vue';
import RelatedCreateLink from '@/components/RelatedCreateLink.vue';
import RecordAttachments from '@/components/RecordAttachments.vue';
import { MisTabs } from '@/components/mis';
import { V2FormPage } from '@/components/v2';
import StatusBadge from '@/components/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useCalendar } from '@/composables/useCalendar';
import { useTranslations } from '@/composables/useTranslations';
import { historyBack } from '@/lib/historyBack';
import { money } from '@/lib/utils';

const props = defineProps<{
    item: any;
    projects: any[];
    customers: any[];
    units: any[];
    unit_id?: number | null;
}>();
const isEdit = computed(() => !!props.item);
const { t } = useTranslations();
const calendar = useCalendar();
const formatDate = (value: string | null | undefined) => calendar.format(value) || '—';
const tab = ref('info');

const initialUnitId = props.item?.unit_id
    ? String(props.item.unit_id)
    : props.unit_id
      ? String(props.unit_id)
      : '';
const initialUnit = initialUnitId
    ? props.units.find((u) => String(u.id) === initialUnitId)
    : undefined;

const projectId = ref(
    props.item?.project_id
        ? String(props.item.project_id)
        : initialUnit?.project_id != null
          ? String(initialUnit.project_id)
          : '',
);
const unitId = ref(initialUnitId);
const customerId = ref(props.item?.customer_id ? String(props.item.customer_id) : '');
const status = ref(String(props.item?.status ?? 'active'));
const notes = ref(String(props.item?.notes ?? ''));
const discount = ref(String(props.item?.discount ?? 0));
const currency = ref(
    String(props.item?.currency ?? initialUnit?.currency ?? 'USD'),
);
const contractDate = ref(
    props.item?.contract_date?.slice?.(0, 10) ??
        new Date().toISOString().slice(0, 10),
);

const projectUnits = computed(() => {
    if (!projectId.value) {
        return props.units;
    }

    return props.units.filter((u) => String(u.project_id) === projectId.value);
});

const selectedUnit = computed(() =>
    props.units.find((u) => String(u.id) === unitId.value),
);

const unitCreateHref = computed(() =>
    projectId.value ? `/units/create?project_id=${projectId.value}` : '/units/create',
);

watch(projectId, () => {
    if (
        unitId.value &&
        !projectUnits.value.some((u) => String(u.id) === unitId.value)
    ) {
        unitId.value = '';
    }
});

watch(unitId, (id) => {
    if (!id) {
        return;
    }

    const unit = props.units.find((u) => String(u.id) === id);
    if (unit?.project_id != null) {
        projectId.value = String(unit.project_id);
    }
});

const installments = ref(
    props.item?.installments?.map((i: any) => ({
        due_date: i.due_date?.slice?.(0, 10),
        amount: i.amount,
        paid_now: '',
        notes: i.notes,
    })) || [{ due_date: '', amount: '', paid_now: '', notes: '' }],
);

const tabs = [
    { id: 'info', label: t('Sale info'), icon: Info },
    { id: 'schedule', label: t('Payment schedule'), icon: CalendarClock },
    { id: 'documents', label: t('Contract documents'), icon: FileText },
];

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Sales', href: '/sales' },
            { title: 'Form', href: '#' },
        ],
    },
});

function addRow() {
    installments.value.push({
        due_date: '',
        amount: '',
        paid_now: '',
        notes: '',
    });
}

function removeRow(index: number) {
    if (installments.value.length <= 1) {
        return;
    }
    installments.value.splice(index, 1);
}

function onFormKeydown(event: KeyboardEvent) {
    if ((event.metaKey || event.ctrlKey) && event.key === 'Enter') {
        event.preventDefault();
        (event.currentTarget as HTMLFormElement | null)?.requestSubmit?.();
    }
}
</script>

<template>
    <Head :title="isEdit ? t('Edit sale') : t('Create sale')" />
    <V2FormPage
        :title="isEdit ? t('Edit sale') : t('Create sale')"
        :eyebrow="t('Sales')"
        :description="isEdit ? t('Update this sale.') : t('Create a new sale contract.')"
        :back-href="isEdit ? '/sales' : undefined"
    >
        <Form
            :action="isEdit ? `/sales/${item.id}` : '/sales'"
            :method="isEdit ? 'put' : 'post'"
            class="mis-form-card"
            v-slot="{ errors, processing }"
            @keydown="onFormKeydown"
        >
            <MisTabs v-model="tab" :tabs="tabs" />

            <div v-show="tab === 'info'" class="mis-form-grid">
                <div class="grid gap-2">
                    <div class="flex items-center justify-between gap-2">
                        <Label>{{ t('Project') }}</Label>
                        <RelatedCreateLink href="/projects/create" :label="t('Add project')" />
                    </div>
                    <select
                        v-model="projectId"
                        name="project_id"
                        required
                        class="mis-form-select"
                    >
                        <option value="" disabled>{{ t('Select') }}</option>
                        <option v-for="p in projects" :key="p.id" :value="String(p.id)">
                            {{ p.name }}
                        </option>
                    </select>
                    <InputError :message="errors.project_id" />
                </div>
                <div class="grid gap-2">
                    <div class="flex items-center justify-between gap-2">
                        <Label>{{ t('Unit') }}</Label>
                        <RelatedCreateLink :href="unitCreateHref" :label="t('Add unit')" />
                    </div>
                    <select
                        v-model="unitId"
                        name="unit_id"
                        required
                        class="mis-form-select"
                        :disabled="!projectId"
                    >
                        <option value="" disabled>
                            {{ projectId ? t('Select') : t('Select project first') }}
                        </option>
                        <option
                            v-for="u in projectUnits"
                            :key="u.id"
                            :value="String(u.id)"
                        >
                            {{ u.unit_no }} — {{ money(u.list_price, u.currency) }}
                            ({{ t(u.status) }})
                        </option>
                    </select>
                    <InputError :message="errors.unit_id" />
                </div>
                <div
                    v-if="selectedUnit"
                    class="mis-form-span rounded-xl border bg-muted/30 px-4 py-3 text-sm"
                >
                    {{ t('Sale price') }}:
                    <span class="font-semibold tabular-nums">
                        {{ money(selectedUnit.list_price, selectedUnit.currency) }}
                    </span>
                    <span
                        v-if="Number(selectedUnit.list_price) <= 0"
                        class="ml-2 text-destructive"
                    >
                        {{ t('Set list price on the unit first.') }}
                    </span>
                </div>
                <div class="grid gap-2">
                    <div class="flex items-center justify-between gap-2">
                        <Label>{{ t('Customer') }}</Label>
                        <RelatedCreateLink href="/customers/create" :label="t('Add customer')" />
                    </div>
                    <select
                        v-model="customerId"
                        name="customer_id"
                        required
                        class="mis-form-select"
                    >
                        <option value="" disabled>{{ t('Select') }}</option>
                        <option
                            v-for="c in customers"
                            :key="c.id"
                            :value="String(c.id)"
                        >
                            {{ c.name }}
                        </option>
                    </select>
                    <InputError :message="errors.customer_id" />
                </div>
                <div class="grid gap-2">
                    <Label>{{ t('Contract date') }}</Label>
                    <Input
                        v-model="contractDate"
                        name="contract_date"
                        type="date"
                    />
                </div>
                <div class="grid gap-2">
                    <Label>{{ t('Discount') }} ({{ currency }})</Label>
                    <div class="flex gap-2 sm:items-center">
                        <Input
                            v-model="discount"
                            name="discount"
                            type="number"
                            step="0.01"
                            class="min-w-0 flex-1"
                        />
                        <CurrencySelect v-model="currency" name="currency" class="w-[9rem] shrink-0" />
                    </div>
                </div>
                <div class="grid gap-2">
                    <Label>{{ t('Status') }}</Label>
                    <select
                        v-model="status"
                        name="status"
                        class="mis-form-select"
                    >
                        <option value="draft">{{ t('draft') }}</option>
                        <option value="active">{{ t('active') }}</option>
                        <option value="completed">{{ t('completed') }}</option>
                        <option value="cancelled">{{ t('cancelled') }}</option>
                    </select>
                </div>
                <div class="grid gap-2 mis-form-span">
                    <Label>{{ t('Notes') }}</Label>
                    <textarea
                        v-model="notes"
                        name="notes"
                        rows="2"
                        class="mis-form-textarea"
                    />
                </div>
            </div>

            <div v-show="tab === 'schedule'" class="space-y-3">
                <template v-if="!isEdit">
                    <div class="flex items-center justify-between">
                        <Button type="button" variant="outline" size="sm" @click="addRow">
                            {{ t('Add row') }}
                        </Button>
                    </div>
                    <div
                        class="hidden text-xs text-muted-foreground sm:grid sm:grid-cols-[1fr_1fr_1fr_1fr_auto] sm:gap-2"
                    >
                        <span>{{ t('Due date') }}</span>
                        <span>{{ t('Amount due') }}</span>
                        <span>{{ t('Paid now') }}</span>
                        <span>{{ t('Notes') }}</span>
                        <span class="w-9" />
                    </div>
                    <div
                        v-for="(row, idx) in installments"
                        :key="idx"
                        class="grid gap-2 sm:grid-cols-[1fr_1fr_1fr_1fr_auto]"
                    >
                        <Input
                            :name="`installments[${idx}][due_date]`"
                            type="date"
                            v-model="row.due_date"
                        />
                        <Input
                            :name="`installments[${idx}][amount]`"
                            type="number"
                            step="0.01"
                            :placeholder="t('Amount due')"
                            v-model="row.amount"
                        />
                        <Input
                            :name="`installments[${idx}][paid_now]`"
                            type="number"
                            step="0.01"
                            :placeholder="t('Received today')"
                            v-model="row.paid_now"
                        />
                        <Input
                            :name="`installments[${idx}][notes]`"
                            :placeholder="t('Notes')"
                            v-model="row.notes"
                        />
                        <Button
                            type="button"
                            variant="ghost"
                            size="icon"
                            class="shrink-0 text-destructive hover:text-destructive"
                            :disabled="installments.length <= 1"
                            :aria-label="t('Remove row')"
                            @click="removeRow(idx)"
                        >
                            <Trash2 class="size-4" />
                        </Button>
                    </div>
                    <InputError :message="errors.installments" />
                </template>
                <template v-else>
                    <table class="min-w-full text-sm">
                        <thead class="table-chrome-head text-start text-muted-foreground">
                            <tr>
                                <th class="px-3 py-2 font-medium">{{ t('Due') }}</th>
                                <th class="px-3 py-2 font-medium">{{ t('Amount') }}</th>
                                <th class="px-3 py-2 font-medium">{{ t('Paid') }}</th>
                                <th class="px-3 py-2 font-medium">{{ t('Status') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="row in item.installments"
                                :key="row.id"
                                class="border-t"
                            >
                                <td class="px-3 py-2">{{ formatDate(row.due_date) }}</td>
                                <td class="px-3 py-2 tabular-nums">
                                    {{ money(row.amount) }}
                                </td>
                                <td class="px-3 py-2 tabular-nums">
                                    {{ money(row.paid_amount) }}
                                </td>
                                <td class="px-3 py-2">
                                    <StatusBadge :status="row.status" />
                                </td>
                            </tr>
                            <tr v-if="!item.installments?.length">
                                <td
                                    colspan="4"
                                    class="px-3 py-8 text-center text-muted-foreground"
                                >
                                    {{ t('No installments') }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <Button as-child type="button" variant="outline" size="sm">
                        <Link :href="`/sales/${item.id}#receive-payment`">
                            {{ t('Receive payment') }}
                        </Link>
                    </Button>
                </template>
            </div>

            <div v-show="tab === 'documents'" class="space-y-3">
                <RecordAttachments
                    attachable="sales"
                    :attachable-id="isEdit ? item.id : null"
                    :attachments="item?.attachments"
                    :label="t('Contract documents')"
                    :error="errors.documents"
                    accept="application/pdf,image/jpeg,image/png,image/webp,.doc,.docx"
                />
            </div>

            <div class="mis-form-actions">
                <Button type="submit" :disabled="processing">{{ t('Save sale') }}</Button>
                <Button v-if="!isEdit" type="button" variant="secondary" @click="historyBack">{{ t('Cancel') }}</Button>
                <Button v-else as-child variant="secondary">
                    <Link href="/sales">{{ t('Cancel') }}</Link>
                </Button>
                <Form v-if="isEdit" :action="`/sales/${item.id}`" method="delete" class="ml-auto" v-slot="{ processing: deleting }">
                    <Button type="submit" variant="destructive" :disabled="deleting">
                        <Trash2 class="size-4" />
                        {{ deleting ? t('Deleting…') : t('Delete') }}
                    </Button>
                </Form>
            </div>
        </Form>
    </V2FormPage>
</template>
