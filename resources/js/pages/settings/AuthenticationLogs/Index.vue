<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import {
    Globe2,
    MapPin,
    Monitor,
    Network,
    ScrollText,
    ShieldAlert,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import {
    MisDetailGrid,
    MisEmptyState,
    MisExpandableRow,
    MisFilterBar,
    MisListPage,
    MisPagination,
    MisSearchInput,
} from '@/components/mis';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { useCalendar } from '@/composables/useCalendar';
import { useTranslations } from '@/composables/useTranslations';
import type { Paginated } from '@/lib/format';

type UserSummary = {
    id: number;
    name: string;
    email: string;
};

type AuthenticationLogRecord = {
    id: number;
    email: string | null;
    event: string;
    success: boolean;
    failure_reason: string | null;
    ip_address: string | null;
    ip_addresses: string[] | null;
    user_agent: string | null;
    device_type: string | null;
    browser: string | null;
    platform: string | null;
    session_id: string | null;
    guard: string | null;
    request_method: string | null;
    request_path: string | null;
    referer: string | null;
    accept_language: string | null;
    country: string | null;
    country_code: string | null;
    region: string | null;
    city: string | null;
    latitude: number | null;
    longitude: number | null;
    timezone: string | null;
    isp: string | null;
    org: string | null;
    asn: string | null;
    is_private_ip: boolean;
    location_label: string | null;
    metadata: Record<string, unknown> | null;
    logged_at: string;
    user?: UserSummary | null;
};

const props = defineProps<{
    logs: Paginated<AuthenticationLogRecord>;
    eventTypes: string[];
    filters?: {
        search?: string | null;
        event?: string | null;
        success?: string | null;
    };
}>();

const { t } = useTranslations();
const calendar = useCalendar();
const search = ref(props.filters?.search ?? '');
const event = ref(props.filters?.event ?? '');
const success = ref(props.filters?.success ?? '');
const expandedLogId = ref<number | null>(null);

const hasLogs = computed(() => props.logs.data.length > 0);

function applyFilters(): void {
    router.get(
        '/settings/login-logs',
        {
            search: search.value || undefined,
            event: event.value || undefined,
            success: success.value || undefined,
        },
        { preserveState: true, replace: true },
    );
}

function clearFilters(): void {
    search.value = '';
    event.value = '';
    success.value = '';
    applyFilters();
}

function eventLabel(value: string): string {
    const labels: Record<string, string> = {
        login_success: t('Successful login'),
        login_failed: t('Failed login'),
        logout: t('Logout'),
        two_factor_challenged: t('Two-factor challenge'),
        two_factor_failed: t('Failed two-factor'),
        lockout: t('Rate limited'),
        session_revoked: t('Session revoked'),
    };

    return labels[value] ?? value.replace(/_/g, ' ');
}

function failureReasonLabel(reason: string | null): string {
    if (!reason) {
        return '—';
    }

    const labels: Record<string, string> = {
        invalid_credentials: t('Invalid credentials'),
        invalid_two_factor_code: t('Invalid two-factor code'),
        rate_limited: t('Too many attempts'),
        account_inactive: t('Account inactive'),
    };

    return labels[reason] ?? reason.replace(/_/g, ' ');
}

function eventVariant(
    value: string,
    wasSuccessful: boolean,
): 'default' | 'secondary' | 'destructive' | 'outline' {
    if (value === 'login_success' || value === 'logout') {
        return 'default';
    }

    if (value === 'two_factor_challenged') {
        return 'secondary';
    }

    return wasSuccessful ? 'outline' : 'destructive';
}

function displayName(log: AuthenticationLogRecord): string {
    return log.user?.name ?? log.email ?? t('Unknown user');
}

function locationText(log: AuthenticationLogRecord): string {
    if (log.location_label) {
        return log.location_label;
    }

    if (log.is_private_ip) {
        return t('Local / private network');
    }

    return t('Location unavailable');
}

function mapUrl(log: AuthenticationLogRecord): string | null {
    if (log.latitude == null || log.longitude == null) {
        return null;
    }

    return `https://www.openstreetmap.org/?mlat=${log.latitude}&mlon=${log.longitude}#map=10/${log.latitude}/${log.longitude}`;
}

function formatDateTime(value: string): string {
    return calendar.formatDateTime(value) || value;
}

function detailRows(log: AuthenticationLogRecord): Array<{ label: string; value: string }> {
    const rows: Array<{ label: string; value: string }> = [];

    const push = (label: string, value: string | number | null | undefined): void => {
        if (value === null || value === undefined || value === '') {
            return;
        }

        rows.push({ label, value: String(value) });
    };

    push(t('User'), log.user?.name);
    push(t('Email'), log.email ?? log.user?.email);
    push(t('IP address'), log.ip_address);
    if (log.ip_addresses?.length) {
        push(t('All IPs'), log.ip_addresses.join(', '));
    }
    push(t('Location'), locationText(log));
    push(t('City'), log.city);
    push(t('Region'), log.region);
    push(t('Country'), log.country ? `${log.country}${log.country_code ? ` (${log.country_code})` : ''}` : null);
    if (log.latitude != null && log.longitude != null) {
        push(t('Coordinates'), `${log.latitude}, ${log.longitude}`);
    }
    push(t('Timezone'), log.timezone);
    push(t('ISP'), log.isp);
    push(t('Organization'), log.org);
    push(t('ASN'), log.asn);
    push(t('Browser'), log.browser);
    push(t('Platform'), log.platform);
    push(t('Device'), log.device_type);
    push(t('Language'), log.accept_language);
    if (log.request_method && log.request_path) {
        push(t('Request'), `${log.request_method} ${log.request_path}`);
    }
    push(t('Referer'), log.referer);
    push(t('Session ID'), log.session_id);
    push(t('Guard'), log.guard);
    push(t('Failure reason'), log.failure_reason ? failureReasonLabel(log.failure_reason) : null);

    const remember = log.metadata?.remember;
    if (typeof remember === 'boolean') {
        push(t('Remember me'), remember ? t('Yes') : t('No'));
    }

    const message = log.metadata?.message;
    if (typeof message === 'string' && message !== '') {
        push(t('Notes'), message);
    }

    const host = log.metadata?.host;
    if (typeof host === 'string' && host !== '') {
        push(t('Host'), host);
    }

    const isSecure = log.metadata?.is_secure;
    if (typeof isSecure === 'boolean') {
        push(t('HTTPS'), isSecure ? t('Yes') : t('No'));
    }

    const headers = log.metadata?.headers;
    if (headers && typeof headers === 'object' && !Array.isArray(headers)) {
        for (const [key, value] of Object.entries(headers)) {
            if (typeof value === 'string' && value !== '') {
                push(key, value);
            }
        }
    }

    return rows;
}

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Settings', href: '/settings/profile' },
            { title: 'Login logs', href: '/settings/login-logs' },
        ],
    },
});
</script>

