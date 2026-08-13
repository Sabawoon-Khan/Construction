<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { Languages, Loader2, Plus } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import {
    MisPagination,
    MisSearchInput,
    MisSegmentedControl,
} from '@/components/mis';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
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
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { useLocale } from '@/composables/useLocale';
import { useTranslations } from '@/composables/useTranslations';
import type { Paginated } from '@/lib/format';
import type { TextDirection } from '@/types/locale';

interface TranslationEntry {
    key: string;
    value: string;
    missing: boolean;
}

const props = defineProps<{
    entries: Paginated<TranslationEntry>;
    editingLocale: string;
    filters: { search?: string | null; locale: string };
}>();

const { t } = useTranslations();
const { locales } = useLocale();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Settings', href: '/settings/profile' },
            { title: 'Translations', href: '/settings/translations' },
        ],
    },
});

const formValues = ref<Record<string, string>>({});

const form = useForm({
    locale: props.editingLocale,
    translations: {} as Record<string, string>,
});

const syncFormValues = (): void => {
    const values: Record<string, string> = {};

    props.entries.data.forEach((entry) => {
        values[entry.key] = entry.value;
    });

    formValues.value = values;
};

watch(() => props.entries, syncFormValues, { immediate: true, deep: true });

const editingDir = computed<TextDirection>(() => {
    return (
        locales.value.find((item) => item.code === props.editingLocale)?.dir ??
        'ltr'
    );
});

const localeTabHref = (code: string): string => {
    const params = new URLSearchParams();

    if (props.filters.search) {
        params.set('search', props.filters.search);
    }

    params.set('locale', code);

    return `/settings/translations?${params.toString()}`;
};

const localeSegments = computed(() =>
    locales.value.map((option) => ({
        id: option.code,
        label: option.native,
        href: localeTabHref(option.code),
    })),
);

const editingLocaleModel = computed({
    get: () => props.editingLocale,
    set: () => undefined,
});

const saveTranslations = (): void => {
    form.locale = props.editingLocale;
    form.translations = { ...formValues.value };
    form.put('/settings/translations', { preserveScroll: true });
};

const search = ref(props.filters.search ?? '');

const submitSearch = (): void => {
    router.get(
        '/settings/translations',
        {
            locale: props.editingLocale,
            search: search.value || undefined,
        },
        { preserveState: true, replace: true },
    );
};

const addOpen = ref(false);

const addForm = useForm({
    code: '',
    name: '',
    native: '',
    dir: 'ltr' as TextDirection,
    from: 'en',
});

const openAddLanguage = (): void => {
    addForm.reset();
    addForm.clearErrors();
    addForm.code = '';
    addForm.name = '';
    addForm.native = '';
    addForm.dir = 'ltr';
    addForm.from = 'en';
    addOpen.value = true;
};

const submitAddLanguage = (): void => {
    addForm.code = addForm.code.trim().toLowerCase();
    addForm
        .transform((data) => ({
            ...data,
            code: data.code.trim().toLowerCase(),
        }))
        .post('/settings/translations', {
            preserveScroll: true,
            onSuccess: () => {
                addOpen.value = false;
                addForm.reset();
            },
        });
};

const setAddDir = (value: string | number | bigint | Record<string, never> | null): void => {
    addForm.dir = value === 'rtl' ? 'rtl' : 'ltr';
};

const setAddFrom = (value: string | number | bigint | Record<string, never> | null): void => {
    addForm.from = String(value ?? 'en');
};
</script>

