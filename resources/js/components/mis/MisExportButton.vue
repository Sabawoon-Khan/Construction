<script setup lang="ts">
import {
    ChevronDown,
    Download,
    FileSpreadsheet,
    Filter,
    List,
} from '@lucide/vue';
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    buildExportUrl,
    downloadClientSpreadsheet,
    downloadExport,
    type ExportCell,
    type ExportScope,
} from '@/composables/useTableExport';
import { useTranslations } from '@/composables/useTranslations';

type FilterValue = string | number | boolean | null | undefined;

const props = withDefaults(
    defineProps<{
        /** Server export endpoint, e.g. `/expenses/export`. */
        url?: string;
        /** Current list filters (applied when scope is filtered). */
        filters?: Record<string, FilterValue>;
        format?: string;
        size?: 'default' | 'sm' | 'lg' | 'icon';
        variant?: 'default' | 'outline' | 'secondary' | 'ghost';
        label?: string;
        /** Client-side export filename (without extension). */
        filename?: string;
        /** Column headers for client-side export. */
        headers?: string[];
        /** All rows for client-side export. */
        rows?: ExportCell[][];
        /** Filtered subset for client-side “export filtered”. Defaults to `rows`. */
        filteredRows?: ExportCell[][];
    }>(),
    {
        url: undefined,
        filters: () => ({}),
        format: 'xlsx',
        size: 'sm',
        variant: 'outline',
        label: undefined,
        filename: 'export',
        headers: undefined,
        rows: undefined,
        filteredRows: undefined,
    },
);

const page = usePage();
const { t } = useTranslations();

const enabled = computed(() => {
    if (page.props.kitFeatures?.exports === false) {
        return false;
    }

    if (props.url) {
        return page.props.kitFeatures?.excel_exports !== false;
    }

    return Boolean(props.headers?.length && props.rows);
});

const buttonLabel = computed(() => props.label ?? t('Export'));
const hasFilters = computed(() =>
    Object.values(props.filters ?? {}).some(
        (value) => value !== null && value !== undefined && value !== '',
    ),
);

function startExport(scope: ExportScope) {
    if (props.url) {
        downloadExport(
            buildExportUrl({
                url: props.url,
                filters: props.filters,
                scope,
                format: props.format,
            }),
        );
        return;
    }

    if (!props.headers?.length) {
        return;
    }

    const source =
        scope === 'filtered'
            ? (props.filteredRows ?? props.rows ?? [])
            : (props.rows ?? []);

    downloadClientSpreadsheet(props.filename ?? 'export', props.headers, source);
}
</script>

<template>
    <DropdownMenu v-if="enabled">
        <DropdownMenuTrigger as-child>
            <Button
                type="button"
                :variant="variant"
                :size="size"
                class="h-8 gap-1.5 border-border/80 bg-background/80 font-medium shadow-none"
            >
                <FileSpreadsheet class="size-3.5 text-emerald-700 dark:text-emerald-400" />
                <span>{{ buttonLabel }}</span>
                <ChevronDown class="size-3.5 opacity-60" />
            </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end" class="w-64">
            <DropdownMenuLabel class="flex items-center gap-2 font-medium">
                <Download class="size-3.5" />
                {{ t('Export to Excel') }}
            </DropdownMenuLabel>
            <DropdownMenuSeparator />
            <DropdownMenuItem
                class="cursor-pointer gap-2.5 py-2.5"
                @click="startExport('filtered')"
            >
                <span
                    class="flex size-8 shrink-0 items-center justify-center rounded-md bg-emerald-500/10 text-emerald-700 dark:text-emerald-400"
                >
                    <Filter class="size-3.5" />
                </span>
                <span class="min-w-0 flex-1">
                    <span class="block text-sm font-medium leading-none">
                        {{ t('Export filtered') }}
                    </span>
                    <span class="mt-1 block text-xs text-muted-foreground">
                        {{
                            hasFilters
                                ? t('Only rows matching current filters')
                                : t('Current table view')
                        }}
                    </span>
                </span>
            </DropdownMenuItem>
            <DropdownMenuItem
                class="cursor-pointer gap-2.5 py-2.5"
                @click="startExport('all')"
            >
                <span
                    class="flex size-8 shrink-0 items-center justify-center rounded-md bg-sky-500/10 text-sky-700 dark:text-sky-400"
                >
                    <List class="size-3.5" />
                </span>
                <span class="min-w-0 flex-1">
                    <span class="block text-sm font-medium leading-none">
                        {{ t('Export all') }}
                    </span>
                    <span class="mt-1 block text-xs text-muted-foreground">
                        {{ t('Every row in this table') }}
                    </span>
                </span>
            </DropdownMenuItem>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
