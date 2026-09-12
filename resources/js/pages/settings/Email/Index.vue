<script setup lang="ts">
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import {
    AlertCircle,
    Check,
    ChevronDown,
    ChevronUp,
    Clock3,
    Inbox,
    Mail,
    MailCheck,
    MailX,
    Send,
    UserRound,
    X,
} from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import InputError from '@/components/InputError.vue';
import {
    MisEmptyState,
    MisFilterBar,
    MisPagination,
    MisSearchInput,
    MisStatCard,
    MisStatGrid,
} from '@/components/mis';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useCalendar } from '@/composables/useCalendar';
import { useTranslations } from '@/composables/useTranslations';
import type { Paginated } from '@/lib/format';
import EmailTabs from '@/pages/settings/Email/EmailTabs.vue';

type MailSettingsPayload = {
    mailer: string;
    from_address: string;
    from_name: string;
    host: string;
    port: number;
    username: string | null;
    password_set: boolean;
    encryption: string;
    mail_alerts: boolean;
    env_mailer: string;
    template?: string;
};

type EmailTemplate = {
    id: string;
    name: string;
    tagline?: string;
    description: string;
    preview: {
        header: string;
        surface: string;
        card: string;
        accent: string;
        button: string;
        text: string;
        muted: string;
        radius: string;
    };
};

type EmailAddress = {
    address: string;
    name: string | null;
};

type EmailLogRecord = {
    id: number;
    mailer: string | null;
    status: string;
    subject: string | null;
    from_address: string | null;
    from_name: string | null;
    to: EmailAddress[] | null;
    cc: EmailAddress[] | null;
    bcc: EmailAddress[] | null;
    message_id: string | null;
    related_type: string | null;
    body_preview: string | null;
    error: string | null;
    headers: Record<string, string> | null;
    metadata: Record<string, unknown> | null;
    logged_at: string;
    user?: { id: number; name: string; email: string } | null;
};

const props = defineProps<{
    tab: 'configuration' | 'templates' | 'logs';
    settings?: MailSettingsPayload | null;
    mailers: string[];
    encryptions?: string[];
    templates?: EmailTemplate[];
    selectedTemplate?: string | null;
    logs?: Paginated<EmailLogRecord>;
    summary?: {
        total: number;
        sent: number;
        failed: number;
        all_time?: number;
    } | null;
    statuses?: string[];
    filters?: {
        search?: string | null;
        status?: string | null;
        mailer?: string | null;
    };
    canManage: boolean;
    canViewLogs: boolean;
}>();

const { t } = useTranslations();
const calendar = useCalendar();
const page = usePage();

const form = useForm({
    mailer: props.settings?.mailer ?? 'log',
    from_address: props.settings?.from_address ?? '',
    from_name: props.settings?.from_name ?? '',
    host: props.settings?.host ?? '',
    port: props.settings?.port ?? 2525,
    username: props.settings?.username ?? '',
    password: '',
    encryption: props.settings?.encryption ?? '',
    mail_alerts: props.settings?.mail_alerts ?? true,
});

const testForm = useForm({
    to: (page.props.auth as { user?: { email?: string } } | undefined)?.user?.email ?? '',
});

const templateForm = useForm({
    template: props.selectedTemplate ?? 'classic',
});

const previewTemplateId = ref(props.selectedTemplate ?? 'classic');
const search = ref(props.filters?.search ?? '');
const status = ref(props.filters?.status ?? '');
const mailerFilter = ref(props.filters?.mailer ?? '');
const expandedLogId = ref<number | null>(null);
const appName = computed(
    () => (page.props.name as string | undefined) ?? 'Yaqeen',
);
const logoUrl = computed(() => (page.props.logo as string | null | undefined) ?? null);