<template>
    <Head :title="t('Login logs')" />

    <MisListPage
        :title="t('Login logs')"
        :icon="ShieldAlert"
    >
        <MisFilterBar
            :apply-label="t('Search')"
            :clear-label="t('Clear')"
            @apply="applyFilters"
            @clear="clearFilters"
        >
            <div class="grid min-w-[14rem] flex-1 gap-1.5">
                <label class="text-sm text-muted-foreground" for="log-search">
                    {{ t('Search') }}
                </label>
                <MisSearchInput
                    id="log-search"
                    v-model="search"
                    class="w-full max-w-sm"
                    :placeholder="t('User, email, IP, city, country, ISP…')"
                    :aria-label="t('Search')"
                />
            </div>
            <div class="grid gap-1.5">
                <label class="text-sm text-muted-foreground" for="log-event">
                    {{ t('Event') }}
                </label>
                <select
                    id="log-event"
                    v-model="event"
                    class="h-9 rounded-md border bg-background px-3 text-sm"
                >
                    <option value="">{{ t('All') }}</option>
                    <option
                        v-for="item in eventTypes"
                        :key="item"
                        :value="item"
                    >
                        {{ eventLabel(item) }}
                    </option>
                </select>
            </div>
            <div class="grid gap-1.5">
                <label class="text-sm text-muted-foreground" for="log-success">
                    {{ t('Status') }}
                </label>
                <select
                    id="log-success"
                    v-model="success"
                    class="h-9 rounded-md border bg-background px-3 text-sm"
                >
                    <option value="">{{ t('All') }}</option>
                    <option value="1">{{ t('Success') }}</option>
                    <option value="0">{{ t('Failed') }}</option>
                </select>
            </div>
        </MisFilterBar>

        <MisEmptyState v-if="!hasLogs">
            {{ t('No login logs yet') }}
        </MisEmptyState>

        <div v-else class="space-y-3">
            <MisExpandableRow
                v-for="log in logs.data"
                :key="log.id"
                :open="expandedLogId === log.id"
                @update:open="
                    (open) => (expandedLogId = open ? log.id : null)
                "
            >
                <div class="min-w-0 space-y-2.5">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="font-medium">{{ displayName(log) }}</span>
                        <Badge :variant="eventVariant(log.event, log.success)">
                            {{ eventLabel(log.event) }}
                        </Badge>
                        <Badge
                            :variant="log.success ? 'default' : 'destructive'"
                        >
                            {{ log.success ? t('Success') : t('Failed') }}
                        </Badge>
                        <Badge
                            v-if="log.country_code"
                            variant="outline"
                            class="font-mono uppercase"
                        >
                            {{ log.country_code }}
                        </Badge>
                    </div>

                    <div
                        class="flex flex-wrap gap-x-4 gap-y-1.5 text-sm text-muted-foreground"
                    >
                        <span class="inline-flex items-center gap-1.5">
                            <ScrollText class="size-3.5 shrink-0" />
                            {{ formatDateTime(log.logged_at) }}
                        </span>
                        <span
                            v-if="log.ip_address"
                            class="inline-flex items-center gap-1.5 font-mono"
                        >
                            <Network class="size-3.5 shrink-0" />
                            {{ log.ip_address }}
                        </span>
                        <span class="inline-flex items-center gap-1.5">
                            <MapPin class="size-3.5 shrink-0" />
                            {{ locationText(log) }}
                        </span>
                        <span
                            v-if="log.browser || log.platform"
                            class="inline-flex items-center gap-1.5"
                        >
                            <Monitor class="size-3.5 shrink-0" />
                            <template v-if="log.browser">{{
                                log.browser
                            }}</template>
                            <template v-if="log.browser && log.platform">
                                ·
                            </template>
                            <template v-if="log.platform">{{
                                log.platform
                            }}</template>
                        </span>
                        <span
                            v-if="log.isp"
                            class="inline-flex items-center gap-1.5"
                        >
                            <Globe2 class="size-3.5 shrink-0" />
                            {{ log.isp }}
                        </span>
                    </div>

                    <p
                        v-if="log.failure_reason"
                        class="text-sm text-destructive"
                    >
                        {{ failureReasonLabel(log.failure_reason) }}
                    </p>
                </div>

                <template #detail>
                    <div class="space-y-4 text-sm">
                        <MisDetailGrid :columns="3" :items="detailRows(log)" />

                        <div v-if="log.user_agent" class="min-w-0">
                            <p
                                class="text-xs font-medium tracking-wide text-muted-foreground uppercase"
                            >
                                {{ t('User agent') }}
                            </p>
                            <p class="mt-0.5 break-all text-muted-foreground">
                                {{ log.user_agent }}
                            </p>
                        </div>

                        <div v-if="mapUrl(log)" class="flex flex-wrap gap-2">
                            <Button as-child variant="outline" size="sm">
                                <a
                                    :href="mapUrl(log)!"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                >
                                    <MapPin class="size-4" />
                                    {{ t('View on map') }}
                                </a>
                            </Button>
                        </div>
                    </div>
                </template>
            </MisExpandableRow>
        </div>

        <template #pagination>
            <MisPagination :pagination="logs" />
        </template>
    </MisListPage>
</template>
