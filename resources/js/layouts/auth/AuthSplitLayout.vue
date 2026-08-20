<script setup lang="ts">
import AuthBrandLogo from '@/components/AuthBrandLogo.vue';
import LanguageSwitcher from '@/components/LanguageSwitcher.vue';
import { useTranslations } from '@/composables/useTranslations';

defineProps<{
    title?: string;
    description?: string;
}>();

const { t } = useTranslations();
</script>

<template>
    <div
        class="relative grid h-dvh flex-col items-center justify-center px-8 sm:px-0 lg:max-w-none lg:grid-cols-2 lg:px-0"
    >
        <div
            class="relative hidden h-full flex-col justify-between overflow-hidden p-10 text-white lg:flex"
        >
            <div
                class="absolute inset-0 bg-[linear-gradient(165deg,#1a2824_0%,#14231f_55%,#0f1715_100%)]"
                aria-hidden="true"
            />
            <div
                class="pointer-events-none absolute -bottom-24 -start-16 size-72 rounded-full bg-[#1a6b5c]/25 blur-3xl"
                aria-hidden="true"
            />
            <AuthBrandLogo
                class="relative z-20 !items-start !text-start [&_span:not(.sr-only)]:text-white"
                size="md"
            />
            <p
                v-if="description"
                class="relative z-20 max-w-sm text-sm leading-relaxed text-white/65"
            >
                {{ t(description) }}
            </p>
        </div>
        <div class="relative lg:p-8">
            <div class="absolute end-4 top-4 z-20 md:end-6 md:top-6 lg:end-8 lg:top-8">
                <LanguageSwitcher />
            </div>
            <div
                class="mx-auto flex w-full flex-col justify-center space-y-6 sm:w-[350px]"
            >
                <div class="flex flex-col items-center gap-4 lg:hidden">
                    <AuthBrandLogo size="md" />
                </div>
                <div class="flex flex-col space-y-2 text-center">
                    <h1 v-if="title" class="text-xl font-medium tracking-tight">
                        {{ t(title) }}
                    </h1>
                    <p
                        v-if="description"
                        class="text-sm text-muted-foreground lg:hidden"
                    >
                        {{ t(description) }}
                    </p>
                </div>
                <slot />
            </div>
        </div>
    </div>
</template>
