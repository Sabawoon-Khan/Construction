<script setup lang="ts">
import { ChevronLeft, ChevronRight } from '@lucide/vue';
import { computed, ref, useId, watch } from 'vue';
import { Button } from '@/components/ui/button';

export type MisChartDataset = {
    label: string;
    data: number[];
    color?: string;
};

const props = withDefaults(
    defineProps<{
        type: 'bar' | 'line' | 'pie';
        labels: string[];
        datasets: MisChartDataset[];
        /** Show numeric values on bars / points */
        showValues?: boolean;
        /** Vertical grouped bars (default) or horizontal bars */
        orientation?: 'vertical' | 'horizontal';
        /**
         * How many categories per page for vertical bar/line charts.
         * 0 = show all. Default 6 when there are more than 6 labels.
         */
        pageSize?: number;
    }>(),
    {
        showValues: true,
        orientation: 'vertical',
        pageSize: undefined,
    },
);

const chartId = useId();

const defaultPalette = [
    'var(--chart-1)',
    'var(--chart-2)',
    'var(--chart-3)',
    'var(--chart-4)',
    'var(--chart-5)',
];

function seriesColor(index: number, dataset?: MisChartDataset): string {
    return dataset?.color || defaultPalette[index % defaultPalette.length]!;
}

const page = ref(0);

const resolvedPageSize = computed(() => {
    if (props.pageSize !== undefined) {
        return Math.max(0, props.pageSize);
    }
    if (
        (props.type === 'bar' && props.orientation !== 'horizontal') ||
        props.type === 'line'
    ) {
        return props.labels.length > 6 ? 6 : 0;
    }
    if (props.type === 'bar' && props.orientation === 'horizontal') {
        return props.labels.length > 6 ? 6 : 0;
    }
    return 0;
});

const pageCount = computed(() => {
    const size = resolvedPageSize.value;
    if (!size || props.labels.length <= size) {
        return 1;
    }
    return Math.ceil(props.labels.length / size);
});

const showPager = computed(() => pageCount.value > 1);

watch(
    () => [props.labels.length, resolvedPageSize.value] as const,
    () => {
        page.value = Math.min(page.value, Math.max(0, pageCount.value - 1));
    },
);

watch(
    () => props.labels.join('\0'),
    () => {
        // Jump to the newest page (end) when data reloads — usually most relevant.
        page.value = Math.max(0, pageCount.value - 1);
    },
    { immediate: true },
);

const pageStart = computed(() => {
    const size = resolvedPageSize.value;
    if (!size || !showPager.value) {
        return 0;
    }
    return page.value * size;
});

const pageEnd = computed(() => {
    const size = resolvedPageSize.value;
    if (!size || !showPager.value) {
        return props.labels.length;
    }
    return Math.min(props.labels.length, pageStart.value + size);
});

const visibleLabels = computed(() =>
    props.labels.slice(pageStart.value, pageEnd.value),
);

const visibleDatasets = computed(() =>
    props.datasets.map((dataset) => ({
        ...dataset,
        data: dataset.data.slice(pageStart.value, pageEnd.value),
    })),
);

const pageRangeLabel = computed(() => {
    if (!showPager.value || !visibleLabels.value.length) {
        return '';
    }
    const first = visibleLabels.value[0];
    const last = visibleLabels.value[visibleLabels.value.length - 1];
    if (first === last) {
        return String(first);
    }
    return `${first} – ${last}`;
});

const primary = computed(
    () => visibleDatasets.value[0] ?? { label: '', data: [] },
);

const hasData = computed(
    () =>
        props.datasets.some((d) => d.data.some((n) => Number(n) !== 0)) ||
        props.labels.length > 0,
);

const allValues = computed(() =>
    visibleDatasets.value.flatMap((d) => d.data.map((n) => Number(n) || 0)),
);

const valueMax = computed(() => Math.max(0, ...allValues.value, 0));
const valueMin = computed(() => Math.min(0, ...allValues.value, 0));

const plotMax = computed(() =>
    niceCeil(Math.max(valueMax.value, Math.abs(valueMin.value) || 0) || 1),
);
const plotMin = computed(() => (valueMin.value < 0 ? -plotMax.value : 0));

/** Wide plot box so the chart fills the card width. */
const V = {
    left: 48,
    right: 692,
    top: 16,
    bottom: 148,
    width: 644,
    height: 132,
};

