<script setup lang="ts">
import RecordAttachments from '@/components/RecordAttachments.vue';

defineProps<{
    item: Record<string, any>;
}>();
</script>

<template>
    <RecordAttachments
        boxed
        attachable="projects"
        :attachable-id="item.id"
        :attachments="item.attachments"
    />
</template>
