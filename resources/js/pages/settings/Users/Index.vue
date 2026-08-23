<script setup lang="ts">
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { Ban, CheckCircle2, KeyRound, Pencil, Shield, UserCog } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import Can from '@/components/Can.vue';
import InputError from '@/components/InputError.vue';
import {
    MisEmptyState,
    MisListPage,
    MisListRow,
    MisPagination,
    MisSearchInput,
} from '@/components/mis';
import PasswordInput from '@/components/PasswordInput.vue';
import RowActionsMenu from '@/components/RowActionsMenu.vue';
import SettingsAddButton from '@/components/SettingsAddButton.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { useTranslations } from '@/composables/useTranslations';
import type { Paginated } from '@/lib/format';
import type { RowActionItem } from '@/lib/row-actions';

type Role = { id: number; name: string };

type UserRecord = {
    id: number;
    name: string;
    email: string;
    is_active: boolean;
    roles?: Role[];
};

const props = defineProps<{
    users: Paginated<UserRecord>;
    roles: Role[];
    filters?: { search?: string | null };
    passwordRules?: string;
}>();

const { t } = useTranslations();
const page = usePage();
const currentUserId = computed(() => page.props.auth?.user?.id as number | undefined);

const search = ref(props.filters?.search ?? '');
const passwordUser = ref<UserRecord | null>(null);
const passwordForm = useForm({
    password: '',
    password_confirmation: '',
});

const passwordDialogOpen = computed({
    get: () => passwordUser.value !== null,
    set: (open: boolean) => {
        if (!open) {
            passwordUser.value = null;
            passwordForm.reset();
            passwordForm.clearErrors();
        }
    },
});

let searchTimer: ReturnType<typeof setTimeout> | null = null;

watch(
    () => props.filters?.search,
    (value) => {
        search.value = value ?? '';
    },
);

watch(search, (value) => {
    if (searchTimer) {
        clearTimeout(searchTimer);
    }

    searchTimer = setTimeout(() => {
        const next = value.trim();
        const current = (props.filters?.search ?? '').trim();
        if (next === current) {
            return;
        }

        router.get(
            '/settings/users',
            { search: next || undefined },
            { preserveState: true, replace: true, preserveScroll: true },
        );
    }, 300);
});

function updateRoles(user: UserRecord, event: Event): void {
    const select = event.target as HTMLSelectElement;
    const selected = Array.from(select.selectedOptions).map((o) => o.value);
    router.patch(`/settings/users/${user.id}`, { roles: selected }, { preserveScroll: true });
}

function openPasswordDialog(user: UserRecord): void {
    passwordUser.value = user;
    passwordForm.reset();
    passwordForm.clearErrors();
}