function yFor(value: number): number {
    const min = plotMin.value;
    const max = plotMax.value;
    const span = max - min || 1;
    return V.bottom - ((value - min) / span) * V.height;
}

function zeroY(): number {
    return yFor(0);
}

const yTicks = computed(() => {
    const min = plotMin.value;
    const max = plotMax.value;
    const steps = 4;
    return Array.from({ length: steps + 1 }, (_, i) => {
        const value = min + ((max - min) / steps) * i;
        return {
            value,
            y: yFor(value),
            label: formatCompact(value),
        };
    });
});

const barLayout = computed(() => {
    const n = Math.max(visibleLabels.value.length, 1);
    const series = Math.min(visibleDatasets.value.length, 3);
    const slot = V.width / n;
    const width = Math.min(42, Math.max(10, (slot * 0.72) / series));

    return { slot, width, series };
});

function barX(index: number, seriesIndex: number): number {
    const { slot, width, series } = barLayout.value;
    const gap = 3;
    const groupWidth = width * series + Math.max(0, series - 1) * gap;
    const start = V.left + index * slot + (slot - groupWidth) / 2;

    return start + seriesIndex * (width + gap);
}

const lineSeries = computed(() =>
    visibleDatasets.value.slice(0, 3).map((dataset, di) => {
        const n = Math.max(dataset.data.length - 1, 1);
        const points = dataset.data.map((value, index) => {
            const x = V.left + (index / n) * V.width;
            const y = yFor(Number(value) || 0);
            return { x, y, value: Number(value) || 0 };
        });
        const polyline = points.map((p) => `${p.x},${p.y}`).join(' ');
        const area =
            points.length === 0
                ? ''
                : `${points[0]!.x},${zeroY()} ${polyline} ${points[points.length - 1]!.x},${zeroY()}`;

        return {
            label: humanize(dataset.label),
            color: seriesColor(di, dataset),
            points,
            polyline,
            area,
        };
    }),
);

const horizontalRows = computed(() => {
    const series = visibleDatasets.value.slice(0, 2);
    const maxAbs = Math.max(...allValues.value.map((v) => Math.abs(v)), 1);
    const nice = niceCeil(maxAbs);

    return visibleLabels.value.map((label, index) => ({
        label,
        bars: series.map((dataset, di) => {
            const value = Number(dataset.data[index]) || 0;
            const pct = Math.min(100, (Math.abs(value) / nice) * 100);
            return {
                label: humanize(dataset.label),
                value,
                pct,
                color:
                    series.length === 1
                        ? seriesColor(index)
                        : seriesColor(di, dataset),
                negative: value < 0,
            };
        }),
    }));
});

const pieSlices = computed(() => {
    const data = primary.value.data.map((n) => Math.max(0, Number(n) || 0));
    const total = data.reduce((sum, n) => sum + n, 0) || 1;
    let angle = -Math.PI / 2;
    const cx = 90;
    const cy = 90;
    const r = 72;
    const inner = 44;

    return data.map((value, index) => {
        const slice = (value / total) * Math.PI * 2;
        const start = angle;
        const end = angle + Math.max(slice, 0.0001);
        angle = end;

        const x1 = cx + r * Math.cos(start);
        const y1 = cy + r * Math.sin(start);
        const x2 = cx + r * Math.cos(end);
        const y2 = cy + r * Math.sin(end);
        const ix1 = cx + inner * Math.cos(end);
        const iy1 = cy + inner * Math.sin(end);
        const ix2 = cx + inner * Math.cos(start);
        const iy2 = cy + inner * Math.sin(start);
        const large = slice > Math.PI ? 1 : 0;

        return {
            d: `M ${x1} ${y1} A ${r} ${r} 0 ${large} 1 ${x2} ${y2} L ${ix1} ${iy1} A ${inner} ${inner} 0 ${large} 0 ${ix2} ${iy2} Z`,
            color: seriesColor(index),
            label: props.labels[index] ?? `#${index + 1}`,
            value,
            pct: total ? Math.round((value / total) * 100) : 0,
        };
    });
});

const pieTotal = computed(() =>
    primary.value.data.reduce(
        (sum, n) => sum + Math.max(0, Number(n) || 0),
        0,
    ),
);

const legendItems = computed(() =>
    visibleDatasets.value.slice(0, 3).map((dataset, index) => ({
        label: humanize(dataset.label),
        color: seriesColor(index, dataset),
    })),
);

