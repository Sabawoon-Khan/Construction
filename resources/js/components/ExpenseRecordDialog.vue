<script setup lang="ts">
import { Form, Link, router } from '@inertiajs/vue3';
import {
    ChevronsDown,
    ChevronsUp,
    LoaderCircle,
    Pencil,
    Plus,
    Printer,
    Trash2,
} from '@lucide/vue';
import { computed, nextTick, ref, watch } from 'vue';
import Can from '@/components/Can.vue';
import ConfirmActionDialog from '@/components/ConfirmActionDialog.vue';
import CurrencySelect from '@/components/CurrencySelect.vue';
import InputError from '@/components/InputError.vue';
import RelatedCreateLink from '@/components/RelatedCreateLink.vue';
import RecordAttachments from '@/components/RecordAttachments.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
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
    projectId?: number | null;
    categories?: { id: number; name: string; code: string }[];
    units?: { id: number; unit_name: string }[];
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
const isPurchaseLinked = computed(() => !!props.item?.purchase_id);
const isProjectExpense = computed(() => !!props.projectId || !!props.item?.project_id);
const editing = ref(false);
const currency = ref(baseCurrency.value);
const itemAmount = ref('');
const perPrice = ref('');
const paymentMethod = ref('cash');
const description = ref('');
const paidTo = ref('');
const expenseDate = ref('');
const referenceNo = ref('');
const categoryId = ref('');
const localCategories = ref<{ id: number; name: string; code: string }[]>([]);
const newCategoryName = ref('');
const addingCategory = ref(false);
const categoryError = ref<string | null>(null);
const showNewCategory = ref(false);
const confirmDeleteOpen = ref(false);
const deleting = ref(false);
const moving = ref(false);
const placeAt = ref<'first' | 'last'>('last');
const restoringDraft = ref(false);
const discardOpen = ref(false);
const baseline = ref<string | null>(null);

const draftKey = computed(() => {
    if (isCreate.value) {
        const projectPart = props.projectId ? `:project:${props.projectId}` : '';
        return `expense-dialog-draft:create${projectPart}`;
    }
    return `expense-dialog-draft:edit:${props.item?.id}`;
});

type ExpenseDraft = {
    description?: string;
    paid_to?: string;
    expense_date?: string;
    expense_category_id?: string;
    reference_no?: string;
    payment_method?: string;
    currency?: string;
    item_amount?: string;
    per_price?: string;
};

function readDraft(): ExpenseDraft | null {
    try {
        const raw = localStorage.getItem(draftKey.value);
        if (!raw) {
            return null;
        }
        return JSON.parse(raw) as ExpenseDraft;
    } catch {
        return null;
    }
}

function writeDraft() {
    if (!isCreate.value || !props.open || restoringDraft.value) {
        return;
    }
    const payload: ExpenseDraft = {
        description: description.value,
        paid_to: paidTo.value,
        expense_date: expenseDate.value,
        expense_category_id: categoryId.value,
        reference_no: referenceNo.value,
        payment_method: paymentMethod.value,
        currency: currency.value,
        item_amount: itemAmount.value,
        per_price: perPrice.value,
    };
    localStorage.setItem(draftKey.value, JSON.stringify(payload));
}

function clearDraft() {
    localStorage.removeItem(draftKey.value);
}

function currentDraftSnapshot(): ExpenseDraft {
    return {
        description: description.value,
        paid_to: paidTo.value,
        expense_date: expenseDate.value,
        expense_category_id: categoryId.value,
        reference_no: referenceNo.value,
        payment_method: paymentMethod.value,
        currency: currency.value,
        item_amount: itemAmount.value,
        per_price: perPrice.value,
    };
}

function captureBaseline() {
    baseline.value = JSON.stringify(currentDraftSnapshot());
}

function applyDraft(draft: ExpenseDraft) {
    if (draft.description != null) {
        description.value = draft.description;
    }
    if (draft.paid_to != null) {
        paidTo.value = draft.paid_to;
    }
    if (draft.expense_date != null) {
        expenseDate.value = draft.expense_date;
    }
    if (draft.expense_category_id != null) {
        categoryId.value = draft.expense_category_id;
    }
    if (draft.reference_no != null) {
        referenceNo.value = draft.reference_no;
    }
    if (draft.payment_method === 'bank' || draft.payment_method === 'cash') {
        paymentMethod.value = draft.payment_method;
    }
    if (draft.currency) {
        currency.value = draft.currency;
    }
    if (draft.item_amount != null) {
        itemAmount.value = draft.item_amount;
    }
    if (draft.per_price != null) {
        perPrice.value = draft.per_price;
    }
}

