<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import {
    Archive,
    Database,
    DatabaseBackup,
    Download,
    FolderOpen,
    HardDrive,
    RotateCcw,
    Trash2,
    X,
} from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import Can from '@/components/Can.vue';
import InputError from '@/components/InputError.vue';
import {
    MisDataTable,
    MisEmptyState,
    MisFilterBar,
    MisPagination,
    MisSearchInput,
} from '@/components/mis';
import RowActionsMenu from '@/components/RowActionsMenu.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
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
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { useCalendar } from '@/composables/useCalendar';
import { usePermissions } from '@/composables/usePermissions';
import { useTranslations } from '@/composables/useTranslations';
import type { Paginated } from '@/lib/format';
import type { RowActionItem } from '@/lib/row-actions';

type Backup = {
    id: number;
    uuid: string;
    name: string;
    type: string;
    trigger: string;
    retention_tier: string | null;
    status: string;
    size: number | null;
    error: string | null;
    created_at: string | null;
    creator?: { id: number; name: string } | null;
    restorable: string[];
};

type BackupType = 'database' | 'files' | 'full';

const props = defineProps<{
    backups: Paginated<Backup>;
    auto: {
        enabled: boolean;
        type: string;
    };
    summary: {
        total: number;
        database: number;
        files: number;
        full: number;
    };
    filters: {
        search?: string | null;
        type?: string | null;
        trigger?: string | null;
        status?: string | null;
        date_from?: string | null;
        date_to?: string | null;
    };
}>();

const { t } = useTranslations();
const calendar = useCalendar();
const { can } = usePermissions();

const autoForm = useForm({
    enabled: props.auto.enabled,
    type: (props.auto.type || 'full') as BackupType,
});

watch(
    () => props.auto,
    (auto) => {
        autoForm.enabled = auto.enabled;
        autoForm.type = (auto.type || 'full') as BackupType;
        autoForm.clearErrors();
    },
    { deep: true },
);

const search = ref(props.filters.search ?? '');
const typeFilter = ref(props.filters.type ?? '');
const triggerFilter = ref(props.filters.trigger ?? '');
const statusFilter = ref(props.filters.status ?? '');
const dateFrom = ref(props.filters.date_from ?? '');
const dateTo = ref(props.filters.date_to ?? '');

const creating = ref<string | null>(null);
const restoreOpen = ref(false);
const restoreTarget = ref('full');
const restoreBackup = ref<Backup | null>(null);
const restoring = ref(false);

watch(
    () => props.filters,
    (filters) => {
        search.value = filters.search ?? '';
        typeFilter.value = filters.type ?? '';
        triggerFilter.value = filters.trigger ?? '';
        statusFilter.value = filters.status ?? '';
        dateFrom.value = filters.date_from ?? '';
        dateTo.value = filters.date_to ?? '';
    },
);

const hasActiveFilters = computed(() =>
    Boolean(
        search.value ||
            typeFilter.value ||
            triggerFilter.value ||
            statusFilter.value ||
            dateFrom.value ||
            dateTo.value,
    ),
);

const restoreOptions = computed(() => {
    const layers = restoreBackup.value?.restorable ?? [];

    return [
        { value: 'full', label: t('Full system') },
        { value: 'database', label: t('Database only') },
        { value: 'files', label: t('Storage files only') },
    ].filter((option) => layers.includes(option.value));
});

const actions = computed(() => [
    {
        type: 'database' as const,
        title: t('Database'),
        description: t('SQL dump of your application data'),
        icon: Database,
        count: props.summary.database,
    },
    {
        type: 'files' as const,
        title: t('Storage files'),
        description: t('Uploaded and private storage files'),
        icon: FolderOpen,
        count: props.summary.files,
    },
    {
        type: 'full' as const,
        title: t('Full system'),
        description: t('Database plus storage files together'),
        icon: HardDrive,
        count: props.summary.full,
    },
]);