function niceCeil(value: number): number {
    if (value <= 0) {
        return 1;
    }
    const exp = Math.floor(Math.log10(value));
    const f = value / 10 ** exp;
    const nice = f <= 1 ? 1 : f <= 2 ? 2 : f <= 5 ? 5 : 10;

    return nice * 10 ** exp;
}

function formatCompact(value: number): string {
    const abs = Math.abs(value);
    const sign = value < 0 ? '-' : '';
    if (abs >= 1_000_000) {
        return `${sign}${(abs / 1_000_000).toFixed(abs >= 10_000_000 ? 0 : 1)}M`;
    }
    if (abs >= 1_000) {
        return `${sign}${(abs / 1_000).toFixed(abs >= 10_000 ? 0 : 1)}k`;
    }
    if (Number.isInteger(value)) {
        return String(value);
    }

    return value.toFixed(1);
}

function formatFull(value: number): string {
    return value.toLocaleString(undefined, {
        maximumFractionDigits: 2,
        minimumFractionDigits: Number.isInteger(value) ? 0 : 2,
    });
}

function shortLabel(label: string, max = 10): string {
    const text = String(label ?? '');
    if (text.length <= max) {
        return text;
    }

    return `${text.slice(0, max - 1)}…`;
}

function humanize(label: string): string {
    return String(label)
        .replace(/_/g, ' ')
        .replace(/\b\w/g, (c) => c.toUpperCase());
}

function shouldShowLabel(index: number): boolean {
    const n = visibleLabels.value.length;
    if (n <= 8) {
        return true;
    }
    if (n <= 14) {
        return index % 2 === 0 || index === n - 1;
    }

    return index % Math.ceil(n / 7) === 0 || index === n - 1;
}

function prevPage() {
    page.value = Math.max(0, page.value - 1);
}

function nextPage() {
    page.value = Math.min(pageCount.value - 1, page.value + 1);
}
</script>

