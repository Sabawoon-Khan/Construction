<script setup lang="ts">
import RecordAttachments from '@/components/RecordAttachments.vue';
import { V2Panel } from '@/components/v2';
import { useTranslations } from '@/composables/useTranslations';

defineProps<{
    item: Record<string, any>;
}>();

const { t } = useTranslations();
</script>

<template>
    <V2Panel :padded="false" :title="t('Attachments')">
        <div class="p-5">
            <RecordAttachments
                attachable="projects"
                :attachable-id="item.id"
                :attachments="item.attachments"
            />
        </div>
    </V2Panel>
</template>
