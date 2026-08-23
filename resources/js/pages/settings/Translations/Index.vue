<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { Languages, Loader2, Search } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import MisPagination from '@/components/MisPagination.vue';
import SortableTh from '@/components/SortableTh.vue';
import TableFrame from '@/components/TableFrame.vue';
import TableIndexTd from '@/components/TableIndexTd.vue';
import TableIndexTh from '@/components/TableIndexTh.vue';
import { indexTableColumn } from '@/composables/useTableColumns';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { useLocale } from '@/composables/useLocale';
import { provideTableSort } from '@/composables/useTableSort';
import { useTranslations } from '@/composables/useTranslations';
import type { Paginated } from '@/lib/format';

interface TranslationEntry {
    key: string;
    value: string;
    missing: boolean;
}

interface Props {
    entries: Paginated<TranslationEntry>;
    editingLocale: string;
    filters: { search?: string | null; locale: string };
}

const props = defineProps<Props>();

const { t } = useTranslations();
const tableColumns = computed(() => [
    indexTableColumn(),
    { key: 'key', label: t('Key') },
    { key: 'value', label: t('Translation') },
]);
const { locales } = useLocale();
const { sortedRows } = provideTableSort(() => props.entries.data, { defaultKey: 'key' });

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

const localeTabHref = (code: string): string => {
    const params = new URLSearchParams();

    if (props.filters.search) {
        params.set('search', props.filters.search);
    }

    params.set('locale', code);

    const query = params.toString();

    return query ? `/settings/translations?${query}` : '/settings/translations';
};

const saveTranslations = (): void => {
    form.locale = props.editingLocale;
    form.translations = { ...formValues.value };
    form.put('/settings/translations', { preserveScroll: true });
};

const hasSearch = computed(
    () => (props.filters.search?.trim().length ?? 0) > 0,
);
</script>

<template>
    <Head :title="t('Translations')" />

    <Card>
        <CardHeader class="space-y-4 border-b pb-4">
            <div class="flex flex-col gap-1">
                <CardTitle class="flex items-center gap-2 text-base">
                    <Languages class="size-4" />
                    {{ t('Translations') }}
                </CardTitle>
            </div>

            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div
                    class="inline-flex w-fit gap-0.5 rounded-md bg-muted p-0.5"
                >
                    <a
                        v-for="option in locales"
                        :key="option.code"
                        :href="localeTabHref(option.code)"
                        :class="[
                            'rounded px-2.5 py-1 text-xs font-medium transition-colors',
                            editingLocale === option.code
                                ? 'bg-background text-foreground shadow-sm'
                                : 'text-muted-foreground hover:text-foreground',
                        ]"
                    >
                        {{ option.native }}
                    </a>
                </div>

                <div class="flex flex-1 items-center gap-2 sm:max-w-md sm:justify-end">
                    <form
                        method="get"
                        action="/settings/translations"
                        class="relative min-w-0 flex-1"
                    >
                        <input type="hidden" name="locale" :value="editingLocale" />
                        <Search
                            class="absolute top-1/2 left-2.5 size-3.5 -translate-y-1/2 text-muted-foreground"
                        />
                        <Input
                            name="search"
                            :default-value="filters.search ?? ''"
                            :placeholder="t('Search translations...')"
                            :aria-label="t('Search translations...')"
                            class="h-8 pl-8 text-sm"
                        />
                    </form>

                    <Button
                        type="button"
                        size="sm"
                        class="shrink-0 gap-1.5"
                        :disabled="!hasSearch || form.processing || entries.data.length === 0"
                        @click="saveTranslations"
                    >
                        <Loader2
                            v-if="form.processing"
                            class="size-3.5 animate-spin"
                        />
                        {{ t('Save') }}
                    </Button>
                </div>
            </div>
        </CardHeader>

        <CardContent class="p-0">
            <div
                v-if="!hasSearch"
                class="px-6 py-12 text-center text-sm text-muted-foreground"
            >
                {{ t('Search to load translations.') }}
            </div>

            <div
                v-else-if="entries.data.length === 0"
                class="px-6 py-12 text-center text-sm text-muted-foreground"
            >
                {{ t('No translations found.') }}
            </div>

            <form v-else @submit.prevent="saveTranslations">
                <TableFrame table-id="translations" :columns="tableColumns">
                    <table class="w-full table-fixed text-xs sm:text-sm">
                        <thead>
                            <tr class="border-b bg-muted/30 text-start text-xs text-muted-foreground">
                                <TableIndexTh />
                                <SortableTh class="w-[36%] px-3 py-1.5 sm:w-[34%]" column="key">
                                    {{ t('Key') }}
                                </SortableTh>
                                <SortableTh class="px-3 py-1.5" column="value">
                                    {{ t('Translation') }}
                                </SortableTh>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="(entry, index) in sortedRows"
                                :key="entry.key"
                                class="border-b last:border-b-0 hover:bg-muted/10"
                            >
                                <TableIndexTd :index="index" />
                                <td class="px-3 py-1 align-middle">
                                    <div class="flex items-center gap-2 min-w-0">
                                        <span
                                            v-if="entry.missing"
                                            class="size-1.5 shrink-0 rounded-full bg-amber-500"
                                            :title="t('Missing translation')"
                                        />
                                        <span
                                            class="truncate text-xs text-muted-foreground"
                                            :title="entry.key"
                                        >
                                            {{ entry.key }}
                                        </span>
                                    </div>
                                </td>
                                <td class="px-3 py-0.5 align-middle">
                                    <Input
                                        :id="`translation-${index}`"
                                        v-model="formValues[entry.key]"
                                        class="h-7 border-transparent bg-transparent px-2 text-xs shadow-none focus-visible:border-input focus-visible:bg-background sm:text-sm"
                                    />
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </TableFrame>

                <p
                    v-if="form.errors.translations"
                    class="border-t px-4 py-2 text-xs text-destructive"
                >
                    {{ form.errors.translations }}
                </p>

                <div
                    v-if="entries.meta && entries.meta.last_page > 1"
                    class="border-t px-4 py-3"
                >
                    <MisPagination :pagination="entries" />
                </div>
            </form>
        </CardContent>
    </Card>
</template>
