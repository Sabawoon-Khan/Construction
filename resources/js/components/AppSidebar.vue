<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { ChevronsLeft, ChevronsRight } from '@lucide/vue';
import type { HTMLAttributes } from 'vue';
import { computed } from 'vue';
import AppLogo from '@/components/AppLogo.vue';
import NavMain from '@/components/NavMain.vue';
import { Button } from '@/components/ui/button';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarSeparator,
    useSidebar,
} from '@/components/ui/sidebar';
import { useAppNavigation } from '@/composables/useAppNavigation';
import { useLocale } from '@/composables/useLocale';
import { useTranslations } from '@/composables/useTranslations';
import {
    normalizeThemeColors,
    shellToSidebarVariant,
    type ThemeShell,
} from '@/lib/theme-colors';
import { dashboard } from '@/routes';

const props = defineProps<{
    class?: HTMLAttributes['class'];
}>();

const page = usePage();
const { t } = useTranslations();
const { sidebarSide, dir } = useLocale();
const { toggleSidebar, state } = useSidebar();
const { mainNavItems, constructionNavItems, adminNavItems } = useAppNavigation();

const isCollapsed = computed(() => state.value === 'collapsed');

const CollapseIcon = computed(() =>
    dir.value === 'rtl' ? ChevronsRight : ChevronsLeft,
);

const sidebarVariant = computed(() => {
    const shell = normalizeThemeColors(page.props.themeColors).shell as ThemeShell;

    return shellToSidebarVariant(shell);
});
</script>

<template>
    <Sidebar
        collapsible="icon"
        :variant="sidebarVariant"
        :side="sidebarSide"
        :class="props.class"
        data-tour="app-sidebar"
    >
        <SidebarHeader
            class="gap-0 p-4 pb-3 transition-[padding] duration-300 ease-in-out group-data-[collapsible=icon]:items-center group-data-[collapsible=icon]:px-0 group-data-[collapsible=icon]:pt-3 group-data-[collapsible=icon]:pb-2"
        >
            <SidebarMenu class="group-data-[collapsible=icon]:items-center">
                <SidebarMenuItem
                    class="group-data-[collapsible=icon]:flex group-data-[collapsible=icon]:justify-center"
                >
                    <SidebarMenuButton
                        size="lg"
                        as-child
                        class="h-auto rounded-[var(--radius-control)] p-2 hover:bg-white/10 group-data-[collapsible=icon]:size-10! group-data-[collapsible=icon]:justify-center group-data-[collapsible=icon]:p-0! data-[active=true]:bg-transparent data-[active=true]:text-white data-[active=true]:shadow-none"
                    >
                        <Link
                            :href="dashboard()"
                            class="group-data-[collapsible=icon]:justify-center"
                        >
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
            <SidebarSeparator
                class="my-3 bg-white/15 transition-opacity duration-300 group-data-[collapsible=icon]:pointer-events-none group-data-[collapsible=icon]:my-0 group-data-[collapsible=icon]:h-0 group-data-[collapsible=icon]:opacity-0"
            />
        </SidebarHeader>

        <SidebarContent
            class="px-3 transition-[padding] duration-300 ease-in-out group-data-[collapsible=icon]:items-center group-data-[collapsible=icon]:px-0"
            data-tour="sidebar-nav"
        >
            <NavMain :items="mainNavItems" :label="t('Platform')" />
            <NavMain
                v-if="constructionNavItems.length"
                :items="constructionNavItems"
                :label="t('Construction')"
            />
            <NavMain
                v-if="adminNavItems.length"
                :items="adminNavItems"
                :label="t('Administration')"
            />
        </SidebarContent>

        <SidebarFooter
            class="mt-auto gap-0 p-0 transition-all duration-300 ease-in-out group-data-[collapsible=icon]:items-center"
        >
            <div
                class="flex w-full justify-center border-t border-white/10 px-2 py-2 transition-all duration-300 group-data-[collapsible=icon]:border-white/5 group-data-[collapsible=icon]:px-0 group-data-[collapsible=icon]:py-1.5"
            >
                <Button
                    variant="ghost"
                    :size="isCollapsed ? 'icon' : 'default'"
                    class="rounded-[var(--radius-control)] text-white/70 transition-all duration-300 hover:bg-white/10 hover:text-white group-data-[collapsible=icon]:size-9"
                    :class="isCollapsed ? 'w-9' : 'w-full'"
                    data-tour="sidebar-collapse"
                    @click="toggleSidebar"
                >
                    <component
                        :is="CollapseIcon"
                        class="size-4 shrink-0 transition-transform duration-300"
                        :class="{ 'rotate-180': isCollapsed }"
                    />
                    <span
                        class="text-xs font-medium group-data-[collapsible=icon]:hidden"
                    >
                        {{
                            isCollapsed
                                ? t('Expand sidebar')
                                : t('Collapse sidebar')
                        }}
                    </span>
                </Button>
            </div>
        </SidebarFooter>
    </Sidebar>
</template>
