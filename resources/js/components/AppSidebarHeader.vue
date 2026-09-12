<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import LanguageSwitcher from '@/components/LanguageSwitcher.vue';
import GlobalSearch from '@/components/GlobalSearch.vue';
import NotificationBell from '@/components/NotificationBell.vue';
import UserMenuContent from '@/components/UserMenuContent.vue';
import AiHeaderButton from '@/components/AiHeaderButton.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { SidebarTrigger } from '@/components/ui/sidebar';
import { useInitials } from '@/composables/useInitials';
import { useTranslations } from '@/composables/useTranslations';
import type { BreadcrumbItem } from '@/types';

const props = withDefaults(
    defineProps<{
        breadcrumbs?: BreadcrumbItem[];
    }>(),
    {
        breadcrumbs: () => [],
    },
);

const page = usePage();
const { t } = useTranslations();
const { getInitials } = useInitials();

const user = computed(() => page.props.auth.user);

const pageTitle = computed(() => {
    const last = props.breadcrumbs.at(-1);
    return last ? t(last.title) : t('Dashboard');
});

const showAvatar = computed(
    () => user.value?.avatar && user.value.avatar !== '',
);
</script>

<template>
    <header
        class="glass-header sticky top-0 z-20 flex h-14 shrink-0 items-center justify-between gap-2 border-b border-slate-200/70 px-3 sm:h-16 sm:gap-4 sm:px-6 md:px-8 dark:border-white/10"
        data-tour="app-header"
    >
        <div class="flex min-w-0 items-center gap-2 sm:gap-3">
            <SidebarTrigger class="shrink-0 md:hidden" />
            <h1 class="truncate text-base font-semibold tracking-tight text-foreground sm:text-lg">
                {{ pageTitle }}
            </h1>
        </div>

        <div class="flex shrink-0 items-center gap-0.5 sm:gap-2">
            <GlobalSearch />
            <LanguageSwitcher />
            <AiHeaderButton />
            <NotificationBell />

            <DropdownMenu v-if="user">
                <DropdownMenuTrigger as-child>
                    <Button
                        variant="ghost"
                        class="size-9 rounded-full p-0 hover:bg-accent"
                    >
                        <Avatar class="size-9">
                            <AvatarImage
                                v-if="showAvatar"
                                :src="user.avatar!"
                                :alt="user.name"
                            />
                            <AvatarFallback
                                class="bg-primary text-xs font-semibold text-primary-foreground"
                            >
                                {{ getInitials(user.name) }}
                            </AvatarFallback>
                        </Avatar>
                    </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent
                    align="end"
                    class="min-w-56 rounded-2xl border-border/60 p-1.5 shadow-[0_20px_60px_-20px_rgba(15,23,42,0.25)] backdrop-blur-md"
                >
                    <UserMenuContent :user="user" />
                </DropdownMenuContent>
            </DropdownMenu>
        </div>
    </header>
</template>
