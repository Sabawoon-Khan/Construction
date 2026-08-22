<script setup lang="ts">
import { Form, Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed, nextTick, onMounted, watch } from 'vue';
import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import SecurityController from '@/actions/App/Http/Controllers/Settings/SecurityController';
import AppearanceTabs from '@/components/AppearanceTabs.vue';
import DeleteUser from '@/components/DeleteUser.vue';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useCalendar } from '@/composables/useCalendar';
import { useLocale } from '@/composables/useLocale';
import { useTranslations } from '@/composables/useTranslations';
import { edit } from '@/routes/profile';
import { send } from '@/routes/verification';
import type { LocaleCode } from '@/types/locale';

type BrowserSession = {
    id: string;
    ip_address: string | null;
    user_agent: string | null;
    last_activity: number;
    is_current: boolean;
};

type Props = {
    mustVerifyEmail?: boolean;
    status?: string | null;
    passwordRules: string;
    sessions?: BrowserSession[];
    canListSessions?: boolean;
};

const props = withDefaults(defineProps<Props>(), {
    mustVerifyEmail: false,
    status: null,
    sessions: () => [],
    canListSessions: false,
});

const page = usePage();
const { t } = useTranslations();
const calendar = useCalendar();
const { locale, locales, updateLocale } = useLocale();
const user = computed(() => page.props.auth.user);
const multilingual = computed(
    () => page.props.kitFeatures?.multilingual !== false,
);

const initials = computed(() => {
    const name = String(user.value?.name ?? '');
    const parts = name.trim().split(/\s+/).filter(Boolean);

    if (!parts.length) {
        return '?';
    }

    if (parts.length === 1) {
        return parts[0].slice(0, 2).toUpperCase();
    }

    return `${parts[0][0] ?? ''}${parts[1][0] ?? ''}`.toUpperCase();
});

function revokeOtherSessions(): void {
    router.post('/settings/security/sessions/revoke-others');
}

async function scrollToSection(section: string | null | undefined): Promise<void> {
    if (!section || typeof document === 'undefined') {
        return;
    }

    await nextTick();
    document.getElementById(`settings-${section}`)?.scrollIntoView({
        behavior: 'smooth',
        block: 'start',
    });
}

onMounted(() => {
    const section = new URLSearchParams(window.location.search).get('section');
    void scrollToSection(section);
});

watch(
    () => page.url,
    (url) => {
        const query = url.includes('?') ? url.split('?')[1] ?? '' : '';
        const section = new URLSearchParams(query).get('section');
        void scrollToSection(section);
    },
);

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Profile settings',
                href: edit(),
            },
        ],
    },
});
</script>

