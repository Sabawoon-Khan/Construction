<script setup lang="ts">
import { Layers3, Sparkles } from '@lucide/vue';
import { useAppearance } from '@/composables/useAppearance';
import { useTranslations } from '@/composables/useTranslations';

const { interfaceStyle, updateInterfaceStyle } = useAppearance();
const { t } = useTranslations();

const styles = [
    {
        value: 'standard',
        Icon: Layers3,
        labelKey: 'Standard',
        descriptionKey: 'Solid, clean surfaces with your selected color mode.',
    },
    {
        value: 'glass',
        Icon: Sparkles,
        labelKey: 'Glass',
        descriptionKey:
            'Frosted translucent panels over a soft light workspace — the Structo look.',
    },
] as const;
</script>

<template>
    <div
        class="grid w-full gap-3 sm:grid-cols-2"
        role="tablist"
        :aria-label="t('Interface style')"
    >
        <button
            v-for="{ value, Icon, labelKey, descriptionKey } in styles"
            :key="value"
            type="button"
            role="tab"
            :aria-selected="interfaceStyle === value"
            class="group overflow-hidden rounded-2xl border text-start transition"
            :class="
                interfaceStyle === value
                    ? 'border-primary/40 shadow-soft ring-1 ring-primary/20'
                    : 'border-border/70 hover:border-primary/25'
            "
            @click="updateInterfaceStyle(value)"
        >
            <div
                class="relative h-[88px] overflow-hidden border-b border-border/40"
                :class="
                    value === 'glass'
                        ? 'bg-[linear-gradient(145deg,#eef0ee,#e4e8e6)]'
                        : 'bg-muted/50'
                "
            >
                <img
                    v-if="value === 'glass'"
                    src="/images/dashboard-v2-building.png"
                    alt=""
                    class="pointer-events-none absolute inset-y-0 end-0 w-[58%] object-contain object-right opacity-70"
                />
                <div
                    class="absolute inset-3 rounded-xl border"
                    :class="
                        value === 'glass'
                            ? 'border-white/60 bg-white/35 shadow-[inset_0_1px_0_rgba(255,255,255,0.85)] backdrop-blur-md'
                            : 'border-border/60 bg-card shadow-sm'
                    "
                >
                    <div class="flex h-full items-end gap-1.5 p-2.5">
                        <span
                            v-for="n in 5"
                            :key="n"
                            class="w-1.5 rounded-t-sm"
                            :class="
                                value === 'glass'
                                    ? n >= 2 && n <= 4
                                        ? 'bg-[#ff8a3a]'
                                        : 'bg-black/10'
                                    : 'bg-primary/40'
                            "
                            :style="{ height: `${18 + n * 7}px` }"
                        />
                    </div>
                </div>
            </div>
            <div class="flex items-start gap-3 p-4">
                <span
                    class="grid size-10 shrink-0 place-items-center rounded-xl"
                    :class="
                        interfaceStyle === value
                            ? 'bg-primary text-primary-foreground'
                            : 'bg-muted text-muted-foreground group-hover:text-foreground'
                    "
                >
                    <component :is="Icon" class="size-4" />
                </span>
                <span class="grid gap-1">
                    <strong class="text-sm font-semibold">{{
                        t(labelKey)
                    }}</strong>
                    <small class="text-xs leading-5 text-muted-foreground">
                        {{ t(descriptionKey) }}
                    </small>
                </span>
            </div>
        </button>
    </div>
</template>
