<script setup lang="ts">
import { Form, Link, router } from '@inertiajs/vue3';
import { Pencil, Printer, Trash2 } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import Can from '@/components/Can.vue';
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

watch(
    () => [props.open, props.item] as const,
    () => {
        editing.value = !props.item;
        currency.value = props.item?.currency ?? baseCurrency.value;
    },
    { immediate: true },
);

const showForm = computed(() => isCreate.value || editing.value);

function removeIncome() {
    if (!props.item) {
        return;
    }

    router.delete(`/incomes/${props.item.id}`);
}
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent class="max-h-[90vh] overflow-y-auto sm:max-w-lg">
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
                v-slot="{ errors, processing }"
            >
                <div class="grid gap-2">
                    <Label for="income-description">{{ t('Description') }}</Label>
                    <textarea
                        id="income-description"
                        name="description"
                        rows="3"
                        required
                        class="rounded-md border border-input bg-transparent px-3 py-2 text-sm"
                        :value="item?.description ?? ''"
                    />
                    <InputError :message="errors.description" />
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="grid gap-2">
                        <Label for="income-date">{{ t('Date') }}</Label>
                        <Input
                            id="income-date"
                            name="income_date"
                            type="date"
                            required
                            :default-value="item?.income_date?.slice?.(0, 10) ?? new Date().toISOString().slice(0, 10)"
                        />
                        <InputError :message="errors.income_date" />
                    </div>
                    <div class="grid gap-2 sm:col-span-2">
                        <Label for="income-amount">{{ t('Total amount') }}</Label>
                        <div class="flex gap-2">
                            <Input id="income-amount" name="amount" type="number" step="0.01" min="0" required :default-value="item?.amount ?? ''" class="min-w-0 flex-1" />
                            <CurrencySelect v-model="currency" name="currency" class="w-[9rem] shrink-0" />
                        </div>
                        <InputError :message="errors.amount" />
                    </div>
                </div>
                <DialogFooter class="gap-2 sm:justify-between">
                    <Can permission="incomes.delete">
                        <Button v-if="item && !isCreate" type="button" variant="destructive" @click="removeIncome">
                            <Trash2 class="size-4" /> {{ t('Delete') }}
                        </Button>
                    </Can>
                    <div class="ml-auto flex gap-2">
                        <Button type="button" variant="secondary" @click="emit('update:open', false)">{{ t('Cancel') }}</Button>
                        <Button type="submit" :disabled="processing">{{ processing ? t('Saving…') : t('Save') }}</Button>
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
                        <Button type="button" variant="destructive" @click="removeIncome">
                            <Trash2 class="size-4" /> {{ t('Delete') }}
                        </Button>
                    </Can>
                    <div class="ml-auto flex flex-wrap gap-2">
                        <Button as-child variant="outline">
                            <Link :href="`/incomes/${item.id}`" target="_blank">
                                <Printer class="size-4" /> {{ t('Print') }}
                            </Link>
                        </Button>
                        <Can permission="incomes.edit">
                            <Button type="button" @click="editing = true">
                                <Pencil class="size-4" /> {{ t('Edit') }}
                            </Button>
                        </Can>
                    </div>
                </DialogFooter>
            </template>
        </DialogContent>
    </Dialog>
</template>
