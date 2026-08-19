<script setup lang="ts">
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { Settings2 } from '@lucide/vue';
import { computed, watch } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { useTranslations } from '@/composables/useTranslations';

type SystemSettings = {
    registration: boolean;
    reset_password: boolean;
    email_verification: boolean;
    multilingual: boolean;
    translations_ui: boolean;
    exports: boolean;
    excel_exports: boolean;
    tours: boolean;
    seo: boolean;
    attachments: boolean;
    audit: boolean;
    api: boolean;
    sms: boolean;
};

type FeatureDefinition = {
    key: keyof SystemSettings;
    group: string;
    label: string;
    help: string;
};

type ModuleLink = {
    key: string;
    label: string;
    href: string;
    enabled: boolean;
    help: string;
};

const props = defineProps<{
    settings: SystemSettings;
    modules: ModuleLink[];
    definitions: FeatureDefinition[];
}>();

const { t } = useTranslations();
const page = usePage();

const flashSuccess = computed(
    () => (page.props.flash as { success?: string } | undefined)?.success,
);
const flashError = computed(
    () => (page.props.flash as { error?: string } | undefined)?.error,
);

const form = useForm({ ...props.settings });

watch(
    () => props.settings,
    (settings) => {
        form.defaults({ ...settings });
        form.reset();
    },
);

const groups = computed(() => {
    const order = ['auth', 'locale', 'product', 'integrations'] as const;
    const labels: Record<(typeof order)[number], string> = {
        auth: t('Authentication'),
        locale: t('Language & locale'),
        product: t('Product features'),
        integrations: t('Integrations'),
    };

    return order
        .map((group) => ({
            id: group,
            title: labels[group],
            items: props.definitions.filter((item) => item.group === group),
        }))
        .filter((group) => group.items.length > 0);
});

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

function isDisabled(key: keyof SystemSettings): boolean {
    return key === 'excel_exports' && !form.exports;
}

function submit(): void {
    form.put('/settings/system-control', { preserveScroll: true });
}

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Settings', href: '/settings/appearance' },
            { title: 'System Control', href: '/settings/system-control' },
        ],
    },
});
</script>

<template>
    <Head :title="t('System Control')" />

    <div class="space-y-6" data-tour="settings-system-control">
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

        <Card>
            <CardHeader class="border-b border-border/60 bg-muted/20">
                <CardTitle class="flex items-center gap-2">
                    <Settings2 class="size-5" />
                    {{ t('System Control') }}
                </CardTitle>
                <CardDescription class="mt-1">
                    {{
                        t(
                            'Turn core product features on or off without editing .env files.',
                        )
                    }}
                </CardDescription>
            </CardHeader>
            <CardContent class="space-y-8 pt-6">
                <form class="space-y-8" @submit.prevent="submit">
                    <section
                        v-for="group in groups"
                        :key="group.id"
                        class="space-y-3"
                    >
                        <h2 class="text-sm font-semibold">{{ group.title }}</h2>

                        <div
                            v-for="item in group.items"
                            :key="item.key"
                            class="flex items-center justify-between gap-4 rounded-md border px-4 py-3"
                        >
                            <div>
                                <div class="font-medium">
                                    {{ t(item.label) }}
                                </div>
                            </div>
                            <button
                                type="button"
                                role="switch"
                                :aria-checked="form[item.key]"
                                :disabled="isDisabled(item.key)"
                                :class="[
                                    switchClass,
                                    switchTone(form[item.key]),
                                ]"
                                @click="form[item.key] = !form[item.key]"
                            >
                                <span
                                    class="size-5 rounded-full transition"
                                    :class="switchKnob(form[item.key])"
                                />
                                <span
                                    class="pe-2 text-xs font-semibold tracking-tight"
                                >
                                    {{ form[item.key] ? t('On') : t('Off') }}
                                </span>
                            </button>
                        </div>
                    </section>

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

        <Card>
            <CardHeader class="border-b border-border/60 bg-muted/20">
                <CardTitle>{{ t('Related modules') }}</CardTitle>
                <CardDescription class="mt-1">
                    {{
                        t(
                            'These modules have their own settings pages for credentials and options.',
                        )
                    }}
                </CardDescription>
            </CardHeader>
            <CardContent class="space-y-3 pt-6">
                <div
                    v-for="module in modules"
                    :key="module.key"
                    class="flex items-center justify-between gap-4 rounded-md border px-4 py-3"
                >
                    <div>
                        <div class="flex items-center gap-2 font-medium">
                            {{ t(module.label) }}
                            <Badge
                                :variant="
                                    module.enabled ? 'default' : 'secondary'
                                "
                            >
                                {{ module.enabled ? t('On') : t('Off') }}
                            </Badge>
                        </div>
                    </div>
                    <Button as-child variant="outline" size="sm">
                        <Link :href="module.href">{{ t('Configure') }}</Link>
                    </Button>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
