<script setup lang="ts">
import { Skeleton } from '@/components/ui/skeleton';

withDefaults(
    defineProps<{
        /** denser list rows for Users / Roles / Backups style pages */
        variant?: 'form' | 'list';
        rows?: number;
    }>(),
    {
        variant: 'list',
        rows: 5,
    },
);
</script>

<template>
    <div
        class="settings-skeleton"
        data-tour="settings-content-skeleton"
        aria-busy="true"
        aria-live="polite"
    >
        <div class="settings-skeleton-card">
            <div class="settings-skeleton-head">
                <div class="min-w-0 flex-1 space-y-2">
                    <Skeleton class="h-4 w-36 max-w-[45%]" />
                    <Skeleton class="h-3 w-56 max-w-[70%]" />
                </div>
                <Skeleton class="hidden h-8 w-24 shrink-0 sm:block" />
            </div>

            <div class="settings-skeleton-body">
                <template v-if="variant === 'form'">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="space-y-2">
                            <Skeleton class="h-3 w-16" />
                            <Skeleton class="h-10 w-full" />
                        </div>
                        <div class="space-y-2">
                            <Skeleton class="h-3 w-20" />
                            <Skeleton class="h-10 w-full" />
                        </div>
                    </div>
                    <div class="space-y-2">
                        <Skeleton class="h-3 w-24" />
                        <Skeleton class="h-20 w-full" />
                    </div>
                    <div class="flex justify-end gap-2 pt-1">
                        <Skeleton class="h-9 w-22" />
                        <Skeleton class="h-9 w-24" />
                    </div>
                </template>

                <template v-else>
                    <div class="flex flex-wrap items-center gap-3">
                        <Skeleton class="h-9 w-full max-w-xs" />
                        <Skeleton class="h-9 w-24" />
                    </div>

                    <div class="space-y-2.5">
                        <div
                            v-for="n in rows"
                            :key="n"
                            class="settings-skeleton-row"
                        >
                            <div class="min-w-0 flex-1 space-y-2">
                                <Skeleton class="h-3.5 w-40 max-w-[65%]" />
                                <Skeleton class="h-3 w-52 max-w-[85%]" />
                            </div>
                            <Skeleton class="h-8 w-8 shrink-0 rounded-lg" />
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>
</template>

<style scoped>
.settings-skeleton-card {
    overflow: hidden;
    border: 1px solid rgb(255 255 255 / 74%);
    border-radius: 20px;
    background:
        linear-gradient(
            165deg,
            rgb(255 255 255 / 86%) 0%,
            rgb(255 255 255 / 62%) 100%
        );
    box-shadow:
        0 14px 32px rgb(55 65 70 / 5%),
        inset 0 1px rgb(255 255 255 / 95%);
}

.settings-skeleton-head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 12px;
    padding: 16px 18px;
    border-bottom: 1px solid rgb(0 0 0 / 4%);
    background: rgb(248 249 248 / 72%);
}

.settings-skeleton-body {
    display: flex;
    flex-direction: column;
    gap: 14px;
    padding: 16px 18px 18px;
}

.settings-skeleton-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 12px 14px;
    border: 1px solid rgb(0 0 0 / 4%);
    border-radius: 14px;
    background: rgb(255 255 255 / 55%);
}
</style>
