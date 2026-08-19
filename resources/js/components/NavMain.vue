<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    SidebarGroup,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { isNavItemActive } from '@/composables/useAppNavigation';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import type { NavItem } from '@/types';

const props = defineProps<{
    label: string;
    items: NavItem[];
}>();

const { currentUrl } = useCurrentUrl();
</script>

<template>
    <SidebarGroup class="px-0 py-1">
        <SidebarGroupLabel
            v-if="props.label"
            class="px-2.5 pb-1.5 text-[11px] font-semibold tracking-[0.14em] uppercase opacity-80"
        >
            {{ props.label }}
        </SidebarGroupLabel>
        <SidebarMenu class="gap-1.5">
            <SidebarMenuItem v-for="item in items" :key="String(item.href)">
                <SidebarMenuButton
                    as-child
                    :is-active="isNavItemActive(item, currentUrl)"
                    :tooltip="item.title"
                    class="h-9 gap-2.5 rounded-xl px-2.5 text-[13.5px] font-medium transition-all duration-200 hover:translate-x-[1px] [&>svg]:size-[18px]"
                >
                    <Link :href="item.href">
                        <component :is="item.icon" stroke-width="1.75" />
                        <span class="group-data-[collapsible=icon]:hidden">{{
                            item.title
                        }}</span>
                    </Link>
                </SidebarMenuButton>
            </SidebarMenuItem>
        </SidebarMenu>
    </SidebarGroup>
</template>
