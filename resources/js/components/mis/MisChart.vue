<script setup lang="ts">
import { computed } from 'vue';

export type MisChartDataset = {
    label: string;
    data: number[];
};

const props = withDefaults(
    defineProps<{
        type: 'bar' | 'line' | 'pie';
        labels: string[];
        datasets: MisChartDataset[];
        /** Show numeric values on bars / points */
        showValues?: boolean;
    }>(),
    {
        showValues: true,
    },
);

const palette = [
    'var(--chart-1)',
    'var(--chart-2)',
    'var(--chart-3)',
    'var(--chart-4)',
    'var(--chart-5)',
];

const primary = computed(() => props.datasets[0] ?? { label: '', data: [] });

const maxValue = computed(() => {
    const values = props.datasets.flatMap((d) => d.data.map((n) => Number(n) || 0));
    const max = Math.max(0, ...values);

    return max === 0 ? 1 : max;
});

const yTicks = computed(() => {
    const max = maxValue.value;
    const steps = 4;
    const nice = niceCeil(max);
    return Array.from({ length: steps + 1 }, (_, i) => {
        const value = (nice / steps) * i;
        return {
            value,
            y: 140 - (value / nice) * 110,
            label: formatCompact(value),
        };
    });
});

const plotMax = computed(() => niceCeil(maxValue.value));

const barLayout = computed(() => {
    const n = Math.max(props.labels.length, 1);
    const plotWidth = 300;
    const slot = plotWidth / n;
    const series = Math.min(props.datasets.length, 3);
    const width = Math.min(42, Math.max(8, (slot * 0.72) / series));

    return { slot, width, series, plotWidth };
});

function barX(index: number, seriesIndex: number): number {
    const { slot, width, series } = barLayout.value;
    const groupWidth = width * series + Math.max(0, series - 1) * 3;
    const start = 48 + index * slot + (slot - groupWidth) / 2;

    return start + seriesIndex * (width + 3);
}

const linePoints = computed(() => {
    const data = primary.value.data;
    const n = Math.max(data.length - 1, 1);

    return data
        .map((value, index) => {
            const x = 48 + (index / n) * 300;
            const y = 140 - ((Number(value) || 0) / plotMax.value) * 110;

            return `${x},${y}`;
        })
        .join(' ');
});

const areaPoints = computed(() => {
    const data = primary.value.data;
    if (data.length === 0) {
        return '';
    }
    const n = Math.max(data.length - 1, 1);
    const pts = data.map((value, index) => {
        const x = 48 + (index / n) * 300;
        const y = 140 - ((Number(value) || 0) / plotMax.value) * 110;

        return `${x},${y}`;
    });

    return `${pts[0]} ${pts.join(' ')} ${pts[pts.length - 1]!.split(',')[0]},140 48,140`;
});

const pieSlices = computed(() => {
    const data = primary.value.data.map((n) => Math.max(0, Number(n) || 0));
    const total = data.reduce((sum, n) => sum + n, 0) || 1;
    let angle = -Math.PI / 2;
    const cx = 92;
    const cy = 92;
    const r = 74;
    const inner = 40;

    return data.map((value, index) => {
        const slice = (value / total) * Math.PI * 2;
        const start = angle;
        const end = angle + Math.max(slice, 0.0001);
        angle = end;
        const mid = (start + end) / 2;

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
            color: palette[index % palette.length],
            label: props.labels[index] ?? `#${index + 1}`,
            value,
            pct: Math.round((value / total) * 100),
            tipX: cx + (r - 18) * Math.cos(mid),
            tipY: cy + (r - 18) * Math.sin(mid),
        };
    });
});

const pieTotal = computed(() =>
    primary.value.data.reduce((sum, n) => sum + Math.max(0, Number(n) || 0), 0),
);

