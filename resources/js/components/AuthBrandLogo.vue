<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import type { HTMLAttributes } from 'vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { home } from '@/routes';

const props = withDefaults(
    defineProps<{
        linked?: boolean;
        showName?: boolean;
        size?: 'md' | 'lg';
        class?: HTMLAttributes['class'];
    }>(),
    {
        linked: true,
        showName: true,
        size: 'lg',
    },
);

const page = usePage();
const name = computed(() => page.props.name || 'Yaqeen Kit');
const logo = computed(() => {
    const value = page.props.logo;

    return typeof value === 'string' && value !== '' ? value : null;
});

const markClass = computed(() =>
    props.size === 'lg' ? 'size-16' : 'size-12',
);
</script>

<template>
    <component
        :is="linked ? Link : 'div'"
        v-bind="linked ? { href: home() } : {}"
        :class="[
            'group flex flex-col items-center gap-3 text-center',
            props.class,
        ]"
    >
        <div
            :class="[
                'flex items-center justify-center overflow-hidden',
                markClass,
            ]"
        >
            <img
                v-if="logo"
                :src="logo"
                :alt="name"
                class="max-h-full max-w-full object-contain"
            />
            <AppLogoIcon
                v-else
                :class="[
                    markClass,
                    'fill-current text-[#14231f] dark:text-white',
                ]"
            />
        </div>
        <span
            v-if="showName"
            class="font-display text-2xl font-semibold tracking-tight text-[#14231f] dark:text-white"
        >
            {{ name }}
        </span>
        <span v-else class="sr-only">{{ name }}</span>
    </component>
</template>