function saveAuto(): void {
    autoForm
        .transform((data) => ({
            enabled: data.enabled ? 1 : 0,
            type: data.type || 'full',
        }))
        .put('/settings/backups/auto', {
            preserveScroll: true,
            onFinish: () => {
                autoForm.transform((data) => data);
            },
        });
}

function runBackup(type: BackupType): void {
    creating.value = type;
    router.post(
        '/settings/backups',
        { type },
        {
            preserveScroll: true,
            onFinish: () => {
                creating.value = null;
            },
        },
    );
}

function applyFilters(): void {
    router.get(
        '/settings/backups',
        {
            search: search.value || undefined,
            type: typeFilter.value || undefined,
            trigger: triggerFilter.value || undefined,
            status: statusFilter.value || undefined,
            date_from: dateFrom.value || undefined,
            date_to: dateTo.value || undefined,
        },
        { preserveState: true, preserveScroll: true, replace: true },
    );
}

function clearFilters(): void {
    search.value = '';
    typeFilter.value = '';
    triggerFilter.value = '';
    statusFilter.value = '';
    dateFrom.value = '';
    dateTo.value = '';
    router.get('/settings/backups', {}, { preserveScroll: true, replace: true });
}

function openRestore(backup: Backup): void {
    restoreBackup.value = backup;
    restoreTarget.value = backup.restorable.includes('full')
        ? 'full'
        : (backup.restorable[0] ?? 'database');
    restoreOpen.value = true;
}

function confirmRestore(): void {
    if (!restoreBackup.value) {
        return;
    }

    restoring.value = true;
    router.post(
        `/settings/backups/${restoreBackup.value.id}/restore`,
        { target: restoreTarget.value },
        {
            preserveScroll: true,
            onFinish: () => {
                restoring.value = false;
                restoreOpen.value = false;
                restoreBackup.value = null;
            },
        },
    );
}

function formatSize(bytes: number | null): string {
    if (!bytes) {
        return '—';
    }

    if (bytes < 1024) {
        return `${bytes} B`;
    }

    if (bytes < 1024 * 1024) {
        return `${(bytes / 1024).toFixed(1)} KB`;
    }

    return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
}

function formatDate(value: string | null): string {
    if (!value) {
        return '—';
    }

    return calendar.formatDateTime(value) || '—';
}

function backupFromRow(row: Record<string, unknown>): Backup | undefined {
    return props.backups.data.find((backup) => backup.id === row.id);
}

function typeLabel(type: string): string {
    if (type === 'full') {
        return t('Full system');
    }

    if (type === 'database') {
        return t('Database');
    }

    if (type === 'files') {
        return t('Storage files');
    }

    return type;
}

function triggerLabel(trigger: string): string {
    if (trigger === 'scheduled') {
        return t('Auto');
    }

    if (trigger === 'pre_restore') {
        return t('Safety copy');
    }

    return t('Manual');
}

function statusVariant(
    status: string,
): 'default' | 'secondary' | 'destructive' | 'outline' {
    if (status === 'completed') {
        return 'default';
    }

    if (status === 'failed') {
        return 'destructive';
    }

    return 'secondary';
}

function backupActions(backup: Backup): RowActionItem[] {
    return [
        {
            label: t('Download'),
            icon: Download,
            href: `/settings/backups/${backup.id}/download`,
            download: true,
            hidden: backup.status !== 'completed',
        },
        {
            label: t('Restore'),
            icon: RotateCcw,
            onClick: () => openRestore(backup),
            hidden:
                !can('backups.restore') ||
                backup.status !== 'completed' ||
                backup.restorable.length === 0,
        },
        {
            label: t('Move to trash'),
            icon: Trash2,
            variant: 'destructive',
            separator: true,
            href: `/settings/backups/${backup.id}`,
            method: 'delete',
            trashType: 'backup_records',
            trashId: backup.id,
            confirm: {
                title: t('Move to trash'),
                description: t('The backup will move to trash. Files are kept until permanently deleted.'),
                confirmLabel: t('Move to trash'),
            },
        },
    ];
}

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Settings', href: '/settings/backups' },
            { title: 'Backups', href: '/settings/backups' },
        ],
    },
});
</script>

