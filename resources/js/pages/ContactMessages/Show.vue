<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, Trash2 } from '@lucide/vue';
import Can from '@/components/Can.vue';
import DetailGrid from '@/components/DetailGrid.vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { useCalendar } from '@/composables/useCalendar';
import { useTranslations } from '@/composables/useTranslations';

defineProps<{
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

    <div class="flex h-full flex-1 flex-col gap-4">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div class="space-y-3">
                <Button as-child variant="ghost" size="sm" class="-ml-2 w-fit">
                    <Link href="/contact-messages">
                        <ArrowLeft class="size-4" /> {{ t('Back') }}
                    </Link>
                </Button>
                <Heading
                    :title="item.name"
                    :description="t('Website contact inquiry')"
                />
            </div>
            <Can permission="contact_messages.delete">
                <Form
                    :action="`/contact-messages/${item.id}`"
                    method="delete"
                    v-slot="{ processing }"
                >
                    <Button type="submit" variant="destructive" :disabled="processing">
                        <Trash2 class="size-4" />
                        {{ processing ? t('Deleting…') : t('Delete') }}
                    </Button>
                </Form>
            </Can>
        </div>

        <DetailGrid
            :title="t('Sender')"
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

        <div class="rounded-xl border bg-card p-5 shadow-sm">
            <h3 class="text-sm font-semibold tracking-tight">{{ t('Message') }}</h3>
            <p class="mt-3 whitespace-pre-wrap text-sm leading-6 text-muted-foreground">
                {{ item.message }}
            </p>
            <div class="mt-4 flex flex-wrap gap-2">
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
            </div>
        </div>
    </div>
</template>
