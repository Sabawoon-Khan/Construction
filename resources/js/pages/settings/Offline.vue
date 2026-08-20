<script setup lang="ts">
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { Wifi } from '@lucide/vue';
import { computed, onMounted, ref } from 'vue';
import InputError from '@/components/InputError.vue';
import { MisAlert, MisStatCard, MisStatGrid } from '@/components/mis';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useCalendar } from '@/composables/useCalendar';
import { useTranslations } from '@/composables/useTranslations';
import { estimateStorage } from '@/offline/db';
import { getOfflineHealth } from '@/offline/outbox';

type OfflineForm = {
    enabled: boolean;
    pwa_enabled: boolean;
    auto_sync: boolean;
    warm_cache: boolean;
    attachments_enabled: boolean;
    metrics_enabled: boolean;
    max_batch_size: number;
    max_outbox_size: number;
    attachment_max_bytes: number;
    slow_request_ms: number;
};

type SyncDeviceRow = {
    id: number;
    device_id: string;
    name: string | null;
    user: { id: number; name: string; email: string } | null;
    user_agent: string | null;
    last_seen_at: string | null;
    revoked_at: string | null;
};

const props = defineProps<{
    settings: OfflineForm & {
        conflict_policy: string;
        resources: string[];
        env_default: boolean;
        allowed_mimes?: string[];
        cache_ttl_seconds?: number;
    };
    stats: {
        failed_mutations: number;
        conflict_mutations: number;
        active_devices: number;
    };
    devices: SyncDeviceRow[];
}>();

const { t } = useTranslations();
const calendar = useCalendar();
const page = usePage();

const flashSuccess = computed(
    () => (page.props.flash as { success?: string } | undefined)?.success,
);
const flashError = computed(
    () => (page.props.flash as { error?: string } | undefined)?.error,
);

const form = useForm<OfflineForm>({
    enabled: props.settings.enabled,
    pwa_enabled: props.settings.pwa_enabled,
    auto_sync: props.settings.auto_sync,
    warm_cache: props.settings.warm_cache,
    attachments_enabled: props.settings.attachments_enabled,
    metrics_enabled: props.settings.metrics_enabled,
    max_batch_size: props.settings.max_batch_size,
    max_outbox_size: props.settings.max_outbox_size,
    attachment_max_bytes: props.settings.attachment_max_bytes,
    slow_request_ms: props.settings.slow_request_ms,
});

const revokeTarget = ref<SyncDeviceRow | null>(null);
const clientHealth = ref<{
    pending: number;
    failed: number;
    conflicts: number;
    lastFlushAt: string | null;
    warmedAt: string | null;
} | null>(null);
const storage = ref<{
    usage: number;
    quota: number;
    percent: number | null;
} | null>(null);

const switchClass =
    'inline-flex shrink-0 items-center gap-2.5 rounded-full border px-1.5 py-1 transition disabled:cursor-not-allowed disabled:opacity-50';

function switchTone(on: boolean): string {
    return on
        ? 'border-[var(--school-navy)]/30 bg-[var(--school-navy)]/8'
        : 'border-border bg-muted/40';
}

function switchKnob(on: boolean): string {
    return on ? 'bg-[var(--school-navy)]' : 'bg-muted-foreground/35';
}

function formatBytes(bytes: number): string {
    if (bytes < 1024) {
        return `${bytes} B`;
    }

    if (bytes < 1024 * 1024) {
        return `${(bytes / 1024).toFixed(1)} KB`;
    }

    return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
}

function formatDeviceDate(value: string | null): string {
    if (!value) {
        return '—';
    }

    return calendar.formatDateTime(value) || value;
}

function conflictPolicyLabel(policy: string): string {
    if (policy === 'last_write_wins') {
        return t('Last write wins');
    }

    return policy;
}

function submit(): void {
    form.put('/settings/offline', { preserveScroll: true });
}

function confirmRevoke(): void {
    if (!revokeTarget.value) {
        return;
    }

    router.delete(`/settings/offline/devices/${revokeTarget.value.id}`, {
        preserveScroll: true,
        onFinish: () => {
            revokeTarget.value = null;
        },
    });
}

