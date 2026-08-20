<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import { useTranslations } from '@/composables/useTranslations';
import { cn } from '@/lib/utils';

type PaginatorLink = {
    url: string | null;
    label: string;
    active: boolean;
};

type Paginator = {
    links?: PaginatorLink[];
    from?: number | null;
    to?: number | null;
    total?: number;
    current_page?: number;
    last_page?: number;
    meta?: {
        from?: number | null;
        to?: number | null;
        total?: number;
        current_page?: number;
        last_page?: number;
        links?: PaginatorLink[];
    };
};

const props = defineProps<{
    paginator: Paginator;
}>();

const { t } = useTranslations();

const links = computed(() => props.paginator.links ?? props.paginator.meta?.links ?? []);
const from = computed(() => props.paginator.meta?.from ?? props.paginator.from);
const to = computed(() => props.paginator.meta?.to ?? props.paginator.to);
const total = computed(() => props.paginator.meta?.total ?? props.paginator.total);
const lastPage = computed(() => props.paginator.meta?.last_page ?? props.paginator.last_page ?? 0);
const visibleLinks = computed(() => links.value.filter((link) => link.label));
const show = computed(() => lastPage.value > 1 || links.value.length > 3);
</script>

<template>
    <div
        v-if="show"
        class="flex flex-col gap-3 border-t bg-muted/20 px-4 py-3 sm:flex-row sm:items-center sm:justify-between"
    >
        <p class="text-sm text-muted-foreground">
            <template v-if="total">
                {{ t('Showing') }}
                <span class="font-medium text-foreground">{{ from ?? 0 }}</span>
                {{ t('to') }}
                <span class="font-medium text-foreground">{{ to ?? 0 }}</span>
                {{ t('of') }}
                <span class="font-medium text-foreground">{{ total }}</span>
            </template>
            <template v-else>{{ t('Pages') }}</template>
        </p>
        <div class="flex flex-wrap items-center gap-1">
            <template v-for="(link, index) in visibleLinks" :key="`${link.label}-${index}`">
                <Button
                    v-if="link.url"
                    as-child
                    size="sm"
                    :variant="link.active ? 'default' : 'outline'"
                    class="min-w-9 px-2.5"
                >
                    <Link :href="link.url" preserve-scroll v-html="link.label" />
                </Button>
                <span
                    v-else
                    :class="
                        cn(
                            'inline-flex h-8 min-w-9 items-center justify-center rounded-md border border-transparent px-2.5 text-sm text-muted-foreground',
                            link.active && 'border-primary bg-primary text-primary-foreground',
                        )
                    "
                    v-html="link.label"
                />
            </template>
        </div>
    </div>
</template>