function submitPassword(): void {
    if (!passwordUser.value) {
        return;
    }

    passwordForm.patch(`/settings/users/${passwordUser.value.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            passwordDialogOpen.value = false;
        },
    });
}

function userActions(user: UserRecord): RowActionItem[] {
    const actions: RowActionItem[] = [
        {
            label: t('Change password'),
            icon: KeyRound,
            onClick: () => openPasswordDialog(user),
        },
        {
            label: t('Edit roles'),
            icon: Pencil,
            onClick: () => {
                document
                    .getElementById(`user-roles-${user.id}`)
                    ?.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            },
            hidden: !page.props.auth,
        },
    ];

    if (user.id !== currentUserId.value) {
        actions.push({
            label: user.is_active ? t('Disable') : t('Enable'),
            icon: user.is_active ? Ban : CheckCircle2,
            separator: true,
            variant: user.is_active ? 'destructive' : 'default',
            method: 'patch',
            href: `/settings/users/${user.id}`,
            data: { is_active: !user.is_active },
            confirm: {
                title: user.is_active ? t('Disable user') : t('Enable user'),
                description: user.is_active
                    ? t('This user will no longer be able to sign in.')
                    : t('This user will be able to sign in again.'),
                confirmLabel: user.is_active ? t('Disable') : t('Enable'),
            },
            confirmVariant: user.is_active ? 'destructive' : 'default',
        });
    }

    return actions;
}

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Settings', href: '/settings/users' },
            { title: 'Users', href: '/settings/users' },
        ],
    },
});
</script>

<template>
    <Head :title="t('User Management')" />

    <MisListPage
        :title="t('System Users')"
        :icon="UserCog"
    >
        <template #actions>
            <Can
                v-if="page.props.kitFeatures?.exports !== false"
                permission="users.view"
            >
                <div class="flex flex-wrap items-center gap-2">
                    <Button variant="outline" size="sm" as-child>
                        <a href="/settings/users/export">{{ t('Download') }} CSV</a>
                    </Button>
                    <Button
                        v-if="page.props.kitFeatures?.excel_exports !== false"
                        variant="outline"
                        size="sm"
                        as-child
                    >
                        <a href="/settings/users/export?format=xlsx">{{
                            t('Download Excel')
                        }}</a>
                    </Button>
                    <Button variant="outline" size="sm" as-child>
                        <a href="/settings/users/print" target="_blank" rel="noopener">{{
                            t('Print')
                        }}</a>
                    </Button>
                </div>
            </Can>
            <Can permission="users.create">
                <SettingsAddButton href="/settings/users/create">
                    {{ t('Create user') }}
                </SettingsAddButton>
            </Can>
        </template>

        <MisSearchInput
            v-model="search"
            class="max-w-sm"
            :aria-label="t('Search')"
            :placeholder="t('Search by name or email…')"
        />

        <MisEmptyState
            v-if="users.data.length === 0"
            :title="t('No users found')"
        />

        <div v-else class="space-y-3">
            <MisListRow v-for="user in users.data" :key="user.id">
                <div
                    class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between"
                >
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="font-medium">{{ user.name }}</span>
                            <Badge
                                :variant="
                                    user.is_active ? 'default' : 'destructive'
                                "
                            >
                                {{
                                    user.is_active ? t('Active') : t('Disabled')
                                }}
                            </Badge>
                        </div>
                        <p class="text-sm text-muted-foreground">
                            {{ user.email }}
                        </p>
                        <div
                            v-if="user.roles?.length"
                            class="mt-2 flex flex-wrap gap-1"
                        >
                            <Badge
                                v-for="role in user.roles"
                                :key="role.id"
                                variant="secondary"
                            >
                                <Shield class="size-3" />
                                {{ role.name }}
                            </Badge>
                        </div>
                    </div>

                    <div class="flex flex-col gap-3 sm:min-w-[220px]">
                        <div class="flex items-center justify-end">
                            <Can permission="users.edit">
                                <RowActionsMenu :actions="userActions(user)" />
                            </Can>
                        </div>
                        <Can permission="users.edit">
                            <div
                                :id="`user-roles-${user.id}`"
                                class="grid gap-2"
                            >
                                <Label>{{ t('Assign roles') }}</Label>
                                <select
                                    multiple
                                    class="min-h-24 w-full rounded-md border bg-background px-3 py-2 text-sm"
                                    @change="updateRoles(user, $event)"
                                >
                                    <option
                                        v-for="role in roles"
                                        :key="role.id"
                                        :value="role.name"
                                        :selected="
                                            user.roles?.some(
                                                (r) => r.name === role.name,
                                            )
                                        "
                                    >
                                        {{ role.name }}
                                    </option>
                                </select>
                            </div>
                        </Can>
                    </div>
                </div>
            </MisListRow>
        </div>

        <template #pagination>
            <MisPagination :pagination="users" />
        </template>
    </MisListPage>

    <Dialog v-model:open="passwordDialogOpen">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>{{ t('Change password') }}</DialogTitle>
                <DialogDescription v-if="passwordUser">
                    {{ passwordUser.name }} · {{ passwordUser.email }}
                </DialogDescription>
            </DialogHeader>

            <form class="space-y-4" @submit.prevent="submitPassword">
                <div class="grid gap-2">
                    <Label for="user-password">{{ t('New password') }}</Label>
                    <PasswordInput
                        id="user-password"
                        v-model="passwordForm.password"
                        required
                        autocomplete="new-password"
                        :passwordrules="passwordRules"
                    />
                    <InputError :message="passwordForm.errors.password" />
                </div>

                <div class="grid gap-2">
                    <Label for="user-password-confirmation">{{
                        t('Confirm password')
                    }}</Label>
                    <PasswordInput
                        id="user-password-confirmation"
                        v-model="passwordForm.password_confirmation"
                        required
                        autocomplete="new-password"
                        :passwordrules="passwordRules"
                    />
                    <InputError :message="passwordForm.errors.password_confirmation" />
                </div>

                <DialogFooter class="gap-2 sm:justify-end">
                    <Button
                        type="button"
                        variant="outline"
                        @click="passwordDialogOpen = false"
                    >
                        {{ t('Cancel') }}
                    </Button>
                    <Button type="submit" :disabled="passwordForm.processing">
                        {{
                            passwordForm.processing
                                ? t('Saving…')
                                : t('Update password')
                        }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
