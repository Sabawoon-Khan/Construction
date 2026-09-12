<script lang="ts" setup>
import type { ToasterProps } from 'vue-sonner';
import {
    AlertCircle,
    Check,
    Info,
    Loader2,
    TriangleAlert,
    X,
} from '@lucide/vue';
import { computed } from 'vue';
import { Toaster as Sonner } from 'vue-sonner';
import { cn } from '@/lib/utils';

import 'vue-sonner/style.css';

const props = defineProps<ToasterProps>();

const toasterClass = computed(() =>
    cn('toaster group structo-toaster', props.class),
);
</script>

<template>
    <Sonner
        v-bind="props"
        :class="toasterClass"
        :style="{
            '--normal-bg': 'rgba(255,255,255,0.92)',
            '--normal-text': '#1a1a1a',
            '--normal-border': 'rgba(255,255,255,0.75)',
            '--border-radius': '18px',
            '--success-bg': 'rgba(255,255,255,0.92)',
            '--success-border': 'rgba(255,255,255,0.75)',
            '--success-text': '#1a1a1a',
            '--error-bg': 'rgba(255,255,255,0.92)',
            '--error-border': 'rgba(255,255,255,0.75)',
            '--error-text': '#1a1a1a',
            '--warning-bg': 'rgba(255,255,255,0.92)',
            '--warning-border': 'rgba(255,255,255,0.75)',
            '--warning-text': '#1a1a1a',
            '--info-bg': 'rgba(255,255,255,0.92)',
            '--info-border': 'rgba(255,255,255,0.75)',
            '--info-text': '#1a1a1a',
        }"
        :rich-colors="false"
        :expand="props.expand !== false"
        :duration="props.duration ?? 5000"
        :visible-toasts="props.visibleToasts ?? 4"
        :close-button="props.closeButton !== false"
        :close-button-position="props.closeButtonPosition ?? 'top-right'"
    >
        <template #success-icon>
            <span class="toast-status success" aria-hidden="true">
                <Check class="size-3.5" :stroke-width="3" />
            </span>
        </template>
        <template #info-icon>
            <span class="toast-status info" aria-hidden="true">
                <Info class="size-3.5" :stroke-width="2.75" />
            </span>
        </template>
        <template #warning-icon>
            <span class="toast-status warning" aria-hidden="true">
                <TriangleAlert class="size-3.5" :stroke-width="2.75" />
            </span>
        </template>
        <template #error-icon>
            <span class="toast-status error" aria-hidden="true">
                <AlertCircle class="size-3.5" :stroke-width="2.75" />
            </span>
        </template>
        <template #loading-icon>
            <span class="toast-status loading" aria-hidden="true">
                <Loader2 class="size-3.5 animate-spin" :stroke-width="2.75" />
            </span>
        </template>
        <template #close-icon>
            <X class="size-3.5" :stroke-width="2.5" />
        </template>
    </Sonner>
</template>
