<script setup lang="ts">
import { useLocale } from '@/composables/useLocale';
import { computed } from 'vue';

const props = defineProps<{
    /** Optional override; defaults to app locale direction. */
    dir?: 'ltr' | 'rtl';
}>();

const { dir: localeDir } = useLocale();
const resolvedDir = computed(() => props.dir ?? localeDir.value);
</script>

<template>
    <div class="v2-list-page print-table-only" :dir="resolvedDir">
        <slot />
    </div>
</template>
