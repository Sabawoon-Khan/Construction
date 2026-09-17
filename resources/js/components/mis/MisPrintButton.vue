<script setup lang="ts">
import { Printer } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import type { ButtonVariants } from '@/components/ui/button';
import { useTranslations } from '@/composables/useTranslations';
import { printPage } from '@/lib/voucher';
import { cn } from '@/lib/utils';

const props = withDefaults(
    defineProps<{
        size?: ButtonVariants['size'];
        variant?: 'default' | 'outline' | 'secondary' | 'ghost';
        label?: string;
        class?: string;
    }>(),
    {
        size: 'sm',
        variant: 'outline',
        label: undefined,
        class: undefined,
    },
);

const { t } = useTranslations();
</script>

<template>
    <Button
        type="button"
        :variant="variant"
        :size="size"
        :class="
            cn(
                'h-8 gap-1.5 border-border/80 bg-background/80 font-medium shadow-none',
                props.class,
            )
        "
        @click="printPage"
    >
        <Printer class="size-3.5 text-slate-600 dark:text-slate-300" />
        <span>{{ label ?? t('Print') }}</span>
    </Button>
</template>
