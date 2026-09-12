<script setup lang="ts">
import { Form, Link, router } from '@inertiajs/vue3';
import { Pencil, Printer, Trash2 } from '@lucide/vue';
import { computed, nextTick, ref, watch } from 'vue';
import Can from '@/components/Can.vue';
import ConfirmActionDialog from '@/components/ConfirmActionDialog.vue';
import CurrencySelect from '@/components/CurrencySelect.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useCalendar } from '@/composables/useCalendar';
import { useCurrency } from '@/composables/useCurrency';
import { useTranslations } from '@/composables/useTranslations';
import { money } from '@/lib/utils';

const props = defineProps<{
    open: boolean;
    item: Record<string, any> | null;
    startEditing?: boolean;
}>();

const emit = defineEmits<{
    'update:open': [value: boolean];
}>();

const { t } = useTranslations();
const { baseCurrency } = useCurrency();
const calendar = useCalendar();
const formatDate = (value: string | null | undefined) => calendar.format(value) || '—';

const isCreate = computed(() => !props.item);
const editing = ref(false);
const currency = ref(baseCurrency.value);
const description = ref('');
const incomeDate = ref('');
const amount = ref('');
const confirmDeleteOpen = ref(false);
const deleting = ref(false);
const restoringDraft = ref(false);
const discardOpen = ref(false);
const baseline = ref<string | null>(null);

const draftKey = computed(() =>
    isCreate.value ? 'income-dialog-draft:create' : `income-dialog-draft:edit:${props.item?.id}`,
);

type IncomeDraft = {
    description?: string;
    income_date?: string;
    amount?: string;
    currency?: string;
};

function readDraft(): IncomeDraft | null {
    try {
        const raw = localStorage.getItem(draftKey.value);
        if (!raw) {
            return null;
        }
        return JSON.parse(raw) as IncomeDraft;
    } catch {
        return null;
    }
}

function writeDraft() {
    if (!isCreate.value || !props.open || restoringDraft.value) {
        return;
    }
    localStorage.setItem(
        draftKey.value,
        JSON.stringify({
            description: description.value,
            income_date: incomeDate.value,
            amount: amount.value,
            currency: currency.value,
        } satisfies IncomeDraft),
    );
}

function clearDraft() {
    localStorage.removeItem(draftKey.value);
}

function currentDraftSnapshot(): IncomeDraft {
    return {
        description: description.value,
        income_date: incomeDate.value,
        amount: amount.value,
        currency: currency.value,
    };
}

function captureBaseline() {
    baseline.value = JSON.stringify(currentDraftSnapshot());
}

function requestClose(nextOpen: boolean) {
    if (nextOpen) {
        emit('update:open', true);
        return;
    }
    if (showForm.value && isDirty.value) {
        discardOpen.value = true;
        return;
    }
    emit('update:open', false);
}

function discardChanges() {
    clearDraft();
    discardOpen.value = false;
    emit('update:open', false);
}

watch(
    () => [props.open, props.item, props.startEditing] as const,
    () => {
        editing.value = !props.item || !!props.startEditing;
        currency.value = props.item?.currency ?? baseCurrency.value;
        description.value = props.item?.description ?? '';
        incomeDate.value =
            props.item?.income_date?.slice?.(0, 10) ?? new Date().toISOString().slice(0, 10);
        amount.value = props.item?.amount != null ? String(props.item.amount) : '';

        if (props.open && isCreate.value) {
            restoringDraft.value = true;
            const draft = readDraft();
            if (draft) {
                if (draft.description != null) {
                    description.value = draft.description;
                }
                if (draft.income_date != null) {
                    incomeDate.value = draft.income_date;
                }
                if (draft.amount != null) {
                    amount.value = draft.amount;
                }
                if (draft.currency) {
                    currency.value = draft.currency;
                }
            }
            nextTick(() => {
                restoringDraft.value = false;
                captureBaseline();
            });
        } else {
            nextTick(() => captureBaseline());
        }
    },
    { immediate: true },
);