<template>
    <Head :title="t('Translations')" />

    <Card>
        <CardHeader class="space-y-4 border-b pb-4">
            <div
                class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between"
            >
                <div class="flex flex-col gap-1">
                    <CardTitle class="flex items-center gap-2 text-base">
                        <Languages class="size-4" />
                        {{ t('Translations') }}
                    </CardTitle>
                    <CardDescription class="text-xs">
                        {{
                            t(
                                'Edit application translations. Keys cannot be changed.',
                            )
                        }}
                    </CardDescription>
                </div>

                <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    class="shrink-0"
                    @click="openAddLanguage"
                >
                    <Plus class="size-4" />
                    {{ t('Add language') }}
                </Button>
            </div>

            <div
                class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
            >
                <MisSegmentedControl
                    v-model="editingLocaleModel"
                    :items="localeSegments"
                />

                <form
                    class="flex-1 sm:max-w-xs"
                    @submit.prevent="submitSearch"
                >
                    <MisSearchInput
                        v-model="search"
                        :placeholder="t('Search')"
                        :aria-label="t('Search')"
                    />
                </form>
            </div>
        </CardHeader>

        <CardContent class="space-y-4 pt-4">
            <div
                v-for="entry in entries.data"
                :key="entry.key"
                class="grid gap-2 border-b pb-3 last:border-0 sm:grid-cols-[minmax(0,1fr)_minmax(0,1.4fr)] sm:items-start"
            >
                <div>
                    <p class="font-mono text-xs break-all text-muted-foreground">
                        {{ entry.key }}
                    </p>
                    <p
                        v-if="entry.missing"
                        class="text-xs text-amber-600 dark:text-amber-400"
                    >
                        {{ t('Missing translation') }}
                    </p>
                </div>
                <Input
                    v-model="formValues[entry.key]"
                    class="text-sm"
                    :dir="editingDir"
                />
            </div>

            <div class="flex items-center justify-between gap-3 pt-2">
                <MisPagination :pagination="entries" />
                <Button
                    type="button"
                    :disabled="form.processing"
                    @click="saveTranslations"
                >
                    <Loader2
                        v-if="form.processing"
                        class="size-4 animate-spin"
                    />
                    {{ t('Save') }}
                </Button>
            </div>
        </CardContent>
    </Card>

    <Dialog :open="addOpen" @update:open="(v) => (addOpen = v)">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>{{ t('Add language') }}</DialogTitle>
                <DialogDescription>
                    {{
                        t(
                            'Create a new language by copying translations from an existing one. Choose RTL for right-to-left scripts.',
                        )
                    }}
                </DialogDescription>
            </DialogHeader>

            <form class="space-y-4" @submit.prevent="submitAddLanguage">
                <div class="space-y-2">
                    <Label for="locale-code">{{ t('Language code') }}</Label>
                    <Input
                        id="locale-code"
                        v-model="addForm.code"
                        placeholder="ur"
                        class="font-mono lowercase"
                        autocomplete="off"
                    />
                    <p class="text-xs text-muted-foreground">
                        {{ t('Use a short code like ur, fr, or tr.') }}
                    </p>
                    <p
                        v-if="addForm.errors.code"
                        class="text-xs text-destructive"
                    >
                        {{ addForm.errors.code }}
                    </p>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="space-y-2">
                        <Label for="locale-name">{{ t('English name') }}</Label>
                        <Input
                            id="locale-name"
                            v-model="addForm.name"
                            placeholder="Urdu"
                        />
                        <p
                            v-if="addForm.errors.name"
                            class="text-xs text-destructive"
                        >
                            {{ addForm.errors.name }}
                        </p>
                    </div>
                    <div class="space-y-2">
                        <Label for="locale-native">{{ t('Native name') }}</Label>
                        <Input
                            id="locale-native"
                            v-model="addForm.native"
                            placeholder="اردو"
                            :dir="addForm.dir"
                        />
                        <p
                            v-if="addForm.errors.native"
                            class="text-xs text-destructive"
                        >
                            {{ addForm.errors.native }}
                        </p>
                    </div>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="space-y-2">
                        <Label>{{ t('Text direction') }}</Label>
                        <Select
                            :model-value="addForm.dir"
                            @update:model-value="setAddDir"
                        >
                            <SelectTrigger class="w-full">
                                <SelectValue
                                    :placeholder="t('Text direction')"
                                />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="ltr">
                                    {{ t('Left to right') }} (LTR)
                                </SelectItem>
                                <SelectItem value="rtl">
                                    {{ t('Right to left') }} (RTL)
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <p
                            v-if="addForm.errors.dir"
                            class="text-xs text-destructive"
                        >
                            {{ addForm.errors.dir }}
                        </p>
                    </div>

                    <div class="space-y-2">
                        <Label>{{ t('Copy from') }}</Label>
                        <Select
                            :model-value="addForm.from"
                            @update:model-value="setAddFrom"
                        >
                            <SelectTrigger class="w-full">
                                <SelectValue :placeholder="t('Copy from')" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="option in locales"
                                    :key="option.code"
                                    :value="option.code"
                                >
                                    {{ option.native }} ({{ option.code }})
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <p
                            v-if="addForm.errors.from"
                            class="text-xs text-destructive"
                        >
                            {{ addForm.errors.from }}
                        </p>
                    </div>
                </div>

                <DialogFooter>
                    <Button
                        type="button"
                        variant="outline"
                        @click="addOpen = false"
                    >
                        {{ t('Cancel') }}
                    </Button>
                    <Button type="submit" :disabled="addForm.processing">
                        <Loader2
                            v-if="addForm.processing"
                            class="size-4 animate-spin"
                        />
                        {{ t('Add language') }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