<template>
    <div class="w-full space-y-3">
        <div
            class="flex flex-wrap items-center justify-between gap-2"
        >
            <div
                v-if="type !== 'pie' && legendItems.length > 1"
                class="flex flex-wrap gap-x-4 gap-y-1.5"
            >
                <div
                    v-for="item in legendItems"
                    :key="item.label"
                    class="flex items-center gap-2 text-xs text-muted-foreground"
                >
                    <span
                        class="size-2.5 shrink-0 rounded-full"
                        :style="{ background: item.color }"
                    />
                    <span class="font-medium text-foreground/80">{{
                        item.label
                    }}</span>
                </div>
            </div>
            <div v-else />

            <div
                v-if="showPager"
                class="ms-auto flex items-center gap-1"
            >
                <Button
                    type="button"
                    variant="outline"
                    size="icon"
                    class="size-7"
                    :disabled="page <= 0"
                    @click="prevPage"
                >
                    <ChevronLeft class="size-3.5 rtl:rotate-180" />
                </Button>
                <span
                    class="min-w-28 px-1 text-center text-xs tabular-nums text-muted-foreground"
                >
                    {{ pageRangeLabel }}
                    <span class="text-border">·</span>
                    {{ page + 1 }}/{{ pageCount }}
                </span>
                <Button
                    type="button"
                    variant="outline"
                    size="icon"
                    class="size-7"
                    :disabled="page >= pageCount - 1"
                    @click="nextPage"
                >
                    <ChevronRight class="size-3.5 rtl:rotate-180" />
                </Button>
            </div>
        </div>

        <div
            v-if="!hasData || labels.length === 0"
            class="flex h-48 items-center justify-center rounded-xl border border-dashed border-border/70 bg-muted/20 text-sm text-muted-foreground"
        >
            No data for this period
        </div>

        <div
            v-else-if="type === 'bar' && orientation === 'horizontal'"
            class="space-y-3"
        >
            <div
                v-for="(row, index) in horizontalRows"
                :key="`h-${index}`"
                class="space-y-1.5"
            >
                <div class="flex items-center justify-between gap-3 text-sm">
                    <span
                        class="truncate font-medium text-foreground/90"
                        :title="row.label"
                    >
                        {{ row.label }}
                    </span>
                    <span
                        class="shrink-0 font-medium tabular-nums text-foreground/80"
                    >
                        <template v-if="row.bars.length === 1">
                            {{ formatFull(row.bars[0]!.value) }}
                        </template>
                        <template v-else>
                            {{
                                row.bars
                                    .map(
                                        (b) =>
                                            `${b.label}: ${formatCompact(b.value)}`,
                                    )
                                    .join(' · ')
                            }}
                        </template>
                    </span>
                </div>
                <div class="space-y-1">
                    <div
                        v-for="(bar, bi) in row.bars"
                        :key="`hb-${index}-${bi}`"
                        class="h-3 overflow-hidden rounded-full bg-muted/70"
                    >
                        <div
                            class="h-full rounded-full transition-all"
                            :class="bar.negative ? 'ml-auto' : ''"
                            :style="{
                                width: `${bar.pct}%`,
                                background: bar.color,
                                opacity: bar.negative ? 0.75 : 1,
                            }"
                        />
                    </div>
                </div>
            </div>
        </div>

        <svg
            v-else-if="type === 'bar'"
            viewBox="0 0 720 180"
            class="w-full"
            style="aspect-ratio: 720 / 180"
            role="img"
            aria-label="Bar chart"
        >
            <defs>
                <linearGradient
                    v-for="(_, di) in visibleDatasets.slice(0, barLayout.series)"
                    :id="`bar-grad-${chartId}-${di}`"
                    :key="`grad-${di}`"
                    x1="0"
                    y1="0"
                    x2="0"
                    y2="1"
                >
                    <stop
                        offset="0%"
                        :stop-color="seriesColor(di, visibleDatasets[di])"
                        stop-opacity="1"
                    />
                    <stop
                        offset="100%"
                        :stop-color="seriesColor(di, visibleDatasets[di])"
                        stop-opacity="0.72"
                    />
                </linearGradient>
            </defs>

            <line
                v-for="tick in yTicks"
                :key="`grid-${tick.value}`"
                :x1="V.left"
                :y1="tick.y"
                :x2="V.right"
                :y2="tick.y"
                stroke="currentColor"
                class="text-border/60"
                stroke-width="1"
                stroke-dasharray="2 5"
            />
            <text
                v-for="tick in yTicks"
                :key="`yt-${tick.value}`"
                :x="V.left - 8"
                :y="tick.y + 3"
                text-anchor="end"
                class="fill-muted-foreground"
                font-size="11"
            >
                {{ tick.label }}
            </text>

            <line
                v-if="plotMin < 0"
                :x1="V.left"
                :y1="zeroY()"
                :x2="V.right"
                :y2="zeroY()"
                stroke="currentColor"
                class="text-foreground/25"
                stroke-width="1.25"
            />

            <g
                v-for="(label, index) in visibleLabels"
                :key="`bar-${index}`"
            >
                <g
                    v-for="(dataset, di) in visibleDatasets.slice(
                        0,
                        barLayout.series,
                    )"
                    :key="`bar-${index}-${di}`"
                >
                    <title>
                        {{ label }} · {{ humanize(dataset.label) }}:
                        {{ formatFull(Number(dataset.data[index]) || 0) }}
                    </title>
                    <rect
                        :x="barX(index, di)"
                        :y="
                            Math.min(
                                yFor(Number(dataset.data[index]) || 0),
                                zeroY(),
                            )
                        "
                        :width="barLayout.width"
                        :height="
                            Math.max(
                                2,
                                Math.abs(
                                    yFor(Number(dataset.data[index]) || 0) -
                                        zeroY(),
                                ),
                            )
                        "
                        :fill="`url(#bar-grad-${chartId}-${di})`"
                        rx="4"
                    />
                </g>
                <text
                    v-if="shouldShowLabel(index)"
                    :x="V.left + index * barLayout.slot + barLayout.slot / 2"
                    y="168"
                    text-anchor="middle"
                    class="fill-muted-foreground"
                    font-size="11"
                >
                    <title>{{ label }}</title>
                    {{
                        shortLabel(
                            label,
                            visibleLabels.length > 8 ? 6 : 10,
                        )
                    }}
                </text>
            </g>
        </svg>

        <svg
            v-else-if="type === 'line'"
            viewBox="0 0 720 180"
            class="w-full"
            style="aspect-ratio: 720 / 180"
            role="img"
            aria-label="Line chart"
        >
            <line
                v-for="tick in yTicks"
                :key="`lgrid-${tick.value}`"
                :x1="V.left"
                :y1="tick.y"
                :x2="V.right"
                :y2="tick.y"
                stroke="currentColor"
                class="text-border/60"
                stroke-width="1"
                stroke-dasharray="2 5"
            />
            <text
                v-for="tick in yTicks"
                :key="`lyt-${tick.value}`"
                :x="V.left - 8"
                :y="tick.y + 3"
                text-anchor="end"
                class="fill-muted-foreground"
                font-size="11"
            >
                {{ tick.label }}
            </text>

            <g v-for="(series, si) in lineSeries" :key="`ls-${si}`">
                <polygon
                    v-if="si === 0"
                    :points="series.area"
                    :fill="series.color"
                    opacity="0.08"
                />
                <polyline
                    :points="series.polyline"
                    fill="none"
                    :stroke="series.color"
                    stroke-width="2.75"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                />
                <g
                    v-for="(point, index) in series.points"
                    :key="`pt-${si}-${index}`"
                >
                    <title>
                        {{ visibleLabels[index] }} · {{ series.label }}:
                        {{ formatFull(point.value) }}
                    </title>
                    <circle
                        :cx="point.x"
                        :cy="point.y"
                        r="4.5"
                        fill="var(--card)"
                        :stroke="series.color"
                        stroke-width="2.25"
                    />
                    <text
                        v-if="
                            showValues &&
                            series.points.length <= 8 &&
                            visibleDatasets.length === 1
                        "
                        :x="point.x"
                        :y="point.y - 10"
                        text-anchor="middle"
                        class="fill-foreground/80"
                        font-size="10"
                        font-weight="600"
                    >
                        {{ formatCompact(point.value) }}
                    </text>
                </g>
            </g>

            <template
                v-for="(label, index) in visibleLabels"
                :key="`ln-${index}`"
            >
                <text
                    v-if="shouldShowLabel(index)"
                    :x="
                        V.left +
                        (index / Math.max(visibleLabels.length - 1, 1)) *
                            V.width
                    "
                    y="168"
                    text-anchor="middle"
                    class="fill-muted-foreground"
                    font-size="11"
                >
                    <title>{{ label }}</title>
                    {{
                        shortLabel(
                            label,
                            visibleLabels.length > 8 ? 6 : 10,
                        )
                    }}
                </text>
            </template>
        </svg>

        <div
            v-else-if="type === 'pie'"
            class="flex flex-col gap-5 sm:flex-row sm:items-center"
        >
            <div class="relative mx-auto shrink-0 sm:mx-0">
                <svg
                    viewBox="0 0 180 180"
                    class="size-44 sm:size-48"
                    role="img"
                    aria-label="Pie chart"
                >
                    <circle
                        cx="90"
                        cy="90"
                        r="72"
                        fill="none"
                        stroke="currentColor"
                        class="text-muted/40"
                        stroke-width="16"
                        opacity="0.35"
                    />
                    <path
                        v-for="(slice, index) in pieSlices"
                        :key="`pie-${index}`"
                        :d="slice.d"
                        :fill="slice.color"
                    >
                        <title>
                            {{ slice.label }}:
                            {{ formatFull(slice.value) }} ({{ slice.pct }}%)
                        </title>
                    </path>
                    <text
                        x="90"
                        y="84"
                        text-anchor="middle"
                        class="fill-muted-foreground"
                        font-size="11"
                    >
                        Total
                    </text>
                    <text
                        x="90"
                        y="106"
                        text-anchor="middle"
                        class="fill-foreground"
                        font-size="18"
                        font-weight="700"
                    >
                        {{ formatCompact(pieTotal) }}
                    </text>
                </svg>
            </div>
            <ul class="min-w-0 flex-1 space-y-3">
                <li
                    v-for="(slice, index) in pieSlices"
                    :key="`legend-${index}`"
                    class="flex items-center gap-3 rounded-xl bg-muted/30 px-3 py-2.5"
                >
                    <span
                        class="size-3 shrink-0 rounded-full"
                        :style="{ background: slice.color }"
                    />
                    <div class="min-w-0 flex-1">
                        <div class="truncate text-sm font-semibold">
                            {{ slice.label }}
                        </div>
                        <div class="text-xs text-muted-foreground">
                            {{ slice.pct }}%
                        </div>
                    </div>
                    <div class="text-sm font-semibold tabular-nums">
                        {{ formatFull(slice.value) }}
                    </div>
                </li>
            </ul>
        </div>
    </div>
</template>