const hasLogs = computed(() => (props.logs?.data.length ?? 0) > 0);
const showSmtpFields = computed(() => form.mailer === 'smtp');
const flashSuccess = computed(() => (page.props.flash as { success?: string } | undefined)?.success);
const flashError = computed(() => (page.props.flash as { error?: string } | undefined)?.error);
const activeTemplateId = computed(
    () => props.selectedTemplate ?? templateForm.template ?? 'classic',
);
const previewTemplate = computed(
    () =>
        (props.templates || []).find((item) => item.id === previewTemplateId.value) ??
        (props.templates || [])[0] ??
        null,
);
const hasActiveFilters = computed(
    () => Boolean(search.value || status.value || mailerFilter.value),
);

watch(
    () => props.selectedTemplate,
    (value) => {
        if (value) {
            previewTemplateId.value = value;
            templateForm.template = value;
        }
    },
);

function submitSettings(): void {
    form.put('/settings/email', { preserveScroll: true });
}

function sendTest(): void {
    testForm.post('/settings/email/test', { preserveScroll: true });
}

function focusTemplate(templateId: string): void {
    previewTemplateId.value = templateId;
}

function selectTemplate(templateId: string): void {
    previewTemplateId.value = templateId;
    templateForm.template = templateId;
    templateForm.put('/settings/email/templates', { preserveScroll: true });
}

function applyFilters(): void {
    router.get(
        '/settings/email/logs',
        {
            search: search.value || undefined,
            status: status.value || undefined,
            mailer: mailerFilter.value || undefined,
        },
        { preserveState: true, replace: true },
    );
}

function clearFilters(): void {
    search.value = '';
    status.value = '';
    mailerFilter.value = '';
    applyFilters();
}

function toggleExpanded(logId: number): void {
    expandedLogId.value = expandedLogId.value === logId ? null : logId;
}

function formatAddresses(addresses: EmailAddress[] | null | undefined): string {
    if (!addresses?.length) {
        return '—';
    }

    return addresses
        .map((item) => (item.name ? `${item.name} <${item.address}>` : item.address))
        .join(', ');
}

function primaryRecipient(log: EmailLogRecord): string {
    const first = log.to?.[0];

    if (!first) {
        return t('Unknown recipient');
    }

    return first.name || first.address;
}

function formatDateTime(value: string): string {
    return calendar.formatDateTime(value) || value;
}

function statusVariant(value: string): 'default' | 'destructive' {
    return value === 'sent' ? 'default' : 'destructive';
}

function statusLabel(value: string): string {
    return value === 'sent' ? t('Sent') : value === 'failed' ? t('Failed') : value;
}

function relatedTypeLabel(value: string | null): string {
    if (!value) {
        return t('System');
    }

    if (value === 'test_email' || value.endsWith('SystemTestEmailNotification')) {
        return t('Test email');
    }

    if (value.endsWith('CriticalAlertNotification')) {
        return t('Critical alert');
    }

    const parts = value.split('\\');

    return parts[parts.length - 1] ?? value;
}

function deliveryRows(log: EmailLogRecord): Array<{ label: string; value: string }> {
    const rows: Array<{ label: string; value: string }> = [];
    const push = (label: string, value: string | number | null | undefined): void => {
        if (value === null || value === undefined || value === '') {
            return;
        }

        rows.push({ label, value: String(value) });
    };

    push(t('From'), log.from_name ? `${log.from_name} <${log.from_address}>` : log.from_address);
    push(t('To'), formatAddresses(log.to));
    push(t('Cc'), formatAddresses(log.cc));
    push(t('Bcc'), formatAddresses(log.bcc));
    push(t('User'), log.user?.name);

    return rows;
}

function technicalRows(log: EmailLogRecord): Array<{ label: string; value: string }> {
    const rows: Array<{ label: string; value: string }> = [];
    const push = (label: string, value: string | number | null | undefined): void => {
        if (value === null || value === undefined || value === '') {
            return;
        }

        rows.push({ label, value: String(value) });
    };

    push(t('Mailer'), log.mailer);
    push(t('Type'), relatedTypeLabel(log.related_type));
    push(t('Message ID'), log.message_id);

    if (log.metadata?.attachment_count != null) {
        push(t('Attachments'), String(log.metadata.attachment_count));
    }

    if (log.headers) {
        for (const [key, value] of Object.entries(log.headers)) {
            if (value) {
                push(key, value);
            }
        }
    }

    return rows;
}

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Settings', href: '/settings/email' },
            { title: 'Email', href: '/settings/email' },
        ],
    },
});
</script>

