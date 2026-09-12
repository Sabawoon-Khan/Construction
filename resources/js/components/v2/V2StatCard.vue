<script setup lang="ts">
withDefaults(
    defineProps<{
        delay?: number;
        accent?: boolean;
        iconTone?: 'default' | 'warm' | 'teal' | 'orange';
        title?: string;
        value?: string | number;
    }>(),
    {
        delay: 0,
        accent: false,
        iconTone: 'default',
    },
);
</script>

<template>
    <article
        class="stat-card"
        :class="{ accent }"
        :style="{ '--d': delay }"
    >
        <div
            v-if="$slots.icon"
            class="stat-icon"
            :class="{
                warm: iconTone === 'warm',
                teal: iconTone === 'teal',
                orange: iconTone === 'orange',
            }"
        >
            <slot name="icon" />
        </div>
        <div>
            <slot>
                <span v-if="title">{{ title }}</span>
                <strong v-if="value != null">{{ value }}</strong>
            </slot>
        </div>
    </article>
</template>