watch([description, incomeDate, amount, currency], () => writeDraft());

const showForm = computed(() => isCreate.value || editing.value);

const isDirty = computed(() => {
    if (!showForm.value || baseline.value == null) {
        return false;
    }
    return JSON.stringify(currentDraftSnapshot()) !== baseline.value;
});

function errorId(field: string) {
    return `income-${field}-error`;
}

function describedBy(errors: Record<string, string>, field: string) {
    return errors[field] ? errorId(field) : undefined;
}

const fieldFocusOrder = ['description', 'income_date', 'amount', 'currency'] as const;
const fieldIds: Record<string, string> = {
    description: 'income-description',
    income_date: 'income-date',
    amount: 'income-amount',
};

function focusFirstInvalid(errors: Record<string, string>) {
    nextTick(() => {
        for (const field of fieldFocusOrder) {
            if (!errors[field]) {
                continue;
            }
            const id = fieldIds[field];
            const el = (id ? document.getElementById(id) : null) as HTMLElement | null;
            const target = el ?? (document.querySelector(`[name="${field}"]`) as HTMLElement | null);
            if (target?.focus) {
                target.focus();
                break;
            }
        }
    });
}

function onFormKeydown(event: KeyboardEvent) {
    if ((event.metaKey || event.ctrlKey) && event.key === 'Enter') {
        event.preventDefault();
        const form = event.currentTarget as HTMLFormElement | null;
        form?.requestSubmit?.();
    }
}

function onSaveSuccess() {
    clearDraft();
    emit('update:open', false);
}

function removeIncome() {
    if (!props.item) {
        return;
    }

    deleting.value = true;

    router.delete(`/incomes/${props.item.id}`, {
        preserveScroll: true,
        onFinish: () => {
            deleting.value = false;
        },
        onSuccess: () => {
            confirmDeleteOpen.value = false;
            emit('update:open', false);
        },
    });
}
</script>

