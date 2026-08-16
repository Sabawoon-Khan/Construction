<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { Pencil, Shield, Trash2 } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import Can from '@/components/Can.vue';
import InputError from '@/components/InputError.vue';
import {
    MisEmptyState,
    MisListPage,
    MisListRow,
    MisSearchInput,
} from '@/components/mis';
import RowActionsMenu from '@/components/RowActionsMenu.vue';
import SettingsAddButton from '@/components/SettingsAddButton.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { usePermissions } from '@/composables/usePermissions';
import { useTranslations } from '@/composables/useTranslations';
import type { RowActionItem } from '@/lib/row-actions';

type Permission = { id: number; name: string };

type RoleRecord = {
    id: number;
    name: string;
    users_count?: number;
    permissions?: Permission[];
};

const props = defineProps<{
    roles: RoleRecord[];
    permissions: Permission[];
    filters?: { search?: string };
}>();

const { t } = useTranslations();
const { can } = usePermissions();
const protectedRoles = ['Owner'];
const editOpen = ref(false);
const editingRole = ref<RoleRecord | null>(null);
const editName = ref('');
const editPermissions = ref<string[]>([]);
const formErrors = ref<Record<string, string>>({});
const processing = ref(false);
const search = ref(props.filters?.search ?? '');
let searchTimer: ReturnType<typeof setTimeout> | null = null;

const permissionGroups = computed(() => {
    const groups: Record<string, Permission[]> = {};

    for (const permission of props.permissions) {
        const [module] = permission.name.split('.');
        const key = module ?? permission.name;

        if (!groups[key]) {
            groups[key] = [];
        }

        groups[key].push(permission);
    }

    return Object.entries(groups).sort(([a], [b]) => a.localeCompare(b));
});

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
            '/settings/roles',
            { search: next || undefined },
            { preserveState: true, replace: true, preserveScroll: true },
        );
    }, 300);
});

function openEdit(role: RoleRecord): void {
    editingRole.value = role;
    editName.value = role.name;
    editPermissions.value = [...(role.permissions?.map((p) => p.name) ?? [])];
    formErrors.value = {};
    editOpen.value = true;
}

function closeEdit(): void {
    if (processing.value) {
        return;
    }

    editOpen.value = false;
    editingRole.value = null;
    editName.value = '';
    editPermissions.value = [];
    formErrors.value = {};
}

function saveRole(): void {
    if (!editingRole.value || processing.value) {
        return;
    }

    const roleId = editingRole.value.id;
    const payload = {
        name: editName.value,
        permissions: [...editPermissions.value],
    };

    processing.value = true;
    formErrors.value = {};

    router.patch(`/settings/roles/${roleId}`, payload, {
        preserveScroll: true,
        onError: (errors) => {
            formErrors.value = Object.fromEntries(
                Object.entries(errors).map(([key, value]) => [key, String(value)]),
            );
        },
        onSuccess: () => {
            editOpen.value = false;
            editingRole.value = null;
            editName.value = '';
            editPermissions.value = [];
            formErrors.value = {};
        },
        onFinish: () => {
            processing.value = false;
        },
    });
}

function roleActions(role: RoleRecord): RowActionItem[] {
    return [
        {
            label: t('Edit'),
            icon: Pencil,
            onClick: () => openEdit(role),
            hidden: !can('roles.edit'),
        },
        {
            label: t('Move to trash'),
            icon: Trash2,
            variant: 'destructive',
            separator: true,
            href: `/settings/roles/${role.id}`,
            method: 'delete',
            hidden: protectedRoles.includes(role.name) || !can('roles.delete'),
            confirm: {
                title: t('Move to trash'),
                description: `${t('Move to trash')}: ${role.name}?`,
                confirmLabel: t('Move to trash'),
            },
        },
    ];
}

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Settings', href: '/settings/roles' },
            { title: 'Roles', href: '/settings/roles' },
        ],
    },
});
</script>

<template>
    <Head :title="t('Role Management')" />

    <MisListPage
        :title="t('Role Management')"
        :description="t('Define roles and permissions')"
        :icon="Shield"
    >
        <template #actions>
            <Can permission="roles.create">
                <SettingsAddButton href="/settings/roles/create">
                    {{ t('Create role') }}
                </SettingsAddButton>
            </Can>
        </template>

        <MisSearchInput
            v-model="search"
            class="max-w-sm"
            :aria-label="t('Search')"
            :placeholder="t('Search...')"
        />

        <MisEmptyState v-if="roles.length === 0">
            {{ t('No roles found') }}
        </MisEmptyState>

        <template v-else>
            <MisListRow
                v-for="role in roles"
                :key="`${role.id}-${role.permissions?.length ?? 0}`"
                class="flex flex-wrap items-center justify-between gap-3"
            >
                <div>
                    <p class="font-medium">{{ role.name }}</p>
                    <p class="text-sm text-muted-foreground">
                        {{ role.permissions?.length ?? 0 }}
                        {{ t('Permissions') }} ·
                        {{ role.users_count ?? 0 }} {{ t('Users') }}
                    </p>
                </div>
                <Can permission="roles.edit">
                    <RowActionsMenu :actions="roleActions(role)" />
                </Can>
            </MisListRow>
        </template>

        <template #below>
            <Dialog
                :open="editOpen"
                @update:open="(open) => !open && closeEdit()"
            >
                <DialogContent class="max-h-[85vh] overflow-y-auto sm:max-w-lg">
                    <DialogHeader>
                        <DialogTitle>
                            {{ t('Edit') }} {{ editingRole?.name }}
                        </DialogTitle>
                    </DialogHeader>

                    <form class="space-y-4" @submit.prevent="saveRole">
                        <div class="space-y-2">
                            <Label for="role-name">{{ t('Name') }}</Label>
                            <Input
                                id="role-name"
                                v-model="editName"
                                :disabled="
                                    protectedRoles.includes(
                                        editingRole?.name ?? '',
                                    )
                                "
                            />
                            <InputError :message="formErrors.name" />
                        </div>

                        <div class="space-y-4">
                            <div class="flex items-center justify-between gap-2">
                                <p class="text-sm font-medium">
                                    {{ t('Permissions') }}
                                </p>
                                <p class="text-xs text-muted-foreground">
                                    {{ editPermissions.length }} selected
                                </p>
                            </div>
                            <InputError :message="formErrors.permissions" />
                            <div
                                v-for="[module, modulePermissions] in permissionGroups"
                                :key="module"
                                class="space-y-2"
                            >
                                <p
                                    class="text-xs font-semibold tracking-wide text-muted-foreground uppercase"
                                >
                                    {{ module }}
                                </p>
                                <label
                                    v-for="permission in modulePermissions"
                                    :key="permission.id"
                                    class="flex items-center gap-2 text-sm"
                                >
                                    <input
                                        v-model="editPermissions"
                                        type="checkbox"
                                        class="size-4 rounded border border-input accent-primary"
                                        :value="permission.name"
                                    >
                                    <span>{{ permission.name }}</span>
                                </label>
                            </div>
                        </div>

                        <DialogFooter>
                            <Button
                                type="button"
                                variant="outline"
                                :disabled="processing"
                                @click="closeEdit"
                            >
                                {{ t('Cancel') }}
                            </Button>
                            <Button type="submit" :disabled="processing">
                                {{ processing ? t('Saving...') : t('Save') }}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </template>
    </MisListPage>
</template>
