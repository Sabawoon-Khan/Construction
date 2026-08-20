<script setup lang="ts">
import { Languages } from '@lucide/vue';
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { useLocale } from '@/composables/useLocale';
import { useTranslations } from '@/composables/useTranslations';
import type { LocaleCode } from '@/types/locale';

const page = usePage();
const { t } = useTranslations();
const { locale, locales, updateLocale } = useLocale();

const multilingual = computed(
    () => page.props.kitFeatures?.multilingual !== false,
);
</script>

<template>
    <DropdownMenu v-if="multilingual && locales.length > 1">
        <DropdownMenuTrigger as-child>
            <Button variant="ghost" size="icon" class="size-8">
                <Languages class="size-4" />
                <span class="sr-only">{{ t('Language') }}</span>
            </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end">
            <DropdownMenuItem
                v-for="item in locales"
                :key="item.code"
                :class="{ 'bg-muted': item.code === locale }"
                @click="updateLocale(item.code as LocaleCode)"
            >
                {{ item.native }}
            </DropdownMenuItem>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
