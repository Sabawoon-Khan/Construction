<script setup lang="ts">
import AiChatPanel from '@/components/AiChatPanel.vue';
import AiFormFill from '@/components/AiFormFill.vue';
import AiInlineEdit from '@/components/AiInlineEdit.vue';
import AppSidebarLayout from '@/layouts/app/AppSidebarLayout.vue';
import type { BreadcrumbItem } from '@/types';

const { breadcrumbs = [] } = defineProps<{
    breadcrumbs?: BreadcrumbItem[];
}>();
</script>

<template>
    <AppSidebarLayout :breadcrumbs="breadcrumbs">
        <slot />
    </AppSidebarLayout>
    <AiInlineEdit />
    <AiFormFill />
    <AiChatPanel />
</template>
