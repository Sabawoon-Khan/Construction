<script setup lang="ts">
import AuthBrandLogo from '@/components/AuthBrandLogo.vue';
import LanguageSwitcher from '@/components/LanguageSwitcher.vue';

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div
        class="auth-page relative flex min-h-svh flex-col items-center justify-center overflow-hidden p-6 md:p-10"
    >
        <div class="auth-page-grain pointer-events-none absolute inset-0" aria-hidden="true" />

        <div class="absolute end-4 top-4 z-20 md:end-6 md:top-6">
            <LanguageSwitcher />
        </div>

        <div class="auth-page-panel relative z-10 w-full max-w-sm">
            <div class="flex flex-col gap-8">
                <div class="auth-page-brand flex flex-col items-center gap-5">
                    <AuthBrandLogo />
                    <div v-if="title || description" class="space-y-2 text-center">
                        <h1
                            v-if="title"
                            class="text-base font-medium tracking-tight text-[#14231f]/90 dark:text-white/90"
                        >
                            {{ title }}
                        </h1>
                        <p
                            v-if="description"
                            class="text-sm leading-relaxed text-[#14231f]/60 dark:text-white/60"
                        >
                            {{ description }}
                        </p>
                    </div>
                </div>
                <div class="auth-page-form">
                    <slot />
                </div>
            </div>
        </div>
    </div>
</template>