const seriesSummaries = computed(() =>
    props.datasets.slice(0, 3).map((dataset, index) => {
        const values = dataset.data.map((n) => Number(n) || 0);
        const sum = values.reduce((a, b) => a + b, 0);
        const max = Math.max(0, ...values);

        return {
            label: humanize(dataset.label),
            color: palette[index % palette.length],
            sum,
            max,
            points: values.length,
        };
    }),
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
    if (abs >= 1_000_000) {
        return `${(value / 1_000_000).toFixed(abs >= 10_000_000 ? 0 : 1)}M`;
    }
    if (abs >= 1_000) {
        return `${(value / 1_000).toFixed(abs >= 10_000 ? 0 : 1)}k`;
    }
    if (Number.isInteger(value)) {
        return String(value);
    }

    return value.toFixed(1);
}

function formatFull(value: number): string {
    if (Number.isInteger(value)) {
        return value.toLocaleString();
    }

    return value.toLocaleString(undefined, { maximumFractionDigits: 2 });
}

function shortLabel(label: string, max = 12): string {
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
    const n = props.labels.length;
    if (n <= 8) {
        return true;
    }
    if (n <= 16) {
        return index % 2 === 0;
    }

    return index % Math.ceil(n / 8) === 0 || index === n - 1;
}
</script>