<template>
    <Head :title="t('Email')" />

    <div class="space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <EmailTabs
                :active="tab"
                :can-manage="canManage"
                :can-view-logs="canViewLogs"
            />
        </div>

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

        <template v-if="tab === 'configuration' && canManage && settings">
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Mail class="size-5" />
                        {{ t('Email configuration') }}
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <form class="space-y-6" @submit.prevent="submitSettings">
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="grid gap-2">
                                <Label for="mailer">{{ t('Mailer') }}</Label>
                                <select
                                    id="mailer"
                                    v-model="form.mailer"
                                    class="h-9 rounded-md border bg-background px-3 text-sm"
                                >
                                    <option
                                        v-for="item in mailers"
                                        :key="item"
                                        :value="item"
                                    >
                                        {{ item }}
                                    </option>
                                </select>
                                <InputError :message="form.errors.mailer" />
                            </div>

                            <div class="grid gap-2">
                                <Label for="from_name">{{ t('From name') }}</Label>
                                <Input id="from_name" v-model="form.from_name" />
                                <InputError :message="form.errors.from_name" />
                            </div>

                            <div class="grid gap-2 sm:col-span-2">
                                <Label for="from_address">{{ t('From address') }}</Label>
                                <Input
                                    id="from_address"
                                    v-model="form.from_address"
                                    type="email"
                                />
                                <InputError :message="form.errors.from_address" />
                            </div>
                        </div>

                        <div
                            v-if="showSmtpFields"
                            class="grid gap-4 rounded-lg border p-4 sm:grid-cols-2"
                        >
                            <div class="grid gap-2 sm:col-span-2">
                                <Label for="host">{{ t('SMTP host') }}</Label>
                                <Input id="host" v-model="form.host" />
                                <InputError :message="form.errors.host" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="port">{{ t('Port') }}</Label>
                                <Input
                                    id="port"
                                    v-model.number="form.port"
                                    type="number"
                                    min="1"
                                    max="65535"
                                />
                                <InputError :message="form.errors.port" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="encryption">{{ t('Encryption') }}</Label>
                                <select
                                    id="encryption"
                                    v-model="form.encryption"
                                    class="h-9 rounded-md border bg-background px-3 text-sm"
                                >
                                    <option value="">{{ t('Auto') }}</option>
                                    <option value="tls">{{ t('TLS (STARTTLS)') }}</option>
                                    <option value="ssl">{{ t('SSL (SMTPS)') }}</option>
                                </select>
                                <InputError :message="form.errors.encryption" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="username">{{ t('Username') }}</Label>
                                <Input id="username" v-model="form.username" autocomplete="off" />
                                <InputError :message="form.errors.username" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="password">{{ t('Password') }}</Label>
                                <Input
                                    id="password"
                                    v-model="form.password"
                                    type="password"
                                    autocomplete="new-password"
                                    :placeholder="
                                        settings.password_set
                                            ? t('Leave blank to keep current password')
                                            : t('SMTP password')
                                    "
                                />
                                <InputError :message="form.errors.password" />
                            </div>
                        </div>

                        <label class="flex items-start gap-3 rounded-md border p-4">
                            <Checkbox
                                :checked="form.mail_alerts"
                                class="mt-0.5"
                                @update:checked="(v) => (form.mail_alerts = Boolean(v))"
                            />
                            <span>
                                <span class="block font-medium">{{ t('Critical alert emails') }}</span>
                                <span class="block text-sm text-muted-foreground">
                                    {{
                                        t(
                                            'Send mail for critical system alerts such as backup failures',
                                        )
                                    }}
                                </span>
                            </span>
                        </label>

                        <div class="flex justify-end">
                            <Button type="submit" :disabled="form.processing">
                                {{ t('Save email settings') }}
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Send class="size-5" />
                        {{ t('Send test email') }}
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <form class="flex flex-wrap items-end gap-3" @submit.prevent="sendTest">
                        <div class="grid min-w-[16rem] flex-1 gap-2">
                            <Label for="test-to">{{ t('Recipient') }}</Label>
                            <Input
                                id="test-to"
                                v-model="testForm.to"
                                type="email"
                                required
                            />
                            <InputError :message="testForm.errors.to" />
                        </div>
                        <Button type="submit" :disabled="testForm.processing">
                            {{ t('Send test') }}
                        </Button>
                    </form>
                </CardContent>
            </Card>
        </template>

        <template v-else-if="tab === 'templates' && canManage">
            <div class="space-y-5">
                <div class="flex flex-wrap items-end justify-between gap-3">
                    <div class="min-w-0">
                        <h2 class="text-[17px] font-semibold tracking-tight">
                            {{ t('Email templates') }}
                        </h2>
                        <p class="mt-1 text-sm text-muted-foreground">
                            {{
                                t(
                                    'Choose one template for all system emails. Colors follow Appearance branding.',
                                )
                            }}
                        </p>
                    </div>
                    <Badge v-if="previewTemplate" variant="outline" class="gap-1.5">
                        <Check class="size-3.5" />
                        {{ t('Active') }}:
                        {{ t((templates || []).find((item) => item.id === activeTemplateId)?.name || 'Classic brand') }}
                    </Badge>
                </div>

                <div class="grid gap-5 xl:grid-cols-[minmax(0,0.95fr)_minmax(0,1.15fr)]">
                    <div class="space-y-2">
                        <p class="px-1 text-[11px] font-semibold tracking-[0.14em] text-muted-foreground uppercase">
                            {{ t('Options') }}
                        </p>
                        <div class="space-y-2">
                            <button
                                v-for="template in templates || []"
                                :key="template.id"
                                type="button"
                                class="flex w-full items-start gap-3 rounded-2xl border px-4 py-3.5 text-left transition"
                                :class="
                                    previewTemplateId === template.id
                                        ? 'border-[var(--school-navy)] bg-[var(--school-navy)]/[0.04] shadow-soft'
                                        : 'border-border/80 bg-card hover:border-[var(--school-navy)]/35'
                                "
                                @click="focusTemplate(template.id)"
                            >
                                <span
                                    class="mt-0.5 flex size-10 shrink-0 items-center justify-center rounded-xl border"
                                    :style="{
                                        background: template.preview.surface,
                                        borderColor: template.preview.accent,
                                    }"
                                >
                                    <span
                                        class="size-4 rounded-full"
                                        :style="{ background: template.preview.button }"
                                    />
                                </span>
                                <span class="min-w-0 flex-1">
                                    <span class="flex flex-wrap items-center gap-2">
                                        <span class="font-semibold tracking-tight">
                                            {{ t(template.name) }}
                                        </span>
                                        <span
                                            v-if="template.tagline"
                                            class="rounded-full bg-muted px-2 py-0.5 text-[10px] font-semibold tracking-wide text-muted-foreground uppercase"
                                        >
                                            {{ t(template.tagline) }}
                                        </span>
                                        <Badge
                                            v-if="activeTemplateId === template.id"
                                            class="gap-1"
                                        >
                                            <Check class="size-3" />
                                            {{ t('Active') }}
                                        </Badge>
                                    </span>
                                    <span class="mt-1 block text-sm text-muted-foreground">
                                        {{ t(template.description) }}
                                    </span>
                                </span>
                            </button>
                        </div>
                        <InputError :message="templateForm.errors.template" />
                    </div>

                    <div class="space-y-3">
                        <p class="px-1 text-[11px] font-semibold tracking-[0.14em] text-muted-foreground uppercase">
                            {{ t('Preview') }}
                        </p>
                        <div
                            v-if="previewTemplate"
                            class="overflow-hidden rounded-2xl border border-border/80 bg-card shadow-soft"
                        >
                            <div
                                class="border-b px-4 py-3"
                                :style="{ background: previewTemplate.preview.surface }"
                            >
                                <div
                                    class="mx-auto max-w-md overflow-hidden border border-black/5 shadow-sm"
                                    :style="{
                                        background: previewTemplate.preview.card,
                                        borderRadius: previewTemplate.preview.radius,
                                    }"
                                >
                                    <div
                                        class="flex items-center justify-center px-5 py-5"
                                        :style="{
                                            background: previewTemplate.preview.header,
                                            borderBottom:
                                                previewTemplate.id === 'formal'
                                                    ? `3px solid ${previewTemplate.preview.accent}`
                                                    : previewTemplate.id === 'minimal'
                                                      ? `4px solid ${previewTemplate.preview.accent}`
                                                      : undefined,
                                        }"
                                    >
                                        <img
                                            v-if="logoUrl"
                                            :src="logoUrl"
                                            :alt="appName"
                                            class="h-8 w-auto object-contain"
                                        />
                                        <span
                                            v-else
                                            class="text-[15px] font-semibold tracking-tight"
                                            :style="{
                                                color:
                                                    previewTemplate.id === 'classic'
                                                        ? '#ffffff'
                                                        : previewTemplate.preview.text,
                                            }"
                                        >
                                            {{ appName }}
                                        </span>
                                    </div>
                                    <div class="space-y-3.5 px-6 py-6">
                                        <p
                                            class="text-[16px] font-semibold"
                                            :style="{ color: previewTemplate.preview.text }"
                                        >
                                            {{ t('Hello!') }}
                                        </p>
                                        <p
                                            class="text-[13.5px] leading-relaxed"
                                            :style="{ color: previewTemplate.preview.muted }"
                                        >
                                            {{
                                                t(
                                                    'This is how your system emails will look with this template.',
                                                )
                                            }}
                                        </p>
                                        <div>
                                            <span
                                                class="inline-block px-4 py-2.5 text-[12px] font-semibold text-white"
                                                :style="{
                                                    background: previewTemplate.preview.button,
                                                    borderRadius:
                                                        previewTemplate.id === 'modern'
                                                            ? '999px'
                                                            : previewTemplate.preview.radius,
                                                }"
                                            >
                                                {{ t('Open dashboard') }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="flex flex-wrap items-center justify-between gap-3 px-5 py-4">
                                <div class="min-w-0">
                                    <p class="font-semibold tracking-tight">
                                        {{ t(previewTemplate.name) }}
                                    </p>
                                    <p class="mt-0.5 text-sm text-muted-foreground">
                                        {{
                                            activeTemplateId === previewTemplate.id
                                                ? t('Currently used for all system emails')
                                                : t('Click apply to use this for all system emails')
                                        }}
                                    </p>
                                </div>
                                <Button
                                    type="button"
                                    :disabled="
                                        templateForm.processing ||
                                        activeTemplateId === previewTemplate.id
                                    "
                                    @click="selectTemplate(previewTemplate.id)"
                                >
                                    <Check class="size-4" />
                                    {{
                                        activeTemplateId === previewTemplate.id
                                            ? t('Selected')
                                            : t('Use this template')
                                    }}
                                </Button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </template>

        <template v-else-if="tab === 'logs' && canViewLogs && logs">
            <div class="space-y-5">
                <div class="flex flex-wrap items-end justify-between gap-3">
                    <div class="min-w-0">
                        <h2 class="text-[17px] font-semibold tracking-tight">
                            {{ t('Email log') }}
                        </h2>
                        <p class="mt-1 text-sm text-muted-foreground">
                            {{
                                t(
                                    'Delivery history for alerts, verification, password resets, and tests',
                                )
                            }}
                        </p>
                    </div>
                </div>

                <MisStatGrid>
                    <MisStatCard
                        :label="t('Matched')"
                        :value="
                            summary?.total ??
                            logs.meta?.total ??
                            logs.data.length
                        "
                        :icon="Inbox"
                    />
                    <MisStatCard
                        :label="t('Sent')"
                        :value="summary?.sent ?? 0"
                        :icon="MailCheck"
                    />
                    <MisStatCard
                        :label="t('Failed')"
                        :value="summary?.failed ?? 0"
                        :icon="MailX"
                    />
                </MisStatGrid>

                <MisFilterBar
                    :apply-label="t('Filter')"
                    :clear-label="t('Clear')"
                    :show-actions="true"
                    @apply="applyFilters"
                    @clear="clearFilters"
                >
                    <template #actions>
                        <Button type="submit">{{ t('Filter') }}</Button>
                        <Button
                            v-if="hasActiveFilters"
                            type="button"
                            variant="outline"
                            @click="clearFilters"
                        >
                            <X class="size-4" />
                            {{ t('Clear') }}
                        </Button>
                    </template>
                    <div class="grid min-w-[14rem] flex-1 gap-1.5">
                        <Label for="email-log-search">{{ t('Search') }}</Label>
                        <MisSearchInput
                            id="email-log-search"
                            v-model="search"
                            :placeholder="t('Subject, recipient, type…')"
                            :aria-label="t('Search')"
                        />
                    </div>
                    <div class="grid min-w-[8.5rem] gap-1.5">
                        <Label for="email-log-status">{{ t('Status') }}</Label>
                        <select
                            id="email-log-status"
                            v-model="status"
                            class="h-9 rounded-md border bg-background px-3 text-sm"
                        >
                            <option value="">{{ t('All') }}</option>
                            <option
                                v-for="item in statuses || []"
                                :key="item"
                                :value="item"
                            >
                                {{ statusLabel(item) }}
                            </option>
                        </select>
                    </div>
                    <div class="grid min-w-[8.5rem] gap-1.5">
                        <Label for="email-log-mailer">{{ t('Mailer') }}</Label>
                        <select
                            id="email-log-mailer"
                            v-model="mailerFilter"
                            class="h-9 rounded-md border bg-background px-3 text-sm"
                        >
                            <option value="">{{ t('All') }}</option>
                            <option
                                v-for="item in mailers"
                                :key="item"
                                :value="item"
                            >
                                {{ item }}
                            </option>
                        </select>
                    </div>
                </MisFilterBar>

                <MisEmptyState
                    v-if="!hasLogs"
                    :icon="Inbox"
                    :title="t('No emails logged yet')"
                />

                <div v-else class="overflow-hidden rounded-2xl border border-border/80 bg-card shadow-soft">
                    <div
                        class="hidden grid-cols-[7rem_minmax(0,1.4fr)_minmax(0,1fr)_7.5rem_6.5rem_2rem] gap-3 border-b table-chrome-head px-4 py-2.5 text-[11px] font-semibold tracking-[0.12em] text-muted-foreground uppercase md:grid"
                    >
                        <span>{{ t('Status') }}</span>
                        <span>{{ t('Subject') }}</span>
                        <span>{{ t('Recipient') }}</span>
                        <span>{{ t('Type') }}</span>
                        <span>{{ t('When') }}</span>
                        <span />
                    </div>

                    <div class="divide-y">
                        <div v-for="log in logs.data" :key="log.id">
                            <button
                                type="button"
                                class="grid w-full grid-cols-1 gap-2 px-4 py-3.5 text-left transition-colors hover:bg-muted/25 md:grid-cols-[7rem_minmax(0,1.4fr)_minmax(0,1fr)_7.5rem_6.5rem_2rem] md:items-center md:gap-3"
                                @click="toggleExpanded(log.id)"
                            >
                                <div>
                                    <Badge :variant="statusVariant(log.status)">
                                        {{ statusLabel(log.status) }}
                                    </Badge>
                                </div>
                                <div class="min-w-0">
                                    <p class="truncate font-medium">
                                        {{ log.subject || t('Untitled email') }}
                                    </p>
                                    <p
                                        v-if="log.error"
                                        class="mt-0.5 truncate text-xs text-destructive md:hidden"
                                    >
                                        {{ log.error }}
                                    </p>
                                </div>
                                <div class="min-w-0 text-sm text-muted-foreground">
                                    <p class="flex items-center gap-1.5 truncate">
                                        <UserRound class="size-3.5 shrink-0" />
                                        {{ primaryRecipient(log) }}
                                    </p>
                                    <p class="mt-0.5 truncate text-xs md:hidden">
                                        {{ formatAddresses(log.to) }}
                                    </p>
                                </div>
                                <div class="text-sm text-muted-foreground">
                                    {{ relatedTypeLabel(log.related_type) }}
                                </div>
                                <div class="flex items-center gap-1.5 text-sm text-muted-foreground">
                                    <Clock3 class="size-3.5 shrink-0" />
                                    <span class="truncate">{{ formatDateTime(log.logged_at) }}</span>
                                </div>
                                <div class="flex justify-end">
                                    <component
                                        :is="
                                            expandedLogId === log.id
                                                ? ChevronUp
                                                : ChevronDown
                                        "
                                        class="size-4 text-muted-foreground"
                                    />
                                </div>
                            </button>

                            <div
                                v-if="expandedLogId === log.id"
                                class="space-y-4 border-t bg-muted/15 px-4 py-4"
                            >
                                <div
                                    v-if="log.error"
                                    class="flex items-start gap-2 rounded-xl border border-destructive/30 bg-destructive/5 px-3 py-2.5 text-sm text-destructive"
                                >
                                    <AlertCircle class="mt-0.5 size-4 shrink-0" />
                                    <span>{{ log.error }}</span>
                                </div>

                                <div class="grid gap-4 lg:grid-cols-2">
                                    <section class="rounded-xl border bg-card p-4">
                                        <p
                                            class="text-[11px] font-semibold tracking-[0.14em] text-muted-foreground uppercase"
                                        >
                                            {{ t('Delivery') }}
                                        </p>
                                        <dl class="mt-3 space-y-2.5 text-sm">
                                            <div
                                                v-for="row in deliveryRows(log)"
                                                :key="`${log.id}-d-${row.label}`"
                                                class="grid gap-0.5 sm:grid-cols-[6.5rem_minmax(0,1fr)]"
                                            >
                                                <dt class="text-muted-foreground">
                                                    {{ row.label }}
                                                </dt>
                                                <dd class="break-all font-medium">
                                                    {{ row.value }}
                                                </dd>
                                            </div>
                                        </dl>
                                    </section>

                                    <section class="rounded-xl border bg-card p-4">
                                        <p
                                            class="text-[11px] font-semibold tracking-[0.14em] text-muted-foreground uppercase"
                                        >
                                            {{ t('Technical') }}
                                        </p>
                                        <dl class="mt-3 space-y-2.5 text-sm">
                                            <div
                                                v-for="row in technicalRows(log)"
                                                :key="`${log.id}-t-${row.label}`"
                                                class="grid gap-0.5 sm:grid-cols-[6.5rem_minmax(0,1fr)]"
                                            >
                                                <dt class="text-muted-foreground">
                                                    {{ row.label }}
                                                </dt>
                                                <dd class="break-all font-medium">
                                                    {{ row.value }}
                                                </dd>
                                            </div>
                                        </dl>
                                    </section>
                                </div>

                                <section
                                    v-if="log.body_preview"
                                    class="rounded-xl border bg-card p-4"
                                >
                                    <p
                                        class="text-[11px] font-semibold tracking-[0.14em] text-muted-foreground uppercase"
                                    >
                                        {{ t('Content preview') }}
                                    </p>
                                    <p
                                        class="mt-3 whitespace-pre-wrap text-sm leading-relaxed text-muted-foreground"
                                    >
                                        {{ log.body_preview }}
                                    </p>
                                </section>
                            </div>
                        </div>
                    </div>

                    <div class="border-t px-4 py-3">
                        <MisPagination :pagination="logs" />
                    </div>
                </div>
            </div>
        </template>
    </div>
</template>