<template>
    <Dialog :open="open" @update:open="requestClose">
        <DialogContent class="sm:max-w-lg">
            <DialogHeader>
                <DialogTitle>
                    {{ isCreate ? t('Record income') : editing ? t('Edit income') : (item?.income_no || t('Income')) }}
                </DialogTitle>
                <DialogDescription>
                    {{ t('Company-wide income not tied to a project.') }}
                </DialogDescription>
            </DialogHeader>

            <Form
                v-if="showForm"
                :action="isCreate ? '/incomes' : `/incomes/${item!.id}`"
                :method="isCreate ? 'post' : 'put'"
                class="grid gap-4"
                :on-success="onSaveSuccess"
                :on-error="focusFirstInvalid"
                v-slot="{ errors, processing }"
                @keydown="onFormKeydown"
            >
                <div class="grid gap-2">
                    <Label for="income-description">{{ t('Description') }}</Label>
                    <textarea
                        id="income-description"
                        v-model="description"
                        name="description"
                        rows="3"
                        required
                        class="rounded-md border border-input bg-transparent px-3 py-2 text-sm"
                        :aria-invalid="errors.description ? 'true' : undefined"
                        :aria-describedby="describedBy(errors, 'description')"
                    />
                    <InputError :id="errorId('description')" :message="errors.description" />
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="grid gap-2">
                        <Label for="income-date">{{ t('Date') }}</Label>
                        <Input
                            id="income-date"
                            v-model="incomeDate"
                            name="income_date"
                            type="date"
                            required
                            :aria-invalid="errors.income_date ? 'true' : undefined"
                            :aria-describedby="describedBy(errors, 'income_date')"
                        />
                        <InputError :id="errorId('income_date')" :message="errors.income_date" />
                    </div>
                    <div class="grid gap-2 sm:col-span-2">
                        <Label for="income-amount">{{ t('Total amount') }} ({{ currency }})</Label>
                        <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                            <Input
                                id="income-amount"
                                v-model="amount"
                                name="amount"
                                type="number"
                                step="0.01"
                                min="0"
                                required
                                class="min-w-0 flex-1"
                                :aria-invalid="errors.amount ? 'true' : undefined"
                                :aria-describedby="describedBy(errors, 'amount')"
                            />
                            <CurrencySelect
                                v-model="currency"
                                name="currency"
                                class="w-full sm:w-[9rem] sm:shrink-0"
                            />
                        </div>
                        <InputError :id="errorId('amount')" :message="errors.amount" />
                    </div>
                </div>
                <DialogFooter class="gap-2 sm:justify-between">
                    <Can permission="incomes.delete">
                        <Button v-if="item && !isCreate" type="button" variant="destructive" class="w-full sm:w-auto" @click="confirmDeleteOpen = true">
                            <Trash2 class="size-4" /> {{ t('Delete') }}
                        </Button>
                    </Can>
                    <div class="flex w-full gap-2 sm:ms-auto sm:w-auto">
                        <Button type="button" variant="secondary" class="flex-1 sm:flex-none" @click="requestClose(false)">{{ t('Cancel') }}</Button>
                        <Button type="submit" class="flex-1 sm:flex-none" :disabled="processing">{{ processing ? t('Saving…') : t('Save') }}</Button>
                    </div>
                </DialogFooter>
            </Form>

            <template v-else-if="item">
                <div class="rounded-lg border bg-muted/30 p-4">
                    <div class="text-sm text-muted-foreground">{{ t('Total amount') }}</div>
                    <div class="mt-1 text-2xl font-semibold tabular-nums text-emerald-700">
                        {{ money(item.amount, item.currency || baseCurrency) }}
                    </div>
                </div>
                <dl class="grid gap-3 text-sm sm:grid-cols-2">
                    <div>
                        <dt class="text-muted-foreground">{{ t('Date') }}</dt>
                        <dd class="font-medium">{{ formatDate(item.income_date) }}</dd>
                    </div>
                    <div class="sm:col-span-2">
                        <dt class="text-muted-foreground">{{ t('Description') }}</dt>
                        <dd class="font-medium whitespace-pre-wrap">{{ item.description || '—' }}</dd>
                    </div>
                </dl>
                <DialogFooter class="gap-2 sm:justify-between">
                    <Can permission="incomes.delete">
                        <Button type="button" variant="destructive" class="w-full sm:w-auto" @click="confirmDeleteOpen = true">
                            <Trash2 class="size-4" /> {{ t('Delete') }}
                        </Button>
                    </Can>
                    <div class="flex w-full flex-wrap gap-2 sm:ms-auto sm:w-auto">
                        <Button as-child variant="outline" class="flex-1 sm:flex-none">
                            <Link :href="`/incomes/${item.id}`" target="_blank">
                                <Printer class="size-4" /> {{ t('Print') }}
                            </Link>
                        </Button>
                        <Can permission="incomes.edit">
                            <Button type="button" class="flex-1 sm:flex-none" @click="editing = true; nextTick(() => captureBaseline())">
                                <Pencil class="size-4" /> {{ t('Edit') }}
                            </Button>
                        </Can>
                    </div>
                </DialogFooter>
            </template>
        </DialogContent>
    </Dialog>

    <ConfirmActionDialog
        v-model:open="confirmDeleteOpen"
        :title="t('Delete income?')"
        :description="t('This cannot be undone.')"
        :confirm-label="t('Delete')"
        :processing="deleting"
        @confirm="removeIncome"
    />

    <ConfirmActionDialog
        v-model:open="discardOpen"
        :title="t('Discard changes?')"
        :description="t('You have unsaved changes. Discard them?')"
        :confirm-label="t('Discard')"
        @confirm="discardChanges"
    />
</template>
