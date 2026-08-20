<script setup lang="ts">
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { KeyRound } from '@lucide/vue';
import { computed, watch } from 'vue';
import InputError from '@/components/InputError.vue';
import { MisAlert } from '@/components/mis';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useTranslations } from '@/composables/useTranslations';

type GoogleAuthSettings = {
    enabled: boolean;
    client_id: string;
    client_secret_set: boolean;
    redirect_uri: string;
    credentials_configured: boolean;
    active: boolean;
    env_default: boolean;
};

const props = defineProps<{
    settings: GoogleAuthSettings;
}>();

const { t } = useTranslations();
const page = usePage();

const flashSuccess = computed(
    () => (page.props.flash as { success?: string } | undefined)?.success,
);
const flashError = computed(
    () => (page.props.flash as { error?: string } | undefined)?.error,
);

const form = useForm({
    enabled: props.settings.enabled,
    client_id: props.settings.client_id,
    client_secret: '',
    redirect_uri: props.settings.redirect_uri,
});

watch(
    () => props.settings,
    (settings) => {
        form.defaults({
            enabled: settings.enabled,
            client_id: settings.client_id,
            client_secret: '',
            redirect_uri: settings.redirect_uri,
        });
        form.reset();
    },
);

const credentialsReady = computed(
    () =>
        form.client_id.trim() !== '' &&
        (props.settings.client_secret_set || form.client_secret.trim() !== ''),
);

const switchClass =
    'inline-flex shrink-0 items-center gap-2.5 rounded-full border px-1.5 py-1 transition disabled:cursor-not-allowed disabled:opacity-50';

function switchTone(on: boolean): string {
    return on
        ? 'border-[var(--school-navy)]/30 bg-[var(--school-navy)]/8'
        : 'border-border bg-muted/40';
}

function switchKnob(on: boolean): string {
    return on ? 'bg-[var(--school-navy)]' : 'bg-muted-foreground/35';
}

function submit(): void {
    form.put('/settings/google-auth', { preserveScroll: true });
}

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Settings', href: '/settings/appearance' },
            { title: 'Google Auth', href: '/settings/google-auth' },
        ],
    },
});
</script>

<template>
    <Head :title="t('Google Auth')" />

    <div class="space-y-6" data-tour="settings-google-auth">
        <div
            v-if="flashSuccess || flashError"
            class="rounded-lg border px-4 py-3 text-sm"
            :class="
                flashError
                    ? 'border-destructive/40 bg-destructive/5 text-destructive'
                    : 'border-border bg-muted/40 text-foreground'
            "
        >
            {{ flashError || flashSuccess }}
        </div>

        <MisAlert v-if="form.enabled && !credentialsReady" tone="warning">
            {{
                t(
                    'Google Auth is turned on, but client ID and secret are still required.',
                )
            }}
        </MisAlert>

        <MisAlert
            v-else-if="form.enabled && credentialsReady"
            tone="success"
        >
            {{
                t(
                    'Google sign-in is active on the login and register pages.',
                )
            }}
        </MisAlert>

        <Card>
            <CardHeader class="border-b border-border/60 bg-muted/20">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <CardTitle class="flex items-center gap-2">
                            <KeyRound class="size-5" />
                            {{ t('Google Auth') }}
                        </CardTitle>
                    </div>
                    <button
                        type="button"
                        role="switch"
                        :aria-checked="form.enabled"
                        :class="[switchClass, switchTone(form.enabled)]"
                        @click="form.enabled = !form.enabled"
                    >
                        <span
                            class="size-5 rounded-full transition"
                            :class="switchKnob(form.enabled)"
                        />
                        <span class="pe-2 text-xs font-semibold tracking-tight">
                            {{ form.enabled ? t('On') : t('Off') }}
                        </span>
                    </button>
                </div>
            </CardHeader>
            <CardContent class="space-y-6 pt-6">
                <form class="space-y-6" @submit.prevent="submit">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="space-y-2 sm:col-span-2">
                            <Label for="client_id">{{ t('Client ID') }}</Label>
                            <Input
                                id="client_id"
                                v-model="form.client_id"
                                type="text"
                                autocomplete="off"
                                :placeholder="t('Google OAuth client ID')"
                            />
                            <InputError :message="form.errors.client_id" />
                        </div>

                        <div class="space-y-2 sm:col-span-2">
                            <div class="flex items-center justify-between gap-2">
                                <Label for="client_secret">{{
                                    t('Client secret')
                                }}</Label>
                                <Badge
                                    v-if="settings.client_secret_set"
                                    variant="secondary"
                                >
                                    {{ t('Configured') }}
                                </Badge>
                            </div>
                            <Input
                                id="client_secret"
                                v-model="form.client_secret"
                                type="password"
                                autocomplete="new-password"
                                :placeholder="
                                    settings.client_secret_set
                                        ? t(
                                              'Leave blank to keep current secret',
                                          )
                                        : t('Google OAuth client secret')
                                "
                            />
                            <InputError :message="form.errors.client_secret" />
                        </div>

                        <div class="space-y-2 sm:col-span-2">
                            <Label for="redirect_uri">{{
                                t('Redirect URI')
                            }}</Label>
                            <Input
                                id="redirect_uri"
                                v-model="form.redirect_uri"
                                type="url"
                                autocomplete="off"
                            />
                            <InputError :message="form.errors.redirect_uri" />
                            <p class="text-sm text-muted-foreground">
                                {{
                                    t(
                                        'Add this exact URI in Google Cloud Console → APIs & Services → Credentials.',
                                    )
                                }}
                            </p>
                        </div>
                    </div>

                    <div
                        class="flex items-center justify-between gap-3 rounded-md border px-4 py-3"
                    >
                        <div>
                            <div class="font-medium">
                                {{ t('Env default') }}
                            </div>
                            <div class="text-sm text-muted-foreground">
                                {{
                                    t(
                                        'KIT_GOOGLE_AUTH before Settings override',
                                    )
                                }}
                            </div>
                        </div>
                        <Badge variant="secondary">
                            {{ settings.env_default ? t('On') : t('Off') }}
                        </Badge>
                    </div>

                    <div class="flex justify-end">
                        <Button
                            type="submit"
                            :disabled="form.processing || !form.isDirty"
                        >
                            {{ t('Save') }}
                        </Button>
                    </div>
                </form>
            </CardContent>
        </Card>
    </div>
</template>
