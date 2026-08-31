<script setup lang="ts">
import { Form, Link, router } from '@inertiajs/vue3';
import { LoaderCircle, Pencil, Plus, Printer, Trash2 } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import Can from '@/components/Can.vue';
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
const categoryId = ref('');
const localCategories = ref<{ id: number; name: string; code: string }[]>([]);
const newCategoryName = ref('');
const addingCategory = ref(false);
const categoryError = ref<string | null>(null);
const showNewCategory = ref(false);

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
        categoryId.value = props.item?.expense_category_id ? String(props.item.expense_category_id) : '';
        newCategoryName.value = '';
        categoryError.value = null;
        showNewCategory.value = false;
    },
    { immediate: true },
);

const showForm = computed(() => !isPurchaseLinked.value && (isCreate.value || editing.value));
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

    router.delete(href, {
        preserveScroll: true,
        onSuccess: () => emit('update:open', false),
    });
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
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent class="max-h-[90vh] overflow-y-auto sm:max-w-2xl">
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
                v-slot="{ errors, processing }"
            >
                <section class="grid gap-3">
                    <h4 class="text-xs font-semibold tracking-wide text-muted-foreground uppercase">
                        {{ t('Details') }}
                    </h4>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="grid gap-2 sm:col-span-2">
                            <Label for="expense-description">{{ t('Description') }}</Label>
                            <textarea
                                id="expense-description"
                                v-model="description"
                                name="description"
                                rows="2"
                                required
                                class="rounded-md border border-input bg-transparent px-3 py-2 text-sm"
                            />
                            <InputError :message="errors.description" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="paid_to">{{ t('Paid to') }}</Label>
                            <Input id="paid_to" name="paid_to" required :default-value="item?.paid_to ?? ''" />
                            <InputError :message="errors.paid_to" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="expense_date">{{ t('Date') }}</Label>
                            <Input
                                id="expense_date"
                                name="expense_date"
                                type="date"
                                required
                                :default-value="item?.expense_date?.slice?.(0, 10) ?? new Date().toISOString().slice(0, 10)"
                            />
                            <InputError :message="errors.expense_date" />
                        </div>
                        <div class="grid gap-2">
                            <div class="flex items-center justify-between gap-2">
                                <Label for="expense_category_id">{{ t('Category') }}</Label>
                                <RelatedCreateLink href="/expense-categories" :label="t('Manage categories')" />
                            </div>
                            <select
                                id="expense_category_id"
                                v-model="categoryId"
                                name="expense_category_id"
                                class="h-9 rounded-md border border-input bg-transparent px-3 text-sm"
                            >
                                <option value="">{{ t('General') }}</option>
                                <option v-for="c in localCategories" :key="c.id" :value="String(c.id)">
                                    {{ c.name }}
                                </option>
                            </select>
                            <div class="flex gap-2">
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
                                    class="w-full"
                                    @click="showNewCategory = true"
                                >
                                    <Plus class="size-4" />
                                    {{ t('Add category') }}
                                </Button>
                            </div>
                            <p v-if="categoryError" class="text-sm text-destructive">{{ categoryError }}</p>
                        </div>
                        <div class="grid gap-2">
                            <Label for="reference_no">{{ t('Bill number') }}</Label>
                            <Input id="reference_no" name="reference_no" :default-value="item?.reference_no ?? ''" />
                            <InputError :message="errors.reference_no" />
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
                                class="h-9 rounded-md border border-input bg-transparent px-3 text-sm"
                            >
                                <option value="cash">{{ t('Cash') }}</option>
                                <option value="bank">{{ t('Bank payment') }}</option>
                            </select>
                            <InputError :message="errors.payment_method" />
                        </div>
                        <template v-if="isProjectExpense">
                            <input type="hidden" name="item_amount" value="1" />
                            <input type="hidden" name="per_price" :value="perPrice || item?.amount || ''" />
                            <div class="grid gap-2">
                                <Label for="expense-amount">{{ t('Amount') }}</Label>
                                <div class="flex gap-2">
                                    <Input
                                        id="expense-amount"
                                        v-model="perPrice"
                                        name="amount"
                                        type="number"
                                        step="0.01"
                                        min="0.01"
                                        required
                                        class="min-w-0 flex-1"
                                    />
                                    <CurrencySelect v-model="currency" name="currency" class="w-[9rem] shrink-0" />
                                </div>
                                <InputError :message="errors.amount" />
                            </div>
                        </template>
                        <template v-else>
                            <div class="grid gap-2">
                                <div class="flex items-center justify-between gap-2">
                                    <Label for="expense_unit_id">{{ t('Unit') }}</Label>
                                    <RelatedCreateLink href="/expense-units" :label="t('Add unit')" />
                                </div>
                                <select
                                    id="expense_unit_id"
                                    name="expense_unit_id"
                                    class="h-9 rounded-md border border-input bg-transparent px-3 text-sm"
                                    :value="item?.expense_unit_id ?? ''"
                                >
                                    <option value="">{{ t('Select unit') }}</option>
                                    <option v-for="u in units || []" :key="u.id" :value="u.id">{{ u.unit_name }}</option>
                                </select>
                            </div>
                            <div class="grid gap-2">
                                <Label for="item_amount">{{ t('Item amount') }}</Label>
                                <Input id="item_amount" v-model="itemAmount" name="item_amount" type="number" step="0.01" min="0" required />
                                <InputError :message="errors.item_amount" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="per_price">{{ t('Per price') }}</Label>
                                <Input id="per_price" v-model="perPrice" name="per_price" type="number" step="0.01" min="0" required />
                                <InputError :message="errors.per_price" />
                            </div>
                            <div class="grid gap-2 sm:col-span-2">
                                <Label>{{ t('Total cost') }}</Label>
                                <div class="flex gap-2">
                                    <Input type="number" readonly :model-value="totalCost" class="min-w-0 flex-1 bg-muted/40" />
                                    <CurrencySelect v-model="currency" name="currency" class="w-[9rem] shrink-0" />
                                </div>
                            </div>
                        </template>
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

                <DialogFooter class="gap-2 sm:justify-between">
                    <Can permission="expenses.delete">
                        <Button v-if="item && !isPurchaseLinked" type="button" variant="destructive" @click="removeExpense">
                            <Trash2 class="size-4" /> {{ t('Delete') }}
                        </Button>
                    </Can>
                    <div class="ms-auto flex gap-2">
                        <Button type="button" variant="secondary" @click="emit('update:open', false)">{{ t('Cancel') }}</Button>
                        <Button type="submit" :disabled="processing">{{ processing ? t('Saving…') : t('Save') }}</Button>
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
                        <Button type="button" variant="destructive" @click="removeExpense">
                            <Trash2 class="size-4" /> {{ t('Delete') }}
                        </Button>
                    </Can>
                    <div class="ms-auto flex flex-wrap gap-2">
                        <Button as-child variant="outline">
                            <Link :href="`/expenses/${item.id}`" target="_blank">
                                <Printer class="size-4" /> {{ t('Print') }}
                            </Link>
                        </Button>
                        <Can permission="expenses.edit">
                            <Button v-if="!isPurchaseLinked" type="button" @click="editing = true">
                                <Pencil class="size-4" /> {{ t('Edit') }}
                            </Button>
                        </Can>
                    </div>
                </DialogFooter>
            </template>
        </DialogContent>
    </Dialog>
</template>
