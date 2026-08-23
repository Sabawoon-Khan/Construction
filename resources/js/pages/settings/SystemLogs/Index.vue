<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import {
    ChevronDown,
    ChevronUp,
    Download,
    FileText,
    Filter,
    ScrollText,
    Trash2,
} from '@lucide/vue';
import { computed, ref } from 'vue';
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
import SortableTh from '@/components/SortableTh.vue';
import TableFrame from '@/components/TableFrame.vue';
import TableIndexTd from '@/components/TableIndexTd.vue';
import TableIndexTh from '@/components/TableIndexTh.vue';
import { indexTableColumn } from '@/composables/useTableColumns';
import { Input } from '@/components/ui/input';
import { useCalendar } from '@/composables/useCalendar';
import { provideTableSort } from '@/composables/useTableSort';
import { useTranslations } from '@/composables/useTranslations';
import type { Paginated } from '@/lib/format';

type LogFile = {
    name: string;
    size: number;
    modified_at: string;
};

type LogEntry = {
    id: string;
    datetime: string | null;
    logged_at: string | null;
    environment: string | null;
    level: string;
    message: string;
    message_full: string;
    has_stack: boolean;
    stack: string | null;
    raw: string;
};

type ScanInfo = {
    bytes_scanned: number;
    file_size: number;
    truncated: boolean;
    matched: number;
};

const props = defineProps<{
    files: LogFile[];
    entries: Paginated<LogEntry>;
    scan: ScanInfo;
    levels: string[];
    summary: {
        file_count: number;
        total_bytes: number;
        latest_file: string | null;
        latest_modified_at: string | null;
    };
    filters?: {
        file?: string | null;
        level?: string | null;
        search?: string | null;
        date_from?: string | null;
        date_to?: string | null;
        per_page?: number | null;
    };
}>();

const { t } = useTranslations();
const tableColumns = computed(() => [
    indexTableColumn(),
    { key: 'datetime', label: t('Time') },
    { key: 'level', label: t('Level') },
    { key: 'environment', label: t('Env') },
    { key: 'message', label: t('Message') },
    { key: 'expand', label: t('Details'), locked: true },
]);
const { sortedRows } = provideTableSort(() => props.entries.data);
const calendar = useCalendar();

const search = ref(props.filters?.search ?? '');
const level = ref(props.filters?.level ?? '');
const file = ref(props.filters?.file ?? props.files[0]?.name ?? '');
const dateFrom = ref(props.filters?.date_from ?? '');
const dateTo = ref(props.filters?.date_to ?? '');
const perPage = ref(String(props.filters?.per_page ?? 50));
const expandedId = ref<string | null>(null);
const showAdvanced = ref(
    Boolean(props.filters?.date_from || props.filters?.date_to || (props.filters?.per_page && props.filters.per_page !== 50)),
);

const hasEntries = computed(() => props.entries.data.length > 0);

function applyFilters(): void {
    router.get(
        '/settings/system-logs',
        {
            file: file.value || undefined,
            level: level.value || undefined,
            search: search.value || undefined,
            date_from: dateFrom.value || undefined,
            date_to: dateTo.value || undefined,
            per_page: perPage.value !== '50' ? perPage.value : undefined,
        },
        { preserveState: true, replace: true },
    );
}

function clearFilters(): void {
    search.value = '';
    level.value = '';
    dateFrom.value = '';
    dateTo.value = '';
    perPage.value = '50';
    applyFilters();
}

function toggleExpanded(id: string): void {
    expandedId.value = expandedId.value === id ? null : id;
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

function formatDateTime(value: string | null): string {
    if (!value) {
        return '—';
    }

    return calendar.formatDateTime(value) || value;
}

function levelVariant(value: string): 'destructive' | 'secondary' | 'outline' | 'default' {
    if (['emergency', 'alert', 'critical', 'error'].includes(value)) {
        return 'destructive';
    }

    if (value === 'warning') {
        return 'secondary';
    }

    if (value === 'debug') {
        return 'outline';
    }

    return 'default';
}

function levelLabel(value: string): string {
    const labels: Record<string, string> = {
        emergency: t('Emergency'),
        alert: t('Alert'),
        critical: t('Critical'),
        error: t('Error'),
        warning: t('Warning'),
        notice: t('Notice'),
        info: t('Info'),
        debug: t('Debug'),
    };

    return labels[value] ?? value;
}

function downloadUrl(): string {
    const params = new URLSearchParams();

    if (file.value) {
        params.set('file', file.value);
    }

    const query = params.toString();

    return query ? `/settings/system-logs/download?${query}` : '/settings/system-logs/download';
}

function deleteFile(): void {
    if (!file.value) {
        return;
    }

    if (!confirm(t('Delete this log file? This cannot be undone.'))) {
        return;
    }

    router.delete('/settings/system-logs', {
        data: { file: file.value },
        preserveScroll: true,
    });
}

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Settings', href: '/settings/system-logs' },
            { title: 'System logs', href: '/settings/system-logs' },
        ],
    },
});
</script>

