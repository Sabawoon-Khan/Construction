<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { Trash2 } from '@lucide/vue';
import { ref } from 'vue';
import Can from '@/components/Can.vue';
import ConfirmActionDialog from '@/components/ConfirmActionDialog.vue';
import { MisDetailGrid } from '@/components/mis';
import { Button } from '@/components/ui/button';
import {
    V2DetailHero,
    V2ListPage,
    V2Panel,
} from '@/components/v2';
import { useCalendar } from '@/composables/useCalendar';
import { useTranslations } from '@/composables/useTranslations';

const props = defineProps<{
    item: {
        id: number;
        name: string;
        email: string;
        phone?: string | null;
        message: string;
        ip_address?: string | null;
        read_at?: string | null;
        created_at?: string | null;
    };
}>();

const { t } = useTranslations();
const { formatDateTime } = useCalendar();

const deleteOpen = ref(false);
const deleting = ref(false);

function confirmDelete() {
    deleting.value = true;
    router.delete(`/contact-messages/${props.item.id}`, {
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
            { title: 'Contact', href: '/contact-messages' },
            { title: 'Details', href: '#' },
        ],
    },
});
</script>

<template>
    <Head :title="item.name" />

    <V2ListPage>
        <div class="flex flex-col gap-4">
            <V2DetailHero image="/images/dashboard-v2-building.png">
                <template #eyebrow>{{ t('Contact') }}</template>
                <template #title>{{ item.name }}</template>
                <template #description>{{ t('Website contact inquiry') }}</template>
                <template #actions>
                    <Can permission="contact_messages.delete">
                        <Button type="button" variant="destructive" @click="deleteOpen = true">
                            <Trash2 class="size-4" />
                            {{ t('Delete') }}
                        </Button>
                    </Can>
                </template>
            </V2DetailHero>

            <V2Panel :title="t('Sender')">
                <MisDetailGrid
                    :items="[
                        { label: t('Name'), value: item.name },
                        { label: t('Email'), value: item.email },
                        { label: t('Phone'), value: item.phone || '—' },
                        {
                            label: t('Received'),
                            value: item.created_at ? formatDateTime(item.created_at) : '—',
                        },
                        { label: t('IP'), value: item.ip_address || '—' },
                    ]"
                />
            </V2Panel>

            <V2Panel :title="t('Message')">
                <template #actions>
                    <Button as-child variant="outline" size="sm">
                        <a :href="`mailto:${item.email}`">{{ t('Reply by email') }}</a>
                    </Button>
                    <Button
                        v-if="item.phone"
                        as-child
                        variant="outline"
                        size="sm"
                    >
                        <a :href="`tel:${item.phone.replace(/\s+/g, '')}`">
                            {{ t('Call') }}
                        </a>
                    </Button>
                </template>
                <p class="whitespace-pre-wrap text-sm leading-6 text-muted-foreground">
                    {{ item.message }}
                </p>
            </V2Panel>

            <ConfirmActionDialog
                v-model:open="deleteOpen"
                :title="t('Delete message?')"
                :description="t('This cannot be undone.')"
                :confirm-label="t('Delete')"
                :processing="deleting"
                @confirm="confirmDelete"
            />
        </div>
    </V2ListPage>
</template>
