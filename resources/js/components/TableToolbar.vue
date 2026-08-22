<script setup lang="ts">
import { Columns3 } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuCheckboxItem,
    DropdownMenuContent,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { useProvidedTableColumns } from '@/composables/useTableColumns';
import { useTranslations } from '@/composables/useTranslations';

const { t } = useTranslations();
const columns = useProvidedTableColumns();
const toggleable = () => columns?.columns.filter((column) => !column.locked) ?? [];
</script>

<template>
    <div
        v-if="columns && toggleable().length"
        class="flex items-center justify-end border-b border-border/70 px-3 py-2"
    >
        <DropdownMenu>
            <DropdownMenuTrigger as-child>
                <Button
                    type="button"
                    variant="outline"
                    size="icon"
                    class="size-8"
                    :title="t('Show or hide columns')"
                    :aria-label="t('Show or hide columns')"
                >
                    <Columns3 class="size-4" />
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" class="w-56">
                <DropdownMenuLabel>{{ t('Show columns') }}</DropdownMenuLabel>
                <DropdownMenuSeparator />
                <DropdownMenuCheckboxItem
                    v-for="column in toggleable()"
                    :key="column.key"
                    :model-value="columns.isVisible(column.key)"
                    @update:model-value="columns.toggle(column.key)"
                >
                    {{ column.label }}
                </DropdownMenuCheckboxItem>
            </DropdownMenuContent>
        </DropdownMenu>
    </div>
</template>
