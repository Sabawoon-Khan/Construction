<script setup lang="ts">
import DetailGrid from '@/components/DetailGrid.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { useCalendar } from '@/composables/useCalendar';
import { useTranslations } from '@/composables/useTranslations';
import { formatNumber, money } from '@/lib/utils';

defineProps<{
    item: Record<string, any>;
}>();

const { t } = useTranslations();
const calendar = useCalendar();
const formatDate = (value: string | null | undefined) =>
    calendar.format(value) || '—';
</script>

<template>
    <div class="grid gap-4 xl:grid-cols-3">
        <div class="space-y-4 xl:col-span-2">
            <div class="rounded-xl border bg-card p-5 shadow-sm">
                <div
                    class="flex flex-wrap items-start justify-between gap-4 border-b pb-4"
                >
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">
                            {{ t('Project overview') }}
                        </p>
                        <h3 class="mt-1 text-lg font-semibold tracking-tight">
                            {{ item.name }}
                        </h3>
                        <p class="mt-1 text-sm text-muted-foreground">
                            {{ item.location || t('No location') }}
                        </p>
                    </div>
                    <StatusBadge :status="item.status" />
                </div>

                <div class="mt-4 rounded-lg border bg-muted/20 p-4">
                    <div class="flex items-center justify-between">
                        <p class="text-sm font-medium">{{ t('Progress') }}</p>
                        <p class="text-sm font-semibold tabular-nums">
                            {{ formatNumber(item.progress_percent) }}%
                        </p>
                    </div>
                    <div class="mt-3 h-2.5 overflow-hidden rounded-full bg-muted">
                        <div
                            class="h-full rounded-full bg-primary transition-all"
                            :style="{
                                width: `${Math.max(0, Math.min(100, Number(item.progress_percent || 0)))}%`,
                            }"
                        />
                    </div>
                </div>

                <dl class="mt-4 grid gap-4 sm:grid-cols-2">
                    <div class="rounded-lg border bg-background/70 p-3">
                        <dt class="text-xs uppercase tracking-wide text-muted-foreground">
                            {{ t('Code') }}
                        </dt>
                        <dd class="mt-1 text-sm font-medium">{{ item.code || '—' }}</dd>
                    </div>
                    <div class="rounded-lg border bg-background/70 p-3">
                        <dt class="text-xs uppercase tracking-wide text-muted-foreground">
                            {{ t('Type') }}
                        </dt>
                        <dd class="mt-1 text-sm font-medium">{{ item.type || '—' }}</dd>
                    </div>
                </dl>
            </div>

            <div class="rounded-xl border bg-card p-5 shadow-sm">
                <h3 class="text-sm font-semibold tracking-tight">
                    {{ t('Description') }}
                </h3>
                <p class="mt-3 text-sm leading-6 text-muted-foreground">
                    {{ item.description || '—' }}
                </p>
            </div>
        </div>

        <DetailGrid
            :title="t('Financial and schedule')"
            :items="[
                {
                    label: t('Start date'),
                    value: formatDate(item.start_date),
                },
                {
                    label: t('Expected completion'),
                    value: formatDate(item.expected_completion_date),
                },
                {
                    label: t('Estimated budget'),
                    value: money(item.estimated_budget),
                },
                {
                    label: t('Estimated revenue'),
                    value: money(item.estimated_revenue),
                },
            ]"
        />
    </div>
</template>