watch(
    () => props.categories,
    (categories) => {
        localCategories.value = [...(categories ?? [])];
    },
    { immediate: true },
);

watch(
    () => [props.open, props.item, props.startEditing] as const,
    () => {
        editing.value = !props.item || (!!props.startEditing && !props.item?.purchase_id);
        currency.value = props.item?.currency ?? baseCurrency.value;
        itemAmount.value = String(props.item?.item_amount ?? (isProjectExpense.value ? '1' : ''));
        perPrice.value = String(props.item?.per_price ?? props.item?.amount ?? '');
        paymentMethod.value = props.item?.payment_method === 'bank' ? 'bank' : 'cash';
        description.value = props.item?.description ?? '';
        paidTo.value = props.item?.paid_to ?? '';
        expenseDate.value = props.item?.expense_date?.slice?.(0, 10) ?? '';
        referenceNo.value = props.item?.reference_no ?? '';
        categoryId.value = props.item?.expense_category_id ? String(props.item.expense_category_id) : '';
        newCategoryName.value = '';
        categoryError.value = null;
        showNewCategory.value = false;
        placeAt.value = 'last';

        if (props.open && isCreate.value) {
            restoringDraft.value = true;
            const draft = readDraft();
            if (draft) {
                applyDraft(draft);
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

watch(
    [description, paidTo, expenseDate, categoryId, referenceNo, paymentMethod, currency, itemAmount, perPrice],
    () => writeDraft(),
);

const showForm = computed(() => !isPurchaseLinked.value && (isCreate.value || editing.value));

const isDirty = computed(() => {
    if (!showForm.value || baseline.value == null) {
        return false;
    }
    return JSON.stringify(currentDraftSnapshot()) !== baseline.value;
});

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

const categoryLabel = computed(() => {
    if (props.item?.category?.name) {
        return props.item.category.name;
    }

    const selected = localCategories.value.find((c) => String(c.id) === categoryId.value);
    return selected?.name ?? t('General');
});
const totalCost = computed(() => {
    const qty = Number(itemAmount.value);
    const price = Number(perPrice.value);
    if (!Number.isFinite(qty) || !Number.isFinite(price)) {
        return '';
    }

    return (qty * price).toFixed(2);
});

const storeAction = computed(() =>
    props.projectId && isCreate.value ? `/projects/${props.projectId}/expenses` : '/expenses',
);

const fieldFocusOrder = [
    'description',
    'paid_to',
    'expense_date',
    'expense_category_id',
    'reference_no',
    'payment_method',
    'amount',
    'item_amount',
    'per_price',
    'currency',
] as const;

const fieldIds: Record<string, string> = {
    description: 'expense-description',
    paid_to: 'paid_to',
    expense_date: 'expense_date',
    expense_category_id: 'expense_category_id',
    reference_no: 'reference_no',
    payment_method: 'payment_method',
    amount: 'expense-amount',
    item_amount: 'item_amount',
    per_price: 'per_price',
};

function errorId(field: string) {
    return `expense-${field}-error`;
}

function describedBy(errors: Record<string, string>, field: string) {
    return errors[field] ? errorId(field) : undefined;
}

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
        (event.currentTarget as HTMLFormElement | null)?.requestSubmit?.();
    }
}

function onSaveSuccess() {
    clearDraft();
    emit('update:open', false);
}

function paymentLabel(method?: string | null) {
    if (method === 'bank') {
        return t('Bank payment');
    }
    return t('Cash');
}

function removeExpense() {
    if (!props.item) {
        return;
    }

    const href = props.item.purchase_id
        ? `/purchases/${props.item.purchase_id}`
        : `/expenses/${props.item.id}`;

    deleting.value = true;

    router.delete(href, {
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

function moveExpense(position: 'first' | 'last') {
    if (!props.item?.id) {
        return;
    }

    moving.value = true;
    router.patch(
        `/expenses/${props.item.id}/move`,
        { position },
        {
            preserveScroll: true,
            onFinish: () => {
                moving.value = false;
            },
            onSuccess: () => {
                emit('update:open', false);
            },
        },
    );
}

async function addCategory() {
    const name = newCategoryName.value.trim();
    if (!name) {
        categoryError.value = t('Category name is required');
        return;
    }

    addingCategory.value = true;
    categoryError.value = null;

    try {
        const response = await fetch('/expense-categories', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN':
                    document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({ name }),
        });

        if (!response.ok) {
            const data = await response.json().catch(() => null);
            categoryError.value =
                data?.errors?.name?.[0] ?? data?.message ?? t('Could not create category');
            return;
        }

        const category = await response.json();
        localCategories.value = [...localCategories.value, category].sort((a, b) =>
            a.name.localeCompare(b.name),
        );
        categoryId.value = String(category.id);
        newCategoryName.value = '';
        showNewCategory.value = false;
    } catch {
        categoryError.value = t('Could not create category');
    } finally {
        addingCategory.value = false;
    }
}
</script>

<template>
    <Dialog :open="open" @update:open="requestClose">
        <DialogContent class="sm:max-w-2xl">
            <DialogHeader>
                <DialogTitle>
                    {{
                        isCreate
                            ? t('Record expense')
                            : editing
                              ? t('Edit expense')
                              : item?.expense_no || t('Expense')
                    }}
                </DialogTitle>
            </DialogHeader>

            <Form
                v-if="showForm"
                :action="isCreate ? storeAction : `/expenses/${item!.id}`"
                :method="isCreate ? 'post' : 'put'"
                class="grid gap-5"
                :on-success="onSaveSuccess"
                :on-error="focusFirstInvalid"
                v-slot="{ errors, processing }"
                @keydown="onFormKeydown"
            >
                <section class="grid gap-3">
                    <h4 class="text-xs font-semibold tracking-wide text-muted-foreground uppercase">
                        {{ t('Details') }}
                    </h4>
                    <div class="grid gap-4">
                        <div class="grid gap-2">
                            <Label for="expense-description">{{ t('Description') }}</Label>
                            <textarea
                                id="expense-description"
                                v-model="description"
                                name="description"
                                rows="2"
                                required
                                class="min-h-[4.5rem] w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm"
                                :aria-invalid="errors.description ? 'true' : undefined"
                                :aria-describedby="describedBy(errors, 'description')"
                            />
                            <InputError :id="errorId('description')" :message="errors.description" />
                        </div>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="grid min-w-0 gap-2">
                                <Label for="paid_to">{{ t('Paid to') }}</Label>
                                <Input
                                    id="paid_to"
                                    v-model="paidTo"
                                    name="paid_to"
                                    required
                                    :aria-invalid="errors.paid_to ? 'true' : undefined"
                                    :aria-describedby="describedBy(errors, 'paid_to')"
                                />
                                <InputError :id="errorId('paid_to')" :message="errors.paid_to" />
                            </div>
                            <div class="grid min-w-0 gap-2">
                                <Label for="expense_date">{{ t('Date') }}</Label>
                                <Input
                                    id="expense_date"
                                    v-model="expenseDate"
                                    name="expense_date"
                                    type="date"
                                    :aria-invalid="errors.expense_date ? 'true' : undefined"
                                    :aria-describedby="describedBy(errors, 'expense_date')"
                                />
                                <InputError :id="errorId('expense_date')" :message="errors.expense_date" />
                            </div>
                        </div>
                        <div class="grid min-w-0 gap-2">
                            <div class="flex min-w-0 flex-wrap items-center justify-between gap-2">
                                <Label for="expense_category_id">{{ t('Category') }}</Label>
                                <RelatedCreateLink href="/expense-categories" :label="t('Manage categories')" />
                            </div>
                            <select
                                id="expense_category_id"
                                v-model="categoryId"
                                name="expense_category_id"
                                class="h-10 w-full min-w-0 rounded-md border border-input bg-transparent px-3 text-sm"
                                :aria-invalid="errors.expense_category_id ? 'true' : undefined"
                                :aria-describedby="describedBy(errors, 'expense_category_id')"
                            >
                                <option value="">{{ t('General') }}</option>
                                <option v-for="c in localCategories" :key="c.id" :value="String(c.id)">
                                    {{ c.name }}
                                </option>
                            </select>
                            <div class="flex min-w-0 flex-col gap-2 sm:flex-row">
                                <Input
                                    v-if="showNewCategory"
                                    v-model="newCategoryName"
                                    :placeholder="t('New category name')"
                                    class="min-w-0 flex-1"
                                    @keydown.enter.prevent="addCategory"
                                />
                                <Button
                                    v-if="showNewCategory"
                                    type="button"
                                    size="sm"
                                    class="shrink-0"
                                    :disabled="addingCategory"
                                    @click="addCategory"
                                >
                                    <LoaderCircle v-if="addingCategory" class="size-4 animate-spin" />
                                    <Plus v-else class="size-4" />
                                    {{ t('Add') }}
                                </Button>
                                <Button
                                    v-else
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    class="w-full shrink-0 sm:w-auto"
                                    @click="showNewCategory = true"
                                >
                                    <Plus class="size-4" />
                                    {{ t('Add category') }}
                                </Button>
                            </div>
                            <InputError
                                :id="errorId('expense_category_id')"
                                :message="errors.expense_category_id"
                            />
                            <p v-if="categoryError" class="text-sm text-destructive">{{ categoryError }}</p>
                        </div>
                        <div class="grid min-w-0 gap-2 sm:max-w-xs">
                            <Label for="reference_no">{{ t('Bill number') }}</Label>
                            <Input
                                id="reference_no"
                                v-model="referenceNo"
                                name="reference_no"
                                :aria-invalid="errors.reference_no ? 'true' : undefined"
                                :aria-describedby="describedBy(errors, 'reference_no')"
                            />
                            <InputError :id="errorId('reference_no')" :message="errors.reference_no" />
                        </div>
                    </div>
                </section>

                <section class="grid gap-3">
                    <h4 class="text-xs font-semibold tracking-wide text-muted-foreground uppercase">
                        {{ t('Payment') }}
                    </h4>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="grid gap-2">
                            <Label for="payment_method">{{ t('Paying type') }}</Label>
                            <select
                                id="payment_method"
                                v-model="paymentMethod"
                                name="payment_method"
                                class="h-10 rounded-md border border-input bg-transparent px-3 text-sm"
                                :aria-invalid="errors.payment_method ? 'true' : undefined"
                                :aria-describedby="describedBy(errors, 'payment_method')"
                            >
                                <option value="cash">{{ t('Cash') }}</option>
                                <option value="bank">{{ t('Bank payment') }}</option>
                            </select>
                            <InputError :id="errorId('payment_method')" :message="errors.payment_method" />
                        </div>
                        <div class="grid gap-2">
                            <div class="flex items-center justify-between gap-2">
                                <Label for="expense_unit_id">{{ t('Unit') }}</Label>
                                <RelatedCreateLink href="/expense-units" :label="t('Add unit')" />
                            </div>
                            <select
                                id="expense_unit_id"
                                name="expense_unit_id"
                                class="h-10 rounded-md border border-input bg-transparent px-3 text-sm"
                                :value="item?.expense_unit_id ?? ''"
                            >
                                <option value="">{{ t('Select unit') }}</option>
                                <option v-for="u in units || []" :key="u.id" :value="u.id">{{ u.unit_name }}</option>
                            </select>
                        </div>
                        <div class="grid gap-2">
                            <Label for="item_amount">{{ t('Item amount') }}</Label>
                            <Input
                                id="item_amount"
                                v-model="itemAmount"
                                name="item_amount"
                                type="number"
                                step="0.01"
                                min="0"
                                required
                                :aria-invalid="errors.item_amount ? 'true' : undefined"
                                :aria-describedby="describedBy(errors, 'item_amount')"
                            />
                            <InputError :id="errorId('item_amount')" :message="errors.item_amount" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="per_price">{{ t('Per price') }} ({{ currency }})</Label>
                            <Input
                                id="per_price"
                                v-model="perPrice"
                                name="per_price"
                                type="number"
                                step="0.01"
                                min="0"
                                required
                                :aria-invalid="errors.per_price ? 'true' : undefined"
                                :aria-describedby="describedBy(errors, 'per_price')"
                            />
                            <InputError :id="errorId('per_price')" :message="errors.per_price" />
                        </div>
                        <div class="grid gap-2 sm:col-span-2">
                            <Label>{{ t('Total cost') }} ({{ currency }})</Label>
                            <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                                <Input type="number" readonly :model-value="totalCost" class="min-w-0 flex-1 bg-muted/40" />
                                <CurrencySelect
                                    v-model="currency"
                                    name="currency"
                                    class="w-full sm:w-[9rem] sm:shrink-0"
                                />
                            </div>
                        </div>
                    </div>
                </section>

                <section class="grid gap-3">
                    <h4 class="text-xs font-semibold tracking-wide text-muted-foreground uppercase">
                        {{ t('Attachment') }}
                    </h4>
                    <RecordAttachments
                        attachable="expenses"
                        :attachable-id="item?.id"
                        :attachments="item?.attachments"
                        :label="t('Receipt file')"
                        accept=".pdf,.jpg,.jpeg,.png,.webp"
                    />
                </section>

                <section v-if="isCreate && isProjectExpense" class="grid gap-3">
                    <h4 class="text-xs font-semibold tracking-wide text-muted-foreground uppercase">
                        {{ t('Order') }}
                    </h4>
                    <div class="grid gap-2 sm:max-w-xs">
                        <Label for="place_at">{{ t('Place in list') }}</Label>
                        <select
                            id="place_at"
                            v-model="placeAt"
                            name="place_at"
                            class="h-10 rounded-md border border-input bg-transparent px-3 text-sm"
                        >
                            <option value="last">{{ t('At the end') }}</option>
                            <option value="first">{{ t('At the beginning') }}</option>
                        </select>
                    </div>
                </section>

                <DialogFooter class="gap-2 sm:justify-between">
                    <Can permission="expenses.delete">
                        <Button v-if="item && !isPurchaseLinked" type="button" variant="destructive" class="w-full sm:w-auto" @click="confirmDeleteOpen = true">
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
                    <div class="text-sm text-muted-foreground">{{ t('Total cost') }}</div>
                    <div class="mt-1 text-2xl font-semibold tabular-nums text-rose-700">
                        {{ money(item.amount, item.currency || baseCurrency) }}
                    </div>
                </div>
                <dl class="grid gap-3 text-sm sm:grid-cols-2">
                    <div>
                        <dt class="text-muted-foreground">{{ t('Date') }}</dt>
                        <dd class="font-medium">{{ formatDate(item.expense_date) }}</dd>
                    </div>
                    <div>
                        <dt class="text-muted-foreground">{{ t('Paid to') }}</dt>
                        <dd class="font-medium">{{ item.paid_to || '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-muted-foreground">{{ t('Category') }}</dt>
                        <dd class="font-medium">{{ categoryLabel }}</dd>
                    </div>
                    <div>
                        <dt class="text-muted-foreground">{{ t('Bill number') }}</dt>
                        <dd class="font-medium">{{ item.reference_no || '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-muted-foreground">{{ t('Paying type') }}</dt>
                        <dd class="font-medium">{{ paymentLabel(item.payment_method) }}</dd>
                    </div>
                    <div v-if="item.expense_unit?.unit_name">
                        <dt class="text-muted-foreground">{{ t('Unit') }}</dt>
                        <dd class="font-medium">{{ item.expense_unit.unit_name }}</dd>
                    </div>
                    <div class="sm:col-span-2">
                        <dt class="text-muted-foreground">{{ t('Description') }}</dt>
                        <dd class="font-medium whitespace-pre-wrap">{{ item.description || '—' }}</dd>
                    </div>
                    <div class="sm:col-span-2">
                        <RecordAttachments
                            attachable="expenses"
                            :attachable-id="item.id"
                            :attachments="item.attachments"
                            :label="t('Receipt file')"
                            accept=".pdf,.jpg,.jpeg,.png,.webp"
                            readonly
                        />
                    </div>
                </dl>
                <DialogFooter class="gap-2 sm:justify-between">
                    <Can :permission="isPurchaseLinked ? 'purchases.delete' : 'expenses.delete'">
                        <Button type="button" variant="destructive" class="w-full sm:w-auto" @click="confirmDeleteOpen = true">
                            <Trash2 class="size-4" /> {{ t('Delete') }}
                        </Button>
                    </Can>
                    <div class="flex w-full flex-wrap gap-2 sm:ms-auto sm:w-auto">
                        <Can permission="expenses.edit">
                            <Button
                                type="button"
                                variant="outline"
                                class="flex-1 sm:flex-none"
                                :disabled="moving"
                                @click="moveExpense('first')"
                            >
                                <ChevronsUp class="size-4" /> {{ t('Move to first') }}
                            </Button>
                            <Button
                                type="button"
                                variant="outline"
                                class="flex-1 sm:flex-none"
                                :disabled="moving"
                                @click="moveExpense('last')"
                            >
                                <ChevronsDown class="size-4" /> {{ t('Move to last') }}
                            </Button>
                        </Can>
                        <Button as-child variant="outline" class="flex-1 sm:flex-none">
                            <Link :href="`/expenses/${item.id}`" target="_blank">
                                <Printer class="size-4" /> {{ t('Print') }}
                            </Link>
                        </Button>
                        <Can permission="expenses.edit">
                            <Button v-if="!isPurchaseLinked" type="button" class="flex-1 sm:flex-none" @click="editing = true; nextTick(() => captureBaseline())">
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
        :title="t('Delete expense?')"
        :description="t('This cannot be undone.')"
        :confirm-label="t('Delete')"
        :processing="deleting"
        @confirm="removeExpense"
    />

    <ConfirmActionDialog
        v-model:open="discardOpen"
        :title="t('Discard changes?')"
        :description="t('You have unsaved changes. Discard them?')"
        :confirm-label="t('Discard')"
        @confirm="discardChanges"
    />
</template>
