<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Pencil, Trash2 } from '@lucide/vue';
import { computed, ref } from 'vue';
import Can from '@/components/Can.vue';
import ConfirmActionDialog from '@/components/ConfirmActionDialog.vue';
import RecordAttachments from '@/components/RecordAttachments.vue';
import { MisDetailGrid } from '@/components/mis';
import { Button } from '@/components/ui/button';
import { V2DetailHero, V2ListPage, V2Panel } from '@/components/v2';
import { useMisPage } from '@/composables/useMisPage';
import { useTranslations } from '@/composables/useTranslations';
import {
    consumePendingTrashUndo,
    expectTrashUndo,
} from '@/lib/trashUndoToast';

const props = defineProps<{ item: Record<string, any> }>();

const { t } = useTranslations();
const { formatDate } = useMisPage();

const deleteOpen = ref(false);
const deleting = ref(false);

const detailItems = computed(() => [
    { label: t('Category'), value: props.item.category_label || props.item.category },
    { label: t('Document date'), value: formatDate(props.item.document_date) },
    { label: t('Added by'), value: props.item.creator?.name || '—' },
    { label: t('Created at'), value: formatDate(props.item.created_at) },
]);

function confirmDelete() {
    deleting.value = true;
    expectTrashUndo({ type: 'archive_documents', id: props.item.id });
    router.delete(`/archive/${props.item.id}`, {
        onError: () => consumePendingTrashUndo(),
        onCancel: () => consumePendingTrashUndo(),
        onFinish: () => {
            deleting.value = false;
            deleteOpen.value = false;
        },
    });
}

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Archive', href: '/archive' },
            { title: 'Details', href: '#' },
        ],
    },
});
</script>

<template>
    <Head :title="item.title" />

    <V2ListPage>
        <V2DetailHero image="/images/dashboard-v2-building.png">
            <template #eyebrow>{{ t('Archive') }}</template>
            <template #title>{{ item.title }}</template>
            <template #description>{{ item.category_label || item.category }}</template>
            <template #actions>
                <Can permission="archive.edit">
                    <Button as-child>
                        <Link :href="`/archive/${item.id}/edit`">
                            <Pencil class="size-4" /> {{ t('Edit') }}
                        </Link>
                    </Button>
                </Can>
                <Can permission="archive.delete">
                    <Button variant="outline" @click="deleteOpen = true">
                        <Trash2 class="size-4" /> {{ t('Move to trash') }}
                    </Button>
                </Can>
            </template>
        </V2DetailHero>

        <V2Panel v-if="item.notes" :title="t('Notes')">
            <p class="text-sm text-muted-foreground whitespace-pre-wrap">{{ item.notes }}</p>
        </V2Panel>

        <V2Panel :title="t('Details')">
            <MisDetailGrid :columns="2" :items="detailItems" />
        </V2Panel>

        <RecordAttachments
            boxed
            readonly
            attachable="archive"
            :attachable-id="item.id"
            :attachments="item.attachments"
            :label="t('Files')"
        />

        <ConfirmActionDialog
            v-model:open="deleteOpen"
            :title="t('Move to trash')"
            :description="t('This item will move to trash. You can restore it later.')"
            :confirm-label="t('Move to trash')"
            :processing="deleting"
            @confirm="confirmDelete"
        />
    </V2ListPage>
</template>