onMounted(() => {
    void getOfflineHealth()
        .then((health) => {
            clientHealth.value = health;
        })
        .catch(() => {
            clientHealth.value = null;
        });

    void estimateStorage()
        .then((estimate) => {
            storage.value = estimate;
        })
        .catch(() => {
            storage.value = null;
        });
});

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Settings', href: '/settings/appearance' },
            { title: 'Offline & PWA', href: '/settings/offline' },
        ],
    },
});
</script>

<template>
    <Head :title="t('Offline & PWA')" />

    <div class="space-y-6" data-tour="settings-offline">
        <div
            v-if="flashSuccess || flashError"
            class="rounded-lg border px-4 py-3 text-sm"
            :class="
                flashError
                    ? 'border-destructive/40 bg-destructive/5 text-destructive'
                    : 'border-border bg-muted/40 text-foreground'
            "
        >
            {{ flashError || flashSuccess }}
        </div>

        <MisStatGrid>
            <MisStatCard
                :label="t('Failed syncs')"
                :value="stats.failed_mutations"
            />
            <MisStatCard
                :label="t('Conflicts')"
                :value="stats.conflict_mutations"
            />
            <MisStatCard
                :label="t('Active devices')"
                :value="stats.active_devices"
            />
        </MisStatGrid>

        <MisAlert
            v-if="stats.failed_mutations > 0 || stats.conflict_mutations > 0"
            tone="warning"
        >
            {{
                t(
                    'There are pending sync failures or conflicts. Review field reports and connected devices.',
                )
            }}
        </MisAlert>

        <Card>
            <CardHeader class="border-b border-border/60 bg-muted/20">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <CardTitle class="flex items-center gap-2">
                            <Wifi class="size-5" />
                            {{ t('Offline configuration') }}
                        </CardTitle>
                    </div>
                    <button
                        type="button"
                        role="switch"
                        :aria-checked="form.enabled"
                        :class="[switchClass, switchTone(form.enabled)]"
                        @click="form.enabled = !form.enabled"
                    >
                        <span
                            class="size-5 rounded-full transition"
                            :class="switchKnob(form.enabled)"
                        />
                        <span class="pe-2 text-xs font-semibold tracking-tight">
                            {{ form.enabled ? t('On') : t('Off') }}
                        </span>
                    </button>
                </div>
            </CardHeader>
            <CardContent class="space-y-8 pt-6">
                <form class="space-y-8" @submit.prevent="submit">
                    <section class="space-y-3">
                        <h2 class="text-sm font-semibold">{{ t('Core') }}</h2>

                        <div
                            v-for="item in [
                                {
                                    key: 'pwa_enabled' as const,
                                    label: t('Enable PWA / service worker'),
                                    help: t(
                                        'Installable app shell, asset precache, and offline fallback page.',
                                    ),
                                },
                                {
                                    key: 'auto_sync' as const,
                                    label: t('Auto-sync when online'),
                                    help: t(
                                        'Flush the outbox on reconnect, tab focus, and background sync where supported.',
                                    ),
                                },
                                {
                                    key: 'warm_cache' as const,
                                    label: t('Warm reference cache'),
                                    help: t(
                                        'Download org settings and opted-in lists into IndexedDB after login.',
                                    ),
                                },
                            ]"
                            :key="item.key"
                            class="flex items-center justify-between gap-4 rounded-md border px-4 py-3"
                        >
                            <div>
                                <div class="font-medium">{{ item.label }}</div>
                            </div>
                            <button
                                type="button"
                                role="switch"
                                :aria-checked="form[item.key]"
                                :disabled="!form.enabled"
                                :class="[
                                    switchClass,
                                    switchTone(form[item.key]),
                                ]"
                                @click="form[item.key] = !form[item.key]"
                            >
                                <span
                                    class="size-5 rounded-full transition"
                                    :class="switchKnob(form[item.key])"
                                />
                                <span
                                    class="pe-2 text-xs font-semibold tracking-tight"
                                >
                                    {{ form[item.key] ? t('On') : t('Off') }}
                                </span>
                            </button>
                        </div>
                    </section>

                    <section class="space-y-3">
                        <h2 class="text-sm font-semibold">
                            {{ t('Attachments & metrics') }}
                        </h2>

                        <div
                            v-for="item in [
                                {
                                    key: 'attachments_enabled' as const,
                                    label: t('Offline attachments'),
                                    help: t(
                                        'Store small files in IndexedDB with forms while offline, then upload on sync.',
                                    ),
                                },
                                {
                                    key: 'metrics_enabled' as const,
                                    label: t('Sync performance metrics'),
                                    help: t(
                                        'Log flush duration and outbox size to the sync log channel.',
                                    ),
                                },
                            ]"
                            :key="item.key"
                            class="flex items-center justify-between gap-4 rounded-md border px-4 py-3"
                        >
                            <div>
                                <div class="font-medium">{{ item.label }}</div>
                            </div>
                            <button
                                type="button"
                                role="switch"
                                :aria-checked="form[item.key]"
                                :disabled="!form.enabled"
                                :class="[
                                    switchClass,
                                    switchTone(form[item.key]),
                                ]"
                                @click="form[item.key] = !form[item.key]"
                            >
                                <span
                                    class="size-5 rounded-full transition"
                                    :class="switchKnob(form[item.key])"
                                />
                                <span
                                    class="pe-2 text-xs font-semibold tracking-tight"
                                >
                                    {{ form[item.key] ? t('On') : t('Off') }}
                                </span>
                            </button>
                        </div>
                    </section>

                    <section class="space-y-4">
                        <h2 class="text-sm font-semibold">{{ t('Limits') }}</h2>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="space-y-2">
                                <Label for="max_batch_size">{{
                                    t('Max sync batch size')
                                }}</Label>
                                <Input
                                    id="max_batch_size"
                                    v-model.number="form.max_batch_size"
                                    type="number"
                                    min="1"
                                    max="200"
                                    :disabled="!form.enabled"
                                />
                                <InputError
                                    :message="form.errors.max_batch_size"
                                />
                            </div>

                            <div class="space-y-2">
                                <Label for="max_outbox_size">{{
                                    t('Max outbox size')
                                }}</Label>
                                <Input
                                    id="max_outbox_size"
                                    v-model.number="form.max_outbox_size"
                                    type="number"
                                    min="10"
                                    max="5000"
                                    :disabled="!form.enabled"
                                />
                                <InputError
                                    :message="form.errors.max_outbox_size"
                                />
                            </div>

                            <div class="space-y-2">
                                <Label for="attachment_max_bytes">{{
                                    t('Max attachment bytes')
                                }}</Label>
                                <Input
                                    id="attachment_max_bytes"
                                    v-model.number="form.attachment_max_bytes"
                                    type="number"
                                    min="1024"
                                    max="20971520"
                                    :disabled="
                                        !form.enabled ||
                                        !form.attachments_enabled
                                    "
                                />
                                <InputError
                                    :message="form.errors.attachment_max_bytes"
                                />
                            </div>

                            <div class="space-y-2">
                                <Label for="slow_request_ms">{{
                                    t('Slow request threshold (ms)')
                                }}</Label>
                                <Input
                                    id="slow_request_ms"
                                    v-model.number="form.slow_request_ms"
                                    type="number"
                                    min="100"
                                    max="60000"
                                    :disabled="!form.enabled"
                                />
                                <InputError
                                    :message="form.errors.slow_request_ms"
                                />
                            </div>
                        </div>
                    </section>

                    <div class="flex justify-end">
                        <Button type="submit" :disabled="form.processing">{{
                            t('Save')
                        }}</Button>
                    </div>
                </form>
            </CardContent>
        </Card>

        <Card>
            <CardHeader class="border-b border-border/60 bg-muted/20">
                <CardTitle>{{ t('Platform info') }}</CardTitle>
            </CardHeader>
            <CardContent class="pt-6">
                <dl class="grid gap-2 text-sm sm:grid-cols-2">
                    <div class="rounded-md border px-3 py-2">
                        <dt class="text-muted-foreground">
                            {{ t('Conflict policy') }}
                        </dt>
                        <dd class="font-medium">
                            {{ conflictPolicyLabel(settings.conflict_policy) }}
                        </dd>
                    </div>
                    <div class="rounded-md border px-3 py-2">
                        <dt class="text-muted-foreground">
                            {{ t('Env default (KIT_OFFLINE)') }}
                        </dt>
                        <dd class="font-medium">
                            {{ settings.env_default ? t('On') : t('Off') }}
                        </dd>
                    </div>
                    <div class="rounded-md border px-3 py-2">
                        <dt class="text-muted-foreground">
                            {{ t('Cache TTL') }}
                        </dt>
                        <dd class="font-medium tabular-nums">
                            {{ settings.cache_ttl_seconds ?? 3600 }}s
                        </dd>
                    </div>
                    <div class="rounded-md border px-3 py-2">
                        <dt class="text-muted-foreground">
                            {{ t('This device storage') }}
                        </dt>
                        <dd class="font-medium tabular-nums">
                            <template v-if="storage">
                                {{ formatBytes(storage.usage) }}
                                <span v-if="storage.percent !== null">
                                    / {{ formatBytes(storage.quota) }} ({{
                                        storage.percent
                                    }}%)
                                </span>
                            </template>
                            <template v-else>—</template>
                        </dd>
                    </div>
                    <div class="rounded-md border px-3 py-2">
                        <dt class="text-muted-foreground">
                            {{ t('Local pending') }}
                        </dt>
                        <dd class="font-medium tabular-nums">
                            {{ clientHealth?.pending ?? '—' }}
                        </dd>
                    </div>
                    <div class="rounded-md border px-3 py-2">
                        <dt class="text-muted-foreground">
                            {{ t('Last successful sync') }}
                        </dt>
                        <dd class="font-medium">
                            {{
                                formatDeviceDate(
                                    clientHealth?.lastFlushAt ?? null,
                                )
                            }}
                        </dd>
                    </div>
                    <div class="rounded-md border px-3 py-2 sm:col-span-2">
                        <dt class="text-muted-foreground">
                            {{ t('Registered sync resources') }}
                        </dt>
                        <dd class="font-medium">
                            {{
                                settings.resources.length
                                    ? settings.resources.join(', ')
                                    : t('None')
                            }}
                        </dd>
                    </div>
                </dl>
            </CardContent>
        </Card>

        <Card>
            <CardHeader class="border-b border-border/60 bg-muted/20">
                <CardTitle>{{ t('Sync devices') }}</CardTitle>
            </CardHeader>
            <CardContent class="pt-6">
                <ul v-if="devices.length" class="divide-y rounded-md border">
                    <li
                        v-for="device in devices"
                        :key="device.id"
                        class="flex flex-col gap-2 px-4 py-3 sm:flex-row sm:items-center sm:justify-between"
                    >
                        <div class="min-w-0">
                            <div
                                class="flex flex-wrap items-center gap-2 font-medium"
                            >
                                {{ device.name || t('Browser') }}
                                <Badge
                                    v-if="device.revoked_at"
                                    variant="destructive"
                                >
                                    {{ t('Revoked') }}
                                </Badge>
                            </div>
                            <div class="text-sm text-muted-foreground">
                                {{ device.user?.email || t('Unknown user') }}
                                ·
                                {{ formatDeviceDate(device.last_seen_at) }}
                            </div>
                            <div class="truncate text-xs text-muted-foreground">
                                {{ device.device_id }}
                            </div>
                        </div>
                        <Button
                            v-if="!device.revoked_at"
                            type="button"
                            variant="outline"
                            size="sm"
                            :aria-label="t('Revoke device')"
                            @click="revokeTarget = device"
                        >
                            {{ t('Revoke') }}
                        </Button>
                    </li>
                </ul>
                <div
                    v-else
                    class="flex flex-col items-center justify-center gap-2 rounded-lg border border-dashed px-4 py-10 text-sm text-muted-foreground"
                >
                    {{ t('No sync devices registered yet.') }}
                </div>
            </CardContent>
        </Card>

        <Dialog
            :open="revokeTarget !== null"
            @update:open="(open) => !open && (revokeTarget = null)"
        >
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>{{
                        t('Revoke this sync device?')
                    }}</DialogTitle>
                    <DialogDescription>
                        {{
                            t(
                                'The device will lose offline sync access until it signs in again and is re-approved.',
                            )
                        }}
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <Button
                        type="button"
                        variant="outline"
                        @click="revokeTarget = null"
                    >
                        {{ t('Cancel') }}
                    </Button>
                    <Button
                        type="button"
                        variant="destructive"
                        @click="confirmRevoke"
                    >
                        {{ t('Revoke') }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </div>
</template>
