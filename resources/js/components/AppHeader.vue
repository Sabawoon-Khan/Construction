<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { Menu } from '@lucide/vue';
import { computed } from 'vue';
import AppLogo from '@/components/AppLogo.vue';
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import LanguageSwitcher from '@/components/LanguageSwitcher.vue';
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
import {
    Sheet,
    SheetContent,
    SheetHeader,
    SheetTitle,
    SheetTrigger,
} from '@/components/ui/sheet';
import { useAppNavigation } from '@/composables/useAppNavigation';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { useInitials } from '@/composables/useInitials';
import { useTranslations } from '@/composables/useTranslations';
import { toUrl } from '@/lib/utils';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';

withDefaults(
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
const { isCurrentOrParentUrl } = useCurrentUrl();
const { allNavItems } = useAppNavigation();

const user = computed(() => page.props.auth.user);
const showAvatar = computed(() =>
    Boolean(user.value?.avatar && user.value.avatar !== ''),
);
</script>

<template>
    <header
        class="glass-header sticky top-0 z-30 border-b border-border/70"
        data-tour="app-header-nav"
    >
        <div
            class="mx-auto flex h-16 w-full max-w-[90rem] items-center gap-3 px-4 md:px-6"
        >
            <div class="lg:hidden">
                <Sheet>
                    <SheetTrigger as-child>
                        <Button variant="ghost" size="icon" class="size-9">
                            <Menu class="size-5" />
                        </Button>
                    </SheetTrigger>
                    <SheetContent side="left" class="w-[300px] p-0">
                        <SheetTitle class="sr-only">{{
                            t('Navigation')
                        }}</SheetTitle>
                        <SheetHeader
                            class="border-b border-border/70 p-4 text-start"
                        >
                            <Link
                                :href="dashboard()"
                                class="flex items-center gap-2"
                            >
                                <AppLogo tone="on-light" />
                            </Link>
                        </SheetHeader>
                        <nav class="space-y-1 p-3">
                            <Link
                                v-for="item in allNavItems"
                                :key="toUrl(item.href)"
                                :href="item.href"
                                class="flex items-center gap-3 rounded-[var(--radius-control)] px-3 py-2.5 text-sm font-medium transition"
                                :class="
                                    isCurrentOrParentUrl(item.href)
                                        ? 'bg-primary text-primary-foreground'
                                        : 'text-foreground/80 hover:bg-muted'
                                "
                            >
                                <component
                                    :is="item.icon"
                                    v-if="item.icon"
                                    class="size-4 shrink-0"
                                />
                                {{ item.title }}
                            </Link>
                        </nav>
                    </SheetContent>
                </Sheet>
            </div>

            <Link :href="dashboard()" class="hidden items-center gap-2 lg:flex">
                <div
                    class="flex aspect-square size-8 items-center justify-center overflow-hidden rounded-[var(--radius-control)] bg-primary text-primary-foreground"
                >
                    <img
                        v-if="page.props.logo"
                        :src="page.props.logo"
                        :alt="page.props.name"
                        class="size-full object-contain p-0.5"
                    />
                    <span v-else class="text-xs font-bold">{{
                        page.props.name?.slice(0, 1) ?? 'Y'
                    }}</span>
                </div>
                <span class="max-w-[10rem] truncate text-sm font-semibold">{{
                    page.props.name
                }}</span>
            </Link>

            <nav class="ms-2 hidden min-w-0 flex-1 items-center gap-1 lg:flex">
                <Link
                    v-for="item in allNavItems"
                    :key="toUrl(item.href)"
                    :href="item.href"
                    class="inline-flex items-center gap-2 rounded-[var(--radius-control)] px-3 py-2 text-sm font-medium transition"
                    :class="
                        isCurrentOrParentUrl(item.href)
                            ? 'bg-primary/10 text-primary'
                            : 'text-muted-foreground hover:bg-muted hover:text-foreground'
                    "
                >
                    <component
                        :is="item.icon"
                        v-if="item.icon"
                        class="size-4 shrink-0"
                    />
                    {{ item.title }}
                </Link>
            </nav>

            <div class="ms-auto flex shrink-0 items-center gap-1.5">
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
                    <DropdownMenuContent align="end" class="w-56">
                        <UserMenuContent :user="user" />
                    </DropdownMenuContent>
                </DropdownMenu>
            </div>
        </div>

        <div
            v-if="breadcrumbs.length"
            class="mx-auto hidden w-full max-w-[90rem] border-t border-border/50 px-4 py-2 md:block md:px-6"
        >
            <Breadcrumbs :breadcrumbs="breadcrumbs" />
        </div>
    </header>
</template>
