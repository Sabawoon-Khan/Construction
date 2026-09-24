<script setup lang="ts">
import { computed, watch } from 'vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { useTranslations } from '@/composables/useTranslations';
import type { PrintRecordOption } from '@/lib/printRecords';
import { formatNumber } from '@/lib/utils';

const open = defineModel<boolean>('open', { required: true });
const selectedKeys = defineModel<string[]>('selectedKeys', { required: true });

const props = withDefaults(
    defineProps<{
        records: PrintRecordOption[];
        title?: string;
        description?: string;
        emptyLabel?: string;
    }>(),
    {
        title: undefined,
        description: undefined,
        emptyLabel: undefined,
    },
);

const emit = defineEmits<{
    confirm: [keys: string[]];
}>();

const { t } = useTranslations();

const allSelected = computed(
    () =>
        props.records.length > 0 &&
        selectedKeys.value.length === props.records.length,
);

function isSelected(key: string): boolean {
    return selectedKeys.value.includes(key);
}

function toggleKey(key: string, checked: boolean | 'indeterminate') {
    if (checked === true) {
        if (!selectedKeys.value.includes(key)) {
            selectedKeys.value = [...selectedKeys.value, key];
        }
        return;
    }

    selectedKeys.value = selectedKeys.value.filter((value) => value !== key);
}

function toggleAll(checked: boolean | 'indeterminate') {
    selectedKeys.value =
        checked === true ? props.records.map((record) => record.key) : [];
}

watch(
    () => [open.value, props.records] as const,
    ([isOpen]) => {
        if (isOpen) {
            selectedKeys.value = props.records.map((record) => record.key);
        }
    },
);

function confirm() {
    if (!selectedKeys.value.length) {
        return;
    }

    emit('confirm', [...selectedKeys.value]);
}
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent class="print:hidden sm:max-w-xl">
            <DialogHeader>
                <DialogTitle>{{ title ?? t('Print records') }}</DialogTitle>
                <DialogDescription>
                    {{
                        description ??
                        t('Select one record or many, then print.')
                    }}
                </DialogDescription>
            </DialogHeader>

            <div
                v-if="records.length"
                class="flex items-center justify-between gap-3 border-b pb-2"
            >
                <label class="flex items-center gap-2 text-sm">
                    <Checkbox
                        :checked="allSelected"
                        @update:checked="toggleAll"
                    />
                    <span>{{ t('Select all') }}</span>
                </label>
                <span class="text-xs text-muted-foreground">
                    {{
                        t(':count selected', {
                            count: formatNumber(selectedKeys.length, 0),
                        })
                    }}
                </span>
            </div>

            <div
                v-if="records.length"
                class="max-h-[min(22rem,50vh)] space-y-0.5 overflow-y-auto"
            >
                <div
                    v-for="record in records"
                    :key="record.key"
                    class="flex cursor-pointer items-start gap-3 rounded-lg px-1.5 py-2 hover:bg-muted/50"
                    @click="toggleKey(record.key, !isSelected(record.key))"
                >
                    <Checkbox
                        class="mt-0.5"
                        :checked="isSelected(record.key)"
                        @click.stop
                        @update:checked="
                            (checked) => toggleKey(record.key, checked)
                        "
                    />
                    <span class="min-w-0 flex-1">
                        <span
                            class="flex flex-wrap items-baseline justify-between gap-x-3 gap-y-0.5"
                        >
                            <span class="font-medium">{{ record.title }}</span>
                            <span
                                v-if="record.amount"
                                class="tabular-nums text-sm"
                            >
                                {{ record.amount }}
                            </span>
                        </span>
                        <span
                            v-if="record.subtitle"
                            class="mt-0.5 block text-xs text-muted-foreground"
                        >
                            {{ record.subtitle }}
                        </span>
                    </span>
                </div>
            </div>
            <p v-else class="py-6 text-center text-sm text-muted-foreground">
                {{ emptyLabel ?? t('No records') }}
            </p>

            <DialogFooter class="gap-2">
                <Button type="button" variant="secondary" @click="open = false">
                    {{ t('Cancel') }}
                </Button>
                <Button
                    type="button"
                    :disabled="!selectedKeys.length"
                    @click="confirm"
                >
                    {{
                        selectedKeys.length === 1
                            ? t('Print record')
                            : t('Print selected')
                    }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