<template>
    <Head :title="t('Backups')" />

    <div class="space-y-6">
        <section class="overflow-hidden rounded-2xl border border-border/80 bg-card shadow-soft">
            <div
                class="relative border-b border-border/60 bg-gradient-to-br from-muted/50 via-card to-card px-6 py-6 sm:px-8 sm:py-7"
            >
                <div
                    class="pointer-events-none absolute inset-y-0 end-0 w-1/2 bg-[radial-gradient(circle_at_top_right,var(--color-primary)_0%,transparent_55%)] opacity-[0.07]"
                />
                <div class="relative flex flex-wrap items-start justify-between gap-4">
                    <div class="max-w-xl space-y-2">
                        <div class="flex items-center gap-2 text-primary">
                            <DatabaseBackup class="size-5" />
                            <span class="text-xs font-semibold tracking-[0.14em] uppercase">
                                {{ t('Backups') }}
                            </span>
                        </div>
                        <h1 class="text-2xl font-semibold tracking-tight sm:text-[1.7rem]">
                            {{ t('Protect your system data') }}
                        </h1>
                        <p class="text-sm leading-relaxed text-muted-foreground">
                            {{
                                t(
                                    'Back up database, storage files, or both. Restore any day without losing newer backups.',
                                )
                            }}
                        </p>
                    </div>
                    <div class="rounded-xl border border-border/70 bg-background/80 px-4 py-3 text-center backdrop-blur-sm">
                        <p class="text-2xl font-semibold tabular-nums tracking-tight">
                            {{ summary.total }}
                        </p>
                    </div>
                </div>
            </div>

            <div class="grid gap-3 p-4 sm:grid-cols-3 sm:p-5">
                <button
                    v-for="action in actions"
                    :key="action.type"
                    type="button"
                    class="group flex flex-col gap-3 rounded-xl border border-border/80 bg-background p-4 text-start transition hover:border-primary/35 hover:bg-muted/30 disabled:cursor-not-allowed disabled:opacity-60"
                    :disabled="creating !== null || !can('backups.run')"
                    @click="runBackup(action.type)"
                >
                    <div class="flex items-start justify-between gap-3">
                        <span
                            class="flex size-10 items-center justify-center rounded-lg bg-primary/10 text-primary transition group-hover:bg-primary/15"
                        >
                            <component :is="action.icon" class="size-5" />
                        </span>
                        <span class="text-xs tabular-nums text-muted-foreground">
                            {{ action.count }}
                        </span>
                    </div>
                    <div>
                        <p class="font-semibold tracking-tight">
                            {{
                                creating === action.type
                                    ? t('Processing...')
                                    : action.title
                            }}
                        </p>
                        <p class="mt-1 text-sm text-muted-foreground">
                            {{ action.description }}
                        </p>
                    </div>
                </button>
            </div>

            <form
                class="flex flex-col gap-4 border-t border-border/60 px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-5"
                @submit.prevent="saveAuto"
            >
                <label class="flex min-w-0 flex-1 items-start gap-3">
                    <Checkbox
                        :checked="autoForm.enabled"
                        class="mt-0.5"
                        @update:checked="(v) => (autoForm.enabled = v === true)"
                    />
                    <span class="min-w-0">
                        <span class="block font-medium">{{ t('Enable daily auto backup') }}</span>
                        <span class="block text-sm text-muted-foreground">
                            {{ t('Runs once a day in the background when the scheduler is active.') }}
                        </span>
                        <InputError class="mt-1" :message="autoForm.errors.enabled" />
                    </span>
                </label>

                <div class="flex flex-wrap items-center gap-2">
                    <div class="space-y-1">
                        <Select
                            :model-value="autoForm.type"
                            :disabled="!autoForm.enabled"
                            @update:model-value="(v) => (autoForm.type = String(v) as BackupType)"
                        >
                            <SelectTrigger class="w-[11.5rem]">
                                <SelectValue :placeholder="t('Choose type')" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="full">{{ t('Full system') }}</SelectItem>
                                <SelectItem value="database">{{ t('Database only') }}</SelectItem>
                                <SelectItem value="files">{{ t('Storage files only') }}</SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="autoForm.errors.type" />
                    </div>
                    <Can permission="backups.run">
                        <Button type="submit" variant="outline" :disabled="autoForm.processing">
                            {{ autoForm.processing ? t('Processing...') : t('Save') }}
                        </Button>
                    </Can>
                </div>
            </form>
        </section>

        <Card>
            <CardHeader>
                <CardTitle class="flex items-center gap-2">
                    <Archive class="size-5" />
                    {{ t('Backup history') }}
                </CardTitle>
            </CardHeader>
            <CardContent class="space-y-4">
                <MisFilterBar
                    :apply-label="t('Filter')"
                    :clear-label="t('Clear')"
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
                        <Label for="backup-search">{{ t('Search') }}</Label>
                        <MisSearchInput
                            id="backup-search"
                            v-model="search"
                            :placeholder="t('Name, UUID, or creator…')"
                            :aria-label="t('Search')"
                        />
                    </div>

                    <div class="grid min-w-[8.5rem] gap-1.5">
                        <Label for="backup-type">{{ t('Type') }}</Label>
                        <select
                            id="backup-type"
                            v-model="typeFilter"
                            class="h-9 rounded-md border bg-background px-3 text-sm"
                        >
                            <option value="">{{ t('All') }}</option>
                            <option value="database">{{ t('Database') }}</option>
                            <option value="files">{{ t('Storage files') }}</option>
                            <option value="full">{{ t('Full system') }}</option>
                        </select>
                    </div>

                    <div class="grid min-w-[8.5rem] gap-1.5">
                        <Label for="backup-trigger">{{ t('Trigger') }}</Label>
                        <select
                            id="backup-trigger"
                            v-model="triggerFilter"
                            class="h-9 rounded-md border bg-background px-3 text-sm"
                        >
                            <option value="">{{ t('All') }}</option>
                            <option value="manual">{{ t('Manual') }}</option>
                            <option value="scheduled">{{ t('Auto') }}</option>
                            <option value="pre_restore">{{ t('Safety copy') }}</option>
                        </select>
                    </div>

                    <div class="grid min-w-[8.5rem] gap-1.5">
                        <Label for="backup-status">{{ t('Status') }}</Label>
                        <select
                            id="backup-status"
                            v-model="statusFilter"
                            class="h-9 rounded-md border bg-background px-3 text-sm"
                        >
                            <option value="">{{ t('All') }}</option>
                            <option value="completed">{{ t('completed') }}</option>
                            <option value="failed">{{ t('failed') }}</option>
                            <option value="pending">{{ t('pending') }}</option>
                        </select>
                    </div>

                    <div class="grid min-w-[9rem] gap-1.5">
                        <Label for="backup-from">{{ t('From') }}</Label>
                        <Input id="backup-from" v-model="dateFrom" type="date" />
                    </div>

                    <div class="grid min-w-[9rem] gap-1.5">
                        <Label for="backup-to">{{ t('To') }}</Label>
                        <Input id="backup-to" v-model="dateTo" type="date" />
                    </div>
                </MisFilterBar>

                <MisEmptyState v-if="backups.data.length === 0">
                    <Archive class="mx-auto size-8 text-muted-foreground opacity-60" />
                    <p class="mt-3 text-sm text-muted-foreground">
                        {{
                            hasActiveFilters
                                ? t('No backups match these filters.')
                                : t('No backups yet')
                        }}
                    </p>
                </MisEmptyState>

                <MisDataTable
                    v-else
                    :columns="[
                        { key: 'name', label: t('Name') },
                        { key: 'type', label: t('Type') },
                        { key: 'trigger', label: t('Trigger') },
                        { key: 'status', label: t('Status') },
                        { key: 'size', label: t('Size') },
                        { key: 'created', label: t('Created') },
                        { key: 'actions', label: t('Actions'), class: 'text-end' },
                    ]"
                    :rows="
                        backups.data.map((backup) => ({
                            ...backup,
                            name: backup.name,
                            type: backup.type,
                            trigger: backup.trigger,
                            status: backup.status,
                            size: backup.size,
                            created: backup.created_at,
                            actions: backup.id,
                        }))
                    "
                >
                    <template #cell-name="{ row }">
                        <div class="font-medium">
                            {{ backupFromRow(row)?.name }}
                        </div>
                        <div
                            v-if="backupFromRow(row)?.creator"
                            class="text-xs text-muted-foreground"
                        >
                            {{ backupFromRow(row)?.creator?.name }}
                        </div>
                        <div
                            v-if="backupFromRow(row)?.error"
                            class="mt-1 text-xs text-destructive"
                        >
                            {{ backupFromRow(row)?.error }}
                        </div>
                    </template>
                    <template #cell-type="{ row }">
                        <Badge variant="outline">{{
                            typeLabel(backupFromRow(row)?.type ?? '')
                        }}</Badge>
                    </template>
                    <template #cell-trigger="{ row }">
                        <span class="text-muted-foreground">{{
                            triggerLabel(backupFromRow(row)?.trigger ?? '')
                        }}</span>
                    </template>
                    <template #cell-status="{ row }">
                        <Badge
                            :variant="
                                statusVariant(
                                    backupFromRow(row)?.status ?? '',
                                )
                            "
                        >
                            {{ backupFromRow(row)?.status }}
                        </Badge>
                    </template>
                    <template #cell-size="{ row }">
                        <span class="tabular-nums text-muted-foreground">{{
                            formatSize(backupFromRow(row)?.size ?? null)
                        }}</span>
                    </template>
                    <template #cell-created="{ row }">
                        <span class="whitespace-nowrap text-muted-foreground">{{
                            formatDate(
                                backupFromRow(row)?.created_at ?? null,
                            )
                        }}</span>
                    </template>
                    <template #cell-actions="{ row }">
                        <div
                            v-if="backupFromRow(row)"
                            class="flex justify-end"
                        >
                            <RowActionsMenu
                                :actions="
                                    backupActions(backupFromRow(row)!)
                                "
                            />
                        </div>
                    </template>
                </MisDataTable>

                <MisPagination :pagination="backups" />
            </CardContent>
        </Card>
    </div>

    <Dialog :open="restoreOpen" @update:open="(v) => (restoreOpen = v)">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>{{ t('Restore backup') }}</DialogTitle>
                <DialogDescription>
                    {{
                        t(
                            'A safety backup of the current system is created first. Newer backups stay available after restore.',
                        )
                    }}
                </DialogDescription>
            </DialogHeader>

            <div class="space-y-3 py-2">
                <p class="text-sm">
                    <span class="text-muted-foreground">{{ t('Backup') }}:</span>
                    {{ restoreBackup?.name }}
                </p>
                <div class="space-y-2">
                    <Label>{{ t('Restore') }}</Label>
                    <Select
                        :model-value="restoreTarget"
                        @update:model-value="(v) => (restoreTarget = String(v))"
                    >
                        <SelectTrigger>
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                v-for="option in restoreOptions"
                                :key="option.value"
                                :value="option.value"
                            >
                                {{ option.label }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>
            </div>

            <DialogFooter>
                <Button variant="outline" :disabled="restoring" @click="restoreOpen = false">
                    {{ t('Cancel') }}
                </Button>
                <Can permission="backups.restore">
                    <Button :disabled="restoring" @click="confirmRestore">
                        {{ restoring ? t('Processing...') : t('Restore') }}
                    </Button>
                </Can>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