<template>
    <div class="w-full space-y-4">
        <!-- Legend -->
        <div
            v-if="type !== 'pie' && seriesSummaries.length"
            class="flex flex-wrap gap-x-4 gap-y-2"
        >
            <div
                v-for="series in seriesSummaries"
                :key="series.label"
                class="flex min-w-0 items-center gap-2 text-xs"
            >
                <span
                    class="size-2.5 shrink-0 rounded-sm"
                    :style="{ background: series.color }"
                />
                <span class="truncate font-medium text-foreground/85">{{
                    series.label
                }}</span>
                <span class="tabular-nums text-muted-foreground">
                    Σ {{ formatCompact(series.sum) }}
                </span>
            </div>
        </div>

        <svg
            v-if="type === 'bar'"
            viewBox="0 0 380 176"
            class="h-56 w-full"
            role="img"
            aria-label="Bar chart"
        >
            <line
                v-for="tick in yTicks"
                :key="`grid-${tick.value}`"
                x1="48"
                :y1="tick.y"
                x2="352"
                :y2="tick.y"
                stroke="currentColor"
                class="text-border/70"
                stroke-width="1"
                stroke-dasharray="3 4"
            />
            <text
                v-for="tick in yTicks"
                :key="`yt-${tick.value}`"
                x="40"
                :y="tick.y + 3"
                text-anchor="end"
                class="fill-muted-foreground"
                font-size="9"
            >
                {{ tick.label }}
            </text>

            <g v-for="(label, index) in labels" :key="`bar-${index}`">
                <g
                    v-for="(dataset, di) in datasets.slice(0, barLayout.series)"
                    :key="`bar-${index}-${di}`"
                >
                    <title>
                        {{ label }} · {{ humanize(dataset.label) }}:
                        {{ formatFull(Number(dataset.data[index]) || 0) }}
                    </title>
                    <rect
                        :x="barX(index, di)"
                        :y="
                            140 -
                            ((Number(dataset.data[index]) || 0) / plotMax) * 110
                        "
                        :width="barLayout.width"
                        :height="
                            Math.max(
                                2,
                                ((Number(dataset.data[index]) || 0) / plotMax) *
                                    110,
                            )
                        "
                        :fill="palette[di % palette.length]"
                        rx="3"
                    />
                    <text
                        v-if="
                            showValues &&
                            labels.length <= 10 &&
                            datasets.length === 1
                        "
                        :x="barX(index, di) + barLayout.width / 2"
                        :y="
                            140 -
                            ((Number(dataset.data[index]) || 0) / plotMax) *
                                110 -
                            6
                        "
                        text-anchor="middle"
                        class="fill-foreground/80"
                        font-size="9"
                        font-weight="600"
                    >
                        {{
                            formatCompact(Number(dataset.data[index]) || 0)
                        }}
                    </text>
                </g>
                <text
                    v-if="shouldShowLabel(index)"
                    :x="48 + index * barLayout.slot + barLayout.slot / 2"
                    y="158"
                    text-anchor="middle"
                    class="fill-muted-foreground"
                    font-size="9"
                >
                    <title>{{ label }}</title>
                    {{ shortLabel(label, labels.length > 8 ? 8 : 12) }}
                </text>
            </g>
        </svg>

        <svg
            v-else-if="type === 'line'"
            viewBox="0 0 380 176"
            class="h-56 w-full"
            role="img"
            aria-label="Line chart"
        >
            <line
                v-for="tick in yTicks"
                :key="`lgrid-${tick.value}`"
                x1="48"
                :y1="tick.y"
                x2="352"
                :y2="tick.y"
                stroke="currentColor"
                class="text-border/70"
                stroke-width="1"
                stroke-dasharray="3 4"
            />
            <text
                v-for="tick in yTicks"
                :key="`lyt-${tick.value}`"
                x="40"
                :y="tick.y + 3"
                text-anchor="end"
                class="fill-muted-foreground"
                font-size="9"
            >
                {{ tick.label }}
            </text>
            <polygon
                :points="areaPoints"
                fill="var(--chart-1)"
                opacity="0.1"
            />
            <polyline
                :points="linePoints"
                fill="none"
                stroke="var(--chart-1)"
                stroke-width="2.5"
                stroke-linecap="round"
                stroke-linejoin="round"
            />
            <g v-for="(value, index) in primary.data" :key="`pt-${index}`">
                <title>
                    {{ labels[index] }}:
                    {{ formatFull(Number(value) || 0) }}
                </title>
                <circle
                    :cx="
                        48 +
                        (index / Math.max(primary.data.length - 1, 1)) * 300
                    "
                    :cy="140 - ((Number(value) || 0) / plotMax) * 110"
                    r="4"
                    fill="var(--card)"
                    stroke="var(--chart-1)"
                    stroke-width="2"
                />
                <text
                    v-if="showValues && primary.data.length <= 12"
                    :x="
                        48 +
                        (index / Math.max(primary.data.length - 1, 1)) * 300
                    "
                    :y="140 - ((Number(value) || 0) / plotMax) * 110 - 10"
                    text-anchor="middle"
                    class="fill-foreground/80"
                    font-size="9"
                    font-weight="600"
                >
                    {{ formatCompact(Number(value) || 0) }}
                </text>
            </g>
            <template v-for="(label, index) in labels" :key="`ln-${index}`">
                <text
                    v-if="shouldShowLabel(index)"
                    :x="
                        48 +
                        (index / Math.max(labels.length - 1, 1)) * 300
                    "
                    y="158"
                    text-anchor="middle"
                    class="fill-muted-foreground"
                    font-size="9"
                >
                    <title>{{ label }}</title>
                    {{ shortLabel(label, labels.length > 8 ? 8 : 12) }}
                </text>
            </template>
        </svg>

        <div
            v-else
            class="flex flex-col gap-5 sm:flex-row sm:items-center"
        >
            <div class="relative mx-auto shrink-0 sm:mx-0">
                <svg
                    viewBox="0 0 184 184"
                    class="size-48"
                    role="img"
                    aria-label="Pie chart"
                >
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
                        x="92"
                        y="88"
                        text-anchor="middle"
                        class="fill-muted-foreground"
                        font-size="10"
                    >
                        Total
                    </text>
                    <text
                        x="92"
                        y="108"
                        text-anchor="middle"
                        class="fill-foreground"
                        font-size="16"
                        font-weight="700"
                    >
                        {{ formatCompact(pieTotal) }}
                    </text>
                </svg>
            </div>
            <ul class="min-w-0 flex-1 space-y-2.5">
                <li
                    v-for="(slice, index) in pieSlices"
                    :key="`legend-${index}`"
                    class="grid grid-cols-[auto_minmax(0,1fr)_auto_auto] items-center gap-2.5 text-sm"
                >
                    <span
                        class="size-2.5 shrink-0 rounded-full"
                        :style="{ background: slice.color }"
                    />
                    <span class="truncate font-medium text-foreground/90">{{
                        slice.label
                    }}</span>
                    <span class="tabular-nums text-foreground">
                        {{ formatFull(slice.value) }}
                    </span>
                    <span
                        class="w-10 text-end tabular-nums text-muted-foreground"
                    >
                        {{ slice.pct }}%
                    </span>
                </li>
            </ul>
        </div>
    </div>
</template>
