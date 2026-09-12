<script setup lang="ts">
import { Sparkles } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import { useAiChat } from '@/composables/useAiChat';
import { useTranslations } from '@/composables/useTranslations';

const { t } = useTranslations();
const { available: chatAvailable, openChat } = useAiChat();
</script>

<template>
    <Button
        v-if="chatAvailable"
        type="button"
        variant="ghost"
        class="h-9 gap-1.5 rounded-full px-2 text-[var(--school-navy)] hover:bg-[var(--school-navy)]/10 hover:text-[var(--school-navy)] sm:px-2.5"
        :aria-label="t('General Chat')"
        :title="t('General Chat')"
        @click="openChat"
    >
        <Sparkles class="size-4 shrink-0" :stroke-width="1.75" />
        <span class="hidden text-sm font-medium sm:inline">{{
            t('AI')
        }}</span>
    </Button>
</template>
