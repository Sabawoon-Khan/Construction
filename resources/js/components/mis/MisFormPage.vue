<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowLeft } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import { useTranslations } from '@/composables/useTranslations';
import { cn } from '@/lib/utils';

const props = defineProps<{
    title: string;
    backHref: string;
    class?: string;
}>();

const { t } = useTranslations();
</script>

<template>
    <div :class="cn('flex h-full min-w-0 flex-1 flex-col gap-5', props.class)">
        <header class="flex min-w-0 items-center gap-2">
            <Button
                as-child
                variant="ghost"
                size="sm"
                class="-ml-2 shrink-0 text-muted-foreground hover:text-foreground"
            >
                <Link :href="backHref">
                    <ArrowLeft class="size-4" />
                    {{ t('Back') }}
                </Link>
            </Button>
            <h1 class="truncate text-lg font-semibold tracking-tight">
                {{ title }}
            </h1>
        </header>
        <slot />
    </div>
</template>