<template>
    <Head :title="t('Profile settings')" />

    <h1 class="sr-only">{{ t('Profile settings') }}</h1>

    <div class="space-y-6">
        <Card id="settings-profile" class="scroll-mt-24 overflow-hidden">
            <CardHeader class="border-b border-border/60 bg-muted/20">
                <div class="flex flex-wrap items-start gap-4">
                    <div
                        class="flex size-14 shrink-0 items-center justify-center rounded-2xl bg-[var(--school-navy)] text-lg font-semibold tracking-tight text-white"
                        aria-hidden="true"
                    >
                        {{ initials }}
                    </div>
                    <div class="min-w-0 flex-1 space-y-1">
                        <CardTitle>{{ t('Profile') }}</CardTitle>
                        <p class="pt-1 text-sm text-muted-foreground">
                            {{ user.email }}
                        </p>
                    </div>
                </div>
            </CardHeader>
            <CardContent class="pt-6">
                <Form
                    v-bind="ProfileController.update.form()"
                    class="space-y-5"
                    v-slot="{ errors, processing }"
                >
                    <div class="grid gap-5 sm:grid-cols-2">
                        <div class="grid gap-2">
                            <Label for="name">{{ t('Name') }}</Label>
                            <Input
                                id="name"
                                class="mt-1 block w-full"
                                name="name"
                                :default-value="user.name"
                                required
                                autocomplete="name"
                                :placeholder="t('Full name')"
                            />
                            <InputError class="mt-2" :message="errors.name" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="email">{{ t('Email address') }}</Label>
                            <Input
                                id="email"
                                type="email"
                                class="mt-1 block w-full"
                                name="email"
                                :default-value="user.email"
                                required
                                autocomplete="username"
                                :placeholder="t('Email address')"
                            />
                            <InputError class="mt-2" :message="errors.email" />
                        </div>
                    </div>

                    <div
                        v-if="mustVerifyEmail && !user.email_verified_at"
                        class="rounded-xl border border-amber-200/80 bg-amber-50/80 p-4 text-sm text-amber-950 dark:border-amber-500/20 dark:bg-amber-500/10 dark:text-amber-50"
                    >
                        <p>
                            {{ t('Your email address is unverified.') }}
                            <Link
                                :href="send()"
                                as="button"
                                class="font-medium underline decoration-amber-300 underline-offset-4 transition-colors hover:decoration-current"
                            >
                                {{
                                    t(
                                        'Click here to re-send the verification email.',
                                    )
                                }}
                            </Link>
                        </p>

                        <div
                            v-if="status === 'verification-link-sent'"
                            class="mt-2 font-medium text-green-700 dark:text-green-300"
                        >
                            {{
                                t(
                                    'A new verification link has been sent to your email address.',
                                )
                            }}
                        </div>
                    </div>

                    <div class="flex items-center gap-4 border-t border-border/50 pt-5">
                        <Button
                            :disabled="processing"
                            data-test="update-profile-button"
                        >
                            {{ t('Save') }}
                        </Button>
                    </div>
                </Form>
            </CardContent>
        </Card>

        <Card id="settings-preferences" class="scroll-mt-24 overflow-hidden">
            <CardHeader class="border-b border-border/60 bg-muted/20">
                <CardTitle>{{ t('Preferences') }}</CardTitle>
            </CardHeader>
            <CardContent class="space-y-6 pt-6">
                <div v-if="multilingual" class="grid gap-2">
                    <Label for="profile-language">{{ t('Language') }}</Label>
                    <select
                        id="profile-language"
                        class="flex h-10 w-full max-w-sm rounded-md border border-input bg-background px-3 py-2 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                        :value="locale"
                        @change="
                            updateLocale(
                                ($event.target as HTMLSelectElement)
                                    .value as LocaleCode,
                            )
                        "
                    >
                        <option
                            v-for="item in locales"
                            :key="item.code"
                            :value="item.code"
                        >
                            {{ item.native }} ({{ item.name }})
                        </option>
                    </select>
                </div>

                <div
                    class="grid gap-2 pt-5"
                    :class="multilingual ? 'border-t border-border/50' : ''"
                >
                    <Label>{{ t('Color mode') }}</Label>
                    <AppearanceTabs />
                </div>
            </CardContent>
        </Card>

        <Card id="settings-password" class="scroll-mt-24 overflow-hidden">
            <CardHeader class="border-b border-border/60 bg-muted/20">
                <CardTitle>{{ t('Change password') }}</CardTitle>
            </CardHeader>
            <CardContent class="pt-6">
                <Form
                    v-bind="SecurityController.update.form()"
                    :options="{ preserveScroll: true }"
                    reset-on-success
                    :reset-on-error="[
                        'password',
                        'password_confirmation',
                        'current_password',
                    ]"
                    class="space-y-5"
                    v-slot="{ errors, processing }"
                >
                    <div class="grid gap-2">
                        <Label for="current_password">{{
                            t('Current password')
                        }}</Label>
                        <PasswordInput
                            id="current_password"
                            name="current_password"
                            class="mt-1 block w-full max-w-md"
                            autocomplete="current-password"
                            :placeholder="t('Current password')"
                        />
                        <InputError :message="errors.current_password" />
                    </div>

                    <div class="grid gap-5 sm:grid-cols-2">
                        <div class="grid gap-2">
                            <Label for="password">{{ t('New password') }}</Label>
                            <PasswordInput
                                id="password"
                                name="password"
                                class="mt-1 block w-full"
                                autocomplete="new-password"
                                :placeholder="t('New password')"
                                :passwordrules="props.passwordRules"
                            />
                            <InputError :message="errors.password" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="password_confirmation">{{
                                t('Confirm password')
                            }}</Label>
                            <PasswordInput
                                id="password_confirmation"
                                name="password_confirmation"
                                class="mt-1 block w-full"
                                autocomplete="new-password"
                                :placeholder="t('Confirm password')"
                                :passwordrules="props.passwordRules"
                            />
                            <InputError
                                :message="errors.password_confirmation"
                            />
                        </div>
                    </div>

                    <div class="flex items-center gap-4 border-t border-border/50 pt-5">
                        <Button
                            :disabled="processing"
                            data-test="update-password-button"
                        >
                            {{ t('Update password') }}
                        </Button>
                    </div>
                </Form>
            </CardContent>
        </Card>

        <Card id="settings-sessions" class="scroll-mt-24 overflow-hidden">
            <CardHeader class="border-b border-border/60 bg-muted/20">
                <CardTitle>{{ t('Browser sessions') }}</CardTitle>
            </CardHeader>
            <CardContent class="space-y-4 pt-6">
                <div
                    v-if="canListSessions && sessions.length"
                    class="divide-y rounded-xl border border-border/80"
                >
                    <div
                        v-for="session in sessions"
                        :key="session.id"
                        class="flex flex-wrap items-center justify-between gap-2 p-3.5 text-sm"
                    >
                        <div class="min-w-0">
                            <p class="font-medium">
                                {{ session.ip_address || '—' }}
                                <span
                                    v-if="session.is_current"
                                    class="text-muted-foreground"
                                >
                                    ({{ t('This device') }})
                                </span>
                            </p>
                            <p class="truncate text-muted-foreground">
                                {{ session.user_agent || '—' }}
                            </p>
                        </div>
                        <p class="text-muted-foreground">
                            {{
                                calendar.formatDateTime(
                                    new Date(session.last_activity * 1000),
                                ) || '—'
                            }}
                        </p>
                    </div>
                </div>

                <p v-else class="text-sm text-muted-foreground">
                    {{
                        t(
                            'Revoke other sessions to invalidate older logins on other devices.',
                        )
                    }}
                </p>

                <Button variant="outline" @click="revokeOtherSessions">
                    {{ t('Sign out other sessions') }}
                </Button>
            </CardContent>
        </Card>

        <Card
            id="settings-danger"
            class="scroll-mt-24 overflow-hidden border-red-200/80 dark:border-red-500/20"
        >
            <CardHeader class="border-b border-red-100 bg-red-50/60 dark:border-red-500/15 dark:bg-red-500/5">
                <CardTitle class="text-red-700 dark:text-red-200">{{
                    t('Delete account')
                }}</CardTitle>
            </CardHeader>
            <CardContent class="pt-6">
                <DeleteUser embedded />
            </CardContent>
        </Card>
    </div>
</template>