<template>
    <Head :title="t('System logs')" />

    <div class="space-y-6">
        <Card>
            <CardHeader>
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="space-y-1.5">
                        <CardTitle class="flex items-center gap-2">
                            <ScrollText class="size-5" />
                            {{ t('System logs') }}
                        </CardTitle>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <Button as-child variant="outline" size="sm">
                            <a :href="downloadUrl()">
                                <Download class="size-4" />
                                {{ t('Download file') }}
                            </a>
                        </Button>
                        <Button
                            variant="destructive"
                            size="sm"
                            :disabled="!file"
                            @click="deleteFile"
                        >
                            <Trash2 class="size-4" />
                            {{ t('Delete file') }}
                        </Button>
                    </div>
                </div>
            </CardHeader>
            <CardContent class="space-y-4">
                <MisStatGrid>
                    <MisStatCard
                        :label="t('Log files')"
                        :value="summary.file_count"
                        tone="muted"
                    />
                    <MisStatCard
                        :label="t('Total size')"
                        :value="formatBytes(summary.total_bytes)"
                        tone="muted"
                    />
                    <MisStatCard
                        :label="t('Matches')"
                        :value="scan.matched"
                        tone="muted"
                    />
                </MisStatGrid>

                <MisFilterBar
                    :apply-label="t('Search')"
                    :clear-label="t('Clear')"
                    @apply="applyFilters"
                    @clear="clearFilters"
                >
                    <div class="grid min-w-[12rem] flex-1 gap-1.5">
                        <label
                            class="text-sm text-muted-foreground"
                            for="log-file"
                        >
                            {{ t('File') }}
                        </label>
                        <select
                            id="log-file"
                            v-model="file"
                            class="h-9 rounded-md border bg-background px-3 text-sm"
                            @change="applyFilters"
                        >
                            <option
                                v-for="item in files"
                                :key="item.name"
                                :value="item.name"
                            >
                                {{ item.name }} ({{ formatBytes(item.size) }})
                            </option>
                        </select>
                    </div>

                    <div class="grid min-w-[10rem] gap-1.5">
                        <label
                            class="text-sm text-muted-foreground"
                            for="log-level"
                        >
                            {{ t('Level') }}
                        </label>
                        <select
                            id="log-level"
                            v-model="level"
                            class="h-9 rounded-md border bg-background px-3 text-sm"
                        >
                            <option value="">{{ t('All levels') }}</option>
                            <option
                                v-for="item in levels"
                                :key="item"
                                :value="item"
                            >
                                {{ levelLabel(item) }}
                            </option>
                        </select>
                    </div>

                    <div class="grid min-w-[16rem] flex-[2] gap-1.5">
                        <label
                            class="text-sm text-muted-foreground"
                            for="log-search"
                        >
                            {{ t('Search') }}
                        </label>
                        <MisSearchInput
                            id="log-search"
                            v-model="search"
                            :placeholder="
                                t('Message, exception, class, path…')
                            "
                            :aria-label="t('Search')"
                        />
                    </div>

                    <template #advanced>
                        <div
                            class="mt-3 flex items-center justify-between gap-3 border-t pt-3"
                        >
                            <Button
                                type="button"
                                variant="ghost"
                                size="sm"
                                class="gap-1.5 text-muted-foreground"
                                @click="showAdvanced = !showAdvanced"
                            >
                                <Filter class="size-3.5" />
                                {{ t('Advanced filters') }}
                                <component
                                    :is="
                                        showAdvanced ? ChevronUp : ChevronDown
                                    "
                                    class="size-3.5"
                                />
                            </Button>
                        </div>

                        <div
                            v-if="showAdvanced"
                            class="mt-3 w-full space-y-3 rounded-lg border bg-muted/20 p-4"
                        >
                            <div
                                class="grid w-full gap-4 sm:grid-cols-2 lg:grid-cols-4"
                            >
                                <div class="grid w-full gap-1.5">
                                    <label
                                        class="text-sm font-medium text-muted-foreground"
                                        for="date-from"
                                    >
                                        {{ t('From date') }}
                                    </label>
                                    <Input
                                        id="date-from"
                                        v-model="dateFrom"
                                        type="date"
                                        class="w-full"
                                    />
                                </div>
                                <div class="grid w-full gap-1.5">
                                    <label
                                        class="text-sm font-medium text-muted-foreground"
                                        for="date-to"
                                    >
                                        {{ t('To date') }}
                                    </label>
                                    <Input
                                        id="date-to"
                                        v-model="dateTo"
                                        type="date"
                                        class="w-full"
                                    />
                                </div>
                                <div class="grid w-full gap-1.5">
                                    <label
                                        class="text-sm font-medium text-muted-foreground"
                                        for="per-page"
                                    >
                                        {{ t('Per page') }}
                                    </label>
                                    <select
                                        id="per-page"
                                        v-model="perPage"
                                        class="h-9 w-full rounded-md border bg-background px-3 text-sm"
                                    >
                                        <option value="25">25</option>
                                        <option value="50">50</option>
                                        <option value="100">100</option>
                                    </select>
                                </div>
                                <div class="flex w-full items-end">
                                    <Button type="submit" class="w-full">
                                        {{ t('Apply') }}
                                    </Button>
                                </div>
                            </div>
                        </div>
                    </template>
                </MisFilterBar>
            </CardContent>
        </Card>

        <Card class="overflow-hidden">
            <CardHeader class="border-b py-4">
                <CardTitle class="flex items-center gap-2 text-base">
                    <FileText class="size-4" />
                    {{ file || t('No log files yet') }}
                </CardTitle>
            </CardHeader>
            <CardContent class="p-0">
                <MisEmptyState v-if="files.length === 0" class="m-6">
                    {{ t('No log files yet') }}
                </MisEmptyState>

                <MisEmptyState v-else-if="!hasEntries" class="m-6">
                    {{ t('No log entries match your filters') }}
                </MisEmptyState>

                <TableFrame v-else table-id="system-logs" :columns="tableColumns">
                    <table class="w-full text-sm">
                        <thead class="bg-muted/40 text-start">
                            <tr class="border-b">
                                <TableIndexTh />
                                <SortableTh column="datetime">
                                    {{ t('Time') }}
                                </SortableTh>
                                <SortableTh column="level">{{ t('Level') }}</SortableTh>
                                <SortableTh column="environment">{{ t('Env') }}</SortableTh>
                                <SortableTh column="message">{{ t('Message') }}</SortableTh>
                                <th class="w-10 px-4 py-3" />
                            </tr>
                        </thead>
                        <tbody>
                            <template v-for="(entry, index) in sortedRows" :key="entry.id">
                                <tr
                                    class="cursor-pointer border-b hover:bg-muted/20"
                                    @click="toggleExpanded(entry.id)"
                                >
                                    <TableIndexTd :index="index" />
                                    <td class="px-4 py-3 align-top whitespace-nowrap text-muted-foreground">
                                        {{ formatDateTime(entry.datetime) }}
                                    </td>
                                    <td class="px-4 py-3 align-top">
                                        <Badge :variant="levelVariant(entry.level)">
                                            {{ levelLabel(entry.level) }}
                                        </Badge>
                                    </td>
                                    <td class="px-4 py-3 align-top text-muted-foreground">
                                        {{ entry.environment || '—' }}
                                    </td>
                                    <td class="max-w-xl px-4 py-3 align-top">
                                        <p class="line-clamp-2 break-all">
                                            {{ entry.message }}
                                        </p>
                                        <p
                                            v-if="entry.has_stack"
                                            class="mt-1 text-xs text-muted-foreground"
                                        >
                                            {{ t('Includes stack trace') }}
                                        </p>
                                    </td>
                                    <td class="px-4 py-3 align-top text-muted-foreground">
                                        <component
                                            :is="expandedId === entry.id ? ChevronUp : ChevronDown"
                                            class="size-4"
                                        />
                                    </td>
                                </tr>
                                <tr v-if="expandedId === entry.id" class="border-b bg-muted/20">
                                    <td colspan="6" class="px-4 py-4">
                                        <div class="space-y-3">
                                            <div>
                                                <p class="mb-1 text-xs font-medium tracking-wide text-muted-foreground uppercase">
                                                    {{ t('Full message') }}
                                                </p>
                                                <pre class="overflow-x-auto rounded-md border bg-background p-3 text-xs leading-relaxed whitespace-pre-wrap break-all">{{ entry.message_full }}</pre>
                                            </div>
                                            <div v-if="entry.stack">
                                                <p class="mb-1 text-xs font-medium tracking-wide text-muted-foreground uppercase">
                                                    {{ t('Stack trace') }}
                                                </p>
                                                <pre class="max-h-80 overflow-auto rounded-md border bg-background p-3 text-xs leading-relaxed whitespace-pre-wrap break-all">{{ entry.stack }}</pre>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </TableFrame>

                <div class="px-4 pb-4">
                    <MisPagination :pagination="entries" />
                </div>
            </CardContent>
        </Card>
    </div>
</template>
