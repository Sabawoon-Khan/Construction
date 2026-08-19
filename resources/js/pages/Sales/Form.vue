<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, CalendarClock, FileText, Info, Trash2 } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import CurrencySelect from '@/components/CurrencySelect.vue';
import FileUploadField, { type ExistingUploadFile } from '@/components/FileUploadField.vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { MisTabs } from '@/components/mis';
import StatusBadge from '@/components/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useCalendar } from '@/composables/useCalendar';
import { useCurrency } from '@/composables/useCurrency';
import { useTranslations } from '@/composables/useTranslations';
import { money } from '@/lib/utils';

const props = defineProps<{ item: any; projects: any[]; customers: any[]; units: any[] }>();
const isEdit = computed(() => !!props.item);
const { t } = useTranslations();
const calendar = useCalendar();
const formatDate = (value: string | null | undefined) => calendar.format(value) || '—';
const tab = ref('info');

const projectId = ref(props.item?.project_id ? String(props.item.project_id) : '');
const unitId = ref(props.item?.unit_id ? String(props.item.unit_id) : '');
const customerId = ref(props.item?.customer_id ? String(props.item.customer_id) : '');
const status = ref(String(props.item?.status ?? 'active'));
const notes = ref(String(props.item?.notes ?? ''));
const discount = ref(String(props.item?.discount ?? 0));
const currency = ref(String(props.item?.currency ?? 'USD'));
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

const existingDocuments = computed<ExistingUploadFile[]>(() =>
    (props.item?.attachments ?? []).map((file: any) => ({
        id: file.id,
        name: file.original_filename || file.title || t('Document'),
        size: file.file_size,
        mime: file.mime_type,
        url: file.download_url,
    })),
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
</script>

<template>
    <Head :title="isEdit ? t('Edit sale') : t('Create sale')" />
    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
        <div class="space-y-3">
            <Button as-child variant="ghost" size="sm" class="-ml-2 w-fit">
                <Link href="/sales"><ArrowLeft class="size-4" /> {{ t('Back') }}</Link>
            </Button>
            <Heading
                :title="isEdit ? t('Edit sale') : t('Create sale')"
                :description="t('Sell a unit to a customer. Sale price is the contract total.')"
            />
        </div>
        <Form
            :action="isEdit ? `/sales/${item.id}` : '/sales'"
            :method="isEdit ? 'put' : 'post'"
            class="max-w-4xl space-y-6 rounded-xl border bg-card p-6"
            v-slot="{ errors, processing }"
        >
            <MisTabs v-model="tab" :tabs="tabs" />

            <div v-show="tab === 'info'" class="grid gap-4 sm:grid-cols-2">
                <div class="grid gap-2">
                    <Label>{{ t('Project') }}</Label>
                    <select
                        v-model="projectId"
                        name="project_id"
                        required
                        class="h-9 rounded-md border px-3 text-sm"
                    >
                        <option value="" disabled>{{ t('Select') }}</option>
                        <option v-for="p in projects" :key="p.id" :value="String(p.id)">
                            {{ p.name }}
                        </option>
                    </select>
                    <InputError :message="errors.project_id" />
                </div>
                <div class="grid gap-2">
                    <Label>{{ t('Unit') }}</Label>
                    <select
                        v-model="unitId"
                        name="unit_id"
                        required
                        class="h-9 rounded-md border px-3 text-sm"
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
                            {{ u.unit_no }} — {{ money(u.list_price, u.currency || 'USD') }}
                            ({{ t(u.status) }})
                        </option>
                    </select>
                    <InputError :message="errors.unit_id" />
                </div>
                <div
                    v-if="selectedUnit"
                    class="sm:col-span-2 rounded-lg border bg-muted/30 px-4 py-3 text-sm"
                >
                    {{ t('Sale price') }}:
                    <span class="font-semibold tabular-nums">
                        {{ money(selectedUnit.list_price, selectedUnit.currency || 'USD') }}
                    </span>
                    <span
                        v-if="Number(selectedUnit.list_price) <= 0"
                        class="ml-2 text-destructive"
                    >
                        {{ t('Set list price on the unit first.') }}
                    </span>
                </div>
                <div class="grid gap-2">
                    <Label>{{ t('Customer') }}</Label>
                    <select
                        v-model="customerId"
                        name="customer_id"
                        required
                        class="h-9 rounded-md border px-3 text-sm"
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
                    <Label>{{ t('Discount') }}</Label>
                    <Input
                        v-model="discount"
                        name="discount"
                        type="number"
                        step="0.01"
                    />
                </div>
                <div class="grid gap-2">
                    <Label>{{ t('Currency') }}</Label>
                    <CurrencySelect v-model="currency" />
                    <input type="hidden" name="currency" :value="currency" />
                </div>
                <div class="grid gap-2">
                    <Label>{{ t('Status') }}</Label>
                    <select
                        v-model="status"
                        name="status"
                        class="h-9 rounded-md border px-3 text-sm"
                    >
                        <option value="draft">{{ t('draft') }}</option>
                        <option value="active">{{ t('active') }}</option>
                        <option value="completed">{{ t('completed') }}</option>
                        <option value="cancelled">{{ t('cancelled') }}</option>
                    </select>
                </div>
                <div class="grid gap-2 sm:col-span-2">
                    <Label>{{ t('Notes') }}</Label>
                    <textarea
                        v-model="notes"
                        name="notes"
                        rows="2"
                        class="rounded-md border px-3 py-2 text-sm"
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
                        <thead class="bg-muted/40 text-left text-muted-foreground">
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
                    <input
                        type="file"
                        name="documents[]"
                        multiple
                        accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx"
                        class="text-sm"
                    />
                    <InputError :message="errors.documents" />
                </div>
            </div>

            <div class="flex gap-2">
                <Button type="submit" :disabled="processing">{{ t('Save sale') }}</Button>
                <Button as-child variant="secondary">
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
    </div>
</template>
